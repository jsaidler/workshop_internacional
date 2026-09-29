<?php
declare(strict_types=1);
function fail_admin_site_ux(string $message): never {fwrite(STDERR,"admin-site-ux: $message\n");exit(1);}
function must_admin_site_ux(bool $condition,string $message): void {if(!$condition)fail_admin_site_ux($message);}
$root=dirname(__DIR__);
$css=(string)file_get_contents($root.'/assets/admin-site.css');$js=(string)file_get_contents($root.'/assets/admin-site.js');
$pages=(string)file_get_contents($root.'/admin/pages.php');$forms=(string)file_get_contents($root.'/admin/forms.php');$responses=(string)file_get_contents($root.'/admin/submissions.php');$export=(string)file_get_contents($root.'/admin/submissions-export.php');$site=(string)file_get_contents($root.'/admin/site.php');$design=(string)file_get_contents($root.'/admin/design.php');$seo=(string)file_get_contents($root.'/admin/seo.php');
must_admin_site_ux($css!==''&&$js!=='','shared Site workspace assets are missing');
must_admin_site_ux(!str_contains($css,'!important'),'Site workspace CSS uses !important');
foreach(['pages'=>$pages,'forms'=>$forms,'site'=>$site,'design'=>$design,'seo'=>$seo] as $name=>$source)must_admin_site_ux(str_contains($source,'/assets/admin-site.css'),$name.' does not consume the shared Site workspace CSS');
foreach(['pages'=>$pages,'forms'=>$forms,'site'=>$site,'design'=>$design,'seo'=>$seo] as $name=>$source)must_admin_site_ux(!str_contains($source,'overview-hero'),$name.' still uses the legacy duplicate hero');
must_admin_site_ux(str_contains($forms,'LIMIT {$pageSize} OFFSET {$offset}')&&str_contains($forms,'admin-pagination'),'Forms is not paginated server-side');
must_admin_site_ux(str_contains($forms,'site-catalog')&&str_contains($forms,'site-detail-layout'),'Forms does not separate catalog and detail work surfaces');
must_admin_site_ux(!str_contains($site,'course_public_title')&&!str_contains($site,'activity_update_identity'),'Navigation still owns Course identity');
must_admin_site_ux(!str_contains($site,'seo[defaultTitle]')&&!str_contains($site,'seo[defaultDescription]'),'Navigation still renders SEO fields');
must_admin_site_ux(str_contains($site,"\$settings['seo']=\$site['seo']"),'Navigation save does not preserve SEO owned by the SEO workspace');
must_admin_site_ux(str_contains($site,'data-site-nav-builder')&&str_contains($site,'/assets/admin-site.js'),'Navigation is not using the shared client behavior');
must_admin_site_ux(!str_contains($site,'<script>'),'Navigation still contains executable inline JavaScript');
must_admin_site_ux(str_contains($seo,'default_title')&&str_contains($seo,'default_description')&&str_contains($seo,"\$settings['seo']"),'SEO does not own global localized defaults');
must_admin_site_ux(str_contains($seo,'site-seo-workspace')&&str_contains($seo,'cms_page_seo_save'),'SEO lost page-specific editing');
must_admin_site_ux(!str_contains($seo,'media_list('),'SEO still loads the entire media library into a select');
must_admin_site_ux(!str_contains($seo,'<script>'),'SEO still contains executable inline JavaScript');
must_admin_site_ux(str_contains($responses,"COALESCE(f.purpose,'common')!='enrollment'")&&str_contains($responses,"admin_shell_start('responses','Outras respostas'"),'Other responses still overlaps the enrollment/registration domain');
must_admin_site_ux(str_contains($responses,'LIMIT {$pageSize} OFFSET {$offset}')&&str_contains($responses,'admin-pagination'),'Other responses is not paginated');
must_admin_site_ux(str_contains($export,"COALESCE(f.purpose,'common')!='enrollment'"),'Other responses CSV still exports enrollment submissions');
must_admin_site_ux(str_contains($design,'site-section-nav')&&str_contains($design,'site-savebar'),'Visual settings lack task navigation or persistent save action');
echo "admin-site-ux: ok\n";
