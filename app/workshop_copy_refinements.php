<?php
declare(strict_types=1);

function workshop_live_session_copy_replacements(): array {
    return [
        'Demonstração prática ao vivo, com o maior número possível de fotografias e variação deliberada de exposição e parâmetros.'=>'Produção fotográfica ao vivo, com o maior número possível de fotografias e variação deliberada de exposição e parâmetros para comparar os resultados.',
        'São três encontros: preparação e exposição; demonstração prática completa com comparação de resultados; e análise das imagens produzidas pelos participantes.'=>'São três encontros: preparação e exposição; produção fotográfica ao vivo com variação deliberada de exposição e parâmetros; e análise das imagens produzidas pelos participantes.',
        'Live practical demonstration with as many photographs as possible and deliberate changes in exposure and processing parameters.'=>'Live photographic production with as many photographs as time allows, deliberately changing exposure and processing parameters so the group can compare the results.',
    ];
}

function workshop_refine_live_session_copy_for_activity(PDO $db,int $activityId): int {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();if(!$hasPages)return 0;
    $replacements=workshop_live_session_copy_replacements();$q=$db->prepare("SELECT id,draft_document_json,published_document_json,draft_revision,published_revision FROM cms_pages WHERE activity_id=? AND status!='archived'");$q->execute([$activityId]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);$now=gmdate('c');$changedPages=0;
    foreach($rows as $row){$sets=[];$args=[];$draft=(string)($row['draft_document_json']??'');$published=(string)($row['published_document_json']??'');$newDraft=str_replace(array_keys($replacements),array_values($replacements),$draft,$draftCount);$newPublished=str_replace(array_keys($replacements),array_values($replacements),$published,$publishedCount);
        if($draftCount>0){$sets[]='draft_document_json=?';$args[]=$newDraft;$sets[]='draft_revision=?';$args[]=(int)$row['draft_revision']+1;$sets[]='draft_updated_at=?';$args[]=$now;}
        if($publishedCount>0&&$published!==''){$sets[]='published_document_json=?';$args[]=$newPublished;$sets[]='published_revision=?';$args[]=max(1,(int)($row['published_revision']??0)+1);$sets[]='published_at=?';$args[]=$now;}
        if(!$sets)continue;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$row['id'];$update=$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?');$update->execute($args);$changedPages++;
    }
    return $changedPages;
}
