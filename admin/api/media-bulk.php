<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
$db=database();$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=[];
if(!verify_csrf('media',$input['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'invalid_csrf']);exit;}
$ids=array_values(array_unique(array_filter(array_map('intval',is_array($input['ids']??null)?$input['ids']:[]),fn($id)=>$id>0)));if(!$ids){http_response_code(400);echo json_encode(['error'=>'no_assets']);exit;}
$action=(string)($input['action']??'');$result=['ok'=>[],'failed'=>[]];
foreach($ids as $id){
    try{
        if($action==='archive'){media_archive_asset($db,$id,true);}
        elseif($action==='restore'){media_archive_asset($db,$id,false);}
        elseif($action==='tag'){
            $asset=media_asset_admin($db,$id);$tags=array_values(array_unique(array_merge($asset['tags']??[],media_clean_tags($input['tags']??[]))));media_set_tags($db,$id,$tags);
        }else throw new RuntimeException('unsupported_action');
        $result['ok'][]=$id;
    }catch(Throwable $e){$result['failed'][]=['id'=>$id,'error'=>$e->getMessage()];}
}
echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
