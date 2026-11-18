<?php
declare(strict_types=1);
require __DIR__ . '/../app/admin_person_record.php';

function record_expect(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "test-admin-person-record: " . $message . "\n");
        exit(1);
    }
}
$form = [
    'id' => 73,
    'form_title' => 'Inscrição original',
    'created_at' => '2026-09-13T10:00:00Z',
    'status' => 'converted',
    'payment_status' => 'paid',
    'payment_note' => 'Confirmado manualmente',
    'payload_json' => json_encode([
        'name' => 'Pessoa de teste',
        'support_size' => '4x5',
        'address' => 'Rua de Exemplo, 10',
        'postal_code' => '25600-000',
        'availability' => ['sat', 'tue'],
        'payment_method' => 'pix',
        'terms' => true,
        'extra_legacy' => 'Resposta fora do schema',
        'free_text' => '<script>alert(1)</script>',
        'zero' => 0,
        'unchecked' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    'form_snapshot_json' => json_encode(['fields' => [
        ['id' => 'name', 'label' => 'Nome informado'],
        ['id' => 'support_size', 'label' => 'Tamanho do suporte (antigo)', 'options' => [
            ['value' => '4x5', 'label' => '4x5" original'],
        ]],
        ['id' => 'address', 'label' => 'Endereço completo'],
        ['id' => 'postal_code', 'label' => 'CEP'],
        ['id' => 'availability', 'label' => 'Dias disponíveis', 'options' => [
            ['value' => 'sat', 'label' => 'Sábado'],
            ['value' => 'tue', 'label' => 'Terça-feira'],
        ]],
        ['id' => 'payment_method', 'label' => 'Forma de pagamento'],
        ['id' => 'terms', 'label' => 'Concordância'],
        ['id' => 'free_text', 'label' => 'Comentário'],
        ['id' => 'zero', 'label' => 'Quantidade'],
        ['id' => 'unchecked', 'label' => 'Não selecionado'],
    ]], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
];
$rows = admin_record_submission_fields($form);
record_expect(count($rows) === 11, 'must retain every submitted key, including unknown, zero and false');
record_expect($rows[0]['label'] === 'Nome informado', 'original form field order must prevail');
record_expect($rows[1]['value'] === '4x5" original', 'original choice label must prevail');
record_expect($rows[4]['value'] === "Sábado\nTerça-feira", 'multiple choices must preserve every selection');
record_expect($rows[6]['value'] === 'Sim', 'consent must be readable');
record_expect($rows[9]['value'] === 'Não', 'false value must be displayed');
record_expect($rows[10]['key'] === 'extra_legacy', 'unknown legacy key must be appended');
$html = admin_record_submission_html($form);
foreach (['Rua de Exemplo, 10', '25600-000', '4x5&quot; original', 'Resposta fora do schema', 'Forma de pagamento'] as $required) {
    record_expect(str_contains($html, $required), 'missing submitted information: ' . $required);
}
record_expect(!str_contains($html, '<script>'), 'unsanitized submission answer');
record_expect(str_contains($html, '&lt;script&gt;'), 'escaped answer was lost');
$profile = admin_record_profile_html([
    'name' => 'Nome atualizado', 'email' => 'novo@example.invalid',
    'cpf' => '12345678901', 'address' => 'Rua Atual, 20',
    'postal_code' => '25601-000',
]);
record_expect(str_contains($profile, 'Rua Atual, 20'), 'current profile address missing');
record_expect(str_contains($profile, '12345678901'), 'administrator cannot read full current CPF');
record_expect(!str_contains($profile, 'Rua de Exemplo, 10'), 'current profile mixed with historic record');
$history = admin_record_history_html([$form]);
record_expect(str_contains($history, 'Inscrição original'), 'historic form title missing');
record_expect(str_contains($history, 'Confirmado manualmente'), 'administrative payment note missing');
record_expect(str_contains($history, 'Rua de Exemplo, 10'), 'historic answer missing');
record_expect(str_contains(admin_record_history_html([]), 'Nenhuma resposta'), 'empty-state missing');

foreach ([
    'admin/people.php' => ['admin_record_profile_html($profile)', 'admin_record_history_html($allSubmissions)'],
    'admin/students.php' => ['admin_record_profile_html(', 'admin_record_history_html('],
    'admin/registrations.php' => ['admin_record_submission_html($selected)', 'admin_record_profile_html('],
    'admin/submissions.php' => ['admin_record_submission_html($selected)'],
] as $file => $needles) {
    $page = file_get_contents(__DIR__ . '/../' . $file);
    foreach ($needles as $needle) {
        record_expect(str_contains((string)$page, $needle), $file . ' does not share the record renderer');
    }
}
echo "Complete admin person and historic submission tests passed\n";
