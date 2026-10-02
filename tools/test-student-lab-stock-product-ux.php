<?php
declare(strict_types=1);
function fail_student_lab_stock_product_ux(string $message): never {fwrite(STDERR,"student-lab-stock-product-ux: $message\n");exit(1);}
function must_student_lab_stock_product_ux(bool $ok,string $message): void {if(!$ok)fail_student_lab_stock_product_ux($message);}
$root=dirname(__DIR__);
$inventory=(string)file_get_contents($root.'/aluno/inventario.php');
$preparations=(string)file_get_contents($root.'/aluno/preparos.php');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$css=(string)file_get_contents($root.'/assets/student-lab-stock.css');
$doc=(string)file_get_contents($root.'/docs/STUDENT_INVENTORY_PREPARATIONS_PRODUCT_UX_TRANCHE_C_2026-10-01.md');

must_student_lab_stock_product_ux(str_contains($inventory,'existem fisicamente no seu laboratório'),'inventory does not explain physical stock');
must_student_lab_stock_product_ux(str_contains($inventory,'Soluções preparadas')&&str_contains($inventory,'Estoque baixo'),'inventory does not expose decision summary');
must_student_lab_stock_product_ux(str_contains($inventory,'Editar item')&&str_contains($inventory,'Registrar entrada ou saída')&&str_contains($inventory,'Arquivar'),'inventory CRUD/actions are not discoverable');
must_student_lab_stock_product_ux(str_contains($inventory,'Entrada aumenta o saldo; saída reduz.'),'inventory movement consequence is not explained');
must_student_lab_stock_product_ux(str_contains($inventory,'Seu inventário ainda está vazio.')&&str_contains($inventory,'Adicionar primeiro item'),'inventory empty state does not teach first action');
must_student_lab_stock_product_ux(str_contains($inventory,'Ver predefinições'),'inventory does not distinguish/link reusable parameters');

must_student_lab_stock_product_ux(str_contains($preparations,'não representa estoque físico'),'preparations do not distinguish parameters from stock');
must_student_lab_stock_product_ux(str_contains($preparations,'Abrir inventário'),'preparations do not link physical stock');
must_student_lab_stock_product_ux(str_contains($preparations,'Ao usar esta predefinição, estes parâmetros são copiados'),'preparation cards do not explain reuse semantics');
must_student_lab_stock_product_ux(str_contains($preparations,'Nenhuma quantidade do Inventário é alterada só por escolher a predefinição.'),'preparation cards imply inventory consumption');
must_student_lab_stock_product_ux(str_contains($preparations,'Editar')&&str_contains($preparations,'Excluir'),'preparation CRUD is not visible');
must_student_lab_stock_product_ux(str_contains($preparations,'Você ainda não salvou nenhuma condição de revelação.'),'preparation empty state is not pedagogical');

must_student_lab_stock_product_ux(str_contains($shell,"\$stockFeature")&&str_contains($shell,'/assets/student-lab-stock.css'),'shell does not load stock UX layer');
foreach(['.student-stock-summary','.student-inventory-item-actions','.student-preparation-card','.student-lab-crosslink'] as $selector)must_student_lab_stock_product_ux(str_contains($css,$selector),'stock UX stylesheet missing '.$selector);
must_student_lab_stock_product_ux(str_contains($doc,'Inventário — o que eu tenho')&&str_contains($doc,'Predefinição de revelação — como eu costumo preparar e usar'),'canonical stock/preparation model is missing');
must_student_lab_stock_product_ux(str_contains($doc,'não pode gerar uma segunda baixa do revelador'),'bath reuse inventory rule is not preserved');
must_student_lab_stock_product_ux(str_contains($doc,'inspeção visual humana'),'stock UX doc does not require human visual inspection');
echo "student-lab-stock-product-ux: ok\n";
