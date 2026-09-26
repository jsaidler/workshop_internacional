<?php
declare(strict_types=1);

function fail_student_placeholder(string $message): never {fwrite(STDERR,"student-placeholder-visibility: $message\n");exit(1);}

$testAdmin=null;
function current_admin(): ?array {global $testAdmin;return $testAdmin;}
function activity_slug(string $value): string {$value=strtolower(trim($value));$value=preg_replace('/[^a-z0-9]+/','-',$value)??'';return trim($value,'-');}
function h(string $value): string {return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function course_page_media_slot(PDO $db,int $pageId,string $slotKey): ?array {return null;}
function media_asset_private(array $asset): bool {return ($asset['visibility']??'public')==='private';}
function media_admin_private_url(int $assetId,string $path): string {return '/private/'.$assetId;}

require dirname(__DIR__).'/app/student_material.php';

$db=new PDO('sqlite::memory:');
$page=['id'=>7];
$source='<section data-cms-section="lesson"><figure data-private-media-slot="densidade-negativo" data-private-media-alt="Densidade do negativo"></figure></section>';

$testAdmin=null;
$student=student_page_resolve_private_media_slots($db,$page,['html'=>$source]);
if(str_contains((string)$student['html'],'Infográfico pendente'))fail_student_placeholder('student output contains pending placeholder text');
if(str_contains((string)$student['html'],'data-private-media-slot'))fail_student_placeholder('student output keeps an unbound private-media slot');

$testAdmin=['id'=>1,'email'=>'admin@example.invalid'];
$admin=student_page_resolve_private_media_slots($db,$page,['html'=>$source]);
if(!str_contains((string)$admin['html'],'Infográfico pendente'))fail_student_placeholder('admin output does not contain pending placeholder text');
if(!str_contains((string)$admin['html'],'slot: densidade-negativo'))fail_student_placeholder('admin output does not identify the technical slot');

echo "student-placeholder-visibility: ok\n";
