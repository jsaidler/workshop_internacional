<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
$db=database();
try{
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        if(!verify_csrf('communications-admin',$_POST['_csrf']??null)){http_response_code(403);throw new RuntimeException('Sessão expirada. Atualize a página.');}
        if(!isset($_FILES['image'])||!is_array($_FILES['image']))throw new RuntimeException('Escolha uma imagem.');
        $file=$_FILES['image'];
        if((int)($file['size']??0)>8*1024*1024)throw new RuntimeException('A imagem deve ter até 8 MB.');
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($file['tmp_name']??'')))
            throw new RuntimeException('Não foi possível receber a imagem.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))
            throw new RuntimeException('Escolha uma imagem JPEG, PNG ou WebP.');
        $image=media_create($db,$file);
        $newId=(int)($image['id']??0);
        if($newId<1)throw new RuntimeException('Não foi possível incluir a imagem na Biblioteca.');
        $asset=communication_media_asset($db,$newId);
        echo json_encode(['items'=>[['id'=>$newId,'title'=>(string)$asset['title'],
          'alt'=>(string)($asset['default_alt']??''),'src'=>media_url((string)$asset['email_path'])]]],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;
    }
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);throw new RuntimeException('Método inválido.');}
    echo json_encode(['items'=>communication_media_choices($db)],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){
    if(http_response_code()<400)http_response_code(422);
    echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
