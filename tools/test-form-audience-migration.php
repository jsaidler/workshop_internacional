<?php
declare(strict_types=1);

function assert_migration(bool $condition,string $message): void {
    if(!$condition)throw new RuntimeException($message);
}

$path=tempnam(sys_get_temp_dir(),'workshop-form-migration-');
if($path===false)throw new RuntimeException('Could not create temporary database.');
try{
    $db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $db->exec("CREATE TABLE interest_submissions (id INTEGER PRIMARY KEY AUTOINCREMENT); CREATE TABLE content_documents (id INTEGER PRIMARY KEY AUTOINCREMENT,draft_json TEXT NULL,published_json TEXT NULL,updated_at TEXT NOT NULL)");
    $legacy=['forms'=>['interest-form'=>['fields'=>[
        ['id'=>'name','type'=>'text','label'=>'Name','required'=>true],
        ['id'=>'country','type'=>'text','label'=>'Country','required'=>false],
        ['id'=>'timezone','type'=>'timezone','label'=>'Time zone','required'=>true],
    ]]]];
    $json=json_encode($legacy,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    $db->prepare('INSERT INTO content_documents(draft_json,published_json,updated_at) VALUES(?,?,?)')->execute([$json,$json,gmdate('c')]);
    (require dirname(__DIR__).'/migrations/010_form_field_audiences_and_city.php')($db);
    $columns=array_column($db->query('PRAGMA table_info(interest_submissions)')->fetchAll(),'name');
    assert_migration(in_array('city',$columns,true),'City column was not added.');
    $document=json_decode((string)$db->query('SELECT draft_json FROM content_documents')->fetchColumn(),true,512,JSON_THROW_ON_ERROR);
    $fields=array_column($document['forms']['interest-form']['fields'],null,'id');
    assert_migration(($fields['name']['audience']??null)==='both','Common field audience was not added.');
    assert_migration(($fields['country']['audience']??null)==='en','Country audience is incorrect.');
    assert_migration(($fields['timezone']['audience']??null)==='en','Timezone audience is incorrect.');
    assert_migration(($fields['city']['audience']??null)==='pt-BR','City field was not added for Portuguese.');
    assert_migration(count(array_filter($document['forms']['interest-form']['fields'],fn(array $field)=>($field['id']??'')==='city'))===1,'City was added more than once.');
    echo "Form audience migration test passed.\n";
}finally{
    unset($db);
    if(is_file($path))unlink($path);
}
