<?php
declare(strict_types=1);

function system_health_item(string $id,string $label,string $status,string $detail): array {
    if(!in_array($status,['ok','warn','error'],true))$status='warn';
    return ['id'=>$id,'label'=>$label,'status'=>$status,'detail'=>$detail];
}

function system_health_writable(string $path,string $id,string $label): array {
    $exists=file_exists($path);$target=$exists?$path:dirname($path);
    if(!$exists&&!is_dir($target))return system_health_item($id,$label,'error','Diretório pai indisponível.');
    if(!is_writable($target))return system_health_item($id,$label,'error','Sem permissão de escrita para o processo PHP.');
    return system_health_item($id,$label,'ok',$exists?'Gravação disponível.':'Diretório pai permite criar o recurso.');
}

function system_health_database(string $path): array {
    if(!is_file($path))return system_health_item('database','Banco SQLite','error','Arquivo do banco não encontrado.');
    if(!is_readable($path))return system_health_item('database','Banco SQLite','error','Banco sem permissão de leitura.');
    try{
        $probe=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $result=$probe->query('PRAGMA quick_check')->fetchColumn();$probe=null;
        if($result!=='ok')return system_health_item('database','Banco SQLite','error','A verificação de integridade não retornou OK.');
    }catch(Throwable $e){return system_health_item('database','Banco SQLite','error','Não foi possível abrir ou verificar o banco.');}
    if(!is_writable($path)||!is_writable(dirname($path)))return system_health_item('database','Banco SQLite','error','Integridade OK, mas o banco ou seu diretório não permite gravação.');
    return system_health_item('database','Banco SQLite','ok','Integridade e permissões de gravação verificadas.');
}

function system_health_collect(): array {
    $root=defined('APP_ROOT')?APP_ROOT:dirname(__DIR__);$storage=$root.'/storage';$uploads=$root.'/uploads';$db=$storage.'/database.sqlite';$updates=$storage.'/updates';
    $items=[];
    $phpOk=version_compare(PHP_VERSION,'8.2.0','>=');$items[]=system_health_item('php','PHP',$phpOk?'ok':'error','Versão '.PHP_VERSION.($phpOk?' compatível.':'; é necessário PHP 8.2 ou superior.'));
    foreach(['pdo_sqlite'=>'PDO SQLite','sqlite3'=>'SQLite3','dom'=>'DOM','mbstring'=>'mbstring'] as $extension=>$label)$items[]=system_health_item('ext-'.$extension,'Extensão '.$label,extension_loaded($extension)?'ok':'error',extension_loaded($extension)?'Carregada.':'Não está carregada no PHP.');
    $items[]=system_health_database($db);
    $items[]=system_health_writable($uploads,'uploads','Uploads');
    $items[]=system_health_writable($storage,'storage','Storage');
    $items[]=system_health_writable($updates,'updates','Backups e atualizações');
    $mailAvailable=function_exists('mail');$items[]=system_health_item('mail','Envio de e-mail',$mailAvailable?'ok':'warn',$mailAvailable?'A função mail() está disponível; a entrega final depende da configuração da hospedagem.':'A função mail() não está disponível; notificações por e-mail não poderão ser enviadas por este método.');
    $free=@disk_free_space($root);$total=@disk_total_space($root);
    if(is_float($free)||is_int($free)){
        $free=(float)$free;$total=(is_float($total)||is_int($total))?(float)$total:0.0;$gb=$free/1073741824;$ratio=$total>0?$free/$total:1.0;$status=($free<268435456||$ratio<0.03)?'error':(($free<1073741824||$ratio<0.10)?'warn':'ok');
        $items[]=system_health_item('disk','Espaço em disco',$status,number_format($gb,2,',','.').' GB disponíveis no volume da aplicação.');
    }else $items[]=system_health_item('disk','Espaço em disco','warn','A hospedagem não informou o espaço livre.');
    $backups=function_exists('update_backup_history')?update_backup_history():[];$count=count($backups);$items[]=system_health_item('backups','Backups locais',$count>0?'ok':'warn',$count.' cópia(s) registrada(s) pelo atualizador.');
    $errors=count(array_filter($items,fn(array $item):bool=>$item['status']==='error'));$warnings=count(array_filter($items,fn(array $item):bool=>$item['status']==='warn'));
    return ['status'=>$errors?'error':($warnings?'warn':'ok'),'errors'=>$errors,'warnings'=>$warnings,'items'=>$items,'checkedAt'=>gmdate('c')];
}
