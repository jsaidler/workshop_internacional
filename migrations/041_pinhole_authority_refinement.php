<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $old='A construção também passou do uso pessoal para um produto: hoje fabrico para venda a NINA, uma câmera autoral de grande formato. A Pinhole Lambe-Lambe nasce da mesma prática de projetar equipamentos funcionais e resolver mecanismos com recursos acessíveis.';
    $new='Além de projetar e construir as câmeras que uso no meu próprio trabalho, fabrico para venda a NINA, uma câmera autoral de grande formato. A Pinhole Lambe-Lambe nasce dessa mesma prática: desenvolver equipamentos fotográficos funcionais a partir das necessidades do processo.';
    $now=gmdate('c');

    $pages=$db->query("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pages as $page){
        $draft=json_decode((string)$page['draft_document_json'],true);
        if(!is_array($draft))continue;
        $draftHtml=(string)($draft['html']??'');
        $draftChanged=str_contains($draftHtml,$old);
        if($draftChanged){
            $draft['html']=str_replace($old,$new,$draftHtml);
        }

        $publishedChanged=false;
        $publishedOut=$page['published_document_json'];
        if(is_string($publishedOut)&&$publishedOut!==''){
            $published=json_decode($publishedOut,true);
            if(is_array($published)){
                $publishedHtml=(string)($published['html']??'');
                if(str_contains($publishedHtml,$old)){
                    $published['html']=str_replace($old,$new,$publishedHtml);
                    $publishedOut=json_encode($published,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                    $publishedChanged=true;
                }
            }
        }

        if(!$draftChanged&&!$publishedChanged)continue;
        $draftJson=json_encode($draft,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $q=$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=CASE WHEN ? THEN COALESCE(published_revision,draft_revision)+1 ELSE published_revision END,draft_updated_at=?,published_at=CASE WHEN ? THEN ? ELSE published_at END,updated_at=? WHERE id=?');
        $q->execute([$draftJson,$publishedOut,$publishedChanged?1:0,$now,$publishedChanged?1:0,$now,$now,(int)$page['id']]);
    }
};
