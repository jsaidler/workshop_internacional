<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $columns=[];
    foreach($db->query('PRAGMA table_info(student_test_media)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
    if(!isset($columns['media_kind']))$db->exec("ALTER TABLE student_test_media ADD COLUMN media_kind TEXT NOT NULL DEFAULT 'result'");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_student_test_media_kind ON student_test_media(test_id,media_kind,id)');

    $activities=$db->query('SELECT id FROM activities ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    foreach($activities as $activityId){
        foreach([PUBLIC_LOCALE_PT_BR,PUBLIC_LOCALE_EN] as $locale){
            $settings=cms_site_settings($db,(int)$activityId,$locale);
            $label=$locale===PUBLIC_LOCALE_PT_BR?'Área do aluno':'Student area';
            $items=is_array($settings['navigation']['items']??null)?$settings['navigation']['items']:[];
            $hasStudent=false;
            foreach($items as $item)if(is_array($item)&&($item['type']??'')==='custom'&&rtrim((string)($item['url']??''),'/')==='/aluno'){$hasStudent=true;break;}
            if(!$hasStudent){
                $items[]=['type'=>'custom','label'=>$label,'url'=>'/aluno/','newTab'=>false];
                $settings['navigation']['items']=$items;
                cms_settings_save($db,'site',(int)$activityId,$locale,$settings);
            }
        }
    }
};
