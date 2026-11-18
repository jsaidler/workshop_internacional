<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $columns=array_column($db->query('PRAGMA table_info(interest_submissions)')->fetchAll(),'name');
    if(!in_array('city',$columns,true))$db->exec("ALTER TABLE interest_submissions ADD COLUMN city TEXT NOT NULL DEFAULT ''");
    $upgrade=static function(?string $json): ?string {
        if($json===null||$json==='')return $json;
        $document=json_decode($json,true,512,JSON_THROW_ON_ERROR);$fields=$document['forms']['interest-form']['fields']??null;
        if(!is_array($fields))return $json;
        $hasCity=(bool)array_filter($fields,fn(array $field)=>($field['id']??'')==='city');$next=[];
        foreach($fields as $field){
            $id=$field['id']??'';$field['audience']=match($id){'country','timezone'=>'en','city'=>'pt-BR',default=>$field['audience']??'both'};
            $next[]=$field;
            if($id==='country'&&!$hasCity)$next[]=['id'=>'city','type'=>'text','label'=>'City','required'=>true,'audience'=>'pt-BR','autocomplete'=>'address-level2'];
        }
        if(!$hasCity&&!array_filter($next,fn(array $field)=>($field['id']??'')==='city'))$next[]=['id'=>'city','type'=>'text','label'=>'City','required'=>true,'audience'=>'pt-BR','autocomplete'=>'address-level2'];
        $document['forms']['interest-form']['fields']=$next;
        return json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    };
    $select=$db->query('SELECT id,draft_json,published_json FROM content_documents');$update=$db->prepare('UPDATE content_documents SET draft_json=?,published_json=?,updated_at=? WHERE id=?');
    foreach($select as $row)$update->execute([$upgrade($row['draft_json']),$upgrade($row['published_json']),gmdate('c'),$row['id']]);
};
