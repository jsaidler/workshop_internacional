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
    $db=database();$locale=(string)$page['locale'];$document=cms_page_document($document);$site=cms_site_settings($db,(int)$activity['id'],$locale);$design=cms_design_settings($db,(int)$activity['id'],$locale);$theme=$document['theme']==='auto'?'':' data-theme="'.h($document['theme']).'"';$title=$document['meta']['title']!==''?$document['meta']['title']:($site['seo']['defaultTitle']?:$page['title']);$description=$document['meta']['description']!==''?$document['meta']['description']:(string)($site['seo']['defaultDescription']??'');$nav=cms_navigation_entries($db,$activity,$locale,$site);$home=cms_page_home($db,(int)$activity['id'],$locale);$brandUrl=$home?cms_page_url($activity,$home,$locale):'/';$brand=(string)($site['wordmark']?:($home?$home['title']:$activity['public_title']));$otherLocale=$locale===PUBLIC_LOCALE_PT_BR?PUBLIC_LOCALE_EN:PUBLIC_LOCALE_PT_BR;$otherLabel=$otherLocale===PUBLIC_LOCALE_PT_BR?'PT':'EN';$langUrl=cms_language_switch_url($db,$activity,$page,$otherLocale);
    $body=media_resolve_cms_html($db,(string)$document['html']);$body=media_resolve_cms_video_html($db,$body);$body=cms_expand_forms($body,$db,$activity,$page,$locale,$editor);
    $header=$site['header'];$footer=$site['footer'];$headerClass=!empty($header['sticky'])?'topbar cms-topbar':'topbar cms-topbar cms-topbar-static';$menuLabel=$locale===PUBLIC_LOCALE_PT_BR?'Menu':'Menu';
    header('Content-Type: text/html; charset=UTF-8');header('Content-Language: '.$locale);header('Vary: Accept-Language',false);
    ?><!doctype html><html lang="<?=h($locale)?>"<?=$theme?>><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($title)?></title><?php if($description!==''):?><meta name="description" content="<?=h($description)?>"><meta property="og:description" content="<?=h($description)?>"><?php endif;?><meta property="og:title" content="<?=h($title)?>"><?php if(!empty($site['seo']['favicon'])):?><link rel="icon" href="<?=h($site['seo']['favicon'])?>"><?php endif;?><?php if(!empty($site['seo']['socialImage'])):?><meta property="og:image" content="<?=h($site['seo']['socialImage'])?>"><?php endif;?><link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/cms.css"><link rel="stylesheet" href="/assets/cms-v3.css"><link rel="stylesheet" href="/assets/cms-pro.css"><link rel="stylesheet" href="/assets/cms-header.css"><style id="cms-design-vars"><?=cms_design_css($design)?></style></head><body class="cms-public<?=$editor?' cms-editor-preview':''?>" data-cms-page-id="<?=(int)$page['id']?>" data-cms-locale="<?=h($locale)?>"><a class="skip-link" href="#main"><?=$locale===PUBLIC_LOCALE_PT_BR?'Pular para o conteúdo':'Skip to content'?></a><header class="<?=h($headerClass)?>" data-cms-public-header><a class="brand" href="<?=h($brandUrl)?>"><?=h($brand)?></a><button class="cms-nav-toggle" type="button" aria-expanded="false" aria-controls="cms-primary-nav"><?=h($menuLabel)?></button><nav id="cms-primary-nav" aria-label="<?=$locale===PUBLIC_LOCALE_PT_BR?'Navegação principal':'Primary navigation'?>"><?php foreach($nav as $item):?><a href="<?=h((string)$item['url'])?>"<?=($item['type']==='page'&&(int)$item['pageId']===(int)$page['id'])?' aria-current="page"':''?><?=!empty($item['newTab'])?' target="_blank" rel="noopener"':''?>><?=h((string)$item['label'])?></a><?php endforeach;?></nav><div class="cms-topbar-actions"><?php if(!empty($header['ctaLabel'])&&!empty($header['ctaUrl'])):?><a class="button cms-header-cta" href="<?=h($header['ctaUrl'])?>"><?=h($header['ctaLabel'])?></a><?php endif;?><?php if(!empty($header['showLanguageSwitch'])):?><a class="cms-language" href="<?=h($langUrl)?>" hreflang="<?=h($otherLocale)?>"><?=$otherLabel?></a><?php endif;?><?php if(!empty($header['showThemeSwitch'])):?><div aria-label="Theme" class="theme-switch"><button aria-pressed="true" data-theme-value="auto" type="button">Auto</button><button aria-pressed="false" data-theme-value="light" type="button">Light</button><button aria-pressed="false" data-theme-value="dark" type="button">Dark</button></div><?php endif;?></div></header><main id="main" data-cms-page-main><?=$body?></main><footer class="cms-footer"><p><?=h((string)$footer['line1'])?></p><p><?=h((string)$footer['line2'])?></p><?php if(!empty($footer['links'])&&is_array($footer['links'])):?><nav><?php foreach($footer['links'] as $link):if(!is_array($link)||empty($link['label'])||empty($link['url']))continue;?><a href="<?=h((string)$link['url'])?>"><?=h((string)$link['label'])?></a><?php endforeach;?></nav><?php endif;?></footer><script defer src="/assets/public.js"></script></body></html><?php
}
