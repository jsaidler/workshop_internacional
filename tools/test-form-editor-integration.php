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
must(strpos($editor,'/editor/cms-form-quick-edit.js')<strpos($editor,'/editor/cms-editor-v3.js'),'form capture controller must register before the core iframe hooks');
must(str_contains($quick,'/admin/api/cms-form-load.php?form='),'visual form editor must load canonical form schema');
must(str_contains($quick,'/admin/api/cms-form-save.php'),'visual form editor must save through the canonical form API');
must(str_contains($quick,'/admin/api/cms-form-publish.php'),'visual form editor must support explicit form publishing');
must(str_contains($quick,"part:'field-label'")&&str_contains($quick,"part:'option-label'")&&str_contains($quick,"part:'field-help'")&&str_contains($quick,"part:'submit-label'"),'field labels, option labels, help text and submit text must be independent visual targets');
must(str_contains($quick,"part:'field-control'"),'actual input/select/textarea controls must remain selectable as field context instead of becoming dead areas');
must(str_contains($quick,'decorateAll(d)'),'all form sub-targets must be decorated when the preview document is installed');
must(str_contains($quick,"setAttribute('contenteditable','true')"),'visible form copy must become editable in place');
must(str_contains($quick,"addEventListener('pointerdown'")&&str_contains($quick,'stopImmediatePropagation'),'form text must intercept interaction before the form wrapper is selected');
must(str_contains($quick,"event.target?.closest?.('[data-cms-form-inline]')"),'interaction must resolve an explicit form sub-target, not the whole form wrapper');
must(str_contains($quick,'installCurrentDocument();'),'form capture must also install when the iframe document already exists, not only on a future load event');
must(str_contains($quick,'placeCaret'),'direct form text editing must place the caret at the clicked point');
must(!str_contains($quick,'id="fq-field"'),'visual editor must not reduce the form to a field dropdown in the inspector');
must(!str_contains($quick,'Rótulo<input id="fq-label"'),'visible field labels must not be edited through a duplicate inspector text box');
must(str_contains($quick,'Valor interno da opção'),'non-visible option value may remain a contextual property of the clicked option');
must(str_contains($quick,'Abrir editor completo'),'visual editor must preserve a path to structural form administration');
must(!str_contains($quick,'MutationObserver'),'form visual editing must not watch and rewrite the inspector recursively');
must(!str_contains($quick,'renderFormHint'),'whole-form inspector hint recursion must not exist');
must(!str_contains($quick,'if(block){clearSelection();event.preventDefault();event.stopImmediatePropagation();return}'),'form wrapper must never blanket-block every click that is not recognized as text');
must(!str_contains($quick,'if(formBlockFrom(event.target)){event.preventDefault();event.stopImmediatePropagation()}'),'click handler must never suppress every interaction merely because it occurred inside a form');
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
