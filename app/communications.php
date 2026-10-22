<?php
declare(strict_types=1);

/**
 * Communication identity belongs to student_users and Course, never to CMS
 * pages or past form submissions. No retroactive newsletter opt-in.
 */
function communication_opted_in(PDO $db,int $studentId): bool {
    $q=$db->prepare('SELECT newsletter_opt_in FROM communication_preferences WHERE student_id=?');
    $q->execute([$studentId]);return (int)$q->fetchColumn()===1;
}
function communication_set_opt_in(PDO $db,int $studentId,bool $allow,string $source='profile'): void {
    $q=$db->prepare("SELECT id FROM student_users WHERE id=? AND status='active'");
    $q->execute([$studentId]);if(!$q->fetchColumn())throw new RuntimeException('Conta inválida.');
    $now=utc_now();
    $db->prepare('INSERT INTO communication_preferences(student_id,newsletter_opt_in,changed_at,source) VALUES(?,?,?,?) ON CONFLICT(student_id) DO UPDATE SET newsletter_opt_in=excluded.newsletter_opt_in,changed_at=excluded.changed_at,source=excluded.source')
       ->execute([$studentId,$allow?1:0,$now,$source]);
}
function communication_unsubscribe_token(int $studentId,string $email): string {
    $secret=(string)app_config()['app_secret'];
    $digest=hash_hmac('sha256','newsletter|'.$studentId.'|'.strtolower(trim($email)),$secret);
    return $studentId.'.'.$digest;
}
function communication_unsubscribe_resolve(PDO $db,string $token): ?array {
    if(!preg_match('/^([1-9][0-9]{0,11})\.([a-f0-9]{64})$/D',$token,$m))return null;
    $q=$db->prepare("SELECT id,email FROM student_users WHERE id=? AND status='active'");
    $q->execute([(int)$m[1]]);$row=$q->fetch();
    if(!$row || !hash_equals(communication_unsubscribe_token((int)$row['id'],(string)$row['email']),$token))return null;
    return $row;
}
function communication_campaigns(PDO $db,int $activityId): array {
    $q=$db->prepare('SELECT campaign.*,c.title course_title,cc.title cohort_title,
    (SELECT COUNT(*) FROM communication_deliveries d WHERE d.campaign_id=campaign.id) audience_count,
    (SELECT COUNT(*) FROM communication_deliveries d WHERE d.campaign_id=campaign.id AND d.status=\'sent\') sent_count,
    (SELECT COUNT(*) FROM communication_deliveries d WHERE d.campaign_id=campaign.id AND d.error_code=\'cancelled_by_admin\') cancelled_count,
    (SELECT COUNT(*) FROM communication_deliveries d WHERE d.campaign_id=campaign.id AND d.status=\'failed\') failed_count,
    (SELECT COUNT(*) FROM communication_deliveries d WHERE d.campaign_id=campaign.id AND d.status=\'pending\') pending_count
    FROM communication_campaigns campaign LEFT JOIN courses c ON c.id=campaign.course_id
    LEFT JOIN course_cohorts cc ON cc.id=campaign.cohort_id
    WHERE campaign.activity_id=? ORDER BY campaign.id DESC LIMIT 80');
    $q->execute([$activityId]);return $q->fetchAll();
}
/** Status per recipient for the selected campaign. Never reveal this outside admin. */
function communication_campaign_deliveries(PDO $db,int $activityId,int $campaignId): array {
    $q=$db->prepare("SELECT d.id,d.status,d.error_code,d.attempts,d.updated_at,u.name,u.email
        FROM communication_deliveries d
        JOIN communication_campaigns c ON c.id=d.campaign_id
        JOIN student_users u ON u.id=d.student_id
        WHERE c.activity_id=? AND c.id=?
        ORDER BY d.id LIMIT 300");
    $q->execute([$activityId,$campaignId]);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_campaign(PDO $db,int $activityId,int $campaignId): ?array {
    $q=$db->prepare('SELECT * FROM communication_campaigns WHERE id=? AND activity_id=?');
    $q->execute([$campaignId,$activityId]);return $q->fetch()?:null;
}
function communication_assert_scope(PDO $db,int $activityId,int $courseId,int $cohortId,string $category): void {
    if(!in_array($category,['operational','newsletter'],true))throw new RuntimeException('Categoria inválida.');
    if($category==='operational' && $courseId<1)throw new RuntimeException('Avisos acadêmicos exigem um curso.');
    if($cohortId>0 && $courseId<1)throw new RuntimeException('Escolha o curso da turma.');
    if($courseId>0){
        $q=$db->prepare("SELECT 1 FROM courses WHERE id=? AND activity_id=? AND status!='archived'");
        $q->execute([$courseId,$activityId]);if(!$q->fetchColumn())throw new RuntimeException('Curso fora desta área.');
    }
    if($cohortId>0){
        $q=$db->prepare("SELECT 1 FROM course_cohorts WHERE id=? AND course_id=? AND status!='archived'");
        $q->execute([$cohortId,$courseId]);if(!$q->fetchColumn())throw new RuntimeException('Turma inválida neste curso.');
    }
}
function communication_audience(PDO $db,int $activityId,string $category,int $courseId,int $cohortId): array {
    communication_assert_scope($db,$activityId,$courseId,$cohortId,$category);
    // One row per person; confirmed listeners of the selected cohort count
    // only while also holding a legitimate enrollment in that same course.
    $sql="SELECT u.id,u.email,u.name FROM student_users u WHERE u.status='active'
      AND (EXISTS (
        SELECT 1 FROM course_enrollments e
        JOIN course_cohorts cc ON cc.id=e.cohort_id
        JOIN courses c ON c.id=cc.course_id
        WHERE e.student_id=u.id AND e.status='active'
          AND cc.status!='archived' AND c.status!='archived'
          AND c.activity_id=? ";
    $args=[$activityId];
    if($courseId>0){$sql.=' AND c.id=?';$args[]=$courseId;}
    if($cohortId>0){$sql.=' AND cc.id=?';$args[]=$cohortId;}
    $sql.=" )";
    if($cohortId>0){
        $sql.=" OR EXISTS (
          SELECT 1 FROM course_listener_reservations lr
          JOIN course_cohorts cc ON cc.id=lr.cohort_id
          WHERE lr.student_id=u.id AND lr.cohort_id=? AND lr.status='confirmed'
          AND cc.course_id=? AND cc.status!='archived'
          AND EXISTS(SELECT 1 FROM course_enrollments origin
            JOIN course_cohorts home ON home.id=origin.cohort_id
            WHERE origin.student_id=u.id AND origin.status='active'
              AND home.course_id=cc.course_id AND home.id!=cc.id)
        )";
        array_push($args,$cohortId,$courseId);
    }
    $sql.=')';
    if($category==='newsletter')$sql.=" AND EXISTS(SELECT 1 FROM communication_preferences p WHERE p.student_id=u.id AND p.newsletter_opt_in=1)";
    $sql.=' ORDER BY u.id';
    $q=$db->prepare($sql);$q->execute($args);
    return array_values(array_filter($q->fetchAll(),static fn(array $r):bool=>filter_var($r['email'],FILTER_VALIDATE_EMAIL)!==false));
}
function communication_campaign_target_ids(PDO $db,int $campaignId): array {
    $q=$db->prepare('SELECT student_id FROM communication_campaign_targets WHERE campaign_id=? ORDER BY student_id');
    $q->execute([$campaignId]);return array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
}
function communication_requested_ids(mixed $raw): array {
    if(!is_array($raw)||count($raw)>5000)throw new RuntimeException('Seleção de destinatários inválida.');
    $ids=[];
    foreach($raw as $value){
        if(!is_scalar($value)||!preg_match('/^[1-9][0-9]{0,11}$/D',(string)$value))
            throw new RuntimeException('Há destinatários inválidos na seleção.');
        $ids[(int)$value]=true;
    }
    return array_keys($ids);
}
function communication_validate_targets(PDO $db,int $activityId,array $input): array {
    $ids=communication_requested_ids($input['recipient_ids']??[]);
    $audience=communication_audience($db,$activityId,(string)$input['category'],(int)$input['course_id'],(int)$input['cohort_id']);
    $eligible=array_fill_keys(array_map('intval',array_column($audience,'id')),true);
    foreach($ids as $id)if(!isset($eligible[$id]))
        throw new RuntimeException('A lista mudou. Atualize os destinatários antes de salvar.');
    return $ids;
}
function communication_campaign_audience(PDO $db,int $activityId,array $campaign): array {
    $audience=communication_audience($db,$activityId,(string)$campaign['category'],(int)$campaign['course_id'],(int)$campaign['cohort_id']);
    if(($campaign['selection_mode']??'all')!=='selected')return $audience;
    $ids=array_fill_keys(communication_campaign_target_ids($db,(int)($campaign['campaign_id']??$campaign['id'])),true);
    return array_values(array_filter($audience,static fn(array $r):bool=>isset($ids[(int)$r['id']])));
}
function communication_save_campaign_targets(PDO $db,int $campaignId,array $ids): void {
    $db->prepare('DELETE FROM communication_campaign_targets WHERE campaign_id=?')->execute([$campaignId]);
    $insert=$db->prepare('INSERT INTO communication_campaign_targets(campaign_id,student_id) VALUES(?,?)');
    foreach($ids as $id)$insert->execute([$campaignId,$id]);
}
function communication_campaign_body(array $input): array {
    $html=communication_safe_html((string)($input['body_html']??''));
    $plain=$html!==''?communication_html_text($html):trim((string)($input['body_text']??''));
    if($plain===''||mb_strlen($plain)>20000)throw new RuntimeException('Escreva uma mensagem de até 20 mil caracteres.');
    return ['body_text'=>$plain,'body_html'=>$html];
}
function communication_create_campaign(PDO $db,int $activityId,array $input): int {
    $category=(string)($input['category']??'');
    $courseId=max(0,(int)($input['course_id']??0));
    $cohortId=max(0,(int)($input['cohort_id']??0));
    communication_assert_scope($db,$activityId,$courseId,$cohortId,$category);
    $subject=trim((string)($input['subject']??''));
    if($subject===''||mb_strlen($subject)>180)throw new RuntimeException('Informe um assunto com até 180 caracteres.');
    $body=communication_campaign_body($input);
    communication_validate_images($db,$body['body_html']);
    $template=communication_template_key((string)($input['template_key']??'minimal'));
    $individual=!empty($input['selection_explicit']);
    $targets=$individual?communication_validate_targets($db,$activityId,$input):[];
    return course_agenda_with_lock($db,static function()use($db,$activityId,$category,$courseId,$cohortId,$subject,$body,$individual,$targets,$template):int{
        $now=utc_now();
        $db->prepare("INSERT INTO communication_campaigns(activity_id,category,course_id,cohort_id,subject,body_text,body_html,selection_mode,template_key,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,'draft',?,?)")
           ->execute([$activityId,$category,$courseId?:null,$cohortId?:null,$subject,$body['body_text'],$body['body_html'],$individual?'selected':'all',$template,$now,$now]);
        $id=(int)$db->lastInsertId();
        if($individual)communication_save_campaign_targets($db,$id,$targets);
        return $id;
    });
}

function communication_queue_campaign(PDO $db,int $activityId,int $id): int {
    return course_agenda_with_lock($db,static function() use($db,$activityId,$id): int {
        $campaign=communication_campaign($db,$activityId,$id);
        if(!$campaign || $campaign['status']!=='draft')throw new RuntimeException('Campanha não disponível para envio.');
        communication_validate_images($db,(string)($campaign['body_html']??''));
        if(str_contains((string)($campaign['body_html']??''),'data-email-media-id')&&communication_site_url($db)==='')
            throw new RuntimeException('Defina o endereço HTTPS do site antes de enviar imagens.');
        $audience=communication_campaign_audience($db,$activityId,$campaign);
        if(!$audience)throw new RuntimeException('Nenhum destinatário elegível. Não foi criado envio.');
        $now=utc_now();
        $q=$db->prepare("INSERT OR IGNORE INTO communication_deliveries(campaign_id,student_id,status,created_at,updated_at) VALUES(?,?,'pending',?,?)");
        foreach($audience as $person)$q->execute([$id,(int)$person['id'],$now,$now]);
        $db->prepare("UPDATE communication_campaigns SET status='queued',queued_at=?,updated_at=? WHERE id=? AND status='draft'")
          ->execute([$now,$now,$id]);
        return count($audience);
    });
}
/**
 * Email transport settings belong to the CMS; the password/API key does not.
 * The sender is optional for PHP mail(), since installations may already
 * deliver form notifications with their server's default sender.
 */
function communication_sender_settings(PDO $db): array {
    $saved=['sender_name'=>'','sender_email'=>''];
    $hasTable=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_sender_settings'")->fetchColumn();
    if($hasTable){
        $row=$db->query('SELECT sender_name,sender_email FROM communication_sender_settings WHERE id=1')->fetch(PDO::FETCH_ASSOC);
        if(is_array($row))$saved=$row;
    }
    $cfg=app_config();
    $sender=trim((string)($saved['sender_email']??''));
    if($sender==='')$sender=trim((string)($cfg['email_from']??getenv('BREVO_FROM_EMAIL')?:''));
    return ['sender_name'=>trim((string)($saved['sender_name']??''))?:((string)($cfg['site_name']??'Fotografia')),
        'sender_email'=>$sender,'custom_sender_email'=>trim((string)($saved['sender_email']??''))];
}
function communication_sender_save(PDO $db,array $input): void {
    $name=trim((string)($input['sender_name']??''));
    $email=trim((string)($input['sender_email']??''));
    if(mb_strlen($name)>100||strpbrk($name,"\r\n")!==false)
        throw new RuntimeException('O nome do remetente deve ter até 100 caracteres, sem quebras de linha.');
    if($email!==''&&(!filter_var($email,FILTER_VALIDATE_EMAIL)||strpbrk($email,"\r\n")!==false))
        throw new RuntimeException('Informe um endereço de e-mail válido para o remetente.');
    $db->prepare('INSERT INTO communication_sender_settings(id,sender_name,sender_email,updated_at) VALUES(1,?,?,?) ON CONFLICT(id) DO UPDATE SET sender_name=excluded.sender_name,sender_email=excluded.sender_email,updated_at=excluded.updated_at')
       ->execute([$name,$email,utc_now()]);
}
function communication_mail_status(PDO $db): array {
    $settings=communication_sender_settings($db);
    $cfg=app_config();
    $brevo=communication_brevo_public_status($db);
    $brevoReady=$brevo['configured'];
    $senderValid=filter_var($settings['sender_email'],FILTER_VALIDATE_EMAIL)!==false;
    return ['transport'=>$brevoReady?'brevo':'php','sender'=>$settings['sender_email'],
        'sender_name'=>$settings['sender_name'],
        'transport_label'=>$brevoReady?($senderValid?'Brevo':'Brevo — configure o remetente'):'E-mail da hospedagem',
        'sender_configured'=>$senderValid,
        'server_available'=>function_exists('mail')];
}
function communication_send_php(PDO $db,array $payload): array {
    $recipient=trim((string)($payload['email']??''));
    if(!filter_var($recipient,FILTER_VALIDATE_EMAIL)||strpbrk($recipient,"\r\n")!==false)
        throw new RuntimeException('Destinatário inválido.');
    $subject=trim((string)($payload['subject']??''));
    if($subject===''||strpbrk($subject,"\r\n")!==false)throw new RuntimeException('Assunto inválido.');
    if(!function_exists('mail'))throw new RuntimeException('O servidor não disponibiliza envio de e-mail. Verifique o serviço da hospedagem.');
    $settings=communication_sender_settings($db);
    $headers=['MIME-Version: 1.0'];
    if(filter_var($settings['sender_email'],FILTER_VALIDATE_EMAIL))
        $headers[]='From: '.$settings['sender_email'];
    $encoded=mb_encode_mimeheader($subject,'UTF-8','B',"\r\n");
    $html=trim((string)($payload['html']??''));
    $body=(string)($payload['body']??'');
    if($html!==''){
        $boundary='alt_'.bin2hex(random_bytes(16));
        $headers[]='Content-Type: multipart/alternative; boundary="'.$boundary.'"';
        $plain=base64_encode($body);$rich=base64_encode(communication_email_document($html,$body,$db,(string)($payload['template']??'minimal'),(int)($payload['activity_id']??0)));
        $body='--'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split($plain,76,"\r\n")
            .'--'.$boundary."\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split($rich,76,"\r\n")
            .'--'.$boundary."--\r\n";
    }else $headers[]='Content-Type: text/plain; charset=UTF-8';
    $accepted=@mail($recipient,$encoded,$body,implode("\r\n",$headers));
    if(!$accepted)throw new RuntimeException('O servidor não aceitou o envio. Verifique o e-mail do remetente e as configurações da hospedagem.');
    return ['id'=>'php-mail-accepted'];
}
function communication_send_message(PDO $db,array $payload): array {
    $status=communication_mail_status($db);
    return $status['transport']==='brevo'?communication_send_brevo($payload,$db):communication_send_php($db,$payload);
}
function communication_worker_status(PDO $db): array {
    $q=$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_worker_state'");
    if(!$q->fetchColumn())return ['last_started_at'=>null,'last_finished_at'=>null,'last_error'=>null];
    $row=$db->query('SELECT last_started_at,last_finished_at,last_error FROM communication_worker_state WHERE id=1')->fetch(PDO::FETCH_ASSOC);
    return $row?:['last_started_at'=>null,'last_finished_at'=>null,'last_error'=>null];
}
function communication_worker_record(PDO $db,bool $started,string $error=''): void {
    $field=$started?'last_started_at':'last_finished_at';
    $now=utc_now();
    if($started){
        $db->prepare("INSERT INTO communication_worker_state(id,last_started_at,last_error) VALUES(1,?,NULL)
            ON CONFLICT(id) DO UPDATE SET last_started_at=excluded.last_started_at,last_error=NULL")->execute([$now]);
    }else{
        $db->prepare("INSERT INTO communication_worker_state(id,last_finished_at,last_error) VALUES(1,?,?)
            ON CONFLICT(id) DO UPDATE SET last_finished_at=excluded.last_finished_at,last_error=excluded.last_error")->execute([$now,$error===''?null:mb_substr($error,0,250)]);
    }
}

function communication_provider_configuration(?PDO $db=null): array {
    $config=app_config();
    $key=$db?communication_brevo_key($db):trim((string)($config['brevo_api_key']??getenv('BREVO_API_KEY')?:''));
    $sender=$db?communication_sender_settings($db):null;
    $from=$sender!==null?$sender['sender_email']:trim((string)($config['email_from']??getenv('BREVO_FROM_EMAIL')?:''));
    if($key===''||!filter_var($from,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Configure um remetente válido em Envio de e-mails.');
    return ['key'=>$key,'from'=>$from,'name'=>$sender!==null?$sender['sender_name']:(string)($config['site_name']??'Fotografia')];
}
function communication_send_brevo(array $payload,?PDO $db=null): array {
    $cfg=communication_provider_configuration($db);
    if(!function_exists('curl_init'))throw new RuntimeException('A extensão PHP cURL é necessária para enviar e-mails.');
    $body=['sender'=>['name'=>$cfg['name'],'email'=>$cfg['from']],
      'to'=>[['name'=>$payload['name'],'email'=>$payload['email']]],
      'subject'=>$payload['subject'],'textContent'=>$payload['body']];
    if(!empty($payload['html']))$body['htmlContent']=communication_email_document((string)$payload['html'],(string)$payload['body'],$db,(string)($payload['template']??'minimal'),(int)($payload['activity_id']??0));
    $request=curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($request,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,
      CURLOPT_TIMEOUT=>18,CURLOPT_CONNECTTIMEOUT=>5,
      CURLOPT_HTTPHEADER=>['api-key: '.$cfg['key'],'Content-Type: application/json','accept: application/json'],
      CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    $raw=curl_exec($request);$http=(int)curl_getinfo($request,CURLINFO_HTTP_CODE);
    curl_close($request);
    if($raw===false||$http<200||$http>=300)throw new RuntimeException('Falha na entrega pelo provedor (HTTP '.$http.').');
    $result=json_decode((string)$raw,true);
    return ['id'=>is_array($result)?(string)($result['messageId']??''):''];
}
/** Claim jobs before any network request; recover abandoned processing jobs. */
function communication_dispatch(PDO $db,int $limit=30,?callable $sender=null,?int $activityId=null): array {
    $limit=max(1,min(50,$limit));
    if($sender===null)$sender=static fn(array $payload):array=>communication_send_message($db,$payload);
    // Brevo Free currently allows 300/day. Default to 250 so retries and
    // other occasional sends do not unexpectedly hit the provider queue.
    $dailyLimit=max(1,min(100000,(int)(app_config()['email_daily_limit']??250)));
    $from=gmdate('Y-m-d').'T00:00:00Z';
    $daily=$db->prepare("SELECT COUNT(*) FROM communication_deliveries WHERE status='sent' AND delivered_at>=?");
    $daily->execute([$from]);
    $limit=min($limit,max(0,$dailyLimit-(int)$daily->fetchColumn()));
    if($limit===0)return ['sent'=>0,'skipped'=>0,'failed'=>0];
    $allowNewsletter=communication_site_url($db)!=='';
    $jobs=course_agenda_with_lock($db,static function() use($db,$limit,$allowNewsletter,$activityId): array {
        $stale=gmdate('Y-m-d\TH:i:s\Z',time()-1800);
        // The provider may have accepted a message before the worker crashed.
        // Never retry uncertain sends automatically: an operator must investigate.
        $db->prepare("UPDATE communication_deliveries SET status='failed',error_code='processing_outcome_unknown',updated_at=? WHERE status='processing' AND updated_at<?")->execute([utc_now(),$stale]);
        $q=$db->prepare("SELECT d.id,d.campaign_id,d.student_id,c.activity_id,c.category,c.course_id,c.cohort_id,c.subject,c.body_text,c.body_html,c.template_key,c.selection_mode,u.name,u.email
          FROM communication_deliveries d JOIN communication_campaigns c ON c.id=d.campaign_id
          JOIN student_users u ON u.id=d.student_id WHERE d.status='pending' AND d.attempts<3
          AND c.status='queued' ".($allowNewsletter?'':"AND c.category!='newsletter' ")
          .($activityId!==null?"AND c.activity_id=? ":"")."ORDER BY d.id LIMIT ?");
        if($activityId!==null)$q->bindValue(1,$activityId,PDO::PARAM_INT);
        $q->bindValue($activityId!==null?2:1,$limit,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll();
        $mark=$db->prepare("UPDATE communication_deliveries SET status='processing',attempts=attempts+1,updated_at=? WHERE id=? AND status='pending'");
        foreach($rows as $row)$mark->execute([utc_now(),(int)$row['id']]);
        return $rows;
    });
    $result=['sent'=>0,'skipped'=>0,'failed'=>0];
    $audiences=[];
    foreach($jobs as $job){
        $id=(int)$job['id'];$campaign=(int)$job['campaign_id'];
        if(!isset($audiences[$campaign])){
            try{
                $audiences[$campaign]=array_flip(array_map('intval',array_column(
                    communication_campaign_audience($db,(int)$job['activity_id'],$job),'id'
                )));
            }catch(Throwable $e){
                $db->prepare("UPDATE communication_deliveries SET status='failed',error_code='audience_validation_error',updated_at=? WHERE id=? AND status='processing'")->execute([utc_now(),$id]);
                $result['failed']++;continue;
            }
        }
        if(!isset($audiences[$campaign][(int)$job['student_id']])){
            $db->prepare("UPDATE communication_deliveries SET status='skipped',error_code='no_longer_eligible',updated_at=? WHERE id=?")->execute([utc_now(),$id]);
            $result['skipped']++;continue;
        }
        $body=(string)$job['body_text'];
        $html=(string)($job['body_html']??'');
        try{
            communication_validate_images($db,$html);
        }catch(Throwable $e){
            $db->prepare("UPDATE communication_deliveries SET status='failed',error_code='media_unavailable',updated_at=? WHERE id=? AND status='processing'")->execute([utc_now(),$id]);
            $result['failed']++;continue;
        }
        if($job['category']==='newsletter'){
            $site=communication_site_url($db);
            if($site==='')throw new RuntimeException('Defina o endereço do site em Comunicações → Configuração antes de enviar newsletter.');
            $unsub=$site.'/newsletter-sair.php?token='.rawurlencode(communication_unsubscribe_token((int)$job['student_id'],(string)$job['email']));
            $body.="\n\nPara deixar de receber novidades: ".$unsub;
            if($html!=='')$html.='<p>Para deixar de receber novidades: <a href="'.htmlspecialchars($unsub,ENT_QUOTES,'UTF-8').'">Cancelar assinatura</a></p>';
        }
        try{
            $receipt=$sender(['name'=>(string)$job['name'],'email'=>(string)$job['email'],
                'subject'=>(string)$job['subject'],'body'=>$body,'html'=>$html,
                'template'=>(string)($job['template_key']??'minimal'),'activity_id'=>(int)$job['activity_id']]);
            $db->prepare("UPDATE communication_deliveries SET status='sent',provider_id=?,error_code=NULL,delivered_at=?,updated_at=? WHERE id=?")
              ->execute([substr((string)($receipt['id']??''),0,240),utc_now(),utc_now(),$id]);
            $result['sent']++;
        }catch(Throwable $e){
            // A network timeout can occur after acceptance: no blind retry.
            $db->prepare("UPDATE communication_deliveries SET status='failed',error_code='provider_failure',updated_at=? WHERE id=? AND status='processing'")
              ->execute([utc_now(),$id]);$result['failed']++;
        }
    }
    $db->exec("UPDATE communication_campaigns SET status='completed',updated_at='".utc_now()."' WHERE status='queued' AND NOT EXISTS(SELECT 1 FROM communication_deliveries d WHERE d.campaign_id=communication_campaigns.id AND d.status IN ('pending','processing'))");
    return $result;
}


/** Counts and mutations are limited to the selected CMS activity. */
function communication_queue_summary(PDO $db,int $activityId): array {
    $q=$db->prepare("SELECT
      COALESCE(SUM(CASE WHEN d.status='pending' AND c.status='queued' THEN 1 ELSE 0 END),0) pending,
      COALESCE(SUM(CASE WHEN d.status='processing' THEN 1 ELSE 0 END),0) processing,
      COALESCE(SUM(CASE WHEN d.status='sent' THEN 1 ELSE 0 END),0) accepted,
      COALESCE(SUM(CASE WHEN d.status='failed' THEN 1 ELSE 0 END),0) failed,
      COALESCE(SUM(CASE WHEN d.status='skipped' AND d.error_code='cancelled_by_admin' THEN 1 ELSE 0 END),0) cancelled
      FROM communication_deliveries d JOIN communication_campaigns c ON c.id=d.campaign_id
      WHERE c.activity_id=?");
    $q->execute([$activityId]);
    return array_map('intval',$q->fetch(PDO::FETCH_ASSOC)?:[
        'pending'=>0,'processing'=>0,'accepted'=>0,'failed'=>0,'cancelled'=>0
    ]);
}

/** Never revoke provider-accepted messages, only entries still pending. */
function communication_cancel_queue(PDO $db,int $activityId,?int $campaignId=null): int {
    return course_agenda_with_lock($db,static function()use($db,$activityId,$campaignId):int{
        $filter=$campaignId!==null?' AND c.id=?':'';
        $params=$campaignId!==null?[$activityId,$campaignId]:[$activityId];
        $q=$db->prepare("SELECT d.id FROM communication_deliveries d JOIN communication_campaigns c ON c.id=d.campaign_id
            WHERE c.activity_id=? AND c.status='queued' AND d.status='pending'".$filter);
        $q->execute($params);
        $ids=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
        if(!$ids)return 0;
        $update=$db->prepare("UPDATE communication_deliveries SET status='skipped',error_code='cancelled_by_admin',updated_at=?
            WHERE id=? AND status='pending'");
        $now=utc_now();$total=0;
        foreach($ids as $id){$update->execute([$now,$id]);$total+=$update->rowCount();}
        $db->prepare("UPDATE communication_campaigns SET status='completed',updated_at=?
            WHERE activity_id=? AND status='queued'
            AND NOT EXISTS(SELECT 1 FROM communication_deliveries d WHERE d.campaign_id=communication_campaigns.id
                AND d.status IN ('pending','processing'))")->execute([$now,$activityId]);
        return $total;
    });
}

/** Reuse the CLI worker's lock, but run safely from an administrator POST. */
function communication_dispatch_manual(PDO $db,int $activityId,int $limit=1,?callable $sender=null): array {
    $lock=@fopen(dirname(__DIR__).'/storage/communications-worker.lock','c+');
    if(!$lock)throw new RuntimeException('Não foi possível acessar o controle de envio. Verifique as permissões do servidor.');
    if(!flock($lock,LOCK_EX|LOCK_NB)){
        fclose($lock);
        return ['busy'=>true,'sent'=>0,'skipped'=>0,'failed'=>0];
    }
    try {
        return ['busy'=>false]+communication_dispatch($db,max(1,min(1,$limit)),$sender,$activityId);
    }finally {
        flock($lock,LOCK_UN);fclose($lock);
    }
}

function communication_update_campaign(PDO $db,int $activityId,int $id,array $input): int {
    if($id===0)return communication_create_campaign($db,$activityId,$input);
    $existing=communication_campaign($db,$activityId,$id);
    if(!$existing || $existing['status']!=='draft')throw new RuntimeException('Somente rascunhos podem ser editados.');
    $category=(string)($input['category']??'');
    $courseId=max(0,(int)($input['course_id']??0));
    $cohortId=max(0,(int)($input['cohort_id']??0));
    communication_assert_scope($db,$activityId,$courseId,$cohortId,$category);
    $subject=trim((string)($input['subject']??''));
    if($subject===''||mb_strlen($subject)>180)throw new RuntimeException('Informe um assunto com até 180 caracteres.');
    $body=communication_campaign_body($input);
    communication_validate_images($db,$body['body_html']);
    $template=communication_template_key((string)($input['template_key']??'minimal'));
    $individual=!empty($input['selection_explicit']);
    $targets=$individual?communication_validate_targets($db,$activityId,$input):[];
    return course_agenda_with_lock($db,static function()use($db,$id,$activityId,$category,$courseId,$cohortId,$subject,$body,$individual,$targets,$template):int{
        $db->prepare('UPDATE communication_campaigns SET category=?,course_id=?,cohort_id=?,subject=?,body_text=?,body_html=?,template_key=?,selection_mode=CASE WHEN ?=1 THEN \'selected\' ELSE selection_mode END,updated_at=? WHERE id=? AND activity_id=? AND status=\'draft\'')
           ->execute([$category,$courseId?:null,$cohortId?:null,$subject,$body['body_text'],$body['body_html'],$template,$individual?1:0,utc_now(),$id,$activityId]);
        if($individual)communication_save_campaign_targets($db,$id,$targets);
        return $id;
    });
}


function communication_duplicate_campaign(PDO $db,int $activityId,int $campaignId):int {
    $source=communication_campaign($db,$activityId,$campaignId);
    if(!$source)throw new RuntimeException('Mensagem não encontrada.');
    $audience=communication_campaign_audience($db,$activityId,$source);
    return communication_create_campaign($db,$activityId,[
        'category'=>(string)$source['category'],'course_id'=>(int)$source['course_id'],
        'cohort_id'=>(int)$source['cohort_id'],'subject'=>(string)$source['subject'],
        'body_text'=>(string)$source['body_text'],'body_html'=>(string)($source['body_html']??''),
        'template_key'=>(string)($source['template_key']??'minimal'),
        'selection_explicit'=>1,'recipient_ids'=>array_column($audience,'id')
    ]);
}
