<?php
declare(strict_types=1);
function analytics_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"test-analytics-dashboard: $message\n");exit(1);}}
$root=dirname(__DIR__);
$migration=(string)file_get_contents($root.'/migrations/033_analytics_dashboard.php');
$repo=(string)file_get_contents($root.'/app/analytics_repository.php');
$index=(string)file_get_contents($root.'/index.php');
$submit=(string)file_get_contents($root.'/form-submit.php');
$endpoint=(string)file_get_contents($root.'/analytics-event.php');
$dashboard=(string)file_get_contents($root.'/admin/analytics.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');

analytics_expect(str_contains($migration,'CREATE TABLE IF NOT EXISTS analytics_events'),'analytics event table must be created');
analytics_expect(str_contains($migration,'session_hash TEXT NOT NULL'),'sessions must use an opaque hash');
analytics_expect(!str_contains($migration,'ip_address')&&!str_contains($migration,'raw_ip'),'analytics storage must not introduce raw IP fields');
analytics_expect(str_contains($repo,"hash_hmac('sha256',\$session"),'browser sessions must be pseudonymized before storage');
analytics_expect(str_contains($repo,"'pageview','form_start','cta_click','form_submit'"),'event model must support the conversion funnel');
analytics_expect(str_contains($repo,"s.status='converted'"),'commercial conversion must use the response status rather than invent a second truth source');
analytics_expect(str_contains($index,'analytics_record_pageview('),'published CMS page loads must be recorded server-side');
analytics_expect(str_contains($submit,"'event_type'=>'form_submit'")&&str_contains($submit,"'submission_id'=>"),'accepted responses must be linked to the analytics funnel');
analytics_expect(str_contains($endpoint,"['same-origin','same-site','none']"),'browser event endpoint must reject cross-site writes');
analytics_expect(str_contains($dashboard,'Acesso → resposta')&&str_contains($dashboard,'Resposta → convertido'),'dashboard must separate website conversion from commercial conversion');
analytics_expect(str_contains($dashboard,'não inventa nem reconstrói acessos anteriores'),'dashboard must state that historical visits cannot be reconstructed');
analytics_expect(str_contains($shell,"'analytics'=>['Métricas'"),'metrics must be a first-class admin destination');

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE activities(id INTEGER PRIMARY KEY);CREATE TABLE cms_pages(id INTEGER PRIMARY KEY);CREATE TABLE cms_forms(id INTEGER PRIMARY KEY);CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY);');
$migrationFn=require $root.'/migrations/033_analytics_dashboard.php';$migrationFn($db);
$columns=array_column($db->query('PRAGMA table_info(analytics_events)')->fetchAll(PDO::FETCH_ASSOC),'name');
foreach(['activity_id','page_id','form_id','submission_id','event_type','session_hash','utm_source','device_type','created_at'] as $column)analytics_expect(in_array($column,$columns,true),'missing analytics column '.$column);

echo "Analytics dashboard tests passed\n";
