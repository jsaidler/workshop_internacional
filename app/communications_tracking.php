<?php
declare(strict_types=1);

function communication_tracking_token(): string {
    return hash_hmac('sha256','workshop-communications-webhook-v1',(string)app_config()['app_secret']);
}
function communication_tracking_authorized(string $authorization): bool {
    return preg_match('/^Bearer\s+(.+)$/i',trim($authorization),$match)===1
        && hash_equals(communication_tracking_token(),trim((string)$match[1]));
}
function communication_tracking_status(PDO $db): array {
    $row=$db->query('SELECT provider_webhook_id,configured_at,last_event_at,last_error FROM communication_webhook_settings WHERE id=1')
      ->fetch(PDO::FETCH_ASSOC);
    return $row?:['provider_webhook_id'=>null,'configured_at'=>null,'last_event_at'=>null,'last_error'=>null];
}
function communication_tracking_request(string $method,string $url,string $apiKey,array $body): array {
    if(!function_exists('curl_init'))throw new RuntimeException('A extensão PHP cURL é necessária para configurar o acompanhamento.');
    $request=curl_init($url);
    curl_setopt_array($request,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>18,CURLOPT_CONNECTTIMEOUT=>5,
        CURLOPT_HTTPHEADER=>['api-key: '.$apiKey,'Content-Type: application/json','accept: application/json'],
        CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
    $raw=curl_exec($request);$http=(int)curl_getinfo($request,CURLINFO_HTTP_CODE);curl_close($request);
    if($raw===false||$http<200||$http>=300)throw new RuntimeException('O Brevo não aceitou a configuração do acompanhamento.');
    $decoded=json_decode((string)$raw,true);
    return is_array($decoded)?$decoded:[];
}
function communication_tracking_configure(PDO $db,?callable $requester=null): int {
    $site=communication_site_url($db);
    if($site==='')throw new RuntimeException('Defina o endereço HTTPS do site antes de ativar o acompanhamento.');
    $provider=communication_provider_configuration($db);
    $status=communication_tracking_status($db);
    $payload=[
        'description'=>'Workshop CMS — acompanhamento transacional',
        'url'=>$site.'/communications-webhook.php',
        'events'=>['delivered','hardBounce','softBounce','blocked','spam','invalid','error','unsubscribed'],
        'type'=>'transactional','channel'=>'email','batched'=>false,
        'auth'=>['type'=>'bearer','token'=>communication_tracking_token()],
    ];
    $id=(int)($status['provider_webhook_id']??0);
    $method=$id>0?'PUT':'POST';
    $url='https://api.brevo.com/v3/webhooks'.($id>0?'/'.$id:'');
    try{
        $result=$requester?$requester($method,$url,(string)$provider['key'],$payload):communication_tracking_request($method,$url,(string)$provider['key'],$payload);
    }catch(Throwable $e){
        $db->prepare('INSERT INTO communication_webhook_settings(id,last_error) VALUES(1,?) ON CONFLICT(id) DO UPDATE SET last_error=excluded.last_error')
          ->execute(['O provedor não aceitou a configuração.']);
        throw $e;
    }
    if($id<1)$id=(int)($result['id']??0);
    if($id<1)throw new RuntimeException('O Brevo não retornou a identificação do acompanhamento.');
    $db->prepare('INSERT INTO communication_webhook_settings(id,provider_webhook_id,configured_at,last_error) VALUES(1,?,?,NULL)
        ON CONFLICT(id) DO UPDATE SET provider_webhook_id=excluded.provider_webhook_id,configured_at=excluded.configured_at,last_error=NULL')
      ->execute([$id,utc_now()]);
    return $id;
}
function communication_tracking_normalize_message_id(string $value): string {
    return trim(trim($value),"<> \t\r\n");
}
function communication_tracking_event_name(string $event): ?string {
    return match(strtolower(trim($event))){
        'delivered'=>'delivered','hard_bounce','hardbounce'=>'hard_bounce',
        'soft_bounce','softbounce'=>'soft_bounce','blocked'=>'blocked','spam'=>'spam',
        'invalid','invalid_email'=>'invalid_email','error'=>'error','unsubscribed'=>'unsubscribed',default=>null,
    };
}
function communication_tracking_match(PDO $db,string $messageId,string $email): ?array {
    $email=strtolower(trim($email));$needle=communication_tracking_normalize_message_id($messageId);
    $sources=[
        ['channel'=>'campaign','sql'=>"SELECT d.id,d.provider_id FROM communication_deliveries d JOIN student_users u ON u.id=d.student_id WHERE lower(u.email)=? AND d.provider_id IS NOT NULL ORDER BY d.id DESC LIMIT 30"],
        ['channel'=>'admin_notice','sql'=>"SELECT n.id,n.provider_id FROM admin_student_notice_outbox n JOIN admin_users a ON a.id=n.admin_id WHERE lower(a.email)=? AND n.provider_id IS NOT NULL ORDER BY n.id DESC LIMIT 30"],
    ];
    foreach($sources as $source){
        $q=$db->prepare($source['sql']);$q->execute([$email]);
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
            if(hash_equals($needle,communication_tracking_normalize_message_id((string)$row['provider_id'])))
                return ['channel'=>$source['channel'],'delivery_id'=>(int)$row['id']];
        }
    }
    return null;
}
function communication_tracking_accept(PDO $db,array $payload): bool {
    $event=communication_tracking_event_name((string)($payload['event']??''));
    $messageId=(string)($payload['message-id']??$payload['messageId']??'');
    $email=(string)($payload['email']??'');
    if($event===null||$messageId===''||!filter_var($email,FILTER_VALIDATE_EMAIL))return false;
    $match=communication_tracking_match($db,$messageId,$email);
    if(!$match)return false;
    $epoch=(int)($payload['ts_event']??$payload['ts']??0);
    $happened=$epoch>0?gmdate('Y-m-d\TH:i:s\Z',$epoch):utc_now();
    $reason=trim((string)($payload['reason']??''));
    $eventKey=hash('sha256',$match['channel'].'|'.$match['delivery_id'].'|'.$event.'|'.$happened.'|'.communication_tracking_normalize_message_id($messageId));
    course_agenda_with_lock($db,static function()use($db,$match,$eventKey,$event,$messageId,$happened,$reason,$email):void{
        $q=$db->prepare('INSERT OR IGNORE INTO communication_delivery_events(channel,delivery_id,event_key,provider_event,provider_message_id,happened_at,reason,created_at) VALUES(?,?,?,?,?,?,?,?)');
        $q->execute([$match['channel'],$match['delivery_id'],$eventKey,$event,communication_tracking_normalize_message_id($messageId),$happened,$reason===''?null:mb_substr($reason,0,240),utc_now()]);
        $inserted=$q->rowCount()===1;
        if($inserted && $match['channel']==='campaign' && in_array($event,['hard_bounce','blocked','spam','invalid_email'],true)){
            $student=$db->prepare('SELECT student_id FROM communication_deliveries WHERE id=?');
            $student->execute([$match['delivery_id']]);$studentId=(int)$student->fetchColumn();
            if($studentId>0)$db->prepare('INSERT INTO communication_email_suppressions(student_id,email,reason,provider_message_id,created_at,updated_at) VALUES(?,?,?,?,?,?)
                ON CONFLICT(student_id,email) DO UPDATE SET reason=excluded.reason,provider_message_id=excluded.provider_message_id,updated_at=excluded.updated_at')
              ->execute([$studentId,strtolower(trim($email)),$event,communication_tracking_normalize_message_id($messageId),utc_now(),utc_now()]);
            if($studentId>0 && $event==='spam')communication_set_opt_in($db,$studentId,false,'provider_spam');
        }
        if($inserted && $match['channel']==='campaign' && $event==='unsubscribed'){
            $student=$db->prepare('SELECT student_id FROM communication_deliveries WHERE id=?');
            $student->execute([$match['delivery_id']]);$studentId=(int)$student->fetchColumn();
            if($studentId>0)communication_set_opt_in($db,$studentId,false,'provider_unsubscribe');
        }
        $db->prepare('INSERT INTO communication_webhook_settings(id,last_event_at,last_error) VALUES(1,?,NULL)
            ON CONFLICT(id) DO UPDATE SET last_event_at=excluded.last_event_at,last_error=NULL')->execute([utc_now()]);
    });
    return true;
}
