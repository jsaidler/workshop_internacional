<?php
declare(strict_types=1);
function fail_p3(string $message): never {fwrite(STDERR,"test-cms-professional-3: $message\n");exit(1);}function expect_p3(bool $ok,string $message):void{if(!$ok)fail_p3($message);}
require __DIR__.'/../app/public_locale.php';
function cms_page_by_id(PDO $db,int $id):?array{$q=$db->prepare('SELECT * FROM cms_pages WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
require __DIR__.'/../app/cms_seo.php';
require __DIR__.'/../app/form_workflow.php';
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec('PRAGMA foreign_keys=ON');$db->exec('CREATE TABLE cms_pages(id INTEGER PRIMARY KEY)');$db->exec('INSERT INTO cms_pages(id) VALUES(1)');$migration=require __DIR__.'/../migrations/018_page_seo.php';$migration($db);
$seo=cms_page_seo_save($db,1,['title'=>'Página','description'=>'Descrição','socialTitle'=>'Social','socialDescription'=>'Compartilhar','socialImage'=>'/uploads/media/test.png','canonicalUrl'=>'https://example.com/pagina/','robots'=>'noindex,follow']);
expect_p3($seo['title']==='Página','SEO title not saved');expect_p3($seo['socialImage']==='/uploads/media/test.png','relative social image rejected');expect_p3($seo['canonicalUrl']==='https://example.com/pagina/','canonical URL not saved');expect_p3($seo['robots']==='noindex,follow','robots not saved');
$bad=cms_page_seo_validate(['canonicalUrl'=>'javascript:alert(1)','socialImage'=>'//evil.example/x.png']);expect_p3($bad['canonicalUrl']===''&&$bad['socialImage']==='','unsafe SEO URLs accepted');
$schema=['fields'=>[]];$updated=cms_form_workflow_update($schema,['success_mode'=>'redirect','redirect_path'=>'/obrigado/','notification_email'=>'joao@example.com','notification_subject'=>"Nova\nresposta"]);$workflow=cms_form_workflow($updated);expect_p3($workflow['successMode']==='redirect'&&$workflow['redirectPath']==='/obrigado/','form redirect workflow failed');expect_p3($workflow['notificationSubject']==='Nova resposta','notification subject newline not sanitized');
$thrown=false;try{cms_form_workflow_update($schema,['success_mode'=>'redirect','redirect_path'=>'https://evil.example/']);}catch(RuntimeException){$thrown=true;}expect_p3($thrown,'external redirect accepted');
$css=(string)file_get_contents(__DIR__.'/../assets/cms-responsive.css');expect_p3(str_contains($css,'data-cms-tablet-columns')&&str_contains($css,'data-cms-mobile-order'),'responsive breakpoint CSS missing');
$js=(string)file_get_contents(__DIR__.'/../editor/responsive-controls.js');expect_p3(str_contains($js,'cmsTabletColumns')&&str_contains($js,'cmsMobileColumns'),'responsive editor controls missing');
echo "Professional CMS v3.1 tests passed\n";
