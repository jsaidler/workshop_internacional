<?php
declare(strict_types=1);

function submissions_ui_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"test-admin-submissions-layout: $message\n");exit(1);}}
$root=dirname(__DIR__);
$responses=(string)file_get_contents($root.'/admin/submissions.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$inbox=(string)file_get_contents($root.'/assets/admin-inbox.css');
$registration=(string)file_get_contents($root.'/assets/admin-registration.css');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$adminCss=(string)file_get_contents($root.'/assets/admin-system.css');

// Generic form responses own the inbox/master-detail presentation.
submissions_ui_expect(str_contains($responses,'function response_value_label('),'generic responses must retain a presentation formatter');
submissions_ui_expect(str_contains($responses,"COALESCE(f.purpose,'common')!='enrollment'"),'generic responses must exclude enrollment submissions');
submissions_ui_expect(str_contains($responses,"admin_shell_start('responses','Outras respostas'"),'generic responses must identify their own workspace');
submissions_ui_expect(str_contains($responses,"'/assets/admin-inbox.css'")&&!str_contains($responses,"'/assets/admin-registration.css'"),'generic responses must use inbox CSS without enrollment-specific presentation');
submissions_ui_expect(!str_contains($responses,'<section class="inbox-toolbar"><div><h2>'),'content area must not repeat the page title already present in the admin shell');

// Enrollment registrations own payment/cohort operations after the domain split.
submissions_ui_expect(str_contains($registrations,'function registration_value_label('),'registration values must retain their presentation formatter');
submissions_ui_expect(str_contains($registrations,"array_key_exists('payment_note',\$_POST)"),'payment actions must preserve an existing payment note');
submissions_ui_expect(str_contains($registrations,"admin_status_label('payment'"),'registration summary must expose payment state through the shared status primitive');
submissions_ui_expect(str_contains($registrations,'name="action" value="confirm"'),'registration detail must expose payment confirmation in context');
submissions_ui_expect(str_contains($registrations,"'/assets/admin-registration.css'"),'registration-specific CSS must be loaded through admin_shell_start');
submissions_ui_expect(!preg_match('/<link[^>]+admin-registration\.css/i',$registrations),'registration stylesheet must not be injected after page content');

// Shared inbox responsiveness remains bounded and keyboard/mobile friendly.
submissions_ui_expect(str_contains($inbox,'body.admin-section-responses .admin-content{width:auto;max-width:none;margin:0 28px'),'responses workspace must align with the shell and use available width');
submissions_ui_expect(str_contains($inbox,'grid-template-columns:minmax(300px,340px) minmax(0,1fr)'),'desktop master-detail must reserve a bounded list and flexible detail');
submissions_ui_expect(str_contains($inbox,'.inbox-list .submission-row.selected{background:#efefe9'),'selection must be neutral rather than reuse success green');
submissions_ui_expect(str_contains($inbox,'small[data-status="new"]{background:#f2e8d6;color:#87540f}'),'new response state must use pending semantics rather than success green');
submissions_ui_expect(str_contains($inbox,'.inbox-detail-header{position:sticky'),'detail identity must remain visible while its pane scrolls');
submissions_ui_expect(!str_contains($inbox,'overflow-wrap:anywhere'),'detail values must not be allowed to break at arbitrary characters');

// Enrollment-specific styling may refine registration groups, but must remain responsive.
submissions_ui_expect(str_contains($registration,'.registration-admin-group .inbox-fields{display:grid;grid-template-columns:repeat(2,minmax(220px,1fr))'),'registration field groups must support a two-column desktop layout');
submissions_ui_expect(str_contains($registration,'@media(max-width:1180px){.registration-admin-group .inbox-fields{grid-template-columns:1fr}}'),'registration field groups must collapse before values become compressed');
submissions_ui_expect(str_contains($registration,'.registration-payment-actions{display:flex;flex-wrap:wrap'),'registration payment actions must wrap rather than overflow');

submissions_ui_expect(str_contains($shell,'admin-wordmark-context'),'Administração must remain a distinct structural element next to the installation wordmark');
submissions_ui_expect(str_contains($adminCss,'.admin-sidebar .admin-wordmark-context{display:block'),'wordmark context styling must live in the canonical admin CSS authority');

echo "Admin submissions layout tests passed\n";
