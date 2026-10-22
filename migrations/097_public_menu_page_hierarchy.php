<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasActivities=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activities'")->fetchColumn();
    if(!$hasPages||!$hasActivities)return;

    $columns=array_column($db->query('PRAGMA table_info(cms_pages)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('parent_page_id',$columns,true))return;

    $activityIds=$db->query("SELECT id FROM activities WHERE is_root=1 AND status!='archived' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    foreach($activityIds as $activityIdRaw){
        $activityId=(int)$activityIdRaw;
        foreach([
            'pt-BR'=>['inscricao'],
            'en'=>['registration','inscricao'],
        ] as $locale=>$slugs){
            $home=$db->prepare("SELECT id FROM cms_pages WHERE activity_id=? AND locale=? AND is_home=1 AND status!='archived' LIMIT 1");
            $home->execute([$activityId,$locale]);$homeId=(int)($home->fetchColumn()?:0);
            if($homeId<1)continue;

            foreach($slugs as $slug){
                $child=$db->prepare("SELECT id,parent_page_id FROM cms_pages WHERE activity_id=? AND locale=? AND slug=? AND is_home=0 AND status!='archived' LIMIT 1");
                $child->execute([$activityId,$locale,$slug]);$row=$child->fetch(PDO::FETCH_ASSOC);
                if(!$row)continue;
                if((int)($row['parent_page_id']??0)===0)$db->prepare('UPDATE cms_pages SET parent_page_id=?,updated_at=? WHERE id=?')->execute([$homeId,gmdate('c'),(int)$row['id']]);
                break;
            }
        }
    }
};
