<?php
declare(strict_types=1);

/** Credentials can be managed in the authenticated CMS without touching hosting files. */
function communication_brevo_credentials_available(PDO $db): bool {
    return (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_brevo_credentials'")->fetchColumn();
}
function communication_brevo_secret_key(): string {
    $secret=(string)(app_config()['app_secret']??'');
    if(strlen($secret)<32)throw new RuntimeException('O segredo da instalação não está configurado.');
    return hash_hmac('sha256','communication-brevo-credentials-v1',$secret,true);
}
function communication_brevo_encrypt(string $key): array {
    if(!function_exists('openssl_encrypt'))throw new RuntimeException('A hospedagem precisa disponibilizar a extensão de criptografia do PHP.');
    $nonce=random_bytes(12);$tag='';
    $data=openssl_encrypt($key,'aes-256-gcm',communication_brevo_secret_key(),OPENSSL_RAW_DATA,$nonce,$tag,'brevo-api-key-v1',16);
    if($data===false||strlen($tag)!==16)throw new RuntimeException('Não foi possível proteger a credencial.');
    return ['ciphertext'=>base64_encode($data),'nonce'=>base64_encode($nonce),'auth_tag'=>base64_encode($tag)];
}
function communication_brevo_decrypt(array $row): string {
    if(!function_exists('openssl_decrypt'))throw new RuntimeException('A hospedagem precisa disponibilizar a extensão de criptografia do PHP.');
    $cipher=base64_decode((string)($row['ciphertext']??''),true);
    $nonce=base64_decode((string)($row['nonce']??''),true);
    $tag=base64_decode((string)($row['auth_tag']??''),true);
    if(!is_string($cipher)||!is_string($nonce)||!is_string($tag)||strlen($nonce)!==12||strlen($tag)!==16)
        throw new RuntimeException('A credencial salva precisa ser substituída.');
    $key=openssl_decrypt($cipher,'aes-256-gcm',communication_brevo_secret_key(),OPENSSL_RAW_DATA,$nonce,$tag,'brevo-api-key-v1');
    if(!is_string($key)||$key==='')throw new RuntimeException('A credencial salva não pôde ser lida. Substitua a chave.');
    return $key;
}
function communication_brevo_saved(PDO $db): ?array {
    if(!communication_brevo_credentials_available($db))return null;
    $q=$db->query('SELECT ciphertext,nonce,auth_tag,verified_at,updated_at FROM communication_brevo_credentials WHERE id=1');
    return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function communication_brevo_key(PDO $db): string {
    $saved=communication_brevo_saved($db);
    if($saved)return communication_brevo_decrypt($saved);
    $cfg=app_config();
    return trim((string)($cfg['brevo_api_key']??getenv('BREVO_API_KEY')?:''));
}
function communication_brevo_public_status(PDO $db): array {
    $saved=communication_brevo_saved($db);
    $cfg=app_config();
    return [
        'configured'=>$saved!==null||trim((string)($cfg['brevo_api_key']??getenv('BREVO_API_KEY')?:''))!=='',
        'managed_in_cms'=>$saved!==null,
        'verified_at'=>$saved['verified_at']??null,
    ];
}
/**
 * Verify the API credential without sending messages. The injectable validator
 * allows deterministic tests without leaking credentials or contacting Brevo.
 */
function communication_brevo_verify_api_key(string $key): void {
    if(!function_exists('curl_init'))throw new RuntimeException('O servidor não tem suporte para conectar ao Brevo.');
    $request=curl_init('https://api.brevo.com/v3/account');
    if($request===false)throw new RuntimeException('Não foi possível iniciar a conexão com o Brevo.');
    curl_setopt_array($request,[
        CURLOPT_HTTPGET=>true,CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>5,
        CURLOPT_HTTPHEADER=>['api-key: '.$key,'Accept: application/json'],
    ]);
    $raw=curl_exec($request);$status=(int)curl_getinfo($request,CURLINFO_HTTP_CODE);
    curl_close($request);
    if($raw===false)throw new RuntimeException('Não foi possível conectar ao Brevo. Verifique a conexão da hospedagem.');
    if($status===401||$status===403)throw new RuntimeException('O Brevo não autorizou esta chave. Confira a chave API v3.');
    if($status!==200)throw new RuntimeException('O Brevo não respondeu à validação da chave (HTTP '.$status.').');
    $result=json_decode((string)$raw,true);
    if(!is_array($result))throw new RuntimeException('A resposta de autenticação do Brevo não foi reconhecida.');
}
function communication_brevo_validate_key_format(string $key): void {
    if(strlen($key)<12||strlen($key)>512||preg_match('/\s|[\x00-\x1F\x7F]/',$key))
        throw new RuntimeException('Cole uma chave API v3 válida do Brevo.');
}
function communication_brevo_save(PDO $db,string $key,?callable $verify=null): void {
    $key=trim($key);
    communication_brevo_validate_key_format($key);
    // Never replace a working credential until the new one is authenticated.
    ($verify??'communication_brevo_verify_api_key')($key);
    $encrypted=communication_brevo_encrypt($key);
    $now=utc_now();
    $q=$db->prepare('INSERT INTO communication_brevo_credentials(id,ciphertext,nonce,auth_tag,verified_at,updated_at)
        VALUES(1,?,?,?,?,?) ON CONFLICT(id) DO UPDATE SET
        ciphertext=excluded.ciphertext,nonce=excluded.nonce,auth_tag=excluded.auth_tag,
        verified_at=excluded.verified_at,updated_at=excluded.updated_at');
    $q->execute([$encrypted['ciphertext'],$encrypted['nonce'],$encrypted['auth_tag'],$now,$now]);
}
function communication_brevo_check_connection(PDO $db,?callable $verify=null): void {
    $key=communication_brevo_key($db);
    if($key==='')throw new RuntimeException('Cadastre a chave API do Brevo para testar a conexão.');
    ($verify??'communication_brevo_verify_api_key')($key);
    if(communication_brevo_saved($db)!==null){
        $db->prepare('UPDATE communication_brevo_credentials SET verified_at=? WHERE id=1')->execute([utc_now()]);
    }
}
function communication_brevo_remove(PDO $db): void {
    if(communication_brevo_credentials_available($db))$db->exec('DELETE FROM communication_brevo_credentials WHERE id=1');
}
