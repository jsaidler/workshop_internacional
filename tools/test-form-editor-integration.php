<?php
declare(strict_types=1);
function must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"form-editor-integration: $message\n");exit(1);}}
$root=dirname(__DIR__);
$editor=(string)file_get_contents($root.'/editor/index.html');
$quick=(string)file_get_contents($root.'/editor/cms-form-quick-edit.js');
$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
$formCss=(string)file_get_contents($root.'/assets/admin-form-ux.css');
$forms=(string)file_get_contents($root.'/admin/forms.php');

must(str_contains($editor,'/editor/cms-form-quick-edit.js'),'page editor must load the simplified form editor');
must(str_contains($editor,'/editor/cms-form-quick-edit.css'),'page editor must load simplified form editor styles');
must(str_contains($quick,'/admin/api/cms-form-load.php?form='),'quick editor must load the selected form schema');
must(str_contains($quick,'/admin/api/cms-form-save.php'),'quick editor must save form draft through the canonical form API');
must(str_contains($quick,'/admin/api/cms-form-publish.php'),'quick editor must support explicit form publishing');
must(str_contains($quick,'Rótulo da opção')&&str_contains($quick,'Valor interno'),'quick editor must expose both option label and internal value');
must(str_contains($quick,'Abrir editor completo'),'quick editor must preserve a path to structural form administration');
must(str_contains($quick,'fieldIdFromTarget'),'clicking a rendered form field must identify the corresponding schema field when possible');
must(!str_contains($quick,'bf-type')&&!str_contains($quick,'condition_source'),'quick editor must stay intentionally limited and not duplicate the full form builder');
must(str_contains($adminShell,'admin_asset_version'),'admin shell must version its CSS/JS after application updates');
must(str_contains($adminShell,'admin-system-choice-controls'),'admin shell must enforce native checkbox/radio geometry independently of cached styles');
must(str_contains($adminShell,'/assets/admin-form-ux.css'),'admin shell must load the form-specific admin UX layer');
must(str_contains($formCss,'body.admin-page input[type="checkbox"],body.admin-page input[type="radio"]'),'admin checkbox/radio normalization must be global, not page-specific');
must(str_contains($formCss,'max-width:18px!important')&&str_contains($formCss,'max-height:18px!important'),'admin choice controls must not inherit text-input geometry');
must(str_contains($formCss,'.admin-section-forms .form-builder{display:block}'),'full form editor settings must not consume a permanent side column');
must(str_contains($formCss,'.admin-section-forms .form-builder-workspace{display:grid;grid-template-columns:1fr'),'form editor workspace must prioritize field editing over a cramped multi-column layout');
must(str_contains($forms,'forms-admin.js'),'full form builder must remain available for structural editing');

echo "Form editor integration tests passed\n";
