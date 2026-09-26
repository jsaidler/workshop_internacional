<?php
declare(strict_types=1);

$root=dirname(__DIR__);
function fp_must(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function fp_source(string $root,string $path): string {$s=file_get_contents($root.'/'.$path);if(!is_string($s))throw new RuntimeException('cannot read '.$path);return $s;}

$accounts=fp_source($root,'app/student_accounts.php');
$lifecycle=fp_source($root,'app/student_lifecycle.php');
$submissions=fp_source($root,'admin/submissions.php');
fp_must(!str_contains($accounts,"f.form_key='registration'"),'student account enrollment must not depend on form_key');
fp_must(!str_contains($lifecycle,"form_key']!=='registration"),'registration deletion must not depend on form_key');
fp_must(!str_contains($submissions,"form_key']==='registration"),'submissions UI must not branch on form_key');
fp_must(str_contains($accounts,"f.purpose='enrollment'"),'student enrollment reconciliation must use explicit purpose');
fp_must(str_contains($submissions,"cms_form_purpose(\$row)==='enrollment'"),'admin registration workflow must use explicit purpose');

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE cms_forms(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,form_key TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active')");
$db->exec("INSERT INTO cms_forms(id,activity_id,form_key,status) VALUES(1,1,'registration','active'),(2,1,'interest','active'),(3,1,'contact','active')");
$migration=require $root.'/migrations/069_form_purpose_authority.php';$migration($db);
$rows=$db->query('SELECT id,form_key,purpose FROM cms_forms ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
fp_must($rows[0]['form_key']==='registration'&&$rows[0]['purpose']==='enrollment','existing registration identity must be preserved while purpose is backfilled');
fp_must($rows[1]['purpose']==='interest','existing interest form must be backfilled');
fp_must($rows[2]['purpose']==='common','unclassified form must remain common');
$migration($db);fp_must((int)$db->query("SELECT COUNT(*) FROM pragma_table_info('cms_forms') WHERE name='purpose'")->fetchColumn()===1,'migration must be idempotent');

require $root.'/app/form_purpose.php';
fp_must(cms_form_purpose(['purpose'=>'registration','form_key'=>'anything'])==='registration','purpose must outrank key');
fp_must(cms_form_purpose(['purpose'=>'common','form_key'=>'registration'])==='common','explicit common purpose must disable legacy registration semantics');
fp_must(cms_form_purpose(['form_key'=>'registration'])==='enrollment','legacy fallback must remain readable before migration');
echo "form purpose enrollment authority tests passed
";
