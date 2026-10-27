<?php
declare(strict_types=1);

function communication_platform_available(PDO $db):bool {
    return (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_segments'")->fetchColumn();
}

function communication_audit(PDO $db,int $activityId,?int $adminId,string $action,string $entityType,?int $entityId,array $details=[]):void {
    if(!communication_platform_available($db))return;
    $db->prepare('INSERT INTO communication_audit_log(activity_id,admin_id,action,entity_type,entity_id,details_json,created_at) VALUES(?,?,?,?,?,?,?)')
      ->execute([$activityId,$adminId?:null,mb_substr($action,0,100),mb_substr($entityType,0,60),$entityId,
        json_encode($details,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),utc_now()]);
}

function communication_segment_rules(mixed $raw):array {
    if(is_string($raw))$raw=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
    if(!is_array($raw))throw new RuntimeException('Defina regras válidas para o segmento.');
    $ids=static function(mixed $value):array {
        if(!is_array($value))return [];
        $out=[];foreach($value as $id){$id=(int)$id;if($id>0)$out[$id]=$id;}
        return array_values($out);
    };
    $allowedBehaviors=['submitted_test','asked_question','never_logged_in','inactive_30_days'];
    $behaviors=[];
    foreach((array)($raw['behaviors']??[]) as $behavior)if(in_array($behavior,$allowedBehaviors,true))$behaviors[$behavior]=$behavior;
    $statuses=[];
    foreach((array)($raw['enrollment_statuses']??['active']) as $status)if(in_array($status,['active','inactive','completed','cancelled'],true))$statuses[$status]=$status;
    $consent=(string)($raw['newsletter_consent']??'any');
    if(!in_array($consent,['any','granted','revoked'],true))$consent='any';
    $profile=(string)($raw['profile']??'any');
    if(!in_array($profile,['any','activated','not_activated'],true))$profile='any';
    return [
        'course_ids'=>$ids($raw['course_ids']??[]),
        'cohort_ids'=>$ids($raw['cohort_ids']??[]),
        'enrollment_statuses'=>array_values($statuses),
        'newsletter_consent'=>$consent,
        'profile'=>$profile,
        'behaviors'=>array_values($behaviors),
    ];
}

function communication_segment_candidates(PDO $db,int $activityId):array {
    $sql="SELECT DISTINCT u.id,u.name,u.email,u.last_login_at,
      CASE WHEN COALESCE(p.newsletter_opt_in,0)=1 THEN 1 ELSE 0 END newsletter_opt_in,
      CASE WHEN u.activated_at IS NOT NULL THEN 1 ELSE 0 END activated,
      e.status enrollment_status,c.id cohort_id,c.course_id,
      CASE WHEN EXISTS(SELECT 1 FROM student_tests t WHERE t.student_id=u.id AND t.status!='draft') THEN 1 ELSE 0 END submitted_test,
      CASE WHEN EXISTS(SELECT 1 FROM student_questions q WHERE q.student_id=u.id) THEN 1 ELSE 0 END asked_question
      FROM student_users u
      JOIN course_enrollments e ON e.student_id=u.id
      JOIN course_cohorts c ON c.id=e.cohort_id
      JOIN courses course ON course.id=c.course_id AND course.activity_id=?
      LEFT JOIN communication_preferences p ON p.student_id=u.id
      WHERE u.status='active' ORDER BY u.name,u.id";
    $q=$db->prepare($sql);$q->execute([$activityId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_segment_matches(array $row,array $rules,string $mode='all'):bool {
    $checks=[];
    if($rules['course_ids'])$checks[]=in_array((int)$row['course_id'],$rules['course_ids'],true);
    if($rules['cohort_ids'])$checks[]=in_array((int)$row['cohort_id'],$rules['cohort_ids'],true);
    if($rules['enrollment_statuses'])$checks[]=in_array((string)$row['enrollment_status'],$rules['enrollment_statuses'],true);
    if($rules['newsletter_consent']!=='any')$checks[]=((int)$row['newsletter_opt_in']===($rules['newsletter_consent']==='granted'?1:0));
    if($rules['profile']!=='any')$checks[]=((int)$row['activated']===($rules['profile']==='activated'?1:0));
    foreach($rules['behaviors'] as $behavior){
        $checks[]=match($behavior){
            'submitted_test'=>(int)$row['submitted_test']===1,
            'asked_question'=>(int)$row['asked_question']===1,
            'never_logged_in'=>empty($row['last_login_at']),
            'inactive_30_days'=>empty($row['last_login_at'])||strtotime((string)$row['last_login_at'])<time()-2592000,
            default=>false,
        };
    }
    if(!$checks)return true;
    return $mode==='any'?in_array(true,$checks,true):!in_array(false,$checks,true);
}

function communication_segment_evaluate(PDO $db,int $activityId,array|string $rules,string $mode='all'):array {
    $rules=communication_segment_rules($rules);$mode=$mode==='any'?'any':'all';$members=[];
    foreach(communication_segment_candidates($db,$activityId) as $row){
        if(!communication_segment_matches($row,$rules,$mode))continue;
        $id=(int)$row['id'];
        if(!isset($members[$id]))$members[$id]=['id'=>$id,'name'=>(string)$row['name'],'email'=>(string)$row['email'],'reasons'=>[]];
        $members[$id]['reasons'][]=['course_id'=>(int)$row['course_id'],'cohort_id'=>(int)$row['cohort_id'],'enrollment_status'=>(string)$row['enrollment_status']];
    }
    return array_values($members);
}

function communication_segments(PDO $db,int $activityId,bool $includeArchived=false):array {
    $q=$db->prepare('SELECT s.*,
      (SELECT COUNT(*) FROM communication_campaigns c WHERE c.segment_id=s.id) campaign_count,
      (SELECT COUNT(*) FROM communication_deliveries d JOIN communication_campaigns c ON c.id=d.campaign_id WHERE c.segment_id=s.id AND d.status=\'sent\') accepted_count,
      (SELECT COUNT(DISTINCT ev.delivery_id) FROM communication_delivery_events ev JOIN communication_deliveries d ON d.id=ev.delivery_id JOIN communication_campaigns c ON c.id=d.campaign_id WHERE c.segment_id=s.id AND ev.channel=\'campaign\' AND ev.provider_event=\'delivered\') delivered_count,
      (SELECT COUNT(*) FROM communication_conversions conversion JOIN communication_campaigns c ON c.id=conversion.campaign_id WHERE c.segment_id=s.id) conversion_count
      FROM communication_segments s WHERE activity_id=?'.($includeArchived?'':' AND status=\'active\'').' ORDER BY status,name,id');
    $q->execute([$activityId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_segment_save(PDO $db,int $activityId,int $id,array $input,int $adminId):int {
    $name=trim((string)($input['name']??''));if($name===''||mb_strlen($name)>120)throw new RuntimeException('Informe um nome de segmento com até 120 caracteres.');
    $description=trim((string)($input['description']??''));if(mb_strlen($description)>500)throw new RuntimeException('A descrição do segmento é muito extensa.');
    $mode=(string)($input['match_mode']??'all');if(!in_array($mode,['all','any'],true))throw new RuntimeException('Modo de combinação inválido.');
    $rules=communication_segment_rules($input['rules']??$input);
    $json=json_encode($rules,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$now=utc_now();
    return course_agenda_with_lock($db,static function()use($db,$activityId,$id,$name,$description,$mode,$json,$adminId,$now):int{
        if($id>0){
            $q=$db->prepare("UPDATE communication_segments SET name=?,description=?,match_mode=?,rules_json=?,updated_at=? WHERE id=? AND activity_id=? AND status='active'");
            $q->execute([$name,$description,$mode,$json,$now,$id,$activityId]);if($q->rowCount()!==1)throw new RuntimeException('Segmento não encontrado nesta área.');
        }else{
            $db->prepare('INSERT INTO communication_segments(activity_id,name,description,match_mode,rules_json,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)')
              ->execute([$activityId,$name,$description,$mode,$json,$adminId?:null,$now,$now]);$id=(int)$db->lastInsertId();
        }
        communication_audit($db,$activityId,$adminId,'segment.saved','segment',$id,['name'=>$name,'match_mode'=>$mode,'rules'=>json_decode($json,true)]);
        return $id;
    });
}

function communication_segment_snapshot(PDO $db,int $activityId,int $campaignId,?int $segmentId):array {
    if(!$segmentId)return ['ids'=>[],'count'=>0,'hash'=>'','rules'=>[]];
    $q=$db->prepare("SELECT * FROM communication_segments WHERE id=? AND activity_id=? AND status='active'");$q->execute([$segmentId,$activityId]);$segment=$q->fetch(PDO::FETCH_ASSOC);
    if(!$segment)throw new RuntimeException('O segmento escolhido não está disponível.');
    $members=communication_segment_evaluate($db,$activityId,(string)$segment['rules_json'],(string)$segment['match_mode']);
    $ids=array_map('intval',array_column($members,'id'));sort($ids);$rules=['match_mode'=>$segment['match_mode'],'rules'=>json_decode((string)$segment['rules_json'],true)];
    $rulesJson=json_encode($rules,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $idsJson=json_encode($ids,JSON_THROW_ON_ERROR);$hash=hash('sha256',$rulesJson."\n".$idsJson);
    $db->prepare('INSERT INTO communication_segment_snapshots(segment_id,campaign_id,rules_json,member_ids_json,member_count,snapshot_hash,created_at) VALUES(?,?,?,?,?,?,?)')
      ->execute([$segmentId,$campaignId,$rulesJson,$idsJson,count($ids),$hash,utc_now()]);
    return ['ids'=>$ids,'count'=>count($ids),'hash'=>$hash,'rules'=>$rules];
}

function communication_campaign_render_builder(PDO $db,array $campaign):?array {
    $table=$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_builder_drafts'")->fetchColumn();
    if(!$table)return null;
    $q=$db->prepare('SELECT mjml_source,media_refs_json FROM communication_builder_drafts WHERE campaign_id=?');$q->execute([(int)$campaign['id']]);$draft=$q->fetch(PDO::FETCH_ASSOC);
    if(!$draft)return null;
    $refs=json_decode((string)$draft['media_refs_json'],true,32,JSON_THROW_ON_ERROR);
    $render=communication_builder_render_email($db,(string)$draft['mjml_source'],is_array($refs)?$refs:[]);
    $preheader=trim((string)($campaign['preheader']??''));
    if($preheader!=='')$render['html']=communication_html_prepend_body($render['html'],'<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">'.htmlspecialchars($preheader,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</div>');
    return $render;
}

function communication_journey_trigger(PDO $db,int $activityId,string $trigger,int $studentId,string $eventKey,array $context=[]):int {
    if(!communication_platform_available($db)||$activityId<1||$studentId<1||trim($eventKey)==='')return 0;
    $allowed=['enrollment_activated','course_completed','newsletter_opt_in','student_inactive'];
    if(!in_array($trigger,$allowed,true))throw new RuntimeException('Gatilho de jornada inválido.');
    $q=$db->prepare("SELECT id,trigger_config_json FROM communication_journeys WHERE activity_id=? AND trigger_key=? AND status='active' ORDER BY id");
    $q->execute([$activityId,$trigger]);$created=0;
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $journey){
        $config=json_decode((string)$journey['trigger_config_json'],true)?:[];
        $configuredCourse=(int)($config['course_id']??0);$eventCourse=(int)($context['course_id']??0);
        if($configuredCourse>0&&$configuredCourse!==$eventCourse)continue;
        if(communication_journey_enroll($db,(int)$journey['id'],$studentId,$eventKey,$context))$created++;
    }
    return $created;
}

function communication_html_prepend_body(string $html,string $fragment):string {
    $position=stripos($html,'<body');if($position===false)return $fragment.$html;
    $close=strpos($html,'>',$position);return $close===false?$fragment.$html:substr($html,0,$close+1).$fragment.substr($html,$close+1);
}

function communication_html_append_body(string $html,string $fragment):string {
    $position=strripos($html,'</body>');return $position===false?$html.$fragment:substr($html,0,$position).$fragment.substr($html,$position);
}

function communication_campaign_freeze_content(PDO $db,int $activityId,int $campaignId):array {
    $campaign=communication_campaign($db,$activityId,$campaignId);if(!$campaign)throw new RuntimeException('Mensagem não encontrada.');
    $render=communication_campaign_render_builder($db,$campaign);$now=utc_now();
    if($render){$source='builder';$html=(string)$render['html'];$text=(string)$render['text'];$hash=(string)$render['sha256'];}
    else{$source='legacy';$html='';$text=(string)$campaign['body_text'];$hash=hash('sha256',(string)($campaign['body_html']??'')."\n".$text);}
    $db->prepare('UPDATE communication_campaigns SET content_source=?,rendered_html=?,rendered_text=?,content_hash=?,rendered_at=?,updated_at=? WHERE id=? AND activity_id=? AND status=\'draft\'')
      ->execute([$source,$html,$text,$hash,$now,$now,$campaignId,$activityId]);
    return ['source'=>$source,'html'=>$html,'text'=>$text,'hash'=>$hash];
}

function communication_campaign_request_approval(PDO $db,int $activityId,int $campaignId,int $adminId):void {
    $campaign=communication_campaign($db,$activityId,$campaignId);if(!$campaign||$campaign['status']!=='draft')throw new RuntimeException('Somente rascunhos podem seguir para aprovação.');
    communication_campaign_freeze_content($db,$activityId,$campaignId);
    $db->prepare("UPDATE communication_campaigns SET approval_status='pending',approval_note='',updated_at=? WHERE id=? AND activity_id=? AND status='draft'")->execute([utc_now(),$campaignId,$activityId]);
    communication_audit($db,$activityId,$adminId,'campaign.approval_requested','campaign',$campaignId);
}

function communication_campaign_decide_approval(PDO $db,int $activityId,int $campaignId,int $adminId,bool $approve,string $note=''):void {
    $note=trim($note);if(mb_strlen($note)>1000)throw new RuntimeException('A observação de aprovação é muito extensa.');
    $status=$approve?'approved':'rejected';$q=$db->prepare("UPDATE communication_campaigns SET approval_status=?,approved_by=?,approved_at=?,approval_note=?,updated_at=? WHERE id=? AND activity_id=? AND status='draft' AND approval_status='pending'");
    $now=utc_now();$q->execute([$status,$adminId?:null,$now,$note,$now,$campaignId,$activityId]);if($q->rowCount()!==1)throw new RuntimeException('A mensagem não está aguardando aprovação.');
    communication_audit($db,$activityId,$adminId,$approve?'campaign.approved':'campaign.rejected','campaign',$campaignId,['note'=>$note]);
}

function communication_campaign_assert_approved(PDO $db,int $activityId,int $campaignId):void {
    $q=$db->prepare('SELECT approval_status FROM communication_campaigns WHERE id=? AND activity_id=?');$q->execute([$campaignId,$activityId]);
    $status=$q->fetchColumn();if($status!==false&&$status!=='approved')throw new RuntimeException('A mensagem precisa ser aprovada antes de agendar ou enviar.');
}

function communication_templates(PDO $db,int $activityId):array {
    $q=$db->prepare("SELECT * FROM communication_templates WHERE activity_id=? AND status='active' ORDER BY name,id");$q->execute([$activityId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_template_save_from_campaign(PDO $db,int $activityId,int $campaignId,string $name,int $adminId):int {
    $name=trim($name);if($name===''||mb_strlen($name)>120)throw new RuntimeException('Informe um nome de template com até 120 caracteres.');
    $campaign=communication_campaign($db,$activityId,$campaignId);if(!$campaign||$campaign['status']!=='draft')throw new RuntimeException('Salve um rascunho antes de criar o template.');
    $q=$db->prepare('SELECT mjml_source,media_refs_json,document_version FROM communication_builder_drafts WHERE campaign_id=?');$q->execute([$campaignId]);$draft=$q->fetch(PDO::FETCH_ASSOC);
    if(!$draft)throw new RuntimeException('Crie e salve o layout visual antes de transformá-lo em template.');
    communication_builder_validate_document($db,(string)$draft['mjml_source'],json_decode((string)$draft['media_refs_json'],true,32,JSON_THROW_ON_ERROR));
    $now=utc_now();$db->prepare('INSERT INTO communication_templates(activity_id,name,category,subject,preheader,document_version,mjml_source,media_refs_json,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
      ->execute([$activityId,$name,$campaign['category'],$campaign['subject'],(string)($campaign['preheader']??''),(int)$draft['document_version'],$draft['mjml_source'],$draft['media_refs_json'],$adminId?:null,$now,$now]);
    $id=(int)$db->lastInsertId();communication_audit($db,$activityId,$adminId,'template.created','template',$id,['campaign_id'=>$campaignId]);return $id;
}

function communication_template_apply_to_campaign(PDO $db,int $activityId,int $campaignId,int $templateId,int $adminId):void {
    $campaign=communication_campaign($db,$activityId,$campaignId);if(!$campaign||$campaign['status']!=='draft'||communication_campaign_is_scheduled($campaign))throw new RuntimeException('O template só pode ser aplicado a um rascunho não agendado.');
    $q=$db->prepare("SELECT * FROM communication_templates WHERE id=? AND activity_id=? AND status='active'");$q->execute([$templateId,$activityId]);$template=$q->fetch(PDO::FETCH_ASSOC);if(!$template)throw new RuntimeException('Template não encontrado nesta área.');
    $refs=json_decode((string)$template['media_refs_json'],true,32,JSON_THROW_ON_ERROR);communication_builder_validate_document($db,(string)$template['mjml_source'],is_array($refs)?$refs:[]);$now=utc_now();
    course_agenda_with_lock($db,static function()use($db,$activityId,$campaignId,$template,$now):void{
        $db->prepare('INSERT INTO communication_builder_drafts(campaign_id,document_version,mjml_source,media_refs_json,revision,created_at,updated_at) VALUES(?,?,?,?,1,?,?) ON CONFLICT(campaign_id) DO UPDATE SET document_version=excluded.document_version,mjml_source=excluded.mjml_source,media_refs_json=excluded.media_refs_json,revision=communication_builder_drafts.revision+1,updated_at=excluded.updated_at')
          ->execute([$campaignId,(int)$template['document_version'],$template['mjml_source'],$template['media_refs_json'],$now,$now]);
        $db->prepare("UPDATE communication_campaigns SET category=?,subject=?,preheader=?,template_id=?,approval_status='draft',approved_by=NULL,approved_at=NULL,approval_note='',rendered_html='',rendered_text='',content_hash='',rendered_at=NULL,updated_at=? WHERE id=? AND activity_id=? AND status='draft'")
          ->execute([$template['category'],$template['subject'],$template['preheader'],(int)$template['id'],$now,$campaignId,$activityId]);
    });
    communication_audit($db,$activityId,$adminId,'template.applied','campaign',$campaignId,['template_id'=>$templateId]);
}

function communication_journeys(PDO $db,int $activityId):array {
    $q=$db->prepare("SELECT j.*,(SELECT COUNT(*) FROM communication_journey_steps s WHERE s.journey_id=j.id) step_count,(SELECT COUNT(*) FROM communication_journey_enrollments e WHERE e.journey_id=j.id AND e.status='active') active_count FROM communication_journeys j WHERE j.activity_id=? AND j.status!='archived' ORDER BY j.id DESC");
    $q->execute([$activityId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_journey_save(PDO $db,int $activityId,int $id,array $input,int $adminId):int {
    $name=trim((string)($input['name']??''));if($name===''||mb_strlen($name)>120)throw new RuntimeException('Informe um nome de jornada com até 120 caracteres.');
    $description=trim((string)($input['description']??''));if(mb_strlen($description)>500)throw new RuntimeException('A descrição da jornada é muito extensa.');
    $trigger=(string)($input['trigger_key']??'manual');$allowed=['manual','enrollment_activated','course_completed','newsletter_opt_in','student_inactive'];
    if(!in_array($trigger,$allowed,true))throw new RuntimeException('Gatilho de jornada inválido.');
    $segmentId=max(0,(int)($input['segment_id']??0));$config=['course_id'=>max(0,(int)($input['course_id']??0)),'inactive_days'=>max(1,min(365,(int)($input['inactive_days']??30)))];
    $now=utc_now();
    if($id>0){$q=$db->prepare("UPDATE communication_journeys SET name=?,description=?,trigger_key=?,trigger_config_json=?,segment_id=?,updated_at=? WHERE id=? AND activity_id=? AND status='draft'");$q->execute([$name,$description,$trigger,json_encode($config,JSON_THROW_ON_ERROR),$segmentId?:null,$now,$id,$activityId]);if($q->rowCount()!==1)throw new RuntimeException('Somente jornadas em rascunho podem ser editadas.');}
    else{$db->prepare("INSERT INTO communication_journeys(activity_id,name,description,trigger_key,trigger_config_json,segment_id,status,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,'draft',?,?,?)")->execute([$activityId,$name,$description,$trigger,json_encode($config,JSON_THROW_ON_ERROR),$segmentId?:null,$adminId?:null,$now,$now]);$id=(int)$db->lastInsertId();}
    communication_audit($db,$activityId,$adminId,'journey.saved','journey',$id,['trigger'=>$trigger]);return $id;
}

function communication_journey_step_save(PDO $db,int $activityId,int $journeyId,array $input,int $adminId):int {
    $q=$db->prepare("SELECT status FROM communication_journeys WHERE id=? AND activity_id=?");$q->execute([$journeyId,$activityId]);if($q->fetchColumn()!=='draft')throw new RuntimeException('Edite etapas somente enquanto a jornada estiver em rascunho.');
    $position=max(1,min(100,(int)($input['position']??1)));$delay=max(0,min(525600,(int)($input['delay_minutes']??0)));$category=(string)($input['category']??'operational');
    if(!in_array($category,['operational','newsletter'],true))throw new RuntimeException('Categoria da etapa inválida.');
    $subject=trim((string)($input['subject']??''));if($subject===''||mb_strlen($subject)>180)throw new RuntimeException('Informe o assunto da etapa.');
    $body=communication_campaign_body($input);$preheader=communication_campaign_preheader($input['preheader']??'');$templateId=max(0,(int)($input['template_id']??0));$now=utc_now();
    $condition=(string)($input['condition_key']??'always');if(!in_array($condition,['always','newsletter_opt_in','submitted_test','asked_question'],true))throw new RuntimeException('Condição da etapa inválida.');
    $db->prepare('INSERT INTO communication_journey_steps(journey_id,position,delay_minutes,condition_json,template_id,category,subject,preheader,body_text,body_html,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(journey_id,position) DO UPDATE SET delay_minutes=excluded.delay_minutes,condition_json=excluded.condition_json,template_id=excluded.template_id,category=excluded.category,subject=excluded.subject,preheader=excluded.preheader,body_text=excluded.body_text,body_html=excluded.body_html,updated_at=excluded.updated_at')
      ->execute([$journeyId,$position,$delay,json_encode(['key'=>$condition],JSON_THROW_ON_ERROR),$templateId?:null,$category,$subject,$preheader,$body['body_text'],$body['body_html'],$now,$now]);
    $id=(int)$db->query('SELECT id FROM communication_journey_steps WHERE journey_id='.(int)$journeyId.' AND position='.(int)$position)->fetchColumn();
    communication_audit($db,$activityId,$adminId,'journey.step_saved','journey',$journeyId,['step_id'=>$id,'position'=>$position]);return $id;
}

function communication_journey_step_matches(PDO $db,int $studentId,array $step):bool {
    $condition=json_decode((string)($step['condition_json']??'{}'),true)?:[];$key=(string)($condition['key']??'always');
    if($key==='always')return true;
    if($key==='newsletter_opt_in')return communication_opted_in($db,$studentId);
    if($key==='submitted_test'){$q=$db->prepare("SELECT 1 FROM student_tests WHERE student_id=? AND status!='draft' LIMIT 1");$q->execute([$studentId]);return (bool)$q->fetchColumn();}
    if($key==='asked_question'){$q=$db->prepare('SELECT 1 FROM student_questions WHERE student_id=? LIMIT 1');$q->execute([$studentId]);return (bool)$q->fetchColumn();}
    return false;
}

function communication_journey_set_status(PDO $db,int $activityId,int $journeyId,string $status,int $adminId):void {
    if(!in_array($status,['active','paused','archived'],true))throw new RuntimeException('Estado de jornada inválido.');
    if($status==='active'){$q=$db->prepare('SELECT COUNT(*) FROM communication_journey_steps WHERE journey_id=?');$q->execute([$journeyId]);if((int)$q->fetchColumn()<1)throw new RuntimeException('Adicione ao menos uma etapa antes de ativar a jornada.');}
    $q=$db->prepare('UPDATE communication_journeys SET status=?,updated_at=? WHERE id=? AND activity_id=?');$q->execute([$status,utc_now(),$journeyId,$activityId]);if($q->rowCount()!==1)throw new RuntimeException('Jornada não encontrada.');
    communication_audit($db,$activityId,$adminId,'journey.status_changed','journey',$journeyId,['status'=>$status]);
}

function communication_journey_enroll(PDO $db,int $journeyId,int $studentId,string $eventKey,array $context=[]):bool {
    if($journeyId<1||$studentId<1||trim($eventKey)==='')throw new RuntimeException('Inscrição de jornada inválida.');
    $q=$db->prepare("SELECT 1 FROM communication_journeys WHERE id=? AND status='active'");$q->execute([$journeyId]);if(!$q->fetchColumn())return false;
    $step=$db->prepare('SELECT position,delay_minutes FROM communication_journey_steps WHERE journey_id=? ORDER BY position,id LIMIT 1');$step->execute([$journeyId]);$first=$step->fetch(PDO::FETCH_ASSOC);if(!$first)return false;
    $delay=(int)$first['delay_minutes'];$position=(int)$first['position'];
    $next=gmdate('Y-m-d\TH:i:s\Z',time()+((int)$delay*60));$now=utc_now();
    $q=$db->prepare("INSERT OR IGNORE INTO communication_journey_enrollments(journey_id,student_id,event_key,current_position,next_run_at,context_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)");
    $q->execute([$journeyId,$studentId,mb_substr($eventKey,0,160),$position,$next,json_encode($context,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$now,$now]);return $q->rowCount()===1;
}

function communication_journey_process(PDO $db,int $limit=20):array {
    if(!communication_platform_available($db))return ['processed'=>0,'queued'=>0,'failed'=>0];
    $limit=max(1,min(100,$limit));$q=$db->prepare("SELECT e.*,j.activity_id,j.segment_id,j.trigger_config_json FROM communication_journey_enrollments e JOIN communication_journeys j ON j.id=e.journey_id WHERE e.status='active' AND j.status='active' AND e.next_run_at<=? ORDER BY e.next_run_at,e.id LIMIT ?");
    $q->bindValue(1,utc_now());$q->bindValue(2,$limit,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll(PDO::FETCH_ASSOC);$result=['processed'=>0,'queued'=>0,'failed'=>0];
    foreach($rows as $row){
        $result['processed']++;$enrollmentId=(int)$row['id'];
        try{
            if((int)($row['segment_id']??0)>0){
                $segment=$db->prepare('SELECT rules_json,match_mode FROM communication_segments WHERE id=? AND activity_id=? AND status=\'active\'');$segment->execute([(int)$row['segment_id'],(int)$row['activity_id']]);$definition=$segment->fetch(PDO::FETCH_ASSOC);
                $members=$definition?communication_segment_evaluate($db,(int)$row['activity_id'],(string)$definition['rules_json'],(string)$definition['match_mode']):[];
                if(!in_array((int)$row['student_id'],array_map('intval',array_column($members,'id')),true)){
                    $db->prepare("UPDATE communication_journey_enrollments SET status='cancelled',last_error='segment_mismatch',updated_at=? WHERE id=?")->execute([utc_now(),$enrollmentId]);continue;
                }
            }
            $step=$db->prepare('SELECT * FROM communication_journey_steps WHERE journey_id=? AND position=?');$step->execute([(int)$row['journey_id'],(int)$row['current_position']]);$step=$step->fetch(PDO::FETCH_ASSOC);
            if(!$step){$db->prepare("UPDATE communication_journey_enrollments SET status='completed',next_run_at=NULL,updated_at=? WHERE id=?")->execute([utc_now(),$enrollmentId]);continue;}
            if(!communication_journey_step_matches($db,(int)$row['student_id'],$step)){
                $next=$db->prepare('SELECT position,delay_minutes FROM communication_journey_steps WHERE journey_id=? AND position>? ORDER BY position LIMIT 1');$next->execute([(int)$row['journey_id'],(int)$row['current_position']]);$next=$next->fetch(PDO::FETCH_ASSOC);
                if($next)$db->prepare("UPDATE communication_journey_enrollments SET current_position=?,next_run_at=?,updated_at=?,last_error=NULL WHERE id=?")->execute([(int)$next['position'],gmdate('Y-m-d\TH:i:s\Z',time()+((int)$next['delay_minutes']*60)),utc_now(),$enrollmentId]);
                else $db->prepare("UPDATE communication_journey_enrollments SET status='completed',next_run_at=NULL,updated_at=?,last_error=NULL WHERE id=?")->execute([utc_now(),$enrollmentId]);
                continue;
            }
            $context=json_decode((string)$row['context_json'],true)?:[];$config=json_decode((string)$row['trigger_config_json'],true)?:[];$courseId=(int)($context['course_id']??$config['course_id']??0);$cohortId=(int)($context['cohort_id']??0);
            $campaignId=communication_create_campaign($db,(int)$row['activity_id'],[
                'category'=>$step['category'],'course_id'=>$courseId,'cohort_id'=>$cohortId,'subject'=>$step['subject'],'preheader'=>$step['preheader'],
                'body_text'=>$step['body_text'],'body_html'=>$step['body_html'],'template_key'=>'minimal','selection_explicit'=>1,'recipient_ids'=>[(int)$row['student_id']],
                'priority'=>60,
            ]);
            if((int)($step['template_id']??0)>0){
                $template=$db->prepare("SELECT * FROM communication_templates WHERE id=? AND activity_id=? AND status='active'");$template->execute([(int)$step['template_id'],(int)$row['activity_id']]);$template=$template->fetch(PDO::FETCH_ASSOC);
                if(!$template)throw new RuntimeException('Template da jornada indisponível.');$now=utc_now();
                $db->prepare('INSERT INTO communication_builder_drafts(campaign_id,document_version,mjml_source,media_refs_json,revision,created_at,updated_at) VALUES(?,?,?,?,1,?,?)')->execute([$campaignId,(int)$template['document_version'],$template['mjml_source'],$template['media_refs_json'],$now,$now]);
            }
            $db->prepare("UPDATE communication_campaigns SET journey_id=?,approval_status='approved',approved_at=?,approval_note='Jornada previamente aprovada' WHERE id=?")->execute([(int)$row['journey_id'],utc_now(),$campaignId]);
            communication_queue_campaign($db,(int)$row['activity_id'],$campaignId);$result['queued']++;
            $next=$db->prepare('SELECT position,delay_minutes FROM communication_journey_steps WHERE journey_id=? AND position>? ORDER BY position LIMIT 1');$next->execute([(int)$row['journey_id'],(int)$row['current_position']]);$next=$next->fetch(PDO::FETCH_ASSOC);
            if($next)$db->prepare("UPDATE communication_journey_enrollments SET current_position=?,next_run_at=?,updated_at=?,last_error=NULL WHERE id=?")->execute([(int)$next['position'],gmdate('Y-m-d\TH:i:s\Z',time()+((int)$next['delay_minutes']*60)),utc_now(),$enrollmentId]);
            else $db->prepare("UPDATE communication_journey_enrollments SET status='completed',next_run_at=NULL,updated_at=?,last_error=NULL WHERE id=?")->execute([utc_now(),$enrollmentId]);
        }catch(Throwable $e){$db->prepare("UPDATE communication_journey_enrollments SET status='failed',last_error=?,updated_at=? WHERE id=?")->execute([mb_substr($e->getMessage(),0,500),utc_now(),$enrollmentId]);$result['failed']++;}
    }
    return $result;
}

function communication_journey_scan_lifecycle(PDO $db,int $limit=100):array {
    if(!communication_platform_available($db))return ['completed'=>0,'inactive'=>0];
    $limit=max(1,min(500,$limit));$result=['completed'=>0,'inactive'=>0];
    $completed=$db->prepare("SELECT e.id enrollment_id,e.student_id,e.updated_at,c.id cohort_id,c.course_id,course.activity_id FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id JOIN courses course ON course.id=c.course_id WHERE e.status='completed' ORDER BY e.updated_at DESC,e.id DESC LIMIT ?");
    $completed->bindValue(1,$limit,PDO::PARAM_INT);$completed->execute();
    foreach($completed->fetchAll(PDO::FETCH_ASSOC) as $row)$result['completed']+=communication_journey_trigger($db,(int)$row['activity_id'],'course_completed',(int)$row['student_id'],'course-completed-'.(int)$row['enrollment_id'].'-'.hash('sha256',(string)$row['updated_at']),['course_id'=>(int)$row['course_id'],'cohort_id'=>(int)$row['cohort_id'],'enrollment_id'=>(int)$row['enrollment_id']]);
    $journeys=$db->query("SELECT id,activity_id,trigger_config_json FROM communication_journeys WHERE trigger_key='student_inactive' AND status='active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach($journeys as $journey){
        $config=json_decode((string)$journey['trigger_config_json'],true)?:[];$days=max(1,min(365,(int)($config['inactive_days']??30)));$courseId=max(0,(int)($config['course_id']??0));$cutoff=gmdate('Y-m-d\TH:i:s\Z',time()-$days*86400);
        $sql="SELECT DISTINCT u.id,u.last_login_at,c.id cohort_id,c.course_id FROM student_users u JOIN course_enrollments e ON e.student_id=u.id AND e.status='active' JOIN course_cohorts c ON c.id=e.cohort_id JOIN courses course ON course.id=c.course_id AND course.activity_id=? WHERE u.status='active' AND (u.last_login_at IS NULL OR u.last_login_at<?)".($courseId>0?' AND c.course_id=?':'')." ORDER BY u.id LIMIT ?";
        $q=$db->prepare($sql);$position=1;$q->bindValue($position++,(int)$journey['activity_id'],PDO::PARAM_INT);$q->bindValue($position++,$cutoff);if($courseId>0)$q->bindValue($position++,$courseId,PDO::PARAM_INT);$q->bindValue($position,$limit,PDO::PARAM_INT);$q->execute();
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){$event='inactive-'.$days.'-'.hash('sha256',(string)($row['last_login_at']??'never'));if(communication_journey_enroll($db,(int)$journey['id'],(int)$row['id'],$event,['course_id'=>(int)$row['course_id'],'cohort_id'=>(int)$row['cohort_id'],'inactive_days'=>$days]))$result['inactive']++;}
    }
    return $result;
}

function communication_worker_run_start(PDO $db):string {
    $token=bin2hex(random_bytes(16));$now=utc_now();$stale=gmdate('Y-m-d\TH:i:s\Z',time()-1800);
    $db->prepare("UPDATE communication_worker_runs SET status='failed',finished_at=?,error_summary='Execução interrompida sem encerramento.' WHERE status='running' AND heartbeat_at<?")->execute([$now,$stale]);
    $db->prepare("INSERT INTO communication_worker_runs(run_token,started_at,heartbeat_at,status) VALUES(?,?,?,'running')")->execute([$token,$now,$now]);return $token;
}

function communication_worker_run_heartbeat(PDO $db,string $token):void {
    $db->prepare("UPDATE communication_worker_runs SET heartbeat_at=? WHERE run_token=? AND status='running'")->execute([utc_now(),$token]);
}

function communication_delivery_preference(PDO $db,int $studentId,string $category):array {
    if(!communication_platform_available($db))return ['enabled'=>1,'frequency'=>'immediate'];
    $q=$db->prepare("SELECT enabled,frequency FROM communication_channel_preferences WHERE student_id=? AND category=? AND channel='email'");
    $q->execute([$studentId,$category]);$row=$q->fetch(PDO::FETCH_ASSOC);
    return $row?:['enabled'=>1,'frequency'=>'immediate'];
}

function communication_student_channel_preferences(PDO $db,int $studentId):array {
    $newsletter=communication_opted_in($db,$studentId);
    $defaults=[
        'operational'=>['email_enabled'=>1,'frequency'=>'immediate','inbox_enabled'=>1],
        'newsletter'=>['email_enabled'=>$newsletter?1:0,'frequency'=>'immediate','inbox_enabled'=>0],
    ];
    if(!communication_platform_available($db))return $defaults;
    $q=$db->prepare('SELECT category,channel,enabled,frequency FROM communication_channel_preferences WHERE student_id=?');$q->execute([$studentId]);
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
        $category=(string)$row['category'];$channel=(string)$row['channel'];if(!isset($defaults[$category]))continue;
        $defaults[$category][$channel.'_enabled']=(int)$row['enabled'];
        if($channel==='email')$defaults[$category]['frequency']=(string)$row['frequency'];
    }
    // The authenticated inbox for necessary academic notices cannot be disabled.
    $defaults['operational']['inbox_enabled']=1;
    return $defaults;
}

function communication_save_channel_preferences(PDO $db,int $studentId,array $input,string $source='profile'):void {
    if(!communication_platform_available($db))return;
    $q=$db->prepare("SELECT 1 FROM student_users WHERE id=? AND status='active'");$q->execute([$studentId]);if(!$q->fetchColumn())throw new RuntimeException('Conta inválida.');
    $newsletterFrequency=(string)($input['newsletter_frequency']??'immediate');
    if(!in_array($newsletterFrequency,['immediate','daily','weekly'],true))throw new RuntimeException('Frequência de novidades inválida.');
    $operationalEmail=!empty($input['operational_email']);$now=utc_now();
    $save=$db->prepare('INSERT INTO communication_channel_preferences(student_id,category,channel,enabled,frequency,changed_at,source) VALUES(?,?,?,?,?,?,?) ON CONFLICT(student_id,category,channel) DO UPDATE SET enabled=excluded.enabled,frequency=excluded.frequency,changed_at=excluded.changed_at,source=excluded.source');
    $save->execute([$studentId,'operational','email',$operationalEmail?1:0,'immediate',$now,$source]);
    $save->execute([$studentId,'operational','inbox',1,'immediate',$now,$source]);
    $save->execute([$studentId,'newsletter','email',communication_opted_in($db,$studentId)?1:0,$newsletterFrequency,$now,$source]);
}

function communication_delivery_frequency_ready(PDO $db,int $studentId,string $category,string $frequency):bool {
    if($frequency==='immediate')return true;
    $since=$frequency==='daily'?gmdate('Y-m-d\T00:00:00\Z'):gmdate('Y-m-d\T00:00:00\Z',time()-6*86400);
    $q=$db->prepare("SELECT 1 FROM communication_deliveries d JOIN communication_campaigns c ON c.id=d.campaign_id WHERE d.student_id=? AND c.category=? AND d.status='sent' AND d.delivered_at>=? LIMIT 1");
    $q->execute([$studentId,$category,$since]);return !$q->fetchColumn();
}

function communication_delivery_backoff_at(int $attempt):string {
    $seconds=min(86400,60*(2**max(0,min(10,$attempt-1))));
    return gmdate('Y-m-d\TH:i:s\Z',time()+$seconds);
}

function communication_delivery_frequency_next_at(string $frequency):string {
    $seconds=$frequency==='weekly'?7*86400:86400;return gmdate('Y-m-d\TH:i:s\Z',time()+$seconds);
}

function communication_worker_run_finish(PDO $db,string $token,array $counts,string $error=''):void {
    $status=$error===''?'completed':'failed';$now=utc_now();
    $db->prepare('UPDATE communication_worker_runs SET heartbeat_at=?,finished_at=?,status=?,claimed_count=?,sent_count=?,skipped_count=?,failed_count=?,error_summary=? WHERE run_token=?')
      ->execute([$now,$now,$status,(int)($counts['claimed']??0),(int)($counts['sent']??0),(int)($counts['skipped']??0),(int)($counts['failed']??0),$error===''?null:mb_substr($error,0,500),$token]);
}

function communication_domain_diagnostics(PDO $db,int $adminId,string $selector='',?callable $resolver=null):array {
    $settings=communication_sender_settings($db);$email=(string)$settings['sender_email'];$domain=strtolower((string)substr(strrchr($email,'@')?:'',1));
    if($domain===''||filter_var('probe@'.$domain,FILTER_VALIDATE_EMAIL)===false)throw new RuntimeException('Configure um remetente válido para verificar o domínio.');
    $selector=strtolower(trim($selector));if($selector!==''&&!preg_match('/^[a-z0-9][a-z0-9._-]{0,62}$/',$selector))throw new RuntimeException('Informe um seletor DKIM válido.');
    $resolver??=static fn(string $name,int $type):array=>dns_get_record($name,$type)?:[];
    $txt=static function(array $records):array {$out=[];foreach($records as $row){$value=(string)($row['txt']??$row['entries'][0]??'');if($value!=='')$out[]=$value;}return $out;};
    $spf=$txt($resolver($domain,DNS_TXT));$dmarc=$txt($resolver('_dmarc.'.$domain,DNS_TXT));
    $spfOk=(bool)array_filter($spf,static fn($v)=>str_starts_with(strtolower($v),'v=spf1'));
    $dmarcOk=(bool)array_filter($dmarc,static fn($v)=>str_starts_with(strtolower($v),'v=dmarc1'));
    $dkimName=$selector.'._domainkey.'.$domain;$dkimRecords=$selector===''?[]:$txt($resolver($dkimName,DNS_TXT));$dkimAliases=[];
    if($selector!=='')foreach($resolver($dkimName,DNS_CNAME) as $row){$target=trim((string)($row['target']??''));if($target!=='')$dkimAliases[]=$target;}
    $dkim=$selector===''?'unknown':(((bool)array_filter($dkimRecords,static fn($v)=>str_contains(strtolower($v),'v=dkim1'))||$dkimAliases)?'valid':'missing');
    $details=['spf'=>$spf,'dmarc'=>$dmarc,'dkim'=>$dkimRecords,'dkim_cname'=>$dkimAliases,'dkim_note'=>$selector===''?'Informe o seletor fornecido pelo serviço de e-mail para verificar DKIM.':'Registro consultado em '.$dkimName.'.'];$now=utc_now();
    $db->prepare('INSERT INTO communication_domain_diagnostics(id,domain,dkim_selector,spf_status,dkim_status,dmarc_status,details_json,checked_at,checked_by) VALUES(1,?,?,?,?,?,?,?,?) ON CONFLICT(id) DO UPDATE SET domain=excluded.domain,dkim_selector=excluded.dkim_selector,spf_status=excluded.spf_status,dkim_status=excluded.dkim_status,dmarc_status=excluded.dmarc_status,details_json=excluded.details_json,checked_at=excluded.checked_at,checked_by=excluded.checked_by')
      ->execute([$domain,$selector,$spfOk?'valid':'missing',$dkim,$dmarcOk?'valid':'missing',json_encode($details,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$now,$adminId?:null]);
    return ['domain'=>$domain,'dkim_selector'=>$selector,'spf_status'=>$spfOk?'valid':'missing','dkim_status'=>$dkim,'dmarc_status'=>$dmarcOk?'valid':'missing','checked_at'=>$now,'details'=>$details];
}

function communication_platform_metrics(PDO $db,int $activityId):array {
    $q=$db->prepare("SELECT COUNT(DISTINCT c.id) campaigns,
      COUNT(DISTINCT CASE WHEN d.status='sent' THEN d.id END) accepted,
      COUNT(DISTINCT CASE WHEN ev.provider_event='delivered' THEN d.id END) delivered,
      COUNT(DISTINCT CASE WHEN ev.provider_event IN ('hard_bounce','soft_bounce','blocked','invalid_email') THEN d.id END) bounced,
      COUNT(DISTINCT CASE WHEN ev.provider_event='spam' THEN d.id END) spam,
      COUNT(DISTINCT conv.id) conversions
      FROM communication_campaigns c
      LEFT JOIN communication_deliveries d ON d.campaign_id=c.id
      LEFT JOIN communication_delivery_events ev ON ev.channel='campaign' AND ev.delivery_id=d.id
      LEFT JOIN communication_conversions conv ON conv.campaign_id=c.id
      WHERE c.activity_id=?");$q->execute([$activityId]);$row=$q->fetch(PDO::FETCH_ASSOC)?:[];
    return array_map('intval',$row+['campaigns'=>0,'accepted'=>0,'delivered'=>0,'bounced'=>0,'spam'=>0,'conversions'=>0]);
}

function communication_recent_audit(PDO $db,int $activityId,int $limit=80):array {
    $q=$db->prepare('SELECT l.*,a.email admin_name FROM communication_audit_log l LEFT JOIN admin_users a ON a.id=l.admin_id WHERE l.activity_id=? ORDER BY l.id DESC LIMIT ?');
    $q->bindValue(1,$activityId,PDO::PARAM_INT);$q->bindValue(2,max(1,min(200,$limit)),PDO::PARAM_INT);$q->execute();return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_recent_worker_runs(PDO $db,int $limit=20):array {
    if(!communication_platform_available($db))return [];$q=$db->prepare('SELECT * FROM communication_worker_runs ORDER BY id DESC LIMIT ?');$q->bindValue(1,max(1,min(100,$limit)),PDO::PARAM_INT);$q->execute();return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_dead_letters(PDO $db,int $activityId,int $limit=50):array {
    if(!communication_platform_available($db))return [];$q=$db->prepare("SELECT d.*,c.subject FROM communication_deliveries d JOIN communication_campaigns c ON c.id=d.campaign_id WHERE c.activity_id=? AND d.dead_letter_at IS NOT NULL ORDER BY d.dead_letter_at DESC,d.id DESC LIMIT ?");$q->bindValue(1,$activityId,PDO::PARAM_INT);$q->bindValue(2,max(1,min(200,$limit)),PDO::PARAM_INT);$q->execute();return $q->fetchAll(PDO::FETCH_ASSOC);
}

function communication_record_enrollment_conversion(PDO $db,int $studentId,int $courseId,int $enrollmentId,int $valueCents=0):bool {
    if(!communication_platform_available($db)||$studentId<1||$courseId<1||$enrollmentId<1)return false;
    $since=gmdate('Y-m-d\TH:i:s\Z',time()-30*86400);
    $q=$db->prepare("SELECT c.id campaign_id,d.id delivery_id FROM communication_deliveries d JOIN communication_campaigns c ON c.id=d.campaign_id WHERE d.student_id=? AND d.status='sent' AND c.goal_key='course_enrollment' AND (c.course_id IS NULL OR c.course_id=?) AND d.delivered_at>=? ORDER BY d.delivered_at DESC,d.id DESC LIMIT 1");
    $q->execute([$studentId,$courseId,$since]);$row=$q->fetch(PDO::FETCH_ASSOC);if(!$row)return false;
    $eventKey='course-enrollment-'.$enrollmentId.'-campaign-'.(int)$row['campaign_id'];
    $insert=$db->prepare("INSERT OR IGNORE INTO communication_conversions(campaign_id,delivery_id,student_id,goal_key,event_key,value_cents,occurred_at,metadata_json) VALUES(?,?,?,'course_enrollment',?,?,?,?)");
    $insert->execute([(int)$row['campaign_id'],(int)$row['delivery_id'],$studentId,$eventKey,$valueCents>0?$valueCents:null,utc_now(),json_encode(['course_id'=>$courseId,'enrollment_id'=>$enrollmentId],JSON_THROW_ON_ERROR)]);return $insert->rowCount()===1;
}
