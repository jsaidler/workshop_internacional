<?php
declare(strict_types=1);

function fail_editor_runtime(string $message): never {fwrite(STDERR,"editor-runtime-integration: $message\n");exit(1);}
function must_editor_runtime(bool $condition,string $message): void {if(!$condition)fail_editor_runtime($message);}

$root=dirname(__DIR__);
$index=(string)file_get_contents($root.'/editor/index.html');
$reliability=(string)file_get_contents($root.'/editor/cms-inline-reliability.js');
$private=(string)file_get_contents($root.'/editor/cms-private-media-preview.js');
$endpoint=(string)file_get_contents($root.'/admin/api/cms-private-media-slots.php');

foreach(['cms-component-editor.js','cms-inline-reliability.js','cms-rich-components.js','cms-private-media-preview.js','cms-hover-selection.js'] as $script){
    must_editor_runtime(str_contains($index,'/editor/'.$script),'production editor does not load '.$script);
}
$componentPos=strpos($index,'cms-component-editor.js');
$reliabilityPos=strpos($index,'cms-inline-reliability.js');
$richPos=strpos($index,'cms-rich-components.js');
must_editor_runtime($componentPos!==false&&$reliabilityPos!==false&&$richPos!==false&&$componentPos<$reliabilityPos&&$reliabilityPos<$richPos,'inline reliability script is not loaded between component and rich-component layers');

must_editor_runtime(!str_contains($reliability,'stopImmediatePropagation'),'inline palette still cancels the button handler with stopImmediatePropagation');
must_editor_runtime(!str_contains($reliability,'button.click()'),'inline palette still synthesizes clicks from pointerdown');
must_editor_runtime(str_contains($reliability,"closest?.('.cms-inline-palette')"),'inline palette does not isolate editor selection events');

must_editor_runtime(str_contains($private,"pointer-events:auto")&&str_contains($private,"data-private-media-choose"),'private media placeholder is not an interactive editor target');
must_editor_runtime(str_contains($private,'Escolher imagem')&&str_contains($private,'Trocar imagem')&&str_contains($private,'Remover vínculo'),'private media slot does not expose the complete editing workflow');
must_editor_runtime(str_contains($private,'MediaLibrary.upload')&&str_contains($private,"visibility:'private'"),'private media chooser does not support direct private-image upload');
must_editor_runtime(str_contains($endpoint,"action==='bind'")&&str_contains($endpoint,'course_page_media_bind'),'private media endpoint does not bind through the canonical service');
must_editor_runtime(str_contains($endpoint,"action==='unbind'")&&str_contains($endpoint,'course_page_media_unbind'),'private media endpoint does not unbind through the canonical service');

echo "editor-runtime-integration: ok\n";
