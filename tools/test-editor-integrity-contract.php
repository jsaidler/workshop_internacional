<?php
declare(strict_types=1);
function integrity_expect(bool $ok,string $reason):void{if(!$ok){fwrite(STDERR,"editor-integrity: ".$reason."\n");exit(1);}}
$root=dirname(__DIR__);
$editor=(string)file_get_contents($root.'/editor/cms-editor-v3.js');
$autosave=(string)file_get_contents($root.'/editor/autosave.js');
$pages=(string)file_get_contents($root.'/app/cms_pages.php');
$html=(string)file_get_contents($root.'/editor/index.html');
$publish=(string)file_get_contents($root.'/admin/api/cms-page-publish.php');
$unpublish=(string)file_get_contents($root.'/admin/api/cms-page-unpublish.php');
integrity_expect(str_contains($editor,'window.CmsEditorDraft='),'editor service missing');
integrity_expect(str_contains($editor,'serial===state.serial&&before===after'),'ACK does not check local edit generation');
integrity_expect(str_contains($editor,"state.page.draftRevision=r.page.draftRevision"),'save does not advance expected revision');
integrity_expect(str_contains($autosave,"window.addEventListener('cms-editor:dirty'"),'autosave relies on status text');
integrity_expect(!str_contains($autosave,'MutationObserver')&&!str_contains($autosave,'1800'),'autosave still guesses completion');
integrity_expect(str_contains($pages,'function cms_page_unpublish('),'CMS cannot withdraw publication');
integrity_expect(str_contains($pages,'AND draft_revision=?'),'CMS publish lacks optimistic revision guard');
integrity_expect(str_contains($publish,"'revision'")&&str_contains($unpublish,"'revision'"),'page publish endpoints missing expected revision');
integrity_expect(str_contains($html,'id="unpublish-page"')&&str_contains($html,'id="save-page"'),'editor controls not present');
integrity_expect(str_contains($editor,"'Publicado · alterações em rascunho'"),'editor conflates draft and live states');
foreach(['cms-pro-editor.js','cms-component-editor.js','cms-rich-components.js','cms-direct-structure-dnd.js','cms-layout-editor.js','cms-history-transactions.js'] as $path){
    $js=(string)file_get_contents($root.'/editor/'.$path);
    integrity_expect(str_contains($js,'CmsEditorDraft'),'module '.$path.' does not await save');
    integrity_expect(!str_contains($js,'setTimeout(()=>{try{frame.contentWindow.location.reload()}catch{}},650)'),'timer reload still present: '.$path);
}
$rich=(string)file_get_contents($root.'/editor/cms-rich-components.js');
integrity_expect(str_contains($editor,'data-cms-link-inspector'),'inline link inspector must be available');
integrity_expect(str_contains($editor,'validLinkDestination'),'link destination validation missing');
integrity_expect(!str_contains($editor,"prompt('URL')"),'browser prompt cannot be the canonical link editor');
integrity_expect(str_contains($editor,'const wrapper=el.closest('),'image insertion must reuse the image block');
integrity_expect(str_contains($rich,'data-rich-list-item')&&str_contains($rich,'data-rich-gallery-item'),'individual list/gallery item selectors missing');

$audience=(string)file_get_contents($root.'/editor/cms-access-controls.js');
integrity_expect(str_contains($audience,'data.pagePublished')&&str_contains($audience,'efeito imediato'),'live-audience changes must disclose immediate exposure');
foreach(['cms-structure-selected','cms-pro-selected','cms-direct-drop-before','cms-inline-empty-outline','data-cms-editor-ui'] as $marker){
    integrity_expect(str_contains($editor,$marker),'transient editor marker not excluded by serialization: '.$marker);
}
$formatter=(string)file_get_contents($root.'/editor/cms-rich-text-commands.js');
integrity_expect(str_contains($formatter,"window.CmsRichTextCommands={apply}"),'native formatter must export command authority');
integrity_expect(!str_contains($editor,'.execCommand('),'core editor still uses deprecated browser formatting');
integrity_expect(str_contains((string)file_get_contents($root.'/editor/index.html'),'/editor/cms-rich-text-commands.js'),'formatter not installed');
echo "editor-integrity: ok\n";
