<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){http_response_code(403);echo json_encode(['error'=>'Acesso indisponível.']);exit;}
try{
    $category=(string)($_GET['category']??'');
    $course=max(0,(int)($_GET['course_id']??0));
    $cohort=max(0,(int)($_GET['cohort_id']??0));
    $rows=communication_audience($db,(int)$activity['id'],$category,$course,$cohort);
    echo json_encode(['recipients'=>array_map(static fn(array $r):array=>[
        'id'=>(int)$r['id'],'name'=>(string)$r['name'],'email'=>(string)$r['email']
    ],$rows)],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){
    http_response_code(422);
    echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
