<?php
declare(strict_types=1);

function submissions_ui_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"test-admin-submissions-layout: $message\n");exit(1);}}
$root=dirname(__DIR__);
$page=(string)file_get_contents($root.'/admin/submissions.php');
$inbox=(string)file_get_contents($root.'/assets/admin-inbox.css');
$registration=(string)file_get_contents($root.'/assets/admin-registration.css');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');

submissions_ui_expect(str_contains($page,'function submission_display_value('),'registration values must have a presentation formatter');
submissions_ui_expect(str_contains($page,"\$key==='cpf'")&&str_contains($page,"\$key==='postal_code'")&&str_contains($page,"\$key==='phone'"),'CPF, CEP and phone formatting must remain explicit');
submissions_ui_expect(str_contains($page,"array_key_exists('payment_note',\$_POST)"),'quick status actions must preserve an existing payment note');
submissions_ui_expect(str_contains($page,'<span>Status</span>'),'registration summary must identify the state as Status, not repeat Inscrição');
submissions_ui_expect(str_contains($page,'registration-status-action'),'registration state must expose the payment action in context');
submissions_ui_expect(str_contains($page,'data-field="<?=h($key)?>"'),'registration field units must expose semantic field keys to the layout');
submissions_ui_expect(str_contains($page,"admin_asset_url('/assets/admin-inbox.css')")&&str_contains($page,"admin_asset_url('/assets/admin-registration.css')"),'page-specific admin CSS must be cache-versioned');
submissions_ui_expect(!str_contains($page,'<section class="inbox-toolbar"><div><h2>Inscrições</h2>'),'content area must not repeat the page title already present in the admin shell');

submissions_ui_expect(str_contains($inbox,'body.admin-section-responses .admin-content{width:auto;max-width:none;margin:0 28px'),'responses workspace must align with the shell and use available width');
submissions_ui_expect(str_contains($inbox,'grid-template-columns:minmax(300px,340px) minmax(0,1fr)'),'desktop master-detail must reserve a bounded list and flexible detail');
submissions_ui_expect(str_contains($inbox,'.inbox-list .submission-row.selected{background:#efefe9'),'selection must be neutral rather than reuse success green');
submissions_ui_expect(str_contains($inbox,'small[data-status="new"]{background:#f2e8d6;color:#87540f}'),'pending/new registration state must use pending semantics rather than success green');
submissions_ui_expect(str_contains($inbox,'.inbox-detail-header{position:sticky'),'detail identity must remain visible while its pane scrolls');
submissions_ui_expect(!str_contains($inbox,'overflow-wrap:anywhere'),'detail values must not be allowed to break at arbitrary characters');

submissions_ui_expect(str_contains($registration,'.registration-admin-group .inbox-fields{display:grid;grid-template-columns:repeat(2,minmax(220px,1fr))'),'registration fields must be label/value units in two desktop columns');
submissions_ui_expect(str_contains($registration,'.registration-admin-group .inbox-fields>div{display:grid;grid-template-columns:1fr'),'each registration datum must stack its label above its value');
submissions_ui_expect(str_contains($registration,'[data-field="address"]{grid-column:1/-1}'),'street address must receive the full row');
submissions_ui_expect(str_contains($registration,'.registration-admin-status.is-pending')&&str_contains($registration,'.registration-admin-status.is-paid'),'pending and confirmed states must remain visually distinct');
submissions_ui_expect(str_contains($registration,'@media(max-width:1180px){.registration-admin-group .inbox-fields{grid-template-columns:1fr}}'),'field units must collapse before their values become compressed');

submissions_ui_expect(str_contains($shell,'admin-wordmark-context')&&str_contains($shell,'.admin-sidebar .admin-wordmark-context{display:block'),'Administração must be structurally separated from the wordmark');

echo "Admin submissions layout tests passed\n";
