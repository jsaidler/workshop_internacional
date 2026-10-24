<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/communications_builder_renderer.php';
function render_assert(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('MJML renderer: '.$message);
}
function communication_site_url(PDO $db):string{return 'https://example.test';}
function media_url(string $path):string{return '/uploads/media/'.ltrim($path,'/');}
function communication_media_asset(PDO $db,int $id):array {
    if($id!==7)throw new RuntimeException('Imagem não autorizada.');
    return ['email_path'=>'photo.jpg'];
}
$db=new PDO('sqlite::memory:');
$source='<mjml><mj-head><mj-attributes><mj-all font-family="Arial,Helvetica,sans-serif"/></mj-attributes></mj-head><mj-body background-color="#f1f0ed">'
 .'<mj-section background-color="#ffffff" padding="20px 24px">'
 .'<mj-column><mj-text font-size="22px" color="#202427">Laboratório <strong>fotográfico</strong></mj-text>'
 .'<mj-button href="https://example.test/workshop" background-color="#186f4d" border-radius="0">Ver detalhes</mj-button></mj-column>'
 .'<mj-column><mj-image src="https://example.test/uploads/media/photo.jpg" alt="Retrato no laboratório" padding="14px 0"/>'
 .'<mj-divider border-color="#eeeeee" border-width="1px"/><mj-spacer height="20px"/></mj-column></mj-section></mj-body></mjml>';
$out=communication_builder_render_email($db,$source,['https://example.test/uploads/media/photo.jpg'=>7]);
render_assert(str_contains($out['html'],'<table')&&!str_contains($out['html'],'<mj-column'),'HTML converted to table layout');
render_assert(str_contains($out['html'],'class="email-col"')&&str_contains($out['html'],'width="50%"'),'two columns visible');
render_assert(str_contains($out['html'],'Laboratório <strong>fotográfico</strong>'),'typography preserved');
render_assert(str_contains($out['html'],'href="https://example.test/workshop"'),'button URL preserved safely');
render_assert(str_contains($out['html'],'font-size:13px;line-height:1;color:#000000;padding:10px 25px'),'MJML default text rhythm');
render_assert(str_contains($out['html'],'font-size:13px;font-weight:normal;line-height:120%'),'MJML default button rhythm');
render_assert(str_contains($out['html'],'@media only screen and (max-width:479px)'),'MJML-compatible 480px column breakpoint');
render_assert(str_contains($out['html'],'alt="Retrato no laboratório"'),'accessible public image');
render_assert(str_contains($out['html'],'<style>@media only screen'),'mobile layout included');
render_assert(str_contains($out['text'],'Laboratório fotográfico')&&str_contains($out['text'],'Ver detalhes'),'plain text generated');
render_assert(strlen($out['sha256'])===64&&$out['sha256']===hash('sha256',$out['html']),'output digest stable');
render_assert(communication_builder_render_email($db,$source,['https://example.test/uploads/media/photo.jpg'=>7])['html']===$out['html'],'deterministic rendering');
$invalid=str_replace('href="https://example.test/workshop"','href="javascript:alert(1)"',$source);
$blocked=false;try{communication_builder_render_email($db,$invalid,['https://example.test/uploads/media/photo.jpg'=>7]);}catch(RuntimeException){$blocked=true;}
render_assert($blocked,'unsafe URL blocked by shared validator');
echo "MJML server renderer: deterministic email tables and safety OK\n";
