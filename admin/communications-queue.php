<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
require_admin();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');

if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok'=>false,'error'=>'Operação não permitida.']);exit;
}
if(!verify_csrf('communications-admin',$_POST['_csrf']??null)){
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Sessão expirada. Atualize a página e tente novamente.']);exit;
}
try{
    $db=database();
    $state=admin_activity_resolution($db);
    if(empty($state['activity']))throw new RuntimeException('Área administrativa indisponível.');
    $activityId=(int)$state['activity']['id'];
    $action=(string)($_POST['action']??'');
    if($action==='run_queue'){
        $outcome=communication_dispatch_manual($db,$activityId,5);
        $response=['ok'=>true,'result'=>$outcome,'queue'=>communication_queue_summary($db,$activityId)];
    }elseif($action==='cancel_queue'){
        $cancelled=communication_cancel_queue($db,$activityId);
        $response=['ok'=>true,'cancelled'=>$cancelled,'queue'=>communication_queue_summary($db,$activityId)];
    }else{
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Ação inválida.']);exit;
    }
    echo json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
    http_response_code(500);
    error_log('communications queue admin: '.get_class($e));
    echo json_encode(['ok'=>false,'error'=>'Não foi possível concluir a operação. Consulte o estado da fila e as configurações de envio.'],JSON_UNESCAPED_UNICODE);
}
