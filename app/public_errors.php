<?php
declare(strict_types=1);

function cms_not_found_context(?array $activity,string $locale): array {
    $pt=$locale===PUBLIC_LOCALE_PT_BR;$brand='João Saidler';$home='/';
    if($activity){
        try{
            $db=database();$site=cms_site_settings($db,(int)$activity['id'],$locale);$homePage=cms_page_home($db,(int)$activity['id'],$locale);$brand=trim((string)($site['wordmark']??''))?:($homePage?(string)$homePage['title']:(string)($activity['public_title']??$brand));$home=$homePage?cms_page_url($activity,$homePage,$locale):'/';
        }catch(Throwable){}
    }
    return ['locale'=>$locale,'brand'=>$brand,'home'=>$home,'title'=>$pt?'Página não encontrada':'Page not found','message'=>$pt?'O endereço solicitado não corresponde a uma página publicada neste site.':'The requested address does not match a published page on this site.','action'=>$pt?'Voltar ao início':'Back to home'];
}
function cms_not_found_html(?array $activity,string $locale): string {
    $view=cms_not_found_context($activity,$locale);$lang=h((string)$view['locale']);$brand=h((string)$view['brand']);$home=h((string)$view['home']);$title=h((string)$view['title']);$message=h((string)$view['message']);$action=h((string)$view['action']);
    return '<!doctype html><html lang="'.$lang.'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$title.' — '.$brand.'</title><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/cms.css"><link rel="stylesheet" href="/assets/cms-pro.css"><link rel="stylesheet" href="/assets/cms-errors.css"></head><body class="cms-public cms-error-page"><header class="cms-error-header"><a class="brand" href="'.$home.'">'.$brand.'</a></header><main class="cms-error-main"><p class="section-label">404</p><h1>'.$title.'</h1><p>'.$message.'</p><a class="button" href="'.$home.'">'.$action.'</a></main></body></html>';
}
function cms_render_not_found(?array $activity,string $locale): never {
    http_response_code(404);header('Content-Type: text/html; charset=UTF-8');header('Content-Language: '.$locale);header('X-Robots-Tag: noindex, nofollow');header('Cache-Control: no-store');echo cms_not_found_html($activity,$locale);exit;
}
