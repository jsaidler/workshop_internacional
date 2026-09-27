<?php
declare(strict_types=1);

function fail_editor_runtime(string $message): never {fwrite(STDERR,"editor-runtime-integration: $message\n");exit(1);}
function must_editor_runtime(bool $condition,string $message): void {if(!$condition)fail_editor_runtime($message);}

$root=dirname(__DIR__);
$index=(string)file_get_contents($root.'/editor/index.html');
$reliability=(string)file_get_contents($root.'/editor/cms-inline-reliability.js');
$coherence=(string)file_get_contents($root.'/editor/cms-section-coherence.js');
$inspector=(string)file_get_contents($root.'/editor/cms-inspector-coherence.js');
$access=(string)file_get_contents($root.'/editor/cms-access-controls.js');
$private=(string)file_get_contents($root.'/editor/cms-private-media-preview.js');
$endpoint=(string)file_get_contents($root.'/admin/api/cms-private-media-slots.php');

foreach(['cms-component-editor.js','cms-inline-reliability.js','cms-rich-components.js','cms-section-coherence.js','cms-inspector-coherence.js'] as $script){
    must_editor_runtime(str_contains($index,'/editor/'.$script),'production editor does not load '.$script);
}
$componentPos=strpos($index,'cms-component-editor.js');
$reliabilityPos=strpos($index,'cms-inline-reliability.js');
$richPos=strpos($index,'cms-rich-components.js');
$sectionCoherencePos=strpos($index,'cms-section-coherence.js');
$inspectorCoherencePos=strpos($index,'cms-inspector-coherence.js');
must_editor_runtime(
    $componentPos!==false&&$reliabilityPos!==false&&$richPos!==false&&$componentPos<$reliabilityPos&&$reliabilityPos<$richPos,
    'inline reliability layer is not loaded between component and rich-component layers'
);
must_editor_runtime(
    $sectionCoherencePos!==false&&$inspectorCoherencePos!==false&&$sectionCoherencePos<$inspectorCoherencePos,
    'inspector coherence layer is not loaded after section coherence'
);

must_editor_runtime(!str_contains($reliability,'stopImmediatePropagation'),'inline palette cancels handlers with stopImmediatePropagation');
must_editor_runtime(!str_contains($reliability,'button.click()'),'inline palette synthesizes a click instead of preserving native activation');
must_editor_runtime(!str_contains($reliability,'preventDefault()'),'inline palette cancels the browser native pointer activation');
must_editor_runtime(str_contains($reliability,'.cms-inline-palette{pointer-events:auto!important}'),'inline palette is not explicitly interactive above the canvas');
must_editor_runtime(str_contains($reliability,'.cms-inline-layer{z-index:2147483600!important}'),'inline editor layer does not have the required stacking priority');

foreach(['cms-private-media-preview.js','cms-hover-selection.js'] as $script){
    must_editor_runtime(str_contains($coherence,"editorAsset('/editor/$script')"),'section coherence layer does not load '.$script.' in the production editor');
}
must_editor_runtime(str_contains($private,'Escolher imagem')&&str_contains($private,'Trocar imagem')&&str_contains($private,'Remover vínculo'),'private media slot does not expose its complete editing workflow');
must_editor_runtime(str_contains($endpoint,"action==='bind'")&&str_contains($endpoint,'course_page_media_bind'),'private media endpoint does not bind through the canonical service');
must_editor_runtime(str_contains($endpoint,"action==='unbind'")&&str_contains($endpoint,'course_page_media_unbind'),'private media endpoint does not unbind through the canonical service');

foreach(['Identidade','Layout','Audiência','Disponibilidade'] as $group){
    must_editor_runtime(str_contains($inspector,"'$group'")||str_contains($inspector,">$group<")||str_contains($inspector,",'$group',"),'coherent inspector does not expose group '.$group);
}
must_editor_runtime(str_contains($inspector,"data-inspector-group=\"audience\"")||str_contains($inspector,"group(panel,'audience'"),'section audience group is not defined');
must_editor_runtime(str_contains($inspector,"group(panel,'availability'"),'section availability group is not defined');
must_editor_runtime(!str_contains($access,'id="cms-page-access-save"'),'page access still exposes an isolated save button');
must_editor_runtime(!str_contains($access,'Salvar acesso da página'),'page access still asks for an isolated save action');
must_editor_runtime(str_contains($access,'data-page-access-state'),'page access does not expose autosave state');
must_editor_runtime(str_contains($access,'Salvando acesso')&&str_contains($access,'Acesso salvo'),'page access autosave lacks visible saving/saved states');

echo "editor-runtime-integration: ok\n";
