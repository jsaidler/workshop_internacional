<?php
declare(strict_types=1);

function must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"task-centric-admin: $message\n");exit(1);}}
$root=dirname(__DIR__);
$admin=(string)file_get_contents($root.'/admin/index.php');
$editor=(string)file_get_contents($root.'/editor/index.html');
$coreEditor=(string)file_get_contents($root.'/editor/cms-editor-v3.js');
$client=(string)file_get_contents($root.'/editor/task-centric.js');
$autosave=(string)file_get_contents($root.'/editor/autosave.js');
$context=(string)file_get_contents($root.'/admin/api/editor-context.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$submissions=(string)file_get_contents($root.'/admin/submissions.php');
$media=(string)file_get_contents($root.'/admin/media.php');
$mediaTask=(string)file_get_contents($root.'/assets/admin-media-task.js');
$mediaLibrary=(string)file_get_contents($root.'/editor/media-library.js');

must(str_contains($admin,"Location: /editor/?page="),'admin entry point must open the editor');
must(str_contains($editor,'id="editor-page-switcher"'),'editor must expose page switching in context');
must(str_contains($editor,'id="editor-submissions"'),'editor must expose inscriptions without leaving the site mental model');
must(str_contains($editor,'id="media-library-search"'),'image replacement must have inline library search');
must(str_contains($editor,'<button type="button" id="save-page">Salvar agora</button>'),'manual save must remain available but secondary');
must(strpos($editor,'id="publish-page"')<strpos($editor,'id="save-page"'),'publish must be the primary visible editorial action');
if(str_contains($coreEditor,"$('#editor-title').textContent"))must(str_contains($editor,'id="editor-title"'),'editor core title target must exist');
if(str_contains($coreEditor,"$('#editor-locale').textContent"))must(str_contains($editor,'id="editor-locale"'),'editor core locale target must exist');
must(str_contains($autosave,'MutationObserver'),'autosave must react to dirty-state changes');
must(str_contains($autosave,'setTimeout(flush,3200)'),'autosave must debounce short editing pauses');
must(!str_contains($autosave,'setInterval'),'autosave must not wait on a fixed polling interval');
must(str_contains($client,'/admin/api/editor-context.php'),'editor context must be loaded from the authenticated endpoint');
must(str_contains($client,"includes('não salvas')"),'page switching must protect unsaved changes');
must(str_contains($context,"status='new'"),'editor context must surface new inscriptions');
must(!str_contains($shell,"'overview'=>['Início'"),'legacy dashboard must not remain in primary navigation');
must(str_contains($shell,"'site'=>['Site','/admin/'"),'site must be the primary admin destination');
must(str_contains($submissions,"foreach(['name','full_name','email','phone']"),'inbox must identify registrations by participant name before email');
must(str_contains($submissions,'Aguardando pagamento'),'registration inbox must expose payment state directly');
must(str_contains($submissions,'Confirmar inscrição e pagamento'),'registration admin must make confirmation the primary operational action');
must(str_contains($media,'id="media-selection-toggle"'),'bulk media actions must be an explicit mode');
must(str_contains($mediaTask,'previewSrc(item,480)'),'media grid must use thumbnail-sized sources');
must(str_contains($mediaLibrary,'previewSrc(item,target=480)'),'media library must choose responsive previews');

echo "Task-centric admin tests passed\n";
