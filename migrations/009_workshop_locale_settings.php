<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS workshop_locale_settings (activity_id INTEGER NOT NULL,locale TEXT NOT NULL,planned_price TEXT NOT NULL,updated_at TEXT NOT NULL,PRIMARY KEY(activity_id,locale),FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE)");
    $insert=$db->prepare('INSERT OR IGNORE INTO workshop_locale_settings(activity_id,locale,planned_price,updated_at) VALUES(?,?,?,?)');
    $now=gmdate('c');
    $content=$db->prepare('SELECT published_json,draft_json FROM content_documents WHERE activity_id=? LIMIT 1');
    foreach($db->query('SELECT id FROM activities') as $activity){
        $activityId=(int)$activity['id'];$englishPrice='US$195';$content->execute([$activityId]);$row=$content->fetch();
        if($row){$document=json_decode((string)($row['published_json']?:$row['draft_json']),true);$candidate=trim(strip_tags((string)($document['texts']['hero-price']['html']??'')));if($candidate!==''&&strlen($candidate)<=80)$englishPrice=$candidate;}
        $insert->execute([$activityId,'en',$englishPrice,$now]);$insert->execute([$activityId,'pt-BR','US$ 195',$now]);
    }
};
