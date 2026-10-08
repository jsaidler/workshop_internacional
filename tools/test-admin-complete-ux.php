<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$fail=[];
function need(string $haystack,string $needle,string $message):void{global $fail;if(!str_contains($haystack,$needle))$fail[]=$message;}
function forbid(string $haystack,string $needle,string $message):void{global $fail;if(str_contains($haystack,$needle))$fail[]=$message;}

$shell=(string)file_get_contents($root.'/app/admin_shell.php');
need($shell,"'site'=>['Cabeçalho e navegação'","Menu Site deve nomear a tarefa completa.");
need($shell,"'design'=>['Design'","Menu deve usar Design como nome canônico.");
need($shell,"'activities'=>['Identidade da instalação'","Manutenção de activity legada não pode se chamar Estrutura do site.");
need($shell,'admin-nav-backdrop','Shell deve possuir backdrop do drawer.');

$adminJs=(string)file_get_contents($root.'/assets/admin.js');
need($adminJs,"matchMedia('(max-width: 1000px)')",'Drawer administrativo deve usar breakpoint intermediário canônico.');
need($adminJs,"event.key === 'Escape'",'Drawer deve fechar por Escape.');
need($adminJs,'navigationBackdrop','Drawer deve fechar pelo backdrop.');
need($adminJs,"event.key === 'Tab'",'Drawer deve conter o foco de teclado enquanto estiver aberto.');

$responsive=(string)file_get_contents($root.'/assets/admin-shell-responsive.css');
need($responsive,'@media(max-width:1000px)','Shell responsivo deve cobrir tablet/desktop estreito.');
need($responsive,'.admin-nav-backdrop','CSS deve renderizar backdrop do drawer.');
need($responsive,'min-height:40px','Controles frequentes no admin móvel precisam de alvo mínimo de 40 px.');

$legacy=(string)file_get_contents($root.'/admin/student-area-legacy.php');
forbid($legacy,'admin_shell_start(','student-area-legacy.php não pode renderizar uma segunda administração.');
need($legacy,"header('Location: '",'student-area-legacy.php deve ser apenas compatibilidade por redirecionamento.');

$access=(string)file_get_contents($root.'/editor/cms-access-controls.js');
need($access,"selectedSection()!==section||inspector.querySelector('[data-cms-access-controls]')",'Injeção de acesso de seção deve rever idempotência depois do await.');
need($access,"#p-title,#page-title')||inspector.querySelector('[data-cms-page-access]')",'Injeção de acesso de página deve rever idempotência depois do await.');

$coherence=(string)file_get_contents($root.'/editor/cms-inspector-coherence.js');
need($coherence,"page-global','Configurações globais'","Links globais devem ficar em grupo secundário próprio.");
forbid($coherence,'display:none!important','Inspector não pode reintroduzir !important em style injetado.');

$editorCss=(string)file_get_contents($root.'/editor/editor-system.css');
need($editorCss,'@media(max-width:1024px){html,body.task-centric-editor','Editor task-centric deve colapsar painéis em tablet.');
foreach([
 'box-shadow:noneposition','var(--editor-body)text-decoration','text-transform:noneopacity','autojustify-content',
 '#dfe0dcoverflow','#c8cac4transition','72pxresize','rgba(0,0,0,.18)position',
 'var(--editor-line)justify-content','#fafaf7text-align','var(--editor-body)cursor',
 '14pxmax-height','4pxtext-align','var(--editor-body)position','70opacity'
] as $bad)forbid($editorCss,$bad,'CSS do editor contém declaração fundida: '.$bad);

$courses=(string)file_get_contents($root.'/admin/courses.php');
forbid($courses,'<h2>Cursos</h2><p>Escolha um curso','Coleção de cursos não pode repetir o título primário do shell.');
$students=(string)file_get_contents($root.'/admin/students.php');
forbid($students,'<h2>Alunos</h2><p>Busca global','Busca global de alunos não pode repetir o título primário do shell.');
$activities=(string)file_get_contents($root.'/admin/activities.php');
forbid($activities,'<h2>Identidade da instalação</h2>','Identidade da instalação não pode repetir o título primário dentro da página.');

$integrity=(string)file_get_contents($root.'/admin/data-integrity.php');
forbid($integrity,'overview-hero','Integridade não pode reintroduzir hero administrativo legado.');

$blocks=(string)file_get_contents($root.'/admin/blocks.php');
forbid($blocks,'overview-hero','Blocos reutilizáveis não pode reintroduzir hero administrativo legado.');
forbid($blocks,'overview-grid','Blocos reutilizáveis deve usar composição administrativa canônica.');

$audit=(string)file_get_contents($root.'/tools/browser-tests/admin-complete-product-audit.spec.cjs');
foreach(['390','768','1280','1600','media-detail','registration','person','process','responses','integrity','activities','seo','blocks','course-students'] as $needle)need($audit,$needle,'Audit completo não cobre requisito: '.$needle);
$editorAudit=(string)file_get_contents($root.'/tools/browser-tests/editor-admin-ux-audit.spec.cjs');
foreach(['phone','tablet','compact','wide','empty','page','section','structure-open','canonical CSS declarations'] as $needle)need($editorAudit,$needle,'Audit do editor não cobre requisito: '.$needle);

if($fail){fwrite(STDERR,"Admin complete UX regression failed:\n - ".implode("\n - ",array_unique($fail))."\n");exit(1);}
echo "Admin complete UX regression passed.\n";
