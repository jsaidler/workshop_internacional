<?php
declare(strict_types=1);
function fail_research(string $message): never {fwrite(STDERR,"student-research-continuity: $message\n");exit(1);}
function must_research(bool $ok,string $message): void {if(!$ok)fail_research($message);}
function utc_now(): string {return '2026-10-04 13:00:00';}
function student_workspace_text(mixed $value,int $max=5000): string {$value=trim((string)$value);return mb_strlen($value)>$max?mb_substr($value,0,$max):$value;}
function student_test_for_student(PDO $db,int $testId,int $studentId): ?array {$q=$db->prepare('SELECT * FROM student_tests WHERE id=? AND student_id=?');$q->execute([$testId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function student_process_create_record(PDO $db,int $studentId,array $input): array {
    $q=$db->prepare("INSERT INTO student_tests(student_id,title,test_date,film,lot,iso_reference,aperture,calculated_time,reciprocity_time,light_condition,tonal_range,notes,status,context_scope,context_cohort_id,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?, 'draft',?,?,?)");
    $q->execute([$studentId,(string)$input['title'],null,(string)$input['film'],(string)$input['lot'],(string)$input['iso_reference'],(string)$input['aperture'],(string)$input['calculated_time'],(string)$input['reciprocity_time'],(string)$input['light_condition'],(string)$input['tonal_range'],(string)$input['notes'],(string)$input['context_scope'],(int)$input['context_cohort_id'],utc_now()]);
    return student_test_for_student($db,(int)$db->lastInsertId(),$studentId)??throw new RuntimeException('create failed');
}

$root=dirname(__DIR__);require_once $root.'/app/student_research.php';
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE student_tests(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,title TEXT NOT NULL,test_date TEXT NULL,film TEXT NOT NULL DEFAULT '',lot TEXT NOT NULL DEFAULT '',iso_reference TEXT NOT NULL DEFAULT '',aperture TEXT NOT NULL DEFAULT '',calculated_time TEXT NOT NULL DEFAULT '',reciprocity_time TEXT NOT NULL DEFAULT '',light_condition TEXT NOT NULL DEFAULT '',tonal_range TEXT NOT NULL DEFAULT '',notes TEXT NOT NULL DEFAULT '',status TEXT NOT NULL DEFAULT 'draft',context_scope TEXT NOT NULL DEFAULT 'personal',context_cohort_id INTEGER NOT NULL DEFAULT 0,updated_at TEXT NOT NULL);
CREATE TABLE student_process_steps(id INTEGER PRIMARY KEY AUTOINCREMENT,test_id INTEGER NOT NULL,position INTEGER NOT NULL,label TEXT NOT NULL DEFAULT '',stage_key TEXT NOT NULL DEFAULT '',chemical_name TEXT NOT NULL DEFAULT '',calculated_dilution TEXT NOT NULL DEFAULT '',total_volume TEXT NOT NULL DEFAULT '',amount_unit TEXT NOT NULL DEFAULT '',temperature TEXT NOT NULL DEFAULT '',duration TEXT NOT NULL DEFAULT '',agitation TEXT NOT NULL DEFAULT '');");
$db->exec("INSERT INTO student_tests(id,student_id,title,test_date,film,lot,iso_reference,aperture,calculated_time,reciprocity_time,light_condition,tonal_range,notes,status,context_scope,context_cohort_id,updated_at) VALUES(10,7,'EI 200 — FeCl3','2026-10-01','Fuji Super H-RU','L24','200','f/64','4 s','7 s','janela lateral','sombras agrupadas','positivo denso nas altas luzes','reviewed','personal',0,'2026-10-04 12:00:00');
INSERT INTO student_process_steps(test_id,position,label,stage_key,chemical_name,calculated_dilution,total_volume,amount_unit,temperature,duration,agitation) VALUES(10,1,'Primeira revelação','first_development','Parodinal','10+550','560','ml','26 °C','07:00','contínua');");
$migration=require $root.'/migrations/084_student_research_lineage.php';$migration($db);
$columns=[];foreach($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
must_research(isset($columns['source_test_id'],$columns['research_intent']),'migration did not add research lineage columns');
must_research(student_research_schema_available($db),'research service does not recognize migrated schema');

$child=student_research_fork($db,10,7,'  Manter o processamento e testar EI 400.  ');
must_research((int)$child['source_test_id']===10,'fork lost source record');
must_research((string)$child['research_intent']==='Manter o processamento e testar EI 400.','fork did not normalize/persist intent');
must_research((string)$child['film']==='Fuji Super H-RU'&&(string)$child['iso_reference']==='200'&&(string)$child['aperture']==='f/64','fork did not inherit exposure baseline');
must_research((string)$child['notes']===''&&(string)$child['status']==='draft','fork copied result/review state');
$newSteps=(int)$db->query('SELECT COUNT(*) FROM student_process_steps WHERE test_id='.(int)$child['id'])->fetchColumn();
must_research($newSteps===0,'fork copied executed processing as if it had already happened');

$a=['test_date'=>'2026-10-01','film'=>' Fuji  Super H-RU ','lot'=>'L24','iso_reference'=>'200','aperture'=>'f/64','calculated_time'=>'4 s','reciprocity_time'=>'7 s','light_condition'=>'Janela lateral','tonal_range'=>'Sombras agrupadas'];
$b=['test_date'=>'2026-10-01','film'=>'fuji super h-ru','lot'=>'L24','iso_reference'=>'400','aperture'=>'f/64','calculated_time'=>'4 s','reciprocity_time'=>'7 s','light_condition'=>'janela lateral','tonal_range'=>'Sombras agrupadas'];
$stepsA=[['label'=>'Primeira revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','total_volume'=>'560','amount_unit'=>'ml','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'contínua']];
$stepsB=[['label'=>'Primeira revelação','chemical_name'=>'parodinal','calculated_dilution'=>'10+550','total_volume'=>'560','amount_unit'=>'ml','temperature'=>'26 °C','duration'=>'06:30','agitation'=>'contínua']];
$differences=student_research_difference_rows($a,$b,$stepsA,$stepsB);$labels=array_column($differences,'label');
must_research(in_array('EI',$labels,true),'exposure change was not detected');
must_research(in_array('Etapa 1 · Tempo',$labels,true),'process duration change was not detected');
must_research(!in_array('Filme',$labels,true)&&!in_array('Condição de luz',$labels,true),'cosmetic whitespace/case created false differences');

$caderno=(string)file_get_contents($root.'/aluno/caderno.php');$compare=(string)file_get_contents($root.'/aluno/comparar-processos.php');$shell=(string)file_get_contents($root.'/app/student_shell.php');$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
must_research(str_contains($caderno,'Comparar com outro registro')&&str_contains($caderno,'Comparar com origem'),'notebook lost contextual comparison entry');
must_research(str_contains($caderno,'student_research_source')&&str_contains($caderno,'student-research-card-lineage'),'derived notebook records do not surface lineage');
must_research(!str_contains($caderno,'student-process-compare'),'permanent comparison controls returned to notebook');
must_research(str_contains($compare,'student_research_difference_rows')&&str_contains($compare,'Resultado observado')&&str_contains($compare,'não atribui causa'),'comparison is no longer descriptive research analysis');
must_research(str_contains($compare,'student_research_fork')&&str_contains($compare,'O que você pretende mudar nesta próxima tentativa?'),'comparison no longer creates an intentional next variation');
must_research(str_contains($shell,"str_contains(\$path,'/aluno/comparar-processos.php')"),'comparison does not receive notebook feature styles');
must_research(str_contains($bootstrap,"'student_research'"),'bootstrap does not load research service');

echo "student-research-continuity: ok\n";
