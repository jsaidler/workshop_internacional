<?php
declare(strict_types=1);

function cms_page_url(array $activity,array $page,string $locale): string {
    $lang='lang='.rawurlencode(public_locale_query($locale));
    if((int)($activity['is_root']??0)===1){
        if((int)$page['is_home']===1||$page['slug']==='')return '/?'.$lang;
        if($page['slug']==='inscricao')return '/inscricao/?'.$lang;
        return '/?page='.rawurlencode((string)$page['slug']).'&'.$lang;
    }
    $base='/'.rawurlencode((string)$activity['slug']).'/';
    if((int)$page['is_home']!==1&&$page['slug']!=='')$base.=rawurlencode((string)$page['slug']).'/';
    return $base.'?'.$lang;
}
function cms_current_page(PDO $db,array $activity,string $locale): ?array {
    $slug=is_string($_GET['page']??null)?(string)$_GET['page']:'';
    return $slug===''?cms_page_home($db,(int)$activity['id'],$locale):cms_page_by_slug($db,(int)$activity['id'],$locale,$slug);
}
function cms_language_switch_url(PDO $db,array $activity,array $page,string $targetLocale): string {
    $candidate=(int)$page['is_home']===1?cms_page_home($db,(int)$activity['id'],$targetLocale):cms_page_by_slug($db,(int)$activity['id'],$targetLocale,(string)$page['slug']);
    if(!$candidate)$candidate=cms_page_home($db,(int)$activity['id'],$targetLocale);
    return $candidate?cms_page_url($activity,$candidate,$targetLocale):'/?lang='.rawurlencode(public_locale_query($targetLocale));
}
function cms_navigation_entries(PDO $db,array $activity,string $locale,array $site): array {
    $configured=is_array($site['navigation']['items']??null)?$site['navigation']['items']:[];$out=[];
    if($configured){
        foreach($configured as $item){
            if(!is_array($item))continue;$type=($item['type']??'page')==='custom'?'custom':'page';
            if($type==='page'){
                $p=cms_page_by_id($db,(int)($item['pageId']??0));if(!$p||(int)$p['activity_id']!==(int)$activity['id']||$p['locale']!==$locale||$p['status']==='archived')continue;
                $out[]=['type'=>'page','pageId'=>(int)$p['id'],'label'=>trim((string)($item['label']??''))?:$p['nav_title'],'url'=>cms_page_url($activity,$p,$locale),'newTab'=>!empty($item['newTab'])];
            }else{
                $label=trim((string)($item['label']??''));$url=trim((string)($item['url']??''));if($label===''||$url==='')continue;
                $out[]=['type'=>'custom','pageId'=>0,'label'=>$label,'url'=>$url,'newTab'=>!empty($item['newTab'])];
            }
        }
        return $out;
    }
    foreach(cms_nav_pages($db,(int)$activity['id'],$locale) as $p)$out[]=['type'=>'page','pageId'=>(int)$p['id'],'label'=>$p['nav_title'],'url'=>cms_page_url($activity,$p,$locale),'newTab'=>false];
    return $out;
}
function cms_form_flash_take(string $formUuid): array {
    $flash=$_SESSION['cms_form_flash'][$formUuid]??[];unset($_SESSION['cms_form_flash'][$formUuid]);return is_array($flash)?$flash:[];
}
function cms_public_asset_version(): string {
    static $version=null;
    if(is_string($version))return $version;
    $root=dirname(__DIR__);$info=$root.'/deploy-info.json';
    if(is_file($info)){
        $decoded=json_decode((string)@file_get_contents($info),true);
        $sha=is_array($decoded)?trim((string)($decoded['sourceSha']??'')):'';
        if($sha!==''&&preg_match('/^[a-f0-9]{7,64}$/i',$sha))return $version=substr($sha,0,16);
    }
    $mtime=@filemtime($root.'/assets/cms-v3.css');
    return $version=$mtime!==false?(string)$mtime:'1';
}
function cms_public_system_css_imports(string $assetVersion): string {
    $version=rawurlencode($assetVersion);
    $paths=['/template/page.css','/assets/cms.css','/assets/cms-v3.css','/assets/cms-pro.css','/assets/cms-responsive.css','/assets/cms-header.css'];
    $css=cms_design_font_import_css();foreach($paths as $path)$css.='@import url("'.$path.'?v='.$version.'") layer(cms-system);';
    return $css;
}
function cms_dom_set_inner_html(DOMDocument $targetDocument,DOMElement $target,string $html): void {
    while($target->firstChild)$target->removeChild($target->firstChild);if($html==='')return;
    $previous=libxml_use_internal_errors(true);$source=new DOMDocument('1.0','UTF-8');$source->loadHTML('<?xml encoding="utf-8" ?><div id="cms-fragment">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$wrapper=$source->getElementById('cms-fragment');if($wrapper)foreach(iterator_to_array($wrapper->childNodes) as $child)$target->appendChild($targetDocument->importNode($child,true));libxml_clear_errors();libxml_use_internal_errors($previous);
}
function cms_expand_forms(string $html,PDO $db,array $activity,array $page,string $locale,bool $editor=false): string {
    if(!str_contains($html,'data-cms-form-key'))return $html;
    $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="cms-render-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);$nodes=iterator_to_array($xpath->query('//*[@data-cms-form-key]')?:[]);
    foreach($nodes as $node){if(!$node instanceof DOMElement)continue;$key=trim($node->getAttribute('data-cms-form-key'));if($key==='')continue;$form=cms_form_by_key($db,(int)$activity['id'],$locale,$key);if(!$form){$node->setAttribute('class',trim($node->getAttribute('class').' cms-form-missing'));cms_dom_set_inner_html($dom,$node,'Form not configured: '.h($key));continue;}$schema=cms_form_schema($form,!$editor);$flash=$editor?[]:cms_form_flash_take((string)$form['form_uuid']);$rendered=cms_render_form($form,$schema,(int)$page['id'],$locale,is_array($flash['values']??null)?$flash['values']:[],is_array($flash['errors']??null)?$flash['errors']:[],!empty($flash['success']),$editor);$node->setAttribute('class',trim($node->getAttribute('class').' cms-form-block'));$node->setAttribute('data-cms-form-block',(string)(int)$form['id']);cms_dom_set_inner_html($dom,$node,$rendered);}
    $root=$dom->getElementById('cms-render-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);libxml_clear_errors();libxml_use_internal_errors($previous);return $out;
}
function cms_render_public_page(array $activity,array $page,array $document,bool $editor=false): void {
    $db=database();$locale=(string)$page['locale'];$document=cms_page_document($document);$site=cms_site_settings($db,(int)$activity['id'],$locale);$design=cms_design_settings($db,(int)$activity['id'],$locale);$seo=cms_page_seo($db,(int)$page['id']);
    $theme=$document['theme']==='auto'?'':' data-theme="'.h($document['theme']).'"';
    $title=$seo['title']!==''?$seo['title']:($document['meta']['title']!==''?$document['meta']['title']:($site['seo']['defaultTitle']?:$page['title']));
    $description=$seo['description']!==''?$seo['description']:($document['meta']['description']!==''?$document['meta']['description']:(string)($site['seo']['defaultDescription']??''));
    $socialTitle=$seo['socialTitle']!==''?$seo['socialTitle']:$title;$socialDescription=$seo['socialDescription']!==''?$seo['socialDescription']:$description;
    $socialImage=cms_absolute_url($seo['socialImage']!==''?$seo['socialImage']:(string)($site['seo']['socialImage']??''));
    $canonical=cms_absolute_url($seo['canonicalUrl']!==''?$seo['canonicalUrl']:cms_page_url($activity,$page,$locale));$robots=$editor?'noindex,nofollow':$seo['robots'];
    $nav=cms_navigation_entries($db,$activity,$locale,$site);$home=cms_page_home($db,(int)$activity['id'],$locale);$brandUrl=$home?cms_page_url($activity,$home,$locale):'/';$brand=(string)($site['wordmark']?:($home?$home['title']:$activity['public_title']));$otherLocale=$locale===PUBLIC_LOCALE_PT_BR?PUBLIC_LOCALE_EN:PUBLIC_LOCALE_PT_BR;$otherLabel=$otherLocale===PUBLIC_LOCALE_PT_BR?'PT':'EN';$langUrl=cms_language_switch_url($db,$activity,$page,$otherLocale);$absoluteLangUrl=cms_absolute_url($langUrl);
    // Expand forms before media resolution so editorial media stored inside
    // form content blocks (for example the Pix QR) follows the same canonical
    // asset/version resolution as images that live directly in page HTML.
    $body=cms_expand_forms((string)$document['html'],$db,$activity,$page,$locale,$editor);$body=media_resolve_cms_html($db,$body);$body=media_resolve_cms_video_html($db,$body);
    $header=$site['header'];$footer=$site['footer'];$headerClass=!empty($header['sticky'])?'topbar cms-topbar':'topbar cms-topbar cms-topbar-static';$menuLabel='Menu';$assetVersion=cms_public_asset_version();$assetVersionHtml=h($assetVersion);
    header('Content-Type: text/html; charset=UTF-8');header('Content-Language: '.$locale);header('Vary: Accept-Language',false);
    ?><!doctype html><html lang="<?=h($locale)?>"<?=$theme?>><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($title)?></title><meta name="robots" content="<?=h($robots)?>"><?php if($description!==''):?><meta name="description" content="<?=h($description)?>"><?php endif;?><meta property="og:type" content="website"><meta property="og:title" content="<?=h($socialTitle)?>"><?php if($socialDescription!==''):?><meta property="og:description" content="<?=h($socialDescription)?>"><?php endif;?><?php if($canonical!==''):?><link rel="canonical" href="<?=h($canonical)?>"><meta property="og:url" content="<?=h($canonical)?>"><?php endif;?><?php if($socialImage!==''):?><meta property="og:image" content="<?=h($socialImage)?>"><meta name="twitter:image" content="<?=h($socialImage)?>"><?php endif;?><meta name="twitter:card" content="<?=$socialImage!==''?'summary_large_image':'summary'?>"><meta name="twitter:title" content="<?=h($socialTitle)?>"><?php if($socialDescription!==''):?><meta name="twitter:description" content="<?=h($socialDescription)?>"><?php endif;?><link rel="alternate" hreflang="<?=h($otherLocale)?>" href="<?=h($absoluteLangUrl)?>"><?php if(!empty($site['seo']['favicon'])):?><link rel="icon" href="<?=h($site['seo']['favicon'])?>"><?php endif;?><style id="cms-system-styles" data-cms-responsive><?=cms_public_system_css_imports($assetVersion)?></style><style id="cms-design-vars">@layer cms-system{<?=cms_design_system_css($design)?>}</style><style id="cms-system-choice-controls">@layer cms-system{body.cms-public input[type="checkbox"],body.cms-public input[type="radio"]{box-sizing:border-box;width:18px;height:18px;min-width:18px;min-height:18px;max-width:18px;max-height:18px;padding:0;margin:2px 0 0;flex:0 0 18px;align-self:flex-start;box-shadow:none}}</style><style id="cms-custom-css"><?=cms_design_custom_css($design)?></style></head><body class="cms-public<?=$editor?' cms-editor-preview':''?>" data-cms-page-id="<?=(int)$page['id']?>" data-cms-locale="<?=h($locale)?>"><a class="skip-link" href="#main"><?=$locale===PUBLIC_LOCALE_PT_BR?'Pular para o conteúdo':'Skip to content'?></a><header class="<?=h($headerClass)?>" data-cms-public-header><a class="brand" href="<?=h($brandUrl)?>"><?=h($brand)?></a><button class="cms-nav-toggle" type="button" aria-expanded="false" aria-controls="cms-primary-nav"><?=h($menuLabel)?></button><nav id="cms-primary-nav" aria-label="<?=$locale===PUBLIC_LOCALE_PT_BR?'Navegação principal':'Primary navigation'?>"><?php foreach($nav as $item):?><a href="<?=h((string)$item['url'])?>"<?=($item['type']==='page'&&(int)$item['pageId']===(int)$page['id'])?' aria-current="page"':''?><?=!empty($item['newTab'])?' target="_blank" rel="noopener"':''?>><?=h((string)$item['label'])?></a><?php endforeach;?></nav><div class="cms-topbar-actions"><?php if(!empty($header['ctaLabel'])&&!empty($header['ctaUrl'])):?><a class="button cms-header-cta" href="<?=h($header['ctaUrl'])?>"><?=h($header['ctaLabel'])?></a><?php endif;?><?php if(!empty($header['showLanguageSwitch'])):?><a class="cms-language" href="<?=h($langUrl)?>" hreflang="<?=h($otherLocale)?>"><?=$otherLabel?></a><?php endif;?><?php if(!empty($header['showThemeSwitch'])):?><div aria-label="Theme" class="theme-switch"><button aria-pressed="true" data-theme-value="auto" type="button">Auto</button><button aria-pressed="false" data-theme-value="light" type="button">Light</button><button aria-pressed="false" data-theme-value="dark" type="button">Dark</button></div><?php endif;?></div></header><main id="main" data-cms-page-main><?=$body?></main><footer class="cms-footer"><p><?=h((string)$footer['line1'])?></p><p><?=h((string)$footer['line2'])?></p><?php if(!empty($footer['links'])&&is_array($footer['links'])):?><nav><?php foreach($footer['links'] as $link):if(!is_array($link)||empty($link['label'])||empty($link['url']))continue;?><a href="<?=h((string)$link['url'])?>"><?=h((string)$link['label'])?></a><?php endforeach;?></nav><?php endif;?></footer><script defer src="/assets/public.js?v=<?=$assetVersionHtml?>"></script></body></html><?php
}
