<?php
declare(strict_types=1);

function cms_page_url(array $activity,array $page,string $locale): string {
    $base='/'.rawurlencode((string)$activity['slug']).'/';
    if((int)$page['is_home']!==1&&$page['slug']!=='')$base.=rawurlencode((string)$page['slug']).'/';
    return $base.'?lang='.rawurlencode(public_locale_query($locale));
}
function cms_current_page(PDO $db,array $activity,string $locale): ?array {
    $slug=is_string($_GET['page']??null)?(string)$_GET['page']:'';
    return cms_page_by_slug($db,(int)$activity['id'],$locale,$slug);
}
function cms_language_switch_url(PDO $db,array $activity,array $page,string $targetLocale): string {
    $candidate=(int)$page['is_home']===1?cms_page_home($db,(int)$activity['id'],$targetLocale):cms_page_by_slug($db,(int)$activity['id'],$targetLocale,(string)$page['slug']);
    if(!$candidate)$candidate=cms_page_home($db,(int)$activity['id'],$targetLocale);
    return $candidate?cms_page_url($activity,$candidate,$targetLocale):'/'.rawurlencode((string)$activity['slug']).'/?lang='.rawurlencode(public_locale_query($targetLocale));
}
function cms_form_flash_take(string $formUuid): array {
    $flash=$_SESSION['cms_form_flash'][$formUuid]??[];
    unset($_SESSION['cms_form_flash'][$formUuid]);
    return is_array($flash)?$flash:[];
}
function cms_expand_forms(string $html,PDO $db,array $activity,array $page,string $locale,bool $editor=false): string {
    return preg_replace_callback('~<div\b([^>]*)data-cms-form-key="([^"]+)"([^>]*)>\s*</div>~i',function(array $match)use($db,$activity,$page,$locale,$editor){
        $key=trim($match[2]);$form=cms_form_by_key($db,(int)$activity['id'],$locale,$key);if(!$form)return '<div class="cms-form-missing" data-cms-form-key="'.h($key).'">Form not configured: '.h($key).'</div>';
        $schema=cms_form_schema($form,!$editor);$flash=$editor?[]:cms_form_flash_take((string)$form['form_uuid']);$success=!empty($flash['success']);$values=is_array($flash['values']??null)?$flash['values']:[];$errors=is_array($flash['errors']??null)?$flash['errors']:[];
        $rendered=cms_render_form($form,$schema,(int)$page['id'],$locale,$values,$errors,$success,$editor);
        return '<div class="cms-form-block" data-cms-form-block="'.(int)$form['id'].'" data-cms-form-key="'.h($key).'">'.$rendered.'</div>';
    },$html)??$html;
}
function cms_render_public_page(array $activity,array $page,array $document,bool $editor=false): void {
    $db=database();$locale=(string)$page['locale'];$document=cms_page_document($document);$theme=$document['theme']==='auto'?'':' data-theme="'.h($document['theme']).'"';$title=$document['meta']['title']!==''?$document['meta']['title']:$page['title'];$description=$document['meta']['description'];$nav=cms_nav_pages($db,(int)$activity['id'],$locale);$home=cms_page_home($db,(int)$activity['id'],$locale);$brandUrl=$home?cms_page_url($activity,$home,$locale):'/'.h($activity['slug']).'/';$otherLocale=$locale===PUBLIC_LOCALE_PT_BR?PUBLIC_LOCALE_EN:PUBLIC_LOCALE_PT_BR;$otherLabel=$otherLocale===PUBLIC_LOCALE_PT_BR?'PT':'EN';$langUrl=cms_language_switch_url($db,$activity,$page,$otherLocale);$body=cms_expand_forms($document['html'],$db,$activity,$page,$locale,$editor);
    header('Content-Type: text/html; charset=UTF-8');header('Content-Language: '.$locale);header('Vary: Accept-Language',false);
    ?><!doctype html><html lang="<?=h($locale)?>"<?=$theme?>><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($title)?></title><?php if($description!==''):?><meta name="description" content="<?=h($description)?>"><?php endif;?><link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/cms.css"></head><body class="cms-public<?= $editor?' cms-editor-preview':'' ?>" data-cms-page-id="<?=(int)$page['id']?>" data-cms-locale="<?=h($locale)?>"><a class="skip-link" href="#main"><?= $locale===PUBLIC_LOCALE_PT_BR?'Pular para o conteúdo':'Skip to content' ?></a><header class="topbar cms-topbar"><a class="brand" href="<?=h($brandUrl)?>"><?=h($activity['public_title'])?></a><nav aria-label="<?= $locale===PUBLIC_LOCALE_PT_BR?'Navegação principal':'Primary navigation' ?>"><?php foreach($nav as $item):?><a href="<?=h(cms_page_url($activity,$item,$locale))?>"<?= (int)$item['id']===(int)$page['id']?' aria-current="page"':'' ?>><?=h($item['nav_title'])?></a><?php endforeach;?></nav><div class="cms-topbar-actions"><a class="cms-language" href="<?=h($langUrl)?>" hreflang="<?=h($otherLocale)?>"><?=$otherLabel?></a><div aria-label="Theme" class="theme-switch"><button aria-pressed="true" data-theme-value="auto" type="button">Auto</button><button aria-pressed="false" data-theme-value="light" type="button">Light</button><button aria-pressed="false" data-theme-value="dark" type="button">Dark</button></div></div></header><main id="main" data-cms-page-main><?=$body?></main><footer class="cms-footer"><p>João Saidler · Petrópolis, <?= $locale===PUBLIC_LOCALE_PT_BR?'Brasil':'Brazil' ?></p><p><?=h($page['title'])?></p></footer><script defer src="/assets/public.js"></script></body></html><?php
}
