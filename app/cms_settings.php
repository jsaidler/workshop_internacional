<?php
declare(strict_types=1);

const CMS_DESIGN_SITE_SCOPE='__site__';

function cms_design_google_fonts(): array {
    return [
        'body'=>[
            'IBM Plex Sans'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600'],
            'Inter'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Source Sans 3'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Noto Sans'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Work Sans'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Manrope'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Roboto'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;700'],
            'Open Sans'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Lato'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;700'],
        ],
        'display'=>[
            'Saira Extra Condensed'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Oswald'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Roboto Condensed'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Barlow Condensed'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Archivo Narrow'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'400;500;600;700'],
            'Space Grotesk'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Montserrat'=>['fallback'=>'Arial, sans-serif','weights'=>'300;400;500;600;700'],
            'Bebas Neue'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'400'],
            'Anton'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'400'],
            'Fjalla One'=>['fallback'=>'"Arial Narrow", Arial, sans-serif','weights'=>'400'],
        ],
        'mono'=>[
            'IBM Plex Mono'=>['fallback'=>'Consolas, monospace','weights'=>'300;400;500;600'],
            'Roboto Mono'=>['fallback'=>'Consolas, monospace','weights'=>'300;400;500;600;700'],
            'Source Code Pro'=>['fallback'=>'Consolas, monospace','weights'=>'300;400;500;600;700'],
            'JetBrains Mono'=>['fallback'=>'Consolas, monospace','weights'=>'300;400;500;600;700'],
            'Space Mono'=>['fallback'=>'Consolas, monospace','weights'=>'400;700'],
            'Inconsolata'=>['fallback'=>'Consolas, monospace','weights'=>'300;400;500;600;700'],
        ],
    ];
}

function cms_design_font_defaults(): array {
    return ['body'=>'IBM Plex Sans','display'=>'Saira Extra Condensed','mono'=>'IBM Plex Mono'];
}

function cms_design_normalize_font_value(string $value,string $role): string {
    $fonts=cms_design_font_defaults();$catalog=cms_design_google_fonts();$value=trim($value);
    $legacy=[
        'body'=>['var(--sans)','var(--body)','"IBM Plex Sans", Arial, sans-serif','IBM Plex Sans, Arial, sans-serif'],
        'display'=>['var(--title)','var(--font-display)','"Saira Extra Condensed", "Arial Narrow", sans-serif','Saira Extra Condensed, "Arial Narrow", sans-serif'],
        'mono'=>['var(--mono)','"IBM Plex Mono", Consolas, monospace','IBM Plex Mono, Consolas, monospace'],
    ];
    if(in_array($value,$legacy[$role]??[],true))return $fonts[$role];
    return isset($catalog[$role][$value])?$value:$fonts[$role];
}

function cms_design_normalize_fonts(array $design): array {
    if(!isset($design['type'])||!is_array($design['type']))return $design;
    foreach(['bodyFont'=>'body','displayFont'=>'display','monoFont'=>'mono'] as $key=>$role){
        if(array_key_exists($key,$design['type']))$design['type'][$key]=cms_design_normalize_font_value((string)$design['type'][$key],$role);
    }
    return $design;
}

function cms_design_font_stack(string $family,string $role): string {
    $catalog=cms_design_google_fonts();$family=cms_design_normalize_font_value($family,$role);$meta=$catalog[$role][$family];
    return '"'.str_replace('"','',$family).'", '.$meta['fallback'];
}

function cms_design_google_fonts_url(array $design): string {
    $design=cms_design_normalize_fonts($design);$catalog=cms_design_google_fonts();$families=[];
    foreach(['bodyFont'=>'body','displayFont'=>'display','monoFont'=>'mono'] as $key=>$role){
        $family=(string)$design['type'][$key];$meta=$catalog[$role][$family];
        if(isset($families[$family]))continue;
        $encoded=str_replace('%20','+',rawurlencode($family));
        $families[$family]='family='.$encoded.':wght@'.$meta['weights'];
    }
    return 'https://fonts.googleapis.com/css2?'.implode('&',$families).'&display=swap';
}

function cms_design_font_import_css(array $design): string {
    return '@import url("'.cms_design_google_fonts_url($design).'") layer(cms-system);';
}

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
        'navigation'=>['items'=>[]],
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
    $fonts=cms_design_font_defaults();
    return [
        'colors'=>[
            'bg'=>'#f2f2ef','surface'=>'#ffffff','surface2'=>'#e7e7e2','text'=>'#0b0c0d','muted'=>'#5f6264','line'=>'#bfc1be','accent'=>'#186f4d',
            'buttonBg'=>'#0b0c0d','buttonText'=>'#ffffff','buttonBorder'=>'#0b0c0d',
            'darkBg'=>'#0c0d0e','darkSurface'=>'#141617','darkSurface2'=>'#1d1f20','darkText'=>'#f0f0ec','darkMuted'=>'#a4a6a4','darkLine'=>'#353839','darkAccent'=>'#79cba7',
            'darkButtonBg'=>'#f0f0ec','darkButtonText'=>'#0b0c0d','darkButtonBorder'=>'#f0f0ec',
        ],
        'layout'=>[
            'maxWidth'=>1520,'gutterMin'=>20,'gutterVw'=>4,'gutterMax'=>72,
            'sectionMin'=>84,'sectionVw'=>10,'sectionMax'=>168,'contentNarrow'=>920,
        ],
        'type'=>[
            'bodyFont'=>$fonts['body'],'displayFont'=>$fonts['display'],'monoFont'=>$fonts['mono'],
            'bodySize'=>17,'bodyLineHeight'=>1.55,'displayLineHeight'=>1.02,'letterSpacing'=>0,
            'h1Min'=>70,'h1Vw'=>9.2,'h1Max'=>154,'h2Min'=>46,'h2Vw'=>6.4,'h2Max'=>108,
            'h3Size'=>28,'leadSize'=>24,'smallSize'=>13,
        ],
        'buttons'=>['radius'=>0,'height'=>54,'borderWidth'=>1,'paddingX'=>22],
        'advanced'=>['customCss'=>''],
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
function cms_design_site_custom_css(PDO $db,int $activityId): ?string {
    $shared=cms_setting_row($db,'cms_design_settings',$activityId,CMS_DESIGN_SITE_SCOPE);
    if(!is_array($shared)||!is_array($shared['advanced']??null)||!array_key_exists('customCss',$shared['advanced']))return null;
    return (string)$shared['advanced']['customCss'];
}
function cms_design_settings(PDO $db,int $activityId,string $locale): array {
    $design=cms_settings_merge(cms_design_defaults(),cms_setting_row($db,'cms_design_settings',$activityId,$locale)??[]);
    $design=cms_design_normalize_fonts($design);
    $sharedCss=cms_design_site_custom_css($db,$activityId);
    if($sharedCss!==null)$design['advanced']['customCss']=$sharedCss;
    return $design;
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

function cms_clean_nav_items(array $items): array {
    $out=[];
    foreach($items as $row){
        if(!is_array($row))continue;
        $type=($row['type']??'page')==='custom'?'custom':'page';$label=trim((string)($row['label']??''));
        if($type==='page'){
            $pageId=(int)($row['pageId']??0);if($pageId<1)continue;
            $out[]=['type'=>'page','pageId'=>$pageId,'label'=>$label,'newTab'=>!empty($row['newTab'])];
        }else{
            $url=trim((string)($row['url']??''));if($label===''||$url==='')continue;
            $out[]=['type'=>'custom','label'=>$label,'url'=>$url,'newTab'=>!empty($row['newTab'])];
        }
        if(count($out)>=30)break;
    }
    return $out;
}
function cms_design_sanitize_custom_css(string $css): string {
    $css=str_ireplace(['</style','<script','</script'],['','',''],$css);
    if(strlen($css)>30000)$css=substr($css,0,30000);
    return $css;
}

function cms_settings_save(PDO $db,string $kind,int $activityId,string $locale,array $settings): array {
    $locale=normalize_public_locale($locale)??PUBLIC_LOCALE_PT_BR;$table=$kind==='design'?'cms_design_settings':'cms_site_settings';$defaults=$kind==='design'?cms_design_defaults():cms_site_defaults($locale);$clean=cms_settings_validate($settings,$defaults);
    if($kind!=='design'){
        if(isset($clean['footer']['links'])&&is_array($clean['footer']['links'])){$links=[];foreach($clean['footer']['links'] as $link)if(is_array($link)&&trim((string)($link['label']??''))!==''&&trim((string)($link['url']??''))!=='')$links[]=['label'=>trim((string)$link['label']),'url'=>trim((string)$link['url'])];$clean['footer']['links']=$links;}
        $clean['navigation']['items']=cms_clean_nav_items(is_array($settings['navigation']['items']??null)?$settings['navigation']['items']:[]);
    }else{
        $clean=cms_design_normalize_fonts($clean);
        $clean['advanced']['customCss']=cms_design_sanitize_custom_css((string)($clean['advanced']['customCss']??''));
    }
    $json=json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$q=$db->prepare("INSERT INTO $table(activity_id,locale,settings_json,updated_at) VALUES(?,?,?,?) ON CONFLICT(activity_id,locale) DO UPDATE SET settings_json=excluded.settings_json,updated_at=excluded.updated_at");$q->execute([$activityId,$locale,$json,gmdate('c')]);
    if($kind==='design'){
        $sharedJson=json_encode(['advanced'=>['customCss'=>(string)$clean['advanced']['customCss']]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $shared=$db->prepare("INSERT INTO cms_design_settings(activity_id,locale,settings_json,updated_at) VALUES(?,?,?,?) ON CONFLICT(activity_id,locale) DO UPDATE SET settings_json=excluded.settings_json,updated_at=excluded.updated_at");
        $shared->execute([$activityId,CMS_DESIGN_SITE_SCOPE,$sharedJson,gmdate('c')]);
    }
    return $clean;
}

function cms_css_color(string $value,string $fallback): string {return preg_match('/^#[0-9a-f]{6}$/i',$value)?$value:$fallback;}

function cms_design_system_css(array $design): string {
    $design=cms_design_normalize_fonts($design);$c=$design['colors'];$l=$design['layout'];$t=$design['type'];$b=$design['buttons'];
    $bodyFont=cms_design_font_stack((string)$t['bodyFont'],'body');$displayFont=cms_design_font_stack((string)$t['displayFont'],'display');$monoFont=cms_design_font_stack((string)$t['monoFont'],'mono');
    $vars=[
        '--bg'=>cms_css_color((string)$c['bg'],'#f2f2ef'),'--surface'=>cms_css_color((string)$c['surface'],'#ffffff'),'--surface-2'=>cms_css_color((string)$c['surface2'],'#e7e7e2'),'--text'=>cms_css_color((string)$c['text'],'#0b0c0d'),'--muted'=>cms_css_color((string)$c['muted'],'#5f6264'),'--line'=>cms_css_color((string)$c['line'],'#bfc1be'),'--focus'=>cms_css_color((string)$c['accent'],'#186f4d'),
        '--cms-button-bg'=>cms_css_color((string)$c['buttonBg'],'#0b0c0d'),'--cms-button-text'=>cms_css_color((string)$c['buttonText'],'#ffffff'),'--cms-button-border'=>cms_css_color((string)$c['buttonBorder'],'#0b0c0d'),
        '--max'=>max(900,min(2200,(int)$l['maxWidth'])).'px','--gutter'=>'clamp('.max(8,(int)$l['gutterMin']).'px,'.max(1,(float)$l['gutterVw']).'vw,'.max(16,(int)$l['gutterMax']).'px)','--section'=>'clamp('.max(24,(int)$l['sectionMin']).'px,'.max(2,(float)$l['sectionVw']).'vw,'.max(40,(int)$l['sectionMax']).'px)','--cms-narrow'=>max(560,min(1400,(int)$l['contentNarrow'])).'px',
        '--sans'=>$bodyFont,'--body'=>$bodyFont,'--title'=>$displayFont,'--mono'=>$monoFont,
        '--cms-body-font'=>$bodyFont,'--cms-display-font'=>$displayFont,'--cms-mono-font'=>$monoFont,
        '--cms-body-size'=>max(12,min(28,(int)$t['bodySize'])).'px','--cms-body-lh'=>max(1.1,min(2.2,(float)$t['bodyLineHeight'])),'--cms-display-lh'=>max(1,min(1.4,(float)$t['displayLineHeight'])),'--cms-letter-spacing'=>max(-3,min(8,(float)$t['letterSpacing'])).'px',
        '--cms-h1'=>'clamp('.max(36,(int)$t['h1Min']).'px,'.max(4,(float)$t['h1Vw']).'vw,'.max(64,(int)$t['h1Max']).'px)','--cms-h2'=>'clamp('.max(30,(int)$t['h2Min']).'px,'.max(3,(float)$t['h2Vw']).'vw,'.max(48,(int)$t['h2Max']).'px)','--cms-h3'=>max(18,min(64,(int)$t['h3Size'])).'px','--cms-lead'=>max(16,min(48,(int)$t['leadSize'])).'px','--cms-small'=>max(10,min(20,(int)$t['smallSize'])).'px',
        '--cms-button-radius'=>max(0,min(40,(int)$b['radius'])).'px','--cms-button-height'=>max(38,min(84,(int)$b['height'])).'px','--cms-button-border-width'=>max(0,min(6,(int)$b['borderWidth'])).'px','--cms-button-padding-x'=>max(8,min(64,(int)$b['paddingX'])).'px',
    ];
    $dark='--bg:'.cms_css_color((string)$c['darkBg'],'#0c0d0e').';--surface:'.cms_css_color((string)$c['darkSurface'],'#141617').';--surface-2:'.cms_css_color((string)$c['darkSurface2'],'#1d1f20').';--text:'.cms_css_color((string)$c['darkText'],'#f0f0ec').';--muted:'.cms_css_color((string)$c['darkMuted'],'#a4a6a4').';--line:'.cms_css_color((string)$c['darkLine'],'#353839').';--focus:'.cms_css_color((string)$c['darkAccent'],'#79cba7').';--cms-button-bg:'.cms_css_color((string)$c['darkButtonBg'],'#f0f0ec').';--cms-button-text:'.cms_css_color((string)$c['darkButtonText'],'#0b0c0d').';--cms-button-border:'.cms_css_color((string)$c['darkButtonBorder'],'#f0f0ec').';';
    $css=':root{';foreach($vars as $key=>$value)$css.=$key.':'.$value.';';$css.='}';$css.=':root[data-theme="dark"]{'.$dark.'}@media(prefers-color-scheme:dark){:root:not([data-theme]){'.$dark.'}}';
    $css.='[data-cms-span="2"]{grid-column:span 2}[data-cms-span="3"]{grid-column:span 3}[data-cms-span="4"]{grid-column:span 4}[data-cms-self="start"]{align-self:start}[data-cms-self="center"]{align-self:center}[data-cms-self="end"]{align-self:end}[data-cms-self="stretch"]{align-self:stretch}';
    $css.='@media(min-width:901px){[data-cms-hidden-desktop="1"]{display:none}}@media(max-width:900px){[data-cms-hidden-mobile="1"]{display:none}[data-cms-span]{grid-column:auto}}';
    return $css;
}

function cms_design_custom_css(array $design): string {
    return (string)($design['advanced']['customCss']??'');
}

function cms_design_css(array $design): string {
    return cms_design_system_css($design).cms_design_custom_css($design);
}

function cms_revision_store(PDO $db,array $page,string $state): void {
    $document=$state==='published'?($page['published_document_json']??null):($page['draft_document_json']??null);if(!is_string($document)||$document==='')return;$settings=json_encode(['title'=>$page['title'],'nav_title'=>$page['nav_title'],'slug'=>$page['slug'],'show_in_nav'=>(bool)$page['show_in_nav']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$revision=$state==='published'?(int)($page['published_revision']??0):(int)$page['draft_revision'];$q=$db->prepare('INSERT INTO cms_page_revisions(page_id,revision,state,document_json,settings_json,created_at) VALUES(?,?,?,?,?,?)');$q->execute([(int)$page['id'],$revision,$state,$document,$settings,gmdate('c')]);
}
