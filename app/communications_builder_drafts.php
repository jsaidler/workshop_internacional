<?php
declare(strict_types=1);
require_once __DIR__.'/communications_builder_document.php';

/**
 * MJML is experimental, never a sendable body. Every operation checks the
 * campaign's activity and draft status, including inside the write lock.
 */
function communication_builder_campaign(PDO $db,int $activityId,int $campaignId):array {
    if($activityId<1||$campaignId<1)throw new RuntimeException('Salve primeiro uma mensagem para editar seu layout.');
    $q=$db->prepare('SELECT id,activity_id,status,subject FROM communication_campaigns WHERE id=? AND activity_id=?');
    $q->execute([$campaignId,$activityId]);
    $campaign=$q->fetch(PDO::FETCH_ASSOC);
    if(!$campaign)throw new RuntimeException('Mensagem não encontrada nesta área.');
    if($campaign['status']!=='draft')throw new RuntimeException('O layout só pode ser editado enquanto a mensagem estiver em rascunho.');
    return $campaign;
}

function communication_builder_draft_read(PDO $db,int $activityId,int $campaignId):array {
    communication_builder_campaign($db,$activityId,$campaignId);
    $q=$db->prepare('SELECT document_version,mjml_source,media_refs_json,revision,updated_at FROM communication_builder_drafts WHERE campaign_id=?');
    $q->execute([$campaignId]);
    $row=$q->fetch(PDO::FETCH_ASSOC);
    if(!$row)return ['version'=>1,'exists'=>false,'revision'=>0,'mjml'=>'','media_refs'=>[]];
    if((int)$row['document_version']!==1)throw new RuntimeException('Versão do documento visual não suportada.');
    $refs=json_decode((string)$row['media_refs_json'],true,32,JSON_THROW_ON_ERROR);
    if(!is_array($refs))throw new RuntimeException('As imagens salvas estão em formato inválido.');
    return ['version'=>1,'exists'=>true,'revision'=>(int)$row['revision'],
        'mjml'=>(string)$row['mjml_source'],'media_refs'=>$refs,'updated_at'=>(string)$row['updated_at']];
}

function communication_builder_draft_write(
    PDO $db,int $activityId,int $campaignId,string $source,array $mediaRefs,int $expectedRevision
):array {
    if($expectedRevision<0)throw new RuntimeException('Revisão do documento inválida.');
    if(count($mediaRefs)>12)throw new RuntimeException('Use no máximo 12 imagens.');
    // All returned content has been validated on the server, not merely in JS.
    $document=communication_builder_validate_document($db,$source,$mediaRefs);
    return course_agenda_with_lock($db,static function()use(
        $db,$activityId,$campaignId,$mediaRefs,$expectedRevision,$document
    ):array {
        communication_builder_campaign($db,$activityId,$campaignId);
        // Image visibility can change before a write lock is acquired.
        $verified=communication_builder_validate_document($db,$document['mjml'],$mediaRefs);
        $q=$db->prepare('SELECT revision FROM communication_builder_drafts WHERE campaign_id=?');
        $q->execute([$campaignId]);
        $present=$q->fetchColumn();
        $current=$present===false?0:(int)$present;
        if($current!==$expectedRevision)
            throw new RuntimeException('Este rascunho foi modificado em outra aba. Reabra a versão atual antes de salvar.');
        $now=utc_now();
        $refs=json_encode($mediaRefs,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
        if($present===false) {
            $q=$db->prepare('INSERT INTO communication_builder_drafts(campaign_id,document_version,mjml_source,media_refs_json,revision,created_at,updated_at) VALUES(?,1,?,?,1,?,?)');
            $q->execute([$campaignId,$verified['mjml'],$refs,$now,$now]);
        }else{
            $q=$db->prepare('UPDATE communication_builder_drafts SET mjml_source=?,media_refs_json=?,revision=?,updated_at=? WHERE campaign_id=?');
            $q->execute([$verified['mjml'],$refs,$current+1,$now,$campaignId]);
        }
        return ['version'=>1,'revision'=>$current+1,'updated_at'=>$now,'counts'=>$verified['counts'],'media_ids'=>$verified['media_ids']];
    });
}
