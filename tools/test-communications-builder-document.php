<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/communications_builder_document.php';
function builder_assert(bool $value,string $message):void {
    if(!$value)throw new RuntimeException('builder document: '.$message);
}
function communication_site_url(PDO $db):string{return 'https://example.test';}
function media_url(string $path):string{return '/uploads/media/'.ltrim($path,'/');}
function communication_media_asset(PDO $db,int $id):array {
    if($id!==7)throw new RuntimeException('Imagem não autorizada.');
    return ['email_path'=>'test.jpg'];
}
$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$sample='<mjml><mj-head><mj-attributes><mj-all font-family="Arial,Helvetica,sans-serif"/></mj-attributes></mj-head>'
.'<mj-body background-color="#f1f0ed">'
.'<mj-section background-color="#ffffff" padding="14px 24px">'
.'<mj-column><mj-text font-size="16px" line-height="1.65" color="#252525">Mensagem <strong>importante</strong></mj-text>'
.'<mj-button href="https://example.test/info" background-color="#186f4d" border-radius="0px">Leia mais</mj-button></mj-column>'
.'<mj-column><mj-divider border-color="#eeeeee" border-width="1px"/></mj-column></mj-section>'
.'</mj-body></mjml>';
$ok=communication_builder_validate_document($db,$sample);
builder_assert($ok['version']===1,'explicit document version');
builder_assert($ok['counts']['sections']===1&&$ok['counts']['columns']===2&&$ok['counts']['buttons']===1,'structure counted');
builder_assert(str_contains($ok['mjml'],'<mj-column>'),'MJML canonicalized');
$cases=[
 'script'=>'<script>alert(1)</script>',
 'dangerous link'=>'href="javascript:alert(1)"',
 'external pixel'=>'<mj-image src="https://evil.test/x.png" alt="pixel"/>',
 'arbitrary style'=>'style="background:url(https://evil.test/x)"',
 'uncontrolled raw'=>'<mj-raw>not allowed</mj-raw>',
 'external stylesheet'=>'<mj-style>.track{background:red}</mj-style>',
 'event attribute'=>'onclick="alert(1)"',
 'unknown alignment'=>'align="justify"',
 'css variable'=>'color="var(--accent)"',
 'data URL'=>'href="data:text/html;base64,abc"',
];
foreach($cases as $name=>$fragment) {
    $value=($name==='external pixel'||$name==='uncontrolled raw'||$name==='external stylesheet'||$name==='script')
       ?str_replace('<mj-divider border-color="#eeeeee" border-width="1px"/>',$fragment,$sample)
       :str_replace('background-color="#186f4d"', 'background-color="#186f4d" '.$fragment,$sample);
    $rejected=false;try{communication_builder_validate_document($db,$value);}catch(RuntimeException){$rejected=true;}
    builder_assert($rejected,'reject '.$name);
}
$doctype='<!DOCTYPE mjml [<!ENTITY injected SYSTEM "file:///etc/passwd">]>'.$sample;
$rejected=false;try{communication_builder_validate_document($db,$doctype);}catch(RuntimeException){$rejected=true;}
builder_assert($rejected,'XML external entity is refused');
$image='<mj-image src="https://example.test/uploads/media/test.jpg" alt="Pessoa ao sol" padding="14px 0"/>';
$withImage=str_replace('<mj-divider border-color="#eeeeee" border-width="1px"/>',$image,$sample);
$accepted=communication_builder_validate_document($db,$withImage,['https://example.test/uploads/media/test.jpg'=>7]);
builder_assert($accepted['counts']['images']===1&&$accepted['media_ids']===[7],'public media ID provenance');
foreach([[],['https://example.test/uploads/media/test.jpg'=>19]] as $wrong){
    $rejected=false;try{communication_builder_validate_document($db,$withImage,$wrong);}catch(RuntimeException){$rejected=true;}
    builder_assert($rejected,'reject absent or unauthorized media reference');
}
builder_assert(strlen($accepted['mjml'])>100,'validated document can be retained for next migration stage');
echo "communication builder: strict MJML preflight OK\n";
