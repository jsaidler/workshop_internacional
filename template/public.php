<?php
declare(strict_types=1);

function public_form_error(string $id,array $errors): string {
    return isset($errors[$id])?'<p class="form-error" id="'.$id.'-error">'.h($errors[$id]).'</p>':'';
}

function public_form_markup(array $form,array $values=[],array $errors=[],string $locale=PUBLIC_LOCALE_EN):string {
    ob_start();
    $days=$values['preferred_days']??[];
    $week=$locale===PUBLIC_LOCALE_PT_BR
        ?['monday'=>'Segunda','tuesday'=>'Terça','wednesday'=>'Quarta','thursday'=>'Quinta','friday'=>'Sexta','saturday'=>'Sábado','sunday'=>'Domingo']
        :['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
    $action='/interest-submit.php?activity='.rawurlencode((string)public_activity()['slug']).'&lang='.rawurlencode(public_locale_query($locale));
    $leaveBlank=$locale===PUBLIC_LOCALE_PT_BR?'Deixe este campo em branco':'Leave this field blank';
    $timezonePlaceholder=$locale===PUBLIC_LOCALE_PT_BR?'Selecione seu fuso horário':'Select your time zone';
    ?>
<form action="<?=h($action)?>" class="interest-form" method="post" novalidate
      data-error-summary="<?=h(public_message('form_summary',$locale))?>"
      data-submit-pending="<?=h(public_message('sending',$locale))?>"
      data-submit-failed="<?=h(public_message('form_failed',$locale))?>">
<input type="hidden" name="_csrf" value="<?=h(csrf_token('interest'))?>">
<input type="hidden" name="_started_at" value="<?=time()?>">
<input type="hidden" name="_locale" value="<?=h($locale)?>">
<div class="honeypot" aria-hidden="true"><label><?=h($leaveBlank)?><input name="_website" tabindex="-1" autocomplete="off"></label></div>
<?php if($errors):?><div class="form-error-summary" role="alert" tabindex="-1"><p><?=h(public_message('form_summary',$locale))?></p></div><?php endif; ?>
<?php foreach (interest_form_groups($form,$locale) as $group): if ($group['label'] !== ''): ?><fieldset><legend><?=h($group['label'])?></legend><?php endif;
    foreach ($group['fields'] as $field):
        $id=$field['id'];$type=$field['type'];$value=$values[$id]??'';$invalid=isset($errors[$id]);$described=$invalid?'aria-describedby="'.h($id).'-error" aria-invalid="true"':'';$required=!empty($field['required'])?' required':'';
?>
<?php if ($id==='timezone'): ?>
<label class="public-field"><span><?=h($field['label'])?></span><select name="timezone" data-timezone-select <?=$described?>><option value=""><?=h($timezonePlaceholder)?></option><?php foreach(DateTimeZone::listIdentifiers() as $zone):?><option value="<?=h($zone)?>" <?=$value===$zone?'selected':''?>><?=h($zone)?></option><?php endforeach;?></select></label><?=public_form_error($id,$errors)?>
<?php elseif ($id==='preferred_days'): ?>
<fieldset class="day-question" <?=$invalid?'aria-describedby="preferred_days-error"':''?>><legend><?=h($field['label'])?></legend><div class="day-grid"><?php foreach($week as $day=>$label):?><label><input type="checkbox" name="preferred_days[]" value="<?=h($day)?>" <?=is_array($days)&&in_array($day,$days,true)?'checked':''?>><span><?=h($label)?></span></label><?php endforeach;?></div></fieldset><?=public_form_error($id,$errors)?>
<?php elseif ($id==='preferred_time'): ?>
<label class="public-field"><span><?=h($field['label'])?></span><input type="time" name="preferred_time" value="<?=h($value)?>" <?=$described?>></label><?=public_form_error($id,$errors)?>
<?php elseif ($type==='radio'): ?>
<div class="radio-question"><p><?=h($field['label'])?><?=!empty($field['required'])?' *':''?></p><div class="radio-grid"><?php foreach ($field['options']??[] as $option): $title=$option['title']??null;$description=$option['description']??null;if(!is_string($title)||!is_string($description))[$title,$description]=array_pad(explode(' — ',$option['label']??'',2),2,'');?><label><input type="radio" name="<?=h($id)?>" value="<?=h($option['value'])?>" <?=$value===$option['value']?'checked':''?><?=$required?> <?=$described?>><span><strong><?=h($title)?></strong><?=h($description)?></span></label><?php endforeach;?></div><?=public_form_error($id,$errors)?></div>
<?php elseif (in_array($type,['checkbox','consent'],true)): ?>
<label class="consent"><input type="checkbox" name="<?=h($id)?>" value="1" <?=$value==='1'?'checked':''?><?=$required?> <?=$described?>><span><?=h($field['label'])?><?=!empty($field['required'])?' *':''?></span></label><?=public_form_error($id,$errors)?>
<?php else: ?>
<label class="public-field"><span><?=h($field['label'])?><?=!empty($field['required'])?' *':''?></span><?php if($type==='textarea'):?><textarea name="<?=h($id)?>" rows="<?=h($field['rows']??4)?>"<?=$required?> <?=$described?>><?=h($value)?></textarea><?php else:?><input type="<?=h($type)?>" name="<?=h($id)?>" value="<?=h($value)?>" autocomplete="<?=h($field['autocomplete']??'')?>"<?=$required?> <?=$described?>><?php endif;?></label><?=public_form_error($id,$errors)?>
<?php endif; endforeach; if ($group['label'] !== ''): ?></fieldset><?php endif; endforeach; ?>
<button class="button" type="submit"><?=h($form['submitLabel'])?> <span aria-hidden="true">↗</span></button>
</form>
<?php return (string)ob_get_clean();
}

function public_confirmation_markup(string $locale): string {
    return '<div class="interest-confirmation" role="status" aria-live="polite"><p class="section-label">'.h(public_message('registered',$locale)).'</p><h2 tabindex="-1">'.h(public_message('thanks',$locale)).'</h2><p>'.h(public_message('confirmation',$locale)).'</p><a class="button" href="#top">'.h(public_message('return',$locale)).' <span aria-hidden="true">↗</span></a></div>';
}

function public_apply_workshop_price(string $html,string $locale,string $price): string {
    $title=$locale===PUBLIC_LOCALE_PT_BR?'Preço previsto: '.$price:'Planned price: '.$price;
    $summary=$locale===PUBLIC_LOCALE_PT_BR?'Valor previsto: '.$price:$price.' planned';
    $html=preg_replace_callback('~(<h3\b[^>]*data-workshop-price-title[^>]*>).*?(</h3>)~is',fn(array $match)=>$match[1].h($title).$match[2],$html,1)??$html;
    return preg_replace_callback('~(<span\b[^>]*data-workshop-price-summary[^>]*>).*?(</span>)~is',fn(array $match)=>$match[1].h($summary).$match[2],$html,1)??$html;
}

function public_video_markup(string $id,array $value):string {
    $src=h((string)($value['src']??''));
    $poster=h(media_poster_url(database(),isset($value['mediaAssetId'])?(int)$value['mediaAssetId']:null,isset($value['mediaVersionId'])?(int)$value['mediaVersionId']:null,isset($value['posterId'])?(int)$value['posterId']:null,(string)($value['poster']??'')));
    $attributes=(!empty($value['controls'])?' controls':'').(!empty($value['muted'])?' muted':'').(!empty($value['loop'])?' loop':'').(!empty($value['autoplay'])&&!empty($value['muted'])?' autoplay':'').' playsinline preload="metadata"'.($src===''?' hidden':'');
    return '<video data-editable-video="'.h($id).'"'.$attributes.($poster!==''?' poster="'.$poster.'"':'').'><source src="'.$src.'"></video>';
}

function public_responsive_images(string $html,array $images,string $locale):string {
    foreach($images as $id=>$value){
        if(!is_array($value))continue;
        $source=media_image_sources(database(),isset($value['mediaAssetId'])?(int)$value['mediaAssetId']:null,isset($value['mediaVersionId'])?(int)$value['mediaVersionId']:null,(string)($value['src']??''));
        $x=isset($value['positionX'])?(float)$value['positionX']:(float)explode(' ',(string)($value['objectPosition']??'50% 50%'))[0];
        $y=isset($value['positionY'])?(float)$value['positionY']:(float)(explode(' ',(string)($value['objectPosition']??'50% 50%'))[1]??50);
        $fit=in_array($value['objectFit']??'cover',['cover','contain'],true)?$value['objectFit']:'cover';$zoom=$fit==='contain'?1:max(1,(float)($value['zoom']??1));
        $style='object-fit:'.$fit.';object-position:'.$x.'% '.$y.'%;transform-origin:'.$x.'% '.$y.'%;'.($zoom===1?'':'transform:scale('.$zoom.');clip-path:inset(0);');
        $alt=public_localized_image_alt((string)$id,(string)($value['alt']??''),$locale);
        $html=preg_replace_callback('~<img\b[^>]*data-editable-image="'.preg_quote((string)$id,'~').'"[^>]*>~i',function(array $match)use($source,$style,$alt){$tag=preg_replace('~\s(?:src|srcset|sizes|alt|style)="[^"]*"~i','',$match[0])??$match[0];$attributes=' src="'.h($source['src']).'" alt="'.h($alt).'" style="'.h($style).'" sizes="(max-width: 720px) 100vw, 50vw"';if($source['srcset']!=='')$attributes.=' srcset="'.h($source['srcset']).'"';return rtrim($tag,'>').$attributes.'>';},$html,1)??$html;
    }
    return $html;
}

function public_apply_videos(string $html,array $videos,string $locale):string {
    $html=public_responsive_images($html,public_content()['images']??[],$locale);
    foreach($videos as $id=>$value){
        if(!is_array($value))continue;
        $video=public_video_markup((string)$id,$value);$native='~<video\b[^>]*data-editable-video="'.preg_quote((string)$id,'~').'"[^>]*>.*?</video>~is';$count=0;
        $html=preg_replace($native,$video,$html,1,$count)??$html;
        if($count===0){$wrapper='~(<div\b[^>]*data-editable-video="'.preg_quote((string)$id,'~').'"[^>]*>.*?(</div>)~is';$html=preg_replace($wrapper,'$1'.$video.'$2',$html,1)??$html;}
        if(!empty($value['src'])){$placeholder='~(<div\b[^>]*data-video-placeholder="'.preg_quote((string)$id,'~').'"[^>]*)(>)~i';$html=preg_replace($placeholder,'$1 hidden$2',$html,1)??$html;}
        if(!empty($value['caption'])){$caption='~(<figcaption\b[^>]*data-editable-media-caption="'.preg_quote((string)$id,'~').'"[^>]*>).*?(</figcaption>)~is';$localized=public_localized_media_caption((string)$id,(string)$value['caption'],$locale);$html=preg_replace($caption,'$1'.h($localized).'$2',$html,1)??$html;}
    }
    return $html;
}

function render_public_page(array $values=[],array $errors=[],bool $success=false):void {
    $locale=public_locale();$content=public_content();$price=workshop_price(database(),(int)public_activity()['id'],$locale);$source=file_get_contents(__DIR__.'/index.html');
    if($source===false)throw new RuntimeException('public_template_missing');
    header('Content-Language: '.$locale);header('Vary: Accept-Language',false);
    $html=public_localize_template($source,$locale);
    $html=str_replace(['href="page.css?responsive-framing=3"','src="page.js?responsive-framing=3"','src="page.js"','../assets/'],['href="/template/page.css"','src="/assets/public.js"','src="/assets/public.js"','/assets/'],$html);
    foreach($content['texts'] as $id=>$value){$pattern='~(<([a-z][a-z0-9]*)\b[^>]*data-editable-text="'.preg_quote((string)$id,'~').'"[^>]*>).*?(</\2>)~is';$localized=(string)$id==='hero-price'?$price:public_localized_html((string)$id,(string)$value['html'],$locale);$html=preg_replace_callback($pattern,fn(array $match)=>$match[1].$localized.$match[3],$html,1)??$html;}
    $html=public_apply_workshop_price($html,$locale,$price);
    $html=public_apply_videos($html,$content['videos']??[],$locale);
    if($success){$html=preg_replace('/<form action="#" class="interest-form".*?<\/form>/s',public_confirmation_markup($locale),$html,1)??$html;$html=str_replace('class="interest" data-section="interest-form"','class="interest has-success" data-section="interest-form"',$html);}
    else{$form=public_form_markup(interest_form_definition($locale),$values,$errors,$locale);$html=preg_replace_callback('/<form action="#" class="interest-form".*?<\/form>/s',fn()=>$form,$html,1)??$html;}
    echo $html;
}
