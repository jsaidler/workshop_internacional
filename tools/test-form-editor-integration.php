<?php
declare(strict_types=1);
function must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"form-editor-integration: $message\n");exit(1);}}
$root=dirname(__DIR__);
$editor=(string)file_get_contents($root.'/editor/index.html');
$quick=(string)file_get_contents($root.'/editor/cms-form-quick-edit.js');
$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
$formCss=(string)file_get_contents($root.'/assets/admin-form-ux.css');
$forms=(string)file_get_contents($root.'/admin/forms.php');

must(str_contains($editor,'/editor/cms-form-quick-edit.js'),'page editor must load the form visual-edit controller');
must(str_contains($editor,'/editor/cms-form-quick-edit.css'),'page editor must load form visual-edit inspector styles');
must(str_contains($quick,'/admin/api/cms-form-load.php?form='),'visual form editor must load canonical form schema');
must(str_contains($quick,'/admin/api/cms-form-save.php'),'visual form editor must save through the canonical form API');
must(str_contains($quick,'/admin/api/cms-form-publish.php'),'visual form editor must support explicit form publishing');
must(str_contains($quick,"data-cms-form-inline")||str_contains($quick,'cmsFormInline'),'form copy must be decorated as direct inline edit targets');
must(str_contains($quick,"part:'field-label'")&&str_contains($quick,"part:'option-label'")&&str_contains($quick,"part:'field-help'")&&str_contains($quick,"part:'submit-label'"),'field labels, option labels, help text and submit text must be independent visual targets');
must(str_contains($quick,"setAttribute('contenteditable','true')"),'visible form copy must become editable in place');
must(str_contains($quick,"addEventListener('pointerdown'")&&str_contains($quick,'stopImmediatePropagation'),'form text must intercept interaction before the form wrapper is selected');
must(str_contains($quick,'placeCaret'),'direct form text editing must place the caret at the clicked point');
must(!str_contains($quick,'id="fq-field"'),'visual editor must not reduce the form to a field dropdown in the inspector');
must(!str_contains($quick,'Rótulo<input id="fq-label"'),'visible field labels must not be edited through a duplicate inspector text box');
must(str_contains($quick,'Valor interno da opção'),'non-visible option value may remain a contextual property of the clicked option');
must(str_contains($quick,'O formulário não é um bloco de texto único'),'whole-form selection must explicitly defer textual editing to direct page targets');
must(str_contains($quick,'Abrir editor completo'),'visual editor must preserve a path to structural form administration');
must(!str_contains($quick,'bf-type')&&!str_contains($quick,'condition_source'),'visual editor must not duplicate the structural form builder');
must(str_contains($adminShell,'admin_asset_version'),'admin shell must version its CSS/JS after application updates');
must(str_contains($adminShell,'admin-system-choice-controls'),'admin shell must enforce native checkbox/radio geometry independently of cached styles');
must(str_contains($adminShell,'/assets/admin-form-ux.css'),'admin shell must load the form-specific admin UX layer');
must(str_contains($formCss,'body.admin-page input[type="checkbox"],body.admin-page input[type="radio"]'),'admin checkbox/radio normalization must be global, not page-specific');
must(str_contains($formCss,'max-width:18px!important')&&str_contains($formCss,'max-height:18px!important'),'admin choice controls must not inherit text-input geometry');
must(str_contains($formCss,'.admin-section-forms .form-builder{display:block}'),'full form editor settings must not consume a permanent side column');
must(str_contains($formCss,'.admin-section-forms .form-builder-workspace{display:grid;grid-template-columns:1fr'),'form editor workspace must prioritize field editing over a cramped multi-column layout');
must(str_contains($forms,'forms-admin.js'),'full form builder must remain available for structural editing');

echo "Form editor integration tests passed\n";
