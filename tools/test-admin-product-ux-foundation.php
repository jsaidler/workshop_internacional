<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fail=[];
$expect=static function(bool $ok,string $message) use (&$fail): void {if(!$ok)$fail[]=$message;};

$overview=(string)file_get_contents($root.'/admin/index.php');
$system=(string)file_get_contents($root.'/admin/system.php');
$responsive=(string)file_get_contents($root.'/assets/admin-shell-responsive.css');

$expect(!str_contains($overview,'overview-hero'),'Visão geral voltou a criar hero duplicado abaixo do título do shell.');
$expect(!str_contains($overview,'Editar nome do curso'),'Visão geral voltou a confundir identidade do site com curso.');
$expect(str_contains($overview,'Configurar site'),'Visão geral deve nomear explicitamente a configuração do site.');
$expect(!str_contains($system,'overview-hero'),'Sistema voltou a criar hero duplicado abaixo do título do shell.');
$expect(!preg_match('/<style\b/i',$system),'Sistema voltou a conter CSS visual inline.');
$expect(str_contains($system,'admin-data-table'),'Diagnóstico técnico deve usar o padrão global de tabela de dados.');
$expect(str_contains($responsive,'.admin-toolbar')&&str_contains($responsive,'position:static'),'Toolbar da rota deve deixar de ser sticky no mobile.');
$expect(str_contains($responsive,'.admin-mobile-header')&&str_contains($responsive,'position:sticky'),'Cabeçalho mobile do shell deve ser a única autoridade sticky no topo.');

if($fail){fwrite(STDERR,"Admin product UX foundation regression failed:\n - ".implode("\n - ",$fail)."\n");exit(1);} 
echo "Admin product UX foundation regression passed.\n";
