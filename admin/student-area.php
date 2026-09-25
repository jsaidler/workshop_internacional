<?php
declare(strict_types=1);

$activityId=(int)($_GET['activity']??$_POST['activity']??0);
$view=(string)($_GET['view']??'');
$legacyEditorialActions=['set_page_access','save_section_map','bind_page_media','unbind_page_media'];
$action=(string)($_POST['action']??'');

if($view==='pages'){
    $target='/admin/pages.php'.($activityId>0?'?activity='.$activityId:'');
    header('Location: '.$target,true,303);
    exit;
}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&in_array($action,$legacyEditorialActions,true)){
    http_response_code(410);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Esta ação editorial foi movida para o CMS. Use Páginas/Editor e Biblioteca de mídia.';
    exit;
}

ob_start();
require __DIR__.'/student-area-legacy.php';
$html=(string)ob_get_clean();
$html=preg_replace('~<a\b[^>]*href="[^"]*view=pages[^"]*"[^>]*>Páginas protegidas</a>~u','',$html)??$html;
$html=preg_replace('~<div class="admin-stat"><span>Páginas protegidas</span><strong>.*?</strong></div>~us','',$html)??$html;
$html=preg_replace('~<article class="overview-card"><h2>Páginas protegidas</h2>.*?</article>~us','',$html)??$html;
$html=str_replace('Turmas, alunos, testes, aulas e conteúdo protegido organizados pelo objeto administrado.','Turmas, alunos, testes e aulas organizados pelo curso. Páginas, acesso editorial e mídia são administrados no CMS.',$html);
echo $html;
