<?php
declare(strict_types=1);

function cms_site_defaults(string $locale): array {
    $pt=$locale===PUBLIC_LOCALE_PT_BR;
    return [
        'siteName'=>$pt?'Positivo direto em filme de raios X':'Direct Positive X-Ray Film',
        'wordmark'=>'Direct Positive Workshop',
        'header'=>[
            'sticky'=>true,
            'showLanguageSwitch'=>true,
            'showThemeSwitch'=>true,
            'ctaLabel'=>'',
            'ctaUrl'=>'',
        ],
        'footer'=>[
            'line1'=>'João Saidler · Petrópolis, '.($pt?'Brasil':'Brazil'),
            'line2'=>$pt?'Fotografia experimental · positivo direto em filme de raios X':'Experimental photography · direct-positive X-ray film',
            'links'=>[],
        ],
        'seo'=>[
            'defaultTitle'=>$pt?'Positivo Direto em Filme de Raios X — João Saidler':'Direct Positive X-Ray Film — João Saidler',
            'defaultDescription'=>'',
            'socialImage'=>'',
            'favicon'=>'',
        ],
    ];
}

function cms_design_defaults(): array {
    return [
        'colors'=>[
            'bg'=>'#f2f2ef','surface'=>'#ffffff','surface2'=>'#e7e7e2','text'=>'#0b0c0d','muted'=>'#5f6264','line'=>'#bfc1be','accent'=>'#186f4d',
            'darkBg'=>'#0c0d0e','darkSurface'=>'#141617','darkSurface2'=>'#1d1f20','darkText'=>'#f0f0ec','darkMuted'=>'#a4a6a4','darkLine'=>'#353839','darkAccent'=>'#79cba7',
        ],
        'layout'=>[
            'maxWidth'=>1520,
            'gutterMin'=>20,
            'gutterVw'=>4,
            'gutterMax'=>72,
            'sectionMin'=>84,
            'sectionVw'=>10,
            'sectionMax'=>168,
            'contentNarrow'=>920,
        ],
        'type'=>[
            'bodySize'=>17,
            'bodyLineHeight'=>1.55,
            'displayLineHeight'=>1.02,
            'h1Min'=>70,
            'h1Vw'=>9.2,
            'h1Max'=>154,
            'h2Min'=>46,
            'h2Vw'=>6.4,
            'h2Max'=>108,
        ],
        'buttons'=>['radius'=>0,'height'=>54],
    ];
}

function cms_settings_merge(array $defaults,array $value): array {
    foreach($value as $key=>$item){
        if(is_array($item)&&isset($defaults[$key])&&is_array($defaults[$key])&&!array_is_list($item)&&!array_is_list($defaults[$key]))$defaults[$key]=cms_settings_merge($defaults[$key],$item);
        else $defaults[$key]=$item;
    }
    return $defaults;
}

function cms_setting_row(PDO $db,string $table,int $activityId,string $locale): ?array {
    $q=$db->prepare("SELECT settings_json FROM $table WHERE activity_id=? AND locale=?");
    $q->execute([$activityId,$locale]);
    $json=$q->fetchColumn();
    if(!is_string($json)||$json==='')return null;
    $decoded=json_decode($json,true);
    return is_array($decoded)?$decoded:null;
}

function cms_site_settings(PDO $db,int $activityId,string $locale): array {
    return cms_settings_merge(cms_site_defaults($locale),cms_setting_row($db,'cms_site_settings',$activityId,$locale)??[]);
}

function cms_design_settings(PDO $db,int $activityId,string $locale): array {
    return cms_settings_merge(cms_design_defaults(),cms_setting_row($db,'cms_design_settings',$activityId,$locale)??[]);
}

function cms_setting_scalar(mixed $value,mixed $fallback): mixed {
    if(is_bool($fallback))return filter_var($value,FILTER_VALIDATE_BOOL,FILTER_NULL_ON_FAILURE)??$fallback;
    if(is_int($fallback))return is_numeric($value)?(int)$value:$fallback;
    if(is_float($fallback))return is_numeric($value)?(float)$value:$fallback;
    return is_scalar($value)?trim((string)$value):$fallback;
}

function cms_settings_validate(array $input,array $defaults): array {
    if($defaults===[]||array_is_list($defaults))return array_values(array_filter($input,fn($row)=>is_array($row)||is_scalar($row)));
    $out=$defaults;
    foreach($defaults as $key=>$default){
        if(!array_key_exists($key,$input))continue;
        if(is_array($default))$out[$key]=cms_settings_validate(is_array($input[$key])?$input[$key]:[],$default);
        else $out[$key]=cms_setting_scalar($input[$key],$default);
    }
    return $out;
}

function cms_settings_save(PDO $db,string $kind,int $activityId,string $locale,array $settings): array {
    $locale=normalize_public_locale($locale)??PUBLIC_LOCALE_PT_BR;
    $table=$kind==='design'?'cms_design_settings':'cms_site_settings';
    $defaults=$kind==='design'?cms_design_defaults():cms_site_defaults($locale);
    $clean=cms_settings_validate($settings,$defaults);
    if($kind!=='design'&&isset($clean['footer']['links'])&&is_array($clean['footer']['links'])){
        $links=[];foreach($clean['footer']['links'] as $link)if(is_array($link)&&trim((string)($link['label']??''))!==''&&trim((string)($link['url']??''))!=='')$links[]=['label'=>trim((string)$link['label']),'url'=>trim((string)$link['url'])];$clean['footer']['links']=$links;
    }
    $json=json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $q=$db->prepare("INSERT INTO $table(activity_id,locale,settings_json,updated_at) VALUES(?,?,?,?) ON CONFLICT(activity_id,locale) DO UPDATE SET settings_json=excluded.settings_json,updated_at=excluded.updated_at");
    $q->execute([$activityId,$locale,$json,gmdate('c')]);
    return $clean;
}

function cms_css_color(string $value,string $fallback): string {
    return preg_match('/^#[0-9a-f]{6}$/i',$value)?$value:$fallback;
}

function cms_design_css(array $design): string {
    $c=$design['colors'];$l=$design['layout'];$t=$design['type'];$b=$design['buttons'];
    $vars=[
        '--bg'=>cms_css_color((string)$c['bg'],'#f2f2ef'),
        '--surface'=>cms_css_color((string)$c['surface'],'#ffffff'),
        '--surface-2'=>cms_css_color((string)$c['surface2'],'#e7e7e2'),
        '--text'=>cms_css_color((string)$c['text'],'#0b0c0d'),
        '--muted'=>cms_css_color((string)$c['muted'],'#5f6264'),
        '--line'=>cms_css_color((string)$c['line'],'#bfc1be'),
        '--focus'=>cms_css_color((string)$c['accent'],'#186f4d'),
        '--max'=>max(900,min(2200,(int)$l['maxWidth'])).'px',
        '--gutter'=>'clamp('.max(8,(int)$l['gutterMin']).'px,'.max(1,(float)$l['gutterVw']).'vw,'.max(16,(int)$l['gutterMax']).'px)',
        '--section'=>'clamp('.max(24,(int)$l['sectionMin']).'px,'.max(2,(float)$l['sectionVw']).'vw,'.max(40,(int)$l['sectionMax']).'px)',
        '--cms-narrow'=>max(560,min(1400,(int)$l['contentNarrow'])).'px',
        '--cms-body-size'=>max(12,min(28,(int)$t['bodySize'])).'px',
        '--cms-body-lh'=>max(1.1,min(2.2,(float)$t['bodyLineHeight'])),
        '--cms-display-lh'=>max(1,min(1.4,(float)$t['displayLineHeight'])),
        '--cms-h1'=>'clamp('.max(36,(int)$t['h1Min']).'px,'.max(4,(float)$t['h1Vw']).'vw,'.max(64,(int)$t['h1Max']).'px)',
        '--cms-h2'=>'clamp('.max(30,(int)$t['h2Min']).'px,'.max(3,(float)$t['h2Vw']).'vw,'.max(48,(int)$t['h2Max']).'px)',
        '--cms-button-radius'=>max(0,min(40,(int)$b['radius'])).'px',
        '--cms-button-height'=>max(38,min(84,(int)$b['height'])).'px',
    ];
    $dark='--bg:'.cms_css_color((string)$c['darkBg'],'#0c0d0e').';--surface:'.cms_css_color((string)$c['darkSurface'],'#141617').';--surface-2:'.cms_css_color((string)$c['darkSurface2'],'#1d1f20').';--text:'.cms_css_color((string)$c['darkText'],'#f0f0ec').';--muted:'.cms_css_color((string)$c['darkMuted'],'#a4a6a4').';--line:'.cms_css_color((string)$c['darkLine'],'#353839').';--focus:'.cms_css_color((string)$c['darkAccent'],'#79cba7').';';
    $css=':root{';foreach($vars as $key=>$value)$css.=$key.':'.$value.';';$css.='}';
    $css.=':root[data-theme="dark"]{'.$dark.'}@media(prefers-color-scheme:dark){:root:not([data-theme]){'.$dark.'}}';
    return $css;
}

function cms_revision_store(PDO $db,array $page,string $state): void {
    $document=$state==='published'?($page['published_document_json']??null):($page['draft_document_json']??null);
    if(!is_string($document)||$document==='')return;
    $settings=json_encode(['title'=>$page['title'],'nav_title'=>$page['nav_title'],'slug'=>$page['slug'],'show_in_nav'=>(bool)$page['show_in_nav']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $revision=$state==='published'?(int)($page['published_revision']??0):(int)$page['draft_revision'];
    $q=$db->prepare('INSERT INTO cms_page_revisions(page_id,revision,state,document_json,settings_json,created_at) VALUES(?,?,?,?,?,?)');
    $q->execute([(int)$page['id'],$revision,$state,$document,$settings,gmdate('c')]);
}
