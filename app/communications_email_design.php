<?php
declare(strict_types=1);
/** Canonical e-mail rendering; media may only come from the public CMS library. */
function communication_template_keys():array {
    return ['minimal'=>'Essencial','editorial'=>'Editorial','destaque'=>'Destaque'];
}
function communication_template_key(string $key):string {
    if(!array_key_exists($key,communication_template_keys()))throw new RuntimeException('Modelo de e-mail inválido.');
    return $key;
}
function communication_media_asset(PDO $db,int $id):array {
    $q=$db->prepare("SELECT a.id,a.kind,a.visibility,a.archived_at,a.processing_status,a.active_version_id,
         a.title,a.default_alt,a.width,v.original_path
         FROM media_assets a JOIN media_versions v ON v.id=a.active_version_id
         WHERE a.id=? AND a.kind='image' AND a.visibility='public'
           AND a.archived_at IS NULL AND a.processing_status='ready' AND v.processing_status='ready'");
    $q->execute([$id]);$asset=$q->fetch(PDO::FETCH_ASSOC);
    if(!is_array($asset))throw new RuntimeException('Uma imagem do e-mail não está disponível na Biblioteca pública.');
    $q=$db->prepare("SELECT path FROM media_derivatives WHERE version_id=?
         AND mime_type IN ('image/jpeg','image/png') AND width<=1024
         ORDER BY CASE WHEN mime_type='image/jpeg' THEN 0 ELSE 1 END,width DESC LIMIT 1");
    $q->execute([(int)$asset['active_version_id']]);
    $derivative=$q->fetchColumn();
    $asset['email_path']=(string)($derivative?:$asset['original_path']);
    $asset['display_width']=max(1,min(600,(int)($asset['width']??600)));
    return $asset;
}
function communication_media_tokens(string $html):array {
    preg_match_all('/<figure data-email-media-id="([0-9]+)" data-email-alt="([^"]+)"><\/figure>/',$html,$matches,PREG_SET_ORDER);
    if(count($matches)>12)throw new RuntimeException('Use no máximo 12 imagens no e-mail.');
    return $matches;
}
function communication_validate_images(PDO $db,string $html):void {
    foreach(communication_media_tokens($html) as $token)communication_media_asset($db,(int)$token[1]);
}
function communication_media_choices(PDO $db):array {
    $q=$db->query("SELECT id,title,default_alt FROM media_assets
        WHERE kind='image' AND visibility='public' AND archived_at IS NULL AND processing_status='ready'
        ORDER BY id DESC LIMIT 150");
    $result=[];
    $canonicalOrigin=communication_site_url($db);
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
        try{
            $asset=communication_media_asset($db,(int)$row['id']);
            $result[]=['id'=>(int)$row['id'],'title'=>(string)$row['title'],
                'alt'=>(string)($row['default_alt']??''),
                'src'=>media_url((string)$asset['email_path']),
                'canonical_src'=>$canonicalOrigin!==''?$canonicalOrigin.media_url((string)$asset['email_path']):''];
        }catch(Throwable){}
    }
    return $result;
}
function communication_render_media(PDO $db,string $html):string {
    if(!str_contains($html,'data-email-media-id'))return $html;
    $origin=communication_site_url($db);
    if($origin==='')throw new RuntimeException('Defina o endereço HTTPS do site antes de enviar imagens.');
    return preg_replace_callback('/<figure data-email-media-id="([0-9]+)" data-email-alt="([^"]+)"><\/figure>/',
      static function(array $m)use($db,$origin):string {
          $asset=communication_media_asset($db,(int)$m[1]);
          $url=$origin.media_url((string)$asset['email_path']);
          $src=htmlspecialchars($url,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
          $alt=htmlspecialchars(html_entity_decode($m[2],ENT_QUOTES|ENT_HTML5,'UTF-8'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
          $width=(int)$asset['display_width'];
          return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:22px 0"><tr><td>'
            .'<img src="'.$src.'" width="'.$width.'" alt="'.$alt.'" style="display:block;width:100%;max-width:'.$width.'px;height:auto;border:0">'
            .'</td></tr></table>';
      },$html)??$html;
}
function communication_email_brand(?PDO $db,int $activityId):array {
    $brand=['name'=>(string)(app_config()['site_name']??'Fotografia'),'ink'=>'#0b0c0d',
      'accent'=>'#186f4d','bg'=>'#f2f2ef','muted'=>'#5f6264'];
    if($db===null||$activityId<1)return $brand;
    try{
        $site=cms_site_settings($db,$activityId,'pt-BR');
        $design=cms_design_settings($db,$activityId,'pt-BR');
        $brand['name']=(string)($site['wordmark']?:$site['siteName']?:$brand['name']);
        foreach(['ink'=>'text','accent'=>'accent','bg'=>'bg','muted'=>'muted'] as $key=>$field){
            $color=(string)($design['colors'][$field]??'');
            if(preg_match('/^#[a-f0-9]{6}$/i',$color))$brand[$key]=$color;
        }
    }catch(Throwable){}
    return $brand;
}
function communication_email_document(string $html,string $plain,?PDO $db=null,string $template='minimal',int $activityId=0):string {
    $template=communication_template_key($template);
    $inner=$html!==''?communication_safe_html($html):nl2br(htmlspecialchars($plain,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'));
    if($db)$inner=communication_render_media($db,$inner);
    $brand=communication_email_brand($db,$activityId);
    $name=htmlspecialchars($brand['name'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $ink=$brand['ink'];$accent=$brand['accent'];$bg=$brand['bg'];$muted=$brand['muted'];
    $inner=str_replace('<hr>','<hr style="border:0;border-top:1px solid #d5d7d4;margin:25px 0">',$inner);
    $inner=str_replace(' data-email-button="1"',' style="display:inline-block;background:'.$accent.';color:#ffffff;padding:12px 20px;text-decoration:none;font:bold 14px Arial,sans-serif"',$inner);
    $ground=$template==='minimal'?'#ffffff':$bg;
    $mast=$template==='destaque'
      ?'<tr><td style="padding:32px 36px;background:'.$ink.';color:#ffffff;font:bold 19px Arial,sans-serif">'.$name.'</td></tr>'
      :'<tr><td style="padding:28px 36px 20px;border-bottom:'.($template==='editorial'?'4px solid '.$accent:'1px solid #d5d7d4').';font:bold 18px Arial,sans-serif;color:'.$ink.'">'.$name.'</td></tr>';
    return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
       .'<body style="margin:0;padding:0;background:'.$ground.'"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:'.$ground.'">'
       .'<tr><td align="center" style="padding:20px 10px"><table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;border-collapse:collapse;background:#ffffff">'
       .$mast.'<tr><td style="padding:34px 36px 40px;font:16px/1.65 Arial,Helvetica,sans-serif;color:'.$ink.';word-wrap:break-word">'
       .$inner.'</td></tr><tr><td style="padding:20px 36px 28px;border-top:1px solid #d5d7d4;font:12px/1.6 Arial,sans-serif;color:'.$muted.'">'
       .$name.'</td></tr></table></td></tr></table></body></html>';
}
