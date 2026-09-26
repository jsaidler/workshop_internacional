<?php
declare(strict_types=1);

function cms_xml_escape(string $value): string {return htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');}
function cms_page_is_indexable(PDO $db,array $page): bool {
    if(($page['status']??'')==='archived'||empty($page['published_document_json']))return false;
    if(($page['access_level']??'public')!=='public')return false;
    $seo=cms_page_seo($db,(int)$page['id']);return !str_starts_with((string)$seo['robots'],'noindex');
}
function cms_page_public_location(PDO $db,array $activity,array $page): string {
    $seo=cms_page_seo($db,(int)$page['id']);$url=$seo['canonicalUrl']!==''?$seo['canonicalUrl']:cms_page_url($activity,$page,(string)$page['locale']);return cms_absolute_url($url);
}
function cms_page_counterpart(PDO $db,array $page,string $locale): ?array {
    if(function_exists('cms_page_translation_counterpart')){
        $candidate=cms_page_translation_counterpart($db,$page,$locale);
    }elseif((string)$page['locale']===$locale){
        $candidate=$page;
    }else{
        $candidate=(int)$page['is_home']===1
            ?cms_page_home($db,(int)$page['activity_id'],$locale)
            :cms_page_by_slug($db,(int)$page['activity_id'],$locale,(string)$page['slug']);
    }
    return $candidate&&cms_page_is_indexable($db,$candidate)?$candidate:null;
}
function cms_sitemap_entries(PDO $db): array {
    $activities=$db->query("SELECT * FROM activities WHERE status='active' ORDER BY is_root DESC,id")->fetchAll();$entries=[];
    foreach($activities as $activity){$activityId=(int)$activity['id'];$q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND status!='archived' AND published_document_json IS NOT NULL ORDER BY locale,sort_order,id");$q->execute([$activityId]);
        foreach($q->fetchAll() as $page){if(!cms_page_is_indexable($db,$page))continue;$loc=cms_page_public_location($db,$activity,$page);if($loc==='')continue;$last=(string)($page['published_at']??$page['updated_at']??'');$timestamp=$last!==''?strtotime($last):false;$alternates=[];
            foreach([PUBLIC_LOCALE_PT_BR,PUBLIC_LOCALE_EN] as $locale){$counterpart=cms_page_counterpart($db,$page,$locale);if(!$counterpart)continue;$url=cms_page_public_location($db,$activity,$counterpart);if($url!=='')$alternates[$locale]=$url;}
            $entries[$loc]=['loc'=>$loc,'lastmod'=>$timestamp!==false?gmdate('c',$timestamp):'','alternates'=>$alternates];
        }
    }
    ksort($entries,SORT_STRING);return array_values($entries);
}
function cms_sitemap_xml(PDO $db): string {
    $out=['<?xml version="1.0" encoding="UTF-8"?>','<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'];
    foreach(cms_sitemap_entries($db) as $entry){$out[]='  <url>';$out[]='    <loc>'.cms_xml_escape((string)$entry['loc']).'</loc>';if($entry['lastmod']!=='')$out[]='    <lastmod>'.cms_xml_escape((string)$entry['lastmod']).'</lastmod>';foreach($entry['alternates'] as $locale=>$url)$out[]='    <xhtml:link rel="alternate" hreflang="'.cms_xml_escape((string)$locale).'" href="'.cms_xml_escape((string)$url).'" />';$out[]='  </url>';}
    $out[]='</urlset>';return implode("\n",$out)."\n";
}
function cms_robots_text(): string {
    $sitemap=cms_absolute_url('/sitemap.xml');return implode("\n",['User-agent: *','Allow: /','Disallow: /admin/','Disallow: /aluno/','Disallow: /editor/','Disallow: /preview/','Disallow: /install/','Disallow: /form-submit.php','Disallow: /form-config.php','',$sitemap!==''?'Sitemap: '.$sitemap:''])."\n";
}
