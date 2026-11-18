<?php
declare(strict_types=1);

/**
 * Read-only administrative presentation of a person and of the exact form
 * answers submitted at the time of each registration. The current profile
 * and historic submissions are deliberately separate sources of truth.
 */
function admin_record_escape(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_record_value_text(mixed $value, array $field = []): string {
    if ($value === null) return '—';
    if (is_bool($value)) return $value ? 'Sim' : 'Não';
    if (is_array($value)) {
        if (!$value) return '—';
        $parts = [];
        foreach ($value as $key => $part) {
            $text = admin_record_value_text($part, $field);
            $parts[] = array_is_list($value) ? $text : ((string)$key . ': ' . $text);
        }
        return implode("\n", $parts);
    }
    $raw = (string)$value;
    foreach (($field['options'] ?? []) as $option) {
        if (is_array($option) && (string)($option['value'] ?? '') === $raw) {
            return (string)($option['label'] ?? $raw);
        }
    }
    return $raw === '' ? '—' : $raw;
}

function admin_record_submission_fields(array $submission): array {
    $payload = json_decode((string)($submission['payload_json'] ?? ''), true);
    if (!is_array($payload)) return [];
    $snapshot = json_decode((string)($submission['form_snapshot_json'] ?? ''), true);
    $definitions = [];
    foreach (($snapshot['fields'] ?? []) as $field) {
        if (is_array($field) && isset($field['id'])) {
            $definitions[(string)$field['id']] = $field;
        }
    }
    $rows = [];
    // The original form snapshot determines the question order and labels.
    foreach ($definitions as $key => $field) {
        if (!array_key_exists($key, $payload)) continue;
        $rows[] = [
            'key' => $key,
            'label' => (string)($field['label'] ?? $key),
            'value' => admin_record_value_text($payload[$key], $field),
        ];
    }
    // Never lose a submitted answer because the saved schema changed.
    foreach ($payload as $key => $value) {
        if (isset($definitions[(string)$key])) continue;
        $rows[] = [
            'key' => (string)$key,
            'label' => (string)$key,
            'value' => admin_record_value_text($value),
        ];
    }
    return $rows;
}

function admin_record_fields_html(array $rows): string {
    if (!$rows) return '<p class="admin-record-empty">Nenhuma resposta armazenada neste registro.</p>';
    $html = '<dl class="admin-record-fields">';
    foreach ($rows as $row) {
        $key = admin_record_escape($row['key']);
        $label = admin_record_escape($row['label']);
        $value = admin_record_escape($row['value']);
        $html .= '<div data-field="' . $key . '"><dt>' . $label . '</dt><dd>' . $value . '</dd></div>';
    }
    return $html . '</dl>';
}

function admin_record_submission_html(array $submission): string {
    return admin_record_fields_html(admin_record_submission_fields($submission));
}

function admin_record_profile_html(array $profile): string {
    $fields = [
        'name' => 'Nome',
        'email' => 'E-mail',
        'cpf' => 'CPF',
        'phone' => 'Telefone / WhatsApp',
        'instagram' => 'Instagram',
        'address' => 'Endereço',
        'city_state' => 'Cidade/UF',
        'postal_code' => 'CEP',
    ];
    $rows = [];
    foreach ($fields as $key => $label) {
        $rows[] = ['key' => $key, 'label' => $label, 'value' => admin_record_value_text($profile[$key] ?? null)];
    }
    return admin_record_fields_html($rows);
}

function admin_record_person_submissions(PDO $db, int $studentId): array {
    $statement = $db->prepare(
        'SELECT s.*, f.title form_title, f.purpose form_purpose, c.title course_title, cc.title cohort_title ' .
        'FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id ' .
        'LEFT JOIN courses c ON c.id=s.course_id ' .
        'LEFT JOIN course_cohorts cc ON cc.id=s.cohort_id ' .
        'WHERE s.student_id=? ORDER BY s.created_at DESC,s.id DESC'
    );
    $statement->execute([$studentId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function admin_record_history_html(array $submissions): string {
    if (!$submissions) return '<p class="admin-record-empty">Nenhuma resposta de formulário vinculada a esta conta.</p>';
    $html = '<div class="admin-record-history">';
    foreach ($submissions as $index => $submission) {
        $date = (string)($submission['created_at'] ?? '');
        $date = $date !== '' && strtotime($date) !== false ? date('d/m/Y H:i', strtotime($date)) : 'Data não registrada';
        $form = (string)($submission['form_title'] ?? 'Formulário');
        $course = trim((string)($submission['course_title'] ?? ''));
        $label = $form . ($course !== '' ? ' · ' . $course : '');
        $html .= '<details class="admin-record-history-item"' . ($index === 0 ? ' open' : '') . '>';
        $html .= '<summary><strong>' . admin_record_escape($label) . '</strong><span>' . admin_record_escape($date) . ' · #' . (int)($submission['id'] ?? 0) . '</span></summary>';
        $html .= '<div class="admin-record-history-body">';
        $html .= admin_record_submission_html($submission);
        $status = (string)($submission['status'] ?? '');
        $payment = (string)($submission['payment_status'] ?? '');
        $cohort = (string)($submission['cohort_title'] ?? '');
        $meta = [];
        if ($status !== '') $meta[] = ['key' => 'status', 'label' => 'Estado da inscrição', 'value' => $status];
        if ($payment !== '') $meta[] = ['key' => 'payment', 'label' => 'Estado do pagamento', 'value' => $payment];
        if ($cohort !== '') $meta[] = ['key' => 'cohort', 'label' => 'Turma', 'value' => $cohort];
        if ($meta) $html .= '<h4>Andamento administrativo</h4>' . admin_record_fields_html($meta);
        $note = trim((string)($submission['payment_note'] ?? ''));
        if ($note !== '') $html .= '<p class="admin-record-payment-note"><strong>Informações do pagamento:</strong> ' . admin_record_escape($note) . '</p>';
        $html .= '</div></details>';
    }
    return $html . '</div>';
}
