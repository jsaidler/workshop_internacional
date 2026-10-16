<?php
declare(strict_types=1);
function h(string $value): string {return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
require_once __DIR__.'/../app/student_inventory_create_panel.php';
function stock_must(bool $okay,string $message): void {if(!$okay){fwrite(STDERR,"student-inventory-create-recovery: $message\n");exit(1);}}
$draft=[
 'item_kind'=>'solution','name'=>'Solucao "B" <script>alert(1)</script>',
 'quantity'=>'125.5','unit'=>'g','category'=>'Fixador',
 'lot_code'=>'LOTE-9','acquired_or_prepared_at'=>'2026-10-10',
 'expires_at'=>'2026-11-10','minimum_quantity'=>'15','location'=>'Armario A',
 'notes'=>'Texto <b>pessoal</b>',
];
ob_start();student_inventory_create_panel($draft,'Erro de gravação <script>x</script>','csrf-example');$html=(string)ob_get_clean();
stock_must(str_contains($html,'data-inventory-new-panel')&&!str_contains($html,'data-inventory-new-panel hidden'),'failed creation hides the task');
stock_must(str_contains($html,'role="alert"')&&str_contains($html,'data-inventory-error-focus'),'failed creation lacks local focused alert');
stock_must(str_contains($html,'&lt;script&gt;')&&!str_contains($html,'<script>'),'POST/error must be escaped');
stock_must(str_contains($html,'value="Solucao &quot;B&quot; &lt;script&gt;alert(1)&lt;/script&gt;"'),'name draft was not preserved');
stock_must(str_contains($html,'value="solution" checked'),'item type changed after error');
stock_must(str_contains($html,'<option value="g" selected>g</option>'),'unit selection lost');
foreach(['quantity','category','lot_code','acquired_or_prepared_at','expires_at','minimum_quantity','location','notes'] as $name)stock_must(str_contains($html,'name="'.$name.'"'),'field '.$name.' missing');
stock_must(str_contains($html,'value="125.5"')&&str_contains($html,'value="LOTE-9"')&&str_contains($html,'value="2026-11-10"'),'stock/dates draft lost');
stock_must(str_contains($html,'<details class="student-optional-fields student-span-2" open>'),'nonempty notes must remain expanded');
stock_must(str_contains($html,'Texto &lt;b&gt;pessoal&lt;/b&gt;'),'notes were not preserved safely');
ob_start();student_inventory_create_panel([],'','csrf-example');$normal=(string)ob_get_clean();
stock_must(str_contains($normal,'data-inventory-new-panel hidden'),'default form must stay collapsed');
stock_must(str_contains($normal,'value="raw" checked')&&str_contains($normal,'<option value="ml" selected>ml</option>'),'default classification/unit changed');
$controller=(string)file_get_contents(__DIR__.'/../aluno/inventario.php');
$route=$controller.(string)file_get_contents(__DIR__.'/../app/student_inventory_view.php');
stock_must(str_contains($controller,"require __DIR__.'/../app/student_inventory_view.php'"),'inventory controller fails to include canonical view');
stock_must(str_contains($route,'$failedCreate=$error')&&str_contains($route,'student_inventory_create_panel($createDraft'),'failed inventory route does not reopen the real form');
stock_must(str_contains($route,'$error&&!$failedCreate'),'create errors must not be duplicated at page top');
echo "student-inventory-create-recovery: ok\n";
