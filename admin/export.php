<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_admin();
$id=(int)($_GET['activity']??0);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="workshop-interest-submissions.csv"');
echo "\xEF\xBB\xBF";
$db=database();
$document=$id?content_for_public($db,$id):canonical_content();
$fields=array_column($document['forms']['interest-form']['fields']??[],'id');
$out=fopen('php://output','w');
fputcsv($out,array_merge(['id','activity','slug','created_at','workshop_locale','name','email','country','city'],$fields));
$q=$db->prepare('SELECT i.*,a.admin_name,a.slug FROM interest_submissions i LEFT JOIN activities a ON a.id=i.activity_id'.($id?' WHERE i.activity_id=?':'').' ORDER BY i.id DESC');
$q->execute($id?[$id]:[]);
foreach($q as $row){
    $payload=json_decode($row['payload_json'],true)?:[];
    $line=[$row['id'],$row['admin_name'],$row['slug'],$row['created_at'],$row['workshop_locale']??'en',$row['name'],$row['email'],$row['country'],$row['city']??''];
    foreach($fields as $field)$line[]=is_array($payload[$field]??null)?implode(', ',$payload[$field]):($payload[$field]??'');
    fputcsv($out,$line);
}
