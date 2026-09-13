<?php
declare(strict_types=1);

function must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"task-centric-admin: $message\n");exit(1);}}
$root=dirname(__DIR__);
$admin=(string)file_get_contents($root.'/admin/index.php');
$editor=(string)file_get_contents($root.'/editor/index.html');
$client=(string)file_get_contents($root.'/editor/task-centric.js');
$context=(string)file_get_contents($root.'/admin/api/editor-context.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');

must(str_contains($admin,"Location: /editor/?page="),'admin entry point must open the editor');
must(str_contains($editor,'id="editor-page-switcher"'),'editor must expose page switching in context');
must(str_contains($editor,'id="editor-submissions"'),'editor must expose inscriptions without leaving the site mental model');
must(str_contains($editor,'id="media-library-search"'),'image replacement must have inline library search');
must(str_contains($client,'/admin/api/editor-context.php'),'editor context must be loaded from the authenticated endpoint');
must(str_contains($client,"includes('não salvas')"),'page switching must protect unsaved changes');
must(str_contains($context,"status='new'"),'editor context must surface new inscriptions');
must(!str_contains($shell,"'overview'=>['Início'"),'legacy dashboard must not remain in primary navigation');
must(str_contains($shell,"'site'=>['Site','/admin/'"),'site must be the primary admin destination');

echo "Task-centric admin tests passed\n";
