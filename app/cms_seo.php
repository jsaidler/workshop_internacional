<?php
declare(strict_types=1);

function cms_page_seo_defaults(): array {
    return ['title'=>'','description'=>'','socialTitle'=>'','socialDescription'=>'','socialImage'=>'','canonicalUrl'=>'','robots'=>'index,follow'];
}
function cms_page_seo_clean_url(string $value,bool $allowPath=true): string {
    $value=trim($value);if($value==='')return '';
    if($allowPath&&str_starts_with($value,'/')&&!str_starts_with($value,'//'))return $value;
    if(filter_var($value,FILTER_VALIDATE_URL)){ $scheme=strtolower((string)parse_url($value,PHP_URL_SCHEME));if(in_array($scheme,['http','https'],true))return $value; }
    return '';
}
function cms_page_seo_validate(array $input): array {
    $robotsInput=(string)($input['robots']??'index,follow');$robots=in_array($robotsInput,['index,follow','noindex,follow','index,nofollow','noindex,nofollow'],true)?$robotsInput:'index,follow';
    $trim=function(mixed $value,int $max):string{$v=trim((string)$value);if(function_exists('mb_substr'))return mb_substr($v,0,$max,'UTF-8');return substr($v,0,$max);};
    return [
        'title'=>$trim($input['title']??'',180),
        'description'=>$trim($input['description']??'',500),
        'socialTitle'=>$trim($input['socialTitle']??'',180),
        'socialDescription'=>$trim($input['socialDescription']??'',500),
        'socialImage'=>cms_page_seo_clean_url((string)($input['socialImage']??''),true),
        'canonicalUrl'=>cms_page_seo_clean_url((string)($input['canonicalUrl']??''),false),
        'robots'=>$robots,
    ];
}
function cms_page_seo(PDO $db,int $pageId): array {
    $q=$db->prepare('SELECT title,description,social_title,social_description,social_image,canonical_url,robots FROM cms_page_seo WHERE page_id=?');$q->execute([$pageId]);$row=$q->fetch();if(!$row)return cms_page_seo_defaults();
    return cms_page_seo_validate(['title'=>$row['title'],'description'=>$row['description'],'socialTitle'=>$row['social_title'],'socialDescription'=>$row['social_description'],'socialImage'=>$row['social_image'],'canonicalUrl'=>$row['canonical_url'],'robots'=>$row['robots']]);
}
function cms_page_seo_save(PDO $db,int $pageId,array $input): array {
    $page=cms_page_by_id($db,$pageId);if(!$page)throw new RuntimeException('page_not_found');$seo=cms_page_seo_validate($input);$q=$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?) ON CONFLICT(page_id) DO UPDATE SET title=excluded.title,description=excluded.description,social_title=excluded.social_title,social_description=excluded.social_description,social_image=excluded.social_image,canonical_url=excluded.canonical_url,robots=excluded.robots,updated_at=excluded.updated_at');$q->execute([$pageId,$seo['title'],$seo['description'],$seo['socialTitle'],$seo['socialDescription'],$seo['socialImage'],$seo['canonicalUrl'],$seo['robots'],gmdate('c')]);return $seo;
}
function cms_absolute_url(string $url): string {
    if($url===''||preg_match('~^https?://~i',$url))return $url;
    if(!str_starts_with($url,'/'))return $url;
    $host=preg_replace('/[^A-Za-z0-9.:-]/','',(string)($_SERVER['HTTP_HOST']??''))??'';if($host==='')return $url;$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';return $scheme.'://'.$host.$url;
}
