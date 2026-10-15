<?php
declare(strict_types=1);
function h(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_test_visibility_label(string $v): string{return $v==='cohort'?'Compartilhado com a turma':'Privado';}
function student_review_value(mixed $v): string{return h(trim((string)$v)===''?'—':$v);}
function student_process_path_label(array $rows): string{return 'Primeira revelação → Branqueamento';}
function student_test_message_date(string $v): string{return '14/10/2026';}
function check_shared(bool $pass,string $why): void {if(!$pass){fwrite(STDERR,"student-shared-record-view: $why\n");exit(1);}}
require __DIR__.'/../app/student_shared_record_view.php';
$record=['id'=>103,'student_id'=>3,'student_name'=>'Aluno <script>','visibility'=>'cohort','cohort_title'=>'Turma A','title'=>'Registro <img src=x>','film'=>'Fuji H-RU','lot'=>'RX-A','iso_reference'=>'400','aperture'=>'f/64','calculated_time'=>'04 s','reciprocity_time'=>'07 s','light_condition'=>'Janela <strong>','tonal_range'=>'Sombras','developer'=>'Parodinal','dilution'=>'10+550','temperature'=>'26 °C','development_time'=>'07:00','notes'=>'Resultado <svg>'];
$steps=[['position'=>1,'label'=>'Primeira revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','temperature'=>'26 °C','duration'=>'07:00','notes'=>'Observação <script>']];
$messages=[['author_role'=>'admin','body'=>'Revisar <img onerror=1>','created_at'=>'2026-10-14 10:00:00']];
ob_start();student_shared_record_view($record,null,$steps,[],[],$messages,103);$out=(string)ob_get_clean();
foreach(['Registro &lt;img src=x&gt;','Aluno &lt;script&gt;','Revisar &lt;img onerror=1&gt;','Observação &lt;script&gt;'] as $phrase)check_shared(str_contains($out,$phrase),'escaped content missing: '.$phrase);
check_shared(!str_contains($out,'Registro <img')&&!str_contains($out,'Aluno <script>'),'unsafe raw content rendered');
check_shared(str_contains($out,'href="/aluno/caderno.php"')&&str_contains($out,'Compartilhado com a turma'),'notebook context missing');
check_shared(str_contains($out,'Avaliação e conversa')&&str_contains($out,'Primeira revelação')&&str_contains($out,'Processamento')&&str_contains($out,'Resultado'),'read-only academic evidence missing');
check_shared(!str_contains($out,'<form')&&!str_contains($out,'name="_csrf"')&&!str_contains($out,'<button'),'a shared record cannot expose mutation controls');
ob_start();student_shared_record_view($record,null,[],[],[],[],103);$empty=(string)ob_get_clean();
check_shared(str_contains($empty,'Nenhuma foto da cena.')&&str_contains($empty,'Nenhuma foto do resultado.')&&str_contains($empty,'Ainda não há mensagens neste registro.'),'empty states missing');
check_shared(str_contains($empty,'Revelador')&&str_contains($empty,'Branqueador'),'no-step fallback lost');
ob_start();student_shared_record_view($record,null,[],[['id'=>98]],[['id'=>98]],[],103);$media=(string)ob_get_clean();
check_shared(str_contains($media,'/aluno/teste-media.php?id=98')&&str_contains($media,'rel="noopener"'),'media routing changed');
$controller=(string)file_get_contents(__DIR__.'/../aluno/teste-compartilhado.php');
check_shared(str_contains($controller,'student_test_accessible_to_student(')&&str_contains($controller,'student_account_current('),'route must retain access guard');
check_shared(str_contains($controller,'student_shared_record_view(')&&str_contains($controller,'student_id')&&str_contains($controller,'header('),'route must delegate and preserve owner redirect');
check_shared(str_contains($controller,'student_test_media(')&&str_contains($controller,'student_test_messages('),'route must load authorized media and thread');
echo "student-shared-record-view: ok\n";
