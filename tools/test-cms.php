<?php
declare(strict_types=1);

function fail_test(string $message): never { fwrite(STDERR,"test-cms: $message\n"); exit(1); }
function expect(bool $condition,string $message): void { if(!$condition) fail_test($message); }
if(!function_exists('h')){function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}}

require __DIR__.'/../app/public_locale.php';
require __DIR__.'/../app/interest_repository.php';
require __DIR__.'/../app/activity_repository.php';
require __DIR__.'/../app/cms_forms.php';
require __DIR__.'/../app/cms_pages.php';

$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE activities (id INTEGER PRIMARY KEY AUTOINCREMENT,admin_name TEXT NOT NULL,public_title TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,status TEXT NOT NULL DEFAULT 'active',is_root INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL,updated_at TEXT NOT NULL); CREATE TABLE submission_rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT,key_hash TEXT NOT NULL UNIQUE,window_started_at TEXT NOT NULL,attempt_count INTEGER NOT NULL,updated_at TEXT NOT NULL);");
$now=utc_now();$db->prepare('INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at)VALUES(?,?,?,?,1,?,?)')->execute(['Workshop','Direct Positive X-Ray Film','workshop','active',$now,$now]);$activityId=(int)$db->lastInsertId();
$migration=require __DIR__.'/../migrations/011_cms_pages_forms.php';$migration($db);

cms_forms_seed($db,$activityId);cms_pages_seed($db,$activityId);
$forms=cms_forms($db,$activityId);$pages=cms_pages($db,$activityId);
expect(count($forms)===2,'expected two seeded forms');
expect(count($pages)===2,'expected two seeded home pages');
$ptForm=cms_form_by_key($db,$activityId,PUBLIC_LOCALE_PT_BR,'registration');$enForm=cms_form_by_key($db,$activityId,PUBLIC_LOCALE_EN,'interest');
expect($ptForm!==null,'missing PT registration form');expect($enForm!==null,'missing EN interest form');
$ptPage=cms_page_home($db,$activityId,PUBLIC_LOCALE_PT_BR);$enPage=cms_page_home($db,$activityId,PUBLIC_LOCALE_EN);
expect($ptPage!==null&&$enPage!==null,'missing localized home page');
expect(str_contains(cms_page_doc($ptPage,true)['html'],'data-cms-form-key="registration"'),'PT page must embed registration form');
expect(str_contains(cms_page_doc($ptPage,true)['html'],'Primeira turma'),'PT page must contain first-cohort evidence');
expect(str_contains(cms_page_doc($ptPage,true)['html'],'suporte'),'PT page must contain processing support section');
expect(str_contains(cms_page_doc($enPage,true)['html'],'data-cms-form-key="interest"'),'EN page must embed interest survey');
expect(str_contains(cms_page_doc($enPage,true)['html'],'first English-language cohort'),'EN page must position first English cohort');

$created=cms_page_create($db,$activityId,PUBLIC_LOCALE_PT_BR,'Perguntas frequentes','faq');
expect($created['slug']==='faq','page slug mismatch');
$copy=cms_page_create($db,$activityId,PUBLIC_LOCALE_PT_BR,'Outra FAQ','faq');
expect($copy['slug']==='faq-2','page slug uniqueness failed');
$doc=cms_page_doc($created,false);$doc['html'].='<section data-cms-section="test" data-cms-section-name="Test"><p data-cms-editable>Teste</p><script>alert(1)</script></section>';
$saved=cms_page_save($db,(int)$created['id'],$doc,(int)$created['draft_revision'],['title'=>'FAQ','nav_title'=>'FAQ','slug'=>'faq','show_in_nav'=>true]);
expect(!str_contains(cms_page_doc($saved,false)['html'],'<script'),'page sanitizer failed');
$published=cms_page_publish($db,(int)$created['id']);expect((int)$published['published_revision']===(int)$published['draft_revision'],'page publication revision mismatch');

$schema=cms_form_schema($enForm,true);[$values,$errors]=cms_form_validate_submission($schema,[],PUBLIC_LOCALE_EN);expect(($errors['name']??'')==='This field is required.','EN validation message not localized');
$ptSchema=cms_form_schema($ptForm,true);[$values,$errors]=cms_form_validate_submission($ptSchema,[],PUBLIC_LOCALE_PT_BR);expect(($errors['name']??'')==='Campo obrigatório.','PT validation message not localized');
$newForm=cms_form_create($db,$activityId,PUBLIC_LOCALE_PT_BR,'Questionário','questionario','interest');$schema=cms_form_schema($newForm,false);$schema['fields'][]=['id'=>'custom_field','type'=>'text','label'=>'Campo customizado','required'=>false,'width'=>'full'];$newForm=cms_form_save($db,(int)$newForm['id'],$schema,(int)$newForm['draft_revision'],'Questionário atualizado');expect(count(cms_form_schema($newForm,false)['fields'])===count($schema['fields']),'form field save failed');$newForm=cms_form_publish($db,(int)$newForm['id']);expect((int)$newForm['published_revision']===(int)$newForm['draft_revision'],'form publication revision mismatch');

echo "CMS smoke tests passed\n";
