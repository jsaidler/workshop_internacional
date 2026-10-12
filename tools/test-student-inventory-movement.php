<?php
declare(strict_types=1);
function check_movement(bool $okay,string $message):void {if(!$okay){fwrite(STDERR,"inventory-movement: $message\n");exit(1);}}
function utc_now():string {return gmdate('c');}
function student_uuid():string {static $counter=0;return sprintf('faaaaaaa-aaaa-4aaa-8aaa-%012d',++$counter);}
function student_workspace_text(mixed $value,int $max):string {return mb_substr(trim((string)$value),0,$max);}
function h(string $value):string {return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
require_once dirname(__DIR__).'/app/student_workbench.php';
require_once dirname(__DIR__).'/app/student_inventory_move_panel.php';
$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE student_inventory_items(id INTEGER PRIMARY KEY,student_id INTEGER,item_kind TEXT,name TEXT,quantity REAL,unit TEXT,archived_at TEXT,updated_at TEXT);
CREATE TABLE student_inventory_movements(id INTEGER PRIMARY KEY AUTOINCREMENT,movement_uuid TEXT NOT NULL UNIQUE,student_id INTEGER NOT NULL,item_id INTEGER NOT NULL,test_id INTEGER,step_id INTEGER,movement_type TEXT NOT NULL,quantity_delta REAL NOT NULL,note TEXT NOT NULL,created_at TEXT NOT NULL);");
$db->exec("INSERT INTO student_inventory_items VALUES(3,7,'raw','Parodinal',100,'ml',NULL,'today'),(4,8,'raw','Outrem',50,'g',NULL,'today')");
$balance=static fn()=> (float)$db->query('SELECT quantity FROM student_inventory_items WHERE id=3')->fetchColumn();
$count=static fn()=> (int)$db->query('SELECT COUNT(*) FROM student_inventory_movements')->fetchColumn();
$uuid='aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa';
student_inventory_move($db,7,3,-20,'consume','Uso declarado',null,null,true,$uuid);
check_movement($balance()===80.0&&$count()===1,'first consumption not atomic');
student_inventory_move($db,7,3,-20,'consume','Uso declarado',null,null,true,$uuid);
check_movement($balance()===80.0&&$count()===1,'identical replay applied twice');
try{student_inventory_move($db,7,3,-5,'consume','Uso declarado',null,null,true,$uuid);check_movement(false,'reused request with changed amount accepted');}catch(RuntimeException $e){check_movement(str_contains($e->getMessage(),'outros dados'),'wrong duplicate payload error');}
check_movement($balance()===80.0&&$count()===1,'changed replay mutated the ledger');
student_inventory_move($db,7,3,10,'in','Reposição',null,null,true,'bbbbbbbb-1111-4111-8111-bbbbbbbbbbbb');
student_inventory_move($db,7,3,-5,'adjust','Correção por medição',null,null,true,'cccccccc-1111-4111-8111-cccccccccccc');
check_movement($balance()===85.0&&$count()===3,'reconciled quantities incorrect');
try{student_inventory_move($db,7,3,-90,'consume','',null,null,true,'dddddddd-1111-4111-8111-dddddddddddd');check_movement(false,'overdraft was accepted');}catch(RuntimeException $e){check_movement(str_contains($e->getMessage(),'suficiente'),'incorrect overdraft message');}
check_movement($balance()===85.0&&$count()===3,'overdraft left audit side effect');
try{student_inventory_move($db,7,4,-1,'consume','',null,null,true,'eeeeeeee-1111-4111-8111-eeeeeeeeeeee');check_movement(false,'cross-owner movement accepted');}catch(RuntimeException $e){}
check_movement($count()===3,'cross-owner ledger row remained');
$db->exec("UPDATE student_inventory_items SET archived_at='today' WHERE id=3");
try{student_inventory_move($db,7,3,1,'in','',null,null,true,'ffffffff-1111-4111-8111-ffffffffffff');check_movement(false,'archived item was modified');}catch(RuntimeException $e){}
check_movement($balance()===85.0&&$count()===3,'archived stock was modified');
$db->exec("UPDATE student_inventory_items SET archived_at=NULL WHERE id=3");
$db->beginTransaction();
student_inventory_move($db,7,3,-4,'consume','Processo',9,11,false);
check_movement($balance()===81.0&&$count()===4,'caller transaction did not see movement');
$db->rollBack();
check_movement($balance()===85.0&&$count()===3,'outer rollback did not preserve atomicity');
$item=['id'=>3,'name'=>'Químico <script>','unit'=>'ml','quantity'=>85];
$draft=['item_id'=>3,'direction'=>'adjust_down','amount'=>'3.500','note'=>'Recontagem <b>','movement_uuid'=>$uuid];
ob_start();student_inventory_move_panel($item,'csrf',$draft,'Saldo <insuficiente>');
$html=(string)ob_get_clean();
foreach(['movement_uuid','item_id','direction','amount','note','_csrf'] as $field)check_movement(str_contains($html,'name="'.$field.'"'),'missing field '.$field);
check_movement(str_contains($html,'data-inventory-move-reopen')&&str_contains($html,'data-inventory-move-error-focus'),'failed movement must reopen and focus');
check_movement(str_contains($html,'85 ml')&&str_contains($html,'adjust_down" selected'),'balance or correction selection missing');
check_movement(str_contains($html,'3.500')&&str_contains($html,'Recontagem &lt;b&gt;'),'draft not preserved/escaped');
check_movement(str_contains($html,'Químico &lt;script&gt;')&&!str_contains($html,'Químico <script>'),'item name not escaped');
check_movement(!str_contains($html,'data-student-local-form'),'stock changes must use native POST/redirect');
$source=(string)file_get_contents(dirname(__DIR__).'/aluno/inventario.php');
check_movement(str_contains($source,'student_inventory_move_panel(')&&str_contains($source,'student-inventory-movement.js'),'authenticated route must reuse renderer');
check_movement(str_contains($source,"'move'?'move':'1'"),'native PRG status not present');
echo "inventory-movement: ok\n";
