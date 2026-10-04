<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasDesign=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_design_settings'")->fetchColumn();
    if(!$hasDesign)return;

    $rows=$db->query(
        "SELECT activity_id,locale,settings_json,updated_at
         FROM cms_design_settings
         WHERE locale!='__site__'
         ORDER BY activity_id,updated_at DESC"
    )->fetchAll();

    $byActivity=[];
    foreach($rows as $row){
        $activityId=(int)$row['activity_id'];
        if($activityId<1)continue;
        $decoded=json_decode((string)$row['settings_json'],true);
        $css=is_array($decoded)&&is_array($decoded['advanced']??null)?(string)($decoded['advanced']['customCss']??''):'';
        if(!isset($byActivity[$activityId]))$byActivity[$activityId]=['latest'=>$css,'nonEmpty'=>null];
        if($byActivity[$activityId]['nonEmpty']===null&&trim($css)!=='')$byActivity[$activityId]['nonEmpty']=$css;
    }

    $upsert=$db->prepare(
        "INSERT INTO cms_design_settings(activity_id,locale,settings_json,updated_at)
         VALUES(?,?,?,?)
         ON CONFLICT(activity_id,locale)
         DO UPDATE SET settings_json=excluded.settings_json,updated_at=excluded.updated_at"
    );
    foreach($byActivity as $activityId=>$values){
        $css=$values['nonEmpty']??$values['latest'];
        $json=json_encode(['advanced'=>['customCss'=>$css]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $upsert->execute([(int)$activityId,'__site__',$json,gmdate('c')]);
    }
};
