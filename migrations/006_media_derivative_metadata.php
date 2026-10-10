<?php
declare(strict_types=1);

return static function(PDO $db):void{
    $columns=$db->query('PRAGMA table_info(media_derivatives)')->fetchAll(PDO::FETCH_COLUMN,1);
    if(!in_array('mime_type',$columns,true))$db->exec('ALTER TABLE media_derivatives ADD COLUMN mime_type TEXT NOT NULL DEFAULT ""');
    if(!in_array('checksum',$columns,true))$db->exec('ALTER TABLE media_derivatives ADD COLUMN checksum TEXT NOT NULL DEFAULT ""');
};
