<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasSettings=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='workshop_locale_settings'")->fetchColumn();
    if(!$hasSettings)return;
    $now=gmdate('c');
    $q=$db->prepare("UPDATE workshop_locale_settings SET planned_price='R$ 698',updated_at=? WHERE locale=? AND TRIM(planned_price) IN ('US$ 195','US$195')");
    $q->execute([$now,PUBLIC_LOCALE_PT_BR]);
};
