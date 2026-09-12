<?php
declare(strict_types=1);

const CMS_FORM_TYPES=['text','email','tel','number','textarea','select','radio','checkbox','checkbox-group','date','time','consent'];

function cms_form_uuid(): string { return bin2hex(random_bytes(16)); }
function cms_form_field_id(string $value): string {
    $value=strtolower(trim($value));
    if(function_exists('iconv')){$ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);if(is_string($ascii)&&$ascii!=='')$value=$ascii;}
    $value=preg_replace('~[^a-z0-9_]+~','_',$value)??'';
    return trim($value,'_')?:'field_'.substr(bin2hex(random_bytes(4)),0,8);
}
function cms_form_message(string $locale,string $key): string {
    $pt=$locale===PUBLIC_LOCALE_PT_BR;
    return match($key){
        'required'=>$pt?'Campo obrigatório.':'This field is required.',
        'email'=>$pt?'Informe um e-mail válido.':'Enter a valid email address.',
        'invalid'=>$pt?'Valor inválido.':'Invalid value.',
        'long'=>$pt?'Conteúdo muito longo.':'This response is too long.',
        default=>$pt?'Valor inválido.':'Invalid value.',
    };
}
function cms_form_schema_default(string $locale,string $kind='interest'): array {
    $pt=$locale===PUBLIC_LOCALE_PT_BR;
    if($kind==='registration'){
        return [
            'version'=>1,
            'submitLabel'=>$pt?'Quero me inscrever':'Register my interest',
            'successTitle'=>$pt?'Inscrição recebida':'Registration received',
            'successMessage'=>$pt?'Recebi seus dados. Entrarei em contato com as próximas etapas da turma.':'Your details were received. I will contact you with the next steps.',
            'fields'=>[
                ['id'=>'name','type'=>'text','label'=>$pt?'Nome':'Name','required'=>true,'autocomplete'=>'name','width'=>'half'],
                ['id'=>'email','type'=>'email','label'=>$pt?'E-mail':'Email','required'=>true,'autocomplete'=>'email','width'=>'half'],
                ['id'=>'phone','type'=>'tel','label'=>$pt?'WhatsApp / telefone':'WhatsApp / phone','required'=>false,'autocomplete'=>'tel','width'=>'half'],
                ['id'=>'city','type'=>'text','label'=>$pt?'Cidade':'City','required'=>false,'autocomplete'=>'address-level2','width'=>'half'],
                ['id'=>'experience','type'=>'textarea','label'=>$pt?'Qual é a sua experiência com fotografia analógica?':'What is your experience with analogue photography?','required'=>false,'rows'=>3,'width'=>'full'],
                ['id'=>'equipment','type'=>'textarea','label'=>$pt?'Que equipamento você pretende usar no workshop?':'What equipment do you plan to use?','required'=>false,'rows'=>3,'width'=>'full'],
                ['id'=>'preferred_days','type'=>'checkbox-group','label'=>$pt?'Quais dias costumam funcionar melhor para você?':'Which days usually work best for you?','required'=>false,'width'=>'full','options'=>array_map(fn($v)=>['value'=>$v[0],'label'=>$v[1]],$pt?[['monday','Segunda'],['tuesday','Terça'],['wednesday','Quarta'],['thursday','Quinta'],['friday','Sexta'],['saturday','Sábado'],['sunday','Domingo']]:[['monday','Monday'],['tuesday','Tuesday'],['wednesday','Wednesday'],['thursday','Thursday'],['friday','Friday'],['saturday','Saturday'],['sunday','Sunday']])],
                ['id'=>'preferred_time','type'=>'text','label'=>$pt?'Quais horários costumam funcionar melhor?':'Which times usually work best?','required'=>false,'width'=>'full'],
                ['id'=>'notes','type'=>'textarea','label'=>$pt?'Há alguma dúvida ou necessidade que eu deva considerar?':'Is there anything I should know before contacting you?','required'=>false,'rows'=>4,'width'=>'full'],
                ['id'=>'consent','type'=>'consent','label'=>$pt?'Concordo em receber informações relacionadas a esta inscrição.':'I agree to receive information related to this registration.','required'=>true,'width'=>'full'],
            ],
        ];
    }
    return [
        'version'=>1,
        'submitLabel'=>$pt?'Enviar meu interesse':'Send my interest',
        'successTitle'=>$pt?'Interesse registrado':'Interest registered',
        'successMessage'=>$pt?'Obrigado. Vou usar estas respostas para organizar a próxima turma.':'Thank you. I will use these responses to organize the first English-language cohort.',
        'fields'=>[
            ['id'=>'name','type'=>'text','label'=>$pt?'Nome':'Name','required'=>true,'autocomplete'=>'name','width'=>'half'],
            ['id'=>'email','type'=>'email','label'=>$pt?'E-mail':'Email','required'=>true,'autocomplete'=>'email','width'=>'half'],
            ['id'=>'country','type'=>'text','label'=>$pt?'País':'Country','required'=>!$pt,'autocomplete'=>'country-name','width'=>'half'],
            ['id'=>'timezone','type'=>'text','label'=>$pt?'Fuso horário':'Time zone','required'=>!$pt,'placeholder'=>$pt?'Ex.: America/Sao_Paulo':'e.g. Europe/London, UTC−5','width'=>'half'],
            ['id'=>'experience','type'=>'textarea','label'=>$pt?'Qual é a sua experiência com fotografia analógica?':'Previous experience','required'=>false,'rows'=>3,'width'=>'full'],
            ['id'=>'preferred_days','type'=>'text','label'=>$pt?'Dias preferidos':'Preferred days','required'=>false,'width'=>'half'],
            ['id'=>'preferred_time','type'=>'text','label'=>$pt?'Horários preferidos':'Preferred time','required'=>false,'width'=>'half'],
            ['id'=>'price_response','type'=>'radio','label'=>$pt?'Você consideraria participar pelo valor apresentado?':'Would you seriously consider joining at the stated price?','required'=>true,'width'=>'full','options'=>[
                ['value'=>'yes','label'=>$pt?'Sim':'Yes'],['value'=>'maybe','label'=>$pt?'Talvez — depende das datas':'Maybe — it depends on the dates'],['value'=>'no','label'=>$pt?'Não neste valor':'No — not at this price'],
            ]],
            ['id'=>'main_interest','type'=>'textarea','label'=>$pt?'O que você mais gostaria de aprender ou resolver?':'What would you most like to learn or solve?','required'=>false,'rows'=>5,'width'=>'full'],
            ['id'=>'consent','type'=>'consent','label'=>$pt?'Quero receber informações sobre este workshop.':'I agree to receive information about this workshop.','required'=>true,'width'=>'full'],
        ],
    ];
}
function cms_validate_form_schema(array $schema): array {
    $schema['version']=1;
    $schema['submitLabel']=trim((string)($schema['submitLabel']??'Enviar'))?:'Enviar';
    $schema['successTitle']=trim((string)($schema['successTitle']??'Obrigado'))?:'Obrigado';
    $schema['successMessage']=trim((string)($schema['successMessage']??''));
    $fields=[];$ids=[];
    foreach(($schema['fields']??[]) as $raw){
        if(!is_array($raw))continue;
        $id=cms_form_field_id((string)($raw['id']??$raw['label']??''));
        if(isset($ids[$id]))$id.='_'.(count($fields)+1);
        $ids[$id]=true;
        $type=(string)($raw['type']??'text');if(!in_array($type,CMS_FORM_TYPES,true))$type='text';
        $field=['id'=>$id,'type'=>$type,'label'=>trim((string)($raw['label']??$id)),'required'=>!empty($raw['required']),'width'=>in_array($raw['width']??'full',['full','half'],true)?$raw['width']:'full'];
        foreach(['placeholder','help','autocomplete'] as $key)if(isset($raw[$key]))$field[$key]=trim((string)$raw[$key]);
        if($type==='textarea')$field['rows']=max(2,min(12,(int)($raw['rows']??4)));
        if(in_array($type,['select','radio','checkbox-group'],true)){
            $field['options']=[];
            foreach(($raw['options']??[]) as $option){if(!is_array($option))continue;$value=trim((string)($option['value']??''));$label=trim((string)($option['label']??$value));if($value!==''&&$label!=='')$field['options'][]=['value'=>$value,'label'=>$label];}
        }
        if($field['label']==='')$field['label']=$id;
        $fields[]=$field;
    }
    $schema['fields']=$fields;
    return $schema;
}
function cms_forms_seed(PDO $db,int $activityId): void {
    $q=$db->prepare("SELECT COUNT(*) FROM cms_forms WHERE activity_id=? AND status!='archived'");$q->execute([$activityId]);if((int)$q->fetchColumn()>0)return;
    $now=utc_now();$seeds=[[PUBLIC_LOCALE_PT_BR,'registration','Inscrição — nova turma',cms_form_schema_default(PUBLIC_LOCALE_PT_BR,'registration')],[PUBLIC_LOCALE_EN,'interest','Interest survey — English cohort',cms_form_schema_default(PUBLIC_LOCALE_EN,'interest')]];
    $insert=$db->prepare('INSERT INTO cms_forms(form_uuid,activity_id,locale,form_key,title,status,draft_schema_json,published_schema_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at)VALUES(?,?,?,?,?,"active",?,?,1,1,?,?,?,?)');
    foreach($seeds as [$locale,$key,$title,$schema]){$json=json_encode(cms_validate_form_schema($schema),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$insert->execute([cms_form_uuid(),$activityId,$locale,$key,$title,$json,$json,$now,$now,$now,$now]);}
}
function cms_forms(PDO $db,int $activityId,?string $locale=null,bool $includeArchived=false): array {cms_forms_seed($db,$activityId);$sql='SELECT * FROM cms_forms WHERE activity_id=?';$args=[$activityId];if($locale!==null){$sql.=' AND locale=?';$args[]=$locale;}if(!$includeArchived)$sql.=" AND status!='archived'";$sql.=' ORDER BY locale,title,id';$q=$db->prepare($sql);$q->execute($args);return $q->fetchAll();}
function cms_form_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM cms_forms WHERE id=?');$q->execute([$id]);return $q->fetch()?:null;}
function cms_form_by_uuid(PDO $db,string $uuid): ?array {$q=$db->prepare("SELECT * FROM cms_forms WHERE form_uuid=? AND status!='archived'");$q->execute([$uuid]);return $q->fetch()?:null;}
function cms_form_by_key(PDO $db,int $activityId,string $locale,string $key): ?array {cms_forms_seed($db,$activityId);$q=$db->prepare("SELECT * FROM cms_forms WHERE activity_id=? AND locale=? AND form_key=? AND status!='archived'");$q->execute([$activityId,$locale,$key]);return $q->fetch()?:null;}
function cms_form_schema(array $form,bool $published=true): array {$json=$published?($form['published_schema_json']??null):($form['draft_schema_json']??null);if(!$json)$json=$form['draft_schema_json']??'{}';$schema=json_decode((string)$json,true);return cms_validate_form_schema(is_array($schema)?$schema:[]);}
function cms_form_create(PDO $db,int $activityId,string $locale,string $title,string $key,string $kind='interest'): array {$locale=normalize_public_locale($locale)??PUBLIC_LOCALE_EN;$key=activity_slug($key);$base=$key;$n=2;while(cms_form_by_key($db,$activityId,$locale,$key))$key=$base.'-'.$n++;$schema=cms_validate_form_schema(cms_form_schema_default($locale,$kind));$json=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$now=utc_now();$q=$db->prepare('INSERT INTO cms_forms(form_uuid,activity_id,locale,form_key,title,status,draft_schema_json,published_schema_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at)VALUES(?,?,?,?,?,"active",?,?,1,1,?,?,?,?)');$q->execute([cms_form_uuid(),$activityId,$locale,$key,trim($title)?:$key,$json,$json,$now,$now,$now,$now]);return cms_form_by_id($db,(int)$db->lastInsertId())??throw new RuntimeException('form_create_failed');}
function cms_form_save(PDO $db,int $id,array $schema,int $revision,?string $title=null): array {$form=cms_form_by_id($db,$id)??throw new RuntimeException('form_not_found');$schema=cms_validate_form_schema($schema);$json=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$new=$revision+1;$now=utc_now();$q=$db->prepare('UPDATE cms_forms SET title=COALESCE(?,title),draft_schema_json=?,draft_revision=?,draft_updated_at=?,updated_at=? WHERE id=? AND draft_revision=?');$q->execute([$title===null?null:trim($title),$json,$new,$now,$now,$id,$revision]);if($q->rowCount()!==1)throw new RuntimeException('revision_conflict');return cms_form_by_id($db,$id)??throw new RuntimeException('form_not_found');}
function cms_form_publish(PDO $db,int $id): array {$form=cms_form_by_id($db,$id)??throw new RuntimeException('form_not_found');$now=utc_now();$q=$db->prepare('UPDATE cms_forms SET published_schema_json=draft_schema_json,published_revision=draft_revision,published_at=?,updated_at=? WHERE id=?');$q->execute([$now,$now,$id]);return cms_form_by_id($db,$id)??throw new RuntimeException('form_not_found');}
function cms_form_archive(PDO $db,int $id): void {$now=utc_now();$q=$db->prepare("UPDATE cms_forms SET status='archived',updated_at=? WHERE id=?");$q->execute([$now,$id]);}
function cms_form_field_value(array $field,array $input): mixed {$id=$field['id'];$type=$field['type'];$value=$input[$id]??($type==='checkbox-group'?[]:'');if($type==='checkbox-group'){if(!is_array($value))$value=[];return array_values(array_map('strval',$value));}if(in_array($type,['checkbox','consent'],true))return !empty($value)?'1':'';return is_array($value)?'':trim((string)$value);}
function cms_form_validate_submission(array $schema,array $input,string $locale=PUBLIC_LOCALE_PT_BR): array {
    $values=[];$errors=[];
    foreach($schema['fields'] as $field){$id=$field['id'];$type=$field['type'];$value=cms_form_field_value($field,$input);$values[$id]=$value;$empty=is_array($value)?count($value)===0:$value==='';if(!empty($field['required'])&&$empty){$errors[$id]=cms_form_message($locale,'required');continue;}if($type==='email'&&!$empty&&!filter_var($value,FILTER_VALIDATE_EMAIL))$errors[$id]=cms_form_message($locale,'email');if(in_array($type,['select','radio'],true)&&!$empty){$allowed=array_column($field['options']??[],'value');if(!in_array($value,$allowed,true))$errors[$id]=cms_form_message($locale,'invalid');}if($type==='checkbox-group'&&!$empty){$allowed=array_column($field['options']??[],'value');foreach($value as $item)if(!in_array($item,$allowed,true)){$errors[$id]=cms_form_message($locale,'invalid');break;}}if(is_string($value)&&strlen($value)>12000)$errors[$id]=cms_form_message($locale,'long');}
    return [$values,$errors];
}
function cms_submission_save(PDO $db,array $form,array $schema,array $values,?int $pageId,string $locale,string $sourceUrl=''): string {$config=app_config();$ip=hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'',$config['ip_hash_secret']);$ua=hash_hmac('sha256',$_SERVER['HTTP_USER_AGENT']??'',$config['app_secret']);$uuid=cms_form_uuid();$now=utc_now();$db->beginTransaction();try{rate_limit_or_throw($db,$ip,$config);$q=$db->prepare('INSERT INTO cms_form_submissions(submission_uuid,form_id,page_id,activity_id,locale,payload_json,form_snapshot_json,source_url,status,notes,consent,ip_hash,user_agent_hash,created_at,updated_at)VALUES(?,?,?,?,?,?,?,?,"new","",?,?,?,?,?)');$consent=0;foreach($schema['fields'] as $field)if($field['type']==='consent'&&!empty($values[$field['id']])){$consent=1;break;}$q->execute([$uuid,(int)$form['id'],$pageId,(int)$form['activity_id'],$locale,json_encode($values,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),substr($sourceUrl,0,1000),$consent,$ip,$ua,$now,$now]);$db->commit();return $uuid;}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}}
function cms_form_input_html(array $field,mixed $value=null): string {
    $id=h($field['id']);$label=h($field['label']);$required=!empty($field['required']);$req=$required?' required':'';$star=$required?' <span aria-hidden="true">*</span>':'';$help=!empty($field['help'])?'<small>'.h($field['help']).'</small>':'';$width=($field['width']??'full')==='half'?' half':'';$type=$field['type'];$name=h($field['id']);$placeholder=isset($field['placeholder'])?' placeholder="'.h($field['placeholder']).'"':'';$autocomplete=isset($field['autocomplete'])?' autocomplete="'.h($field['autocomplete']).'"':'';
    if($type==='textarea')return '<label class="cms-field'.$width.'"><span>'.$label.$star.'</span><textarea name="'.$name.'" rows="'.(int)($field['rows']??4).'"'.$placeholder.$req.'>'.h((string)$value).'</textarea>'.$help.'</label>';
    if($type==='select'){$html='<label class="cms-field'.$width.'"><span>'.$label.$star.'</span><select name="'.$name.'"'.$req.'><option value=""></option>';foreach($field['options']??[] as $option)$html.='<option value="'.h($option['value']).'"'.((string)$value===(string)$option['value']?' selected':'').'>'.h($option['label']).'</option>';$html.='</select>'.$help.'</label>';return $html;}
    if(in_array($type,['radio','checkbox-group'],true)){$inputType=$type==='radio'?'radio':'checkbox';$nameAttr=$type==='radio'?$name:$name.'[]';$selected=is_array($value)?$value:[$value];$groupReq=$type==='radio'?$req:'';$ariaRequired=$required?' aria-required="true"':'';$html='<fieldset class="cms-choice-group'.$width.'"'.$ariaRequired.'><legend>'.$label.$star.'</legend><div class="cms-choice-grid">';foreach($field['options']??[] as $option){$checked=in_array((string)$option['value'],array_map('strval',$selected),true)?' checked':'';$html.='<label><input type="'.$inputType.'" name="'.$nameAttr.'" value="'.h($option['value']).'"'.$checked.$groupReq.'><span>'.h($option['label']).'</span></label>';}$html.='</div>'.$help.'</fieldset>';return $html;}
    if(in_array($type,['checkbox','consent'],true))return '<label class="cms-consent'.$width.'"><input type="checkbox" name="'.$name.'" value="1"'.(!empty($value)?' checked':'').$req.'><span>'.$label.$star.'</span></label>';
    $htmlType=in_array($type,['email','tel','number','date','time'],true)?$type:'text';return '<label class="cms-field'.$width.'"><span>'.$label.$star.'</span><input type="'.$htmlType.'" name="'.$name.'" value="'.h((string)$value).'"'.$placeholder.$autocomplete.$req.'>'.$help.'</label>';
}
function cms_render_form(array $form,array $schema,int $pageId,string $locale,array $values=[],array $errors=[],bool $success=false,bool $editor=false): string {
    $schema=cms_validate_form_schema($schema);$formId=(int)$form['id'];$anchor='form-'.$formId;if($success)return '<div class="cms-form-success" id="'.$anchor.'"><p class="section-label">'.h($form['title']).'</p><h2>'.h($schema['successTitle']).'</h2><p>'.h($schema['successMessage']).'</p></div>';
    $html='<form class="cms-form" id="'.$anchor.'" action="/form-submit.php" method="post"'.($editor?' data-cms-form-preview="1"':'').'>';
    if(!$editor){$return=parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH)?:'/';$query=parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_QUERY);if(is_string($query)&&$query!=='')$return.='?'.$query;$html.='<input type="hidden" name="_csrf" value="'.h(csrf_token('cms-form')).'"><input type="hidden" name="_form_uuid" value="'.h($form['form_uuid']).'"><input type="hidden" name="_page_id" value="'.$pageId.'"><input type="hidden" name="_locale" value="'.h($locale).'"><input type="hidden" name="_return" value="'.h($return).'"><input type="hidden" name="_started_at" value="'.time().'"><label class="honeypot" aria-hidden="true">Leave blank<input name="_website" tabindex="-1" autocomplete="off"></label>';}
    $html.='<div class="cms-form-grid">';foreach($schema['fields'] as $field){$html.=cms_form_input_html($field,$values[$field['id']]??null);if(isset($errors[$field['id']]))$html.='<p class="form-error">'.h($errors[$field['id']]).'</p>';}$html.='</div><button class="button" type="'.($editor?'button':'submit').'">'.h($schema['submitLabel']).' <span aria-hidden="true">↗</span></button></form>';return $html;
}
