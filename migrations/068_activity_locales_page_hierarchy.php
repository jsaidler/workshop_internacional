<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $activitiesExists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activities'")->fetchColumn();
    if(!$activitiesExists)return;

    $db->exec("CREATE TABLE IF NOT EXISTS activity_locales (
        activity_id INTEGER NOT NULL,
        locale TEXT NOT NULL,
        public_title TEXT NOT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        PRIMARY KEY(activity_id,locale),
        FOREIGN KEY(activity_id) REFERENCES activities(id)
    )");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_activity_locales_locale ON activity_locales(locale,activity_id)');

    $now=gmdate('c');
    $pagesExists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if($pagesExists){
        $rows=$db->query("SELECT DISTINCT a.id AS activity_id,p.locale,a.public_title FROM activities a JOIN cms_pages p ON p.activity_id=a.id WHERE TRIM(COALESCE(p.locale,''))<>''")->fetchAll(PDO::FETCH_ASSOC);
        $insert=$db->prepare('INSERT OR IGNORE INTO activity_locales(activity_id,locale,public_title,created_at,updated_at) VALUES(?,?,?,?,?)');
        foreach($rows as $row)$insert->execute([(int)$row['activity_id'],(string)$row['locale'],(string)$row['public_title'],$now,$now]);
    }
    $withoutLocale=$db->query('SELECT a.id,a.public_title FROM activities a WHERE NOT EXISTS(SELECT 1 FROM activity_locales l WHERE l.activity_id=a.id)')->fetchAll(PDO::FETCH_ASSOC);
    $insertFallback=$db->prepare('INSERT OR IGNORE INTO activity_locales(activity_id,locale,public_title,created_at,updated_at) VALUES(?,?,?,?,?)');
    foreach($withoutLocale as $row)$insertFallback->execute([(int)$row['id'],'pt-BR',(string)$row['public_title'],$now,$now]);

    if(!$pagesExists)return;
    $columns=$db->query('PRAGMA table_info(cms_pages)')->fetchAll(PDO::FETCH_ASSOC);
    $columnNames=array_map(static fn(array $row): string=>(string)$row['name'],$columns);
    if(!in_array('parent_page_id',$columnNames,true))$db->exec('ALTER TABLE cms_pages ADD COLUMN parent_page_id INTEGER NULL');
    if(!in_array('translation_group_uuid',$columnNames,true))$db->exec('ALTER TABLE cms_pages ADD COLUMN translation_group_uuid TEXT NULL');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_pages_parent ON cms_pages(activity_id,locale,parent_page_id,sort_order,id)');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_cms_pages_translation_locale ON cms_pages(activity_id,translation_group_uuid,locale) WHERE translation_group_uuid IS NOT NULL');
};
