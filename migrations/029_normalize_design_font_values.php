<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasDesign=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_design_settings'")->fetchColumn();
    if(!$hasDesign)return;

    $fonts=[
        'bodyFont'=>'"IBM Plex Sans", Arial, sans-serif',
        'displayFont'=>'"Saira Extra Condensed", "Arial Narrow", sans-serif',
        'monoFont'=>'"IBM Plex Mono", Consolas, monospace',
    ];
    $legacy=[
        'bodyFont'=>['var(--sans)','var(--body)'],
        'displayFont'=>['var(--title)','var(--font-display)'],
        'monoFont'=>['var(--mono)'],
    ];

    $rows=$db->query("SELECT activity_id,locale,settings_json FROM cms_design_settings")->fetchAll();
    $update=$db->prepare("UPDATE cms_design_settings SET settings_json=?,updated_at=? WHERE activity_id=? AND locale=?");
    foreach($rows as $row){
        $settings=json_decode((string)$row['settings_json'],true);
        if(!is_array($settings)||!is_array($settings['type']??null))continue;
        $changed=false;
        foreach($fonts as $key=>$font){
            $value=trim((string)($settings['type'][$key]??''));
            if(!in_array($value,$legacy[$key],true))continue;
            $settings['type'][$key]=$font;$changed=true;
        }
        if(!$changed)continue;
        $json=json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $update->execute([$json,gmdate('c'),(int)$row['activity_id'],(string)$row['locale']]);
    }
};
