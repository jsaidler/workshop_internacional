<?php
declare(strict_types=1);
function long_db_assert(bool $okay,string $description):void{
  if(!$okay){fwrite(STDERR,"long-editor-db: $description\n");exit(1);}
}
function utc_now():string{return gmdate('c');}
function student_uuid():string{
  static $n=0;return sprintf('beefcafe-0000-4000-8000-%012d',++$n);
}
function student_workspace_text(mixed $value,int $max):string{
  return mb_substr(trim((string)$value),0,$max);
}
function student_workbench_float(mixed $value):?float{
  if($value===null||trim((string)$value)==='')return null;
  return (float)str_replace(',','.',trim((string)$value));
}
require_once dirname(__DIR__).'/app/student_workbench.php';
require_once dirname(__DIR__).'/app/student_process_catalogs.php';
require_once dirname(__DIR__).'/app/student_auxiliary_ux.php';
require_once dirname(__DIR__).'/app/student_workbench_hardening.php';
$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE student_saved_preparations(
 id INTEGER PRIMARY KEY AUTOINCREMENT,preparation_uuid TEXT UNIQUE NOT NULL,
 student_id INTEGER NOT NULL,developer_key TEXT,developer_name TEXT,label TEXT,
 preparation_mode TEXT,developer_amount REAL,water_amount REAL,amount_unit TEXT,
 temperature TEXT,duration TEXT,agitation TEXT,ingredients_json TEXT,
 created_at TEXT,updated_at TEXT);
 CREATE TABLE student_process_calibrations(
 id INTEGER PRIMARY KEY AUTOINCREMENT,calibration_uuid TEXT UNIQUE NOT NULL,
 student_id INTEGER NOT NULL,label TEXT,film TEXT,developer TEXT,preparation TEXT,
 temperature TEXT,prebath TEXT,white_time TEXT,notes TEXT,created_at TEXT,updated_at TEXT);");
$first=[
 'developer_key'=>'brewed-caffenol','label'=>'Caffenol inicial',
 'coffee_amount'=>'45','carbonate_amount'=>'29','ascorbic_amount'=>'10',
 'fresh_volume'=>'650','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'leve'
];
$created=student_saved_preparation_create_guided($db,7,$first);
$id=(int)$created['id'];
$ingredients=json_decode((string)$created['ingredients_json'],true);
long_db_assert($id>0&&$created['preparation_mode']==='fresh'&&abs((float)$created['developer_amount']-650)<0.001,'guided Caffenol creation did not persist fresh volume');
long_db_assert(count($ingredients)===4&&abs((float)$ingredients[0]['amount']-45)<0.001&&abs((float)$ingredients[3]['amount']-650)<0.001,'guided Caffenol creation did not persist ingredients');
long_db_assert($created['water_amount']===null,'fresh preparation incorrectly stores a separate water amount');
$second=$first;
$second['label']='Caffenol revisado';
$second['coffee_amount']='50';
$second['fresh_volume']='500';
$updated=student_saved_preparation_update_guided($db,7,$id,$second);
$ingredients=json_decode((string)$updated['ingredients_json'],true);
long_db_assert($updated['label']==='Caffenol revisado'&&abs((float)$updated['developer_amount']-500)<0.001,'Caffenol update did not persist revised volume');
long_db_assert(count($ingredients)===4&&abs((float)$ingredients[0]['amount']-50)<0.001&&abs((float)$ingredients[3]['amount']-500)<0.001,'Caffenol update did not persist revised ingredient amounts');
try{
  student_saved_preparation_update_guided($db,8,$id,$first);
  long_db_assert(false,'cross-owner preparation edit accepted');
}catch(RuntimeException $e){
  long_db_assert(str_contains($e->getMessage(),'encontrada'),'unexpected cross-owner protection failure');
}
try{
  student_saved_preparation_update_guided($db,7,$id,['developer_key'=>'brewed-caffenol','label'=>'']);
  long_db_assert(false,'empty preparation name accepted');
}catch(RuntimeException $e){
  long_db_assert(str_contains($e->getMessage(),'nome'),'wrong name validation');
}
$after=student_saved_preparation_for_student($db,7,$id);
long_db_assert($after['label']==='Caffenol revisado','failed validation changed stored preparation');
$calInput=['label'=>'HR-U · Parodinal','film'=>'Fuji HR-U','developer'=>'Parodinal','preparation'=>'10/550','temperature'=>'26 °C','prebath'=>'não','white_time'=>'03:00','notes'=>'Referência'];
student_calibration_save($db,7,$calInput);
$cal=$db->query('SELECT * FROM student_process_calibrations WHERE student_id=7 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
long_db_assert($cal&&$cal['label']==='HR-U · Parodinal'&&$cal['white_time']==='03:00','calibration create did not persist');
$calInput['id']=(int)$cal['id'];
$calInput['white_time']='03:30';
$calInput['notes']='Medição revisada';
student_calibration_save($db,7,$calInput);
$cal=$db->query('SELECT * FROM student_process_calibrations WHERE student_id=7 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
long_db_assert($cal['white_time']==='03:30'&&$cal['notes']==='Medição revisada','calibration update did not persist');
$calInput['white_time']='09:00';
student_calibration_save($db,8,$calInput);
$unchanged=$db->query('SELECT white_time FROM student_process_calibrations WHERE student_id=7 LIMIT 1')->fetchColumn();
long_db_assert($unchanged==='03:30','cross-owner calibration update changed stored record');
echo "long-editor-db: ok\n";
