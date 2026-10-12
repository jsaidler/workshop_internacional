<?php
declare(strict_types=1);
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_workbench_number(float $number):string{return (string)$number;}
require_once __DIR__.'/../app/student_inventory_item_editor.php';
function check_item(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"inventory-item-direct: $message\n");exit(1);}}
$item=['item_kind'=>'solution','quantity'=>55,'unit'=>'ml','name'=>'Teste <script>','category'=>'Fixador','lot_code'=>'P-07','acquired_or_prepared_at'=>'2026-10-11','expires_at'=>'2026-11-10','minimum_quantity'=>'5','location'=>'Armário','notes'=>'Texto <b>'];
ob_start();student_inventory_item_editor($item,7,'Ajuste <inválido>','token');$html=(string)ob_get_clean();
foreach(['name','category','lot_code','acquired_or_prepared_at','expires_at','minimum_quantity','location','notes'] as $key)check_item(str_contains($html,'name="'.$key.'"'),'missing metadata field '.$key);
check_item(str_contains($html,'data-inventory-item-error-focus')&&str_contains($html,'tabindex="-1"'),'missing contextual focus alert');
check_item(str_contains($html,'Teste &lt;script&gt;')&&!str_contains($html,'Teste <script>'),'unsafe draft rendering');
check_item(str_contains($html,'Texto &lt;b&gt;'),'notes not escaped');
check_item(str_contains($html,'55 ml')&&str_contains($html,'tipo e unidade não mudam'),'stock quantity and type must remain immutable');
check_item(str_contains($html,'student-inventory-editor-body')&&str_contains($html,'student-inventory-editor-balance')&&!str_contains($html,'student-step-fixed'),'editor must use an inventory-owned quantity summary that wraps in modal and direct page');
$route=(string)file_get_contents(__DIR__.'/../aluno/inventario-item.php');
$local=(string)file_get_contents(__DIR__.'/../assets/student-local-actions.js');
check_item(str_contains($route,'student_inventory_item_editor($item')&&str_contains($route,'is_scalar($_POST[$key])'),'route must reuse renderer and preserve failed POST draft');
check_item(str_contains($local,"if(path.endsWith('/aluno/inventario-item.php'))return null;"),'direct item edit must use native POST/redirect');
check_item(str_contains($route,"header('Location: /aluno/inventario.php?saved=1',true,303)"),'native successful POST must redirect to the real inventory list');
check_item(str_contains($local,'matchMedia(\'(max-width:760px)\').matches'),'mobile item link must preserve dedicated page navigation');
echo "inventory-item-direct: ok\n";