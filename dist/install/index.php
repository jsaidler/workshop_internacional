<?php
declare(strict_types=1);
require_once __DIR__.'/../app/database.php';
require_once __DIR__.'/../app/content/content_validator.php';
require_once __DIR__.'/../app/activity_repository.php';
require_once __DIR__.'/../app/content/content_repository.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
session_name('workshop_install');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);
session_start();

const INSTALL_ROOT=__DIR__.'/..';
function out(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function token(string $scope): string { $_SESSION['install_csrf'][$scope]??=bin2hex(random_bytes(32)); return $_SESSION['install_csrf'][$scope]; }
function valid_token(string $scope, mixed $value): bool { return is_string($value)&&hash_equals($_SESSION['install_csrf'][$scope]??'', $value); }
function problem(string $code, string $detail='', int $status=422): never { error_log("installer.$code $detail"); http_response_code($status); render('Installation cannot continue',"<p class=error><strong>".out($code)."</strong></p><p>A predictable installation condition prevented this step. Check the server setup and try again.</p>"); }
function render(string $title,string $body,int $status=200): never { http_response_code($status);echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.out($title).'</title><style>body{margin:0;background:#f3f4f1;color:#101313;font:16px Arial,sans-serif}main{max-width:720px;margin:48px auto;padding:28px;background:#fff;border:1px solid #d4d8d5}h1{margin-top:0}label{display:block;margin:15px 0;font-weight:700}input,select{box-sizing:border-box;width:100%;margin-top:6px;padding:10px;border:1px solid #aab2ae;font:inherit}button,a.button{display:inline-block;margin-top:18px;padding:11px 15px;background:#186f4d;color:#fff;border:0;text-decoration:none;font:inherit;cursor:pointer}.error{padding:12px;background:#fae5e2;color:#8b1f16}.notice{padding:12px;background:#e0efe6;color:#155a3a}.warn{padding:12px;background:#fff3d8}.checks{padding-left:20px}.checks li{margin:7px 0}.ok{color:#155a3a}.bad{color:#8b1f16}</style></head><body><main><p>Direct Positive Workshop</p><h1>'.out($title).'</h1>'.$body.'</main></body></html>'; exit; }
function requirements(): array { $simulate=getenv('INSTALLER_SIMULATE_REQUIREMENT')?:''; $storage=INSTALL_ROOT.'/storage'; $config=INSTALL_ROOT.'/config'; return [
    'php_version_unsupported'=>['ok'=>$simulate!=='php'&&version_compare(PHP_VERSION,'8.2.0','>='),'label'=>'PHP 8.2+ ('.PHP_VERSION.')'],
    'pdo_missing'=>['ok'=>$simulate!=='pdo'&&extension_loaded('PDO'),'label'=>'PDO extension'],
    'sqlite_extension_missing'=>['ok'=>$simulate!=='sqlite'&&extension_loaded('pdo_sqlite'),'label'=>'pdo_sqlite extension'],
    'mbstring_missing'=>['ok'=>$simulate!=='mbstring'&&extension_loaded('mbstring'),'label'=>'mbstring extension'],
    'storage_not_writable'=>['ok'=>$simulate!=='storage'&&is_dir($storage)&&is_writable($storage),'label'=>'storage writable'],
    'config_not_writable'=>['ok'=>$simulate!=='config'&&is_dir($config)&&is_writable($config),'label'=>'config writable'],
]; }
function environment_page(): never { $items=requirements(); $list='';$failed=[];foreach($items as $code=>$item){$list.='<li class="'.($item['ok']?'ok':'bad').'">'.($item['ok']?'✓':'×').' '.out($item['label']).(!$item['ok']?' — '.out($code):'').'</li>';if(!$item['ok'])$failed[]=$code;} $https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';$warning=$https?'':'<p class="warn">HTTPS was not detected. Complete installation through HTTPS before production use.</p>';if($failed)render('Environment check',$warning.'<ul class="checks">'.$list.'</ul><p class="error">Resolve the listed requirement and reload this page.</p>');render('Environment check',$warning.'<ul class="checks">'.$list.'</ul><form method="post"><input type="hidden" name="_csrf" value="'.out(token('key')).'"><label>Installation key<input name="install_key" type="password" autocomplete="off" required></label><button name="action" value="key">Continue</button></form>'); }

if(is_file(INSTALL_ROOT.'/storage/installed.lock')) render('Installer unavailable','<p class="error">This application is already installed.</p>',403);
$installFile=INSTALL_ROOT.'/config/install.php';
if(!is_file($installFile)) render('Installation key required','<p class="error"><strong>install_key_missing</strong></p><p>Create <code>config/install.php</code> from <code>config/install.example.php</code>, set a long random key, then reload.</p>',403);
$installConfig=require $installFile;$installKey=$installConfig['install_key']??null;
if(!is_string($installKey)||strlen($installKey)<16) render('Installation key required','<p class="error"><strong>install_key_invalid</strong></p><p>Use a random installation key of at least 16 characters.</p>',403);

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='key'){
    if(!valid_token('key',$_POST['_csrf']??null)) problem('csrf_invalid','key');
    $attempts=(int)($_SESSION['install_key_attempts']??0);if($attempts>=5) problem('install_key_rate_limited','',429);
    if(!hash_equals($installKey,(string)($_POST['install_key']??''))){$_SESSION['install_key_attempts']=$attempts+1;render('Installation key required','<p class="error"><strong>install_key_invalid</strong></p><p>The key did not match. Try again.</p><form method="post"><input type="hidden" name="_csrf" value="'.out(token('key')).'"><label>Installation key<input name="install_key" type="password" required></label><button name="action" value="key">Continue</button></form>',403);}
    $_SESSION['install_key_verified']=true;unset($_SESSION['install_key_attempts']);
}
if(empty($_SESSION['install_key_verified'])) environment_page();
foreach(requirements() as $code=>$item)if(!$item['ok']) problem($code);

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='configuration'){
    if(!valid_token('configuration',$_POST['_csrf']??null)) problem('csrf_invalid','configuration');
    $name=trim((string)($_POST['site_name']??''));$url=trim((string)($_POST['public_url']??''));$timezone=(string)($_POST['timezone']??'UTC');
    if($name===''||mb_strlen($name)>120||!filter_var($url,FILTER_VALIDATE_URL)||!in_array($timezone,DateTimeZone::listIdentifiers(),true)) render('Site configuration','<p class="error">Enter a site name, an absolute public URL and a valid timezone.</p>'.configuration_form($name,$url,$timezone));
    $_SESSION['install_configuration']=['site_name'=>$name,'public_url'=>$url,'timezone'=>$timezone,'app_secret'=>bin2hex(random_bytes(32)),'ip_hash_secret'=>bin2hex(random_bytes(32))];
}
function configuration_form(string $name='',string $url='',string $timezone='UTC'): string { $zones=['UTC','America/Sao_Paulo','Europe/London','America/New_York'];$options='';foreach($zones as $zone)$options.='<option value="'.out($zone).'"'.($timezone===$zone?' selected':'').'>'.out($zone).'</option>';return '<form method="post"><input type="hidden" name="_csrf" value="'.out(token('configuration')).'"><label>Site name<input name="site_name" value="'.out($name).'" required></label><label>Public URL<input name="public_url" type="url" value="'.out($url).'" placeholder="https://example.com" required></label><label>Timezone<select name="timezone">'.$options.'</select></label><button name="action" value="configuration">Continue</button></form>'; }
if(empty($_SESSION['install_configuration'])) render('Site configuration',configuration_form());

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='administrator'){
    if(!valid_token('administrator',$_POST['_csrf']??null)) problem('csrf_invalid','administrator');
    $email=strtolower(trim((string)($_POST['email']??'')));$password=(string)($_POST['password']??'');$confirm=(string)($_POST['password_confirmation']??'');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12||$password!==$confirm) render('First administrator','<p class="error">Use a valid email, a password of at least 12 characters, and matching confirmation.</p>'.administrator_form($email));
    $_SESSION['install_administrator']=['email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT)];
}
function administrator_form(string $email=''): string { return '<form method="post"><input type="hidden" name="_csrf" value="'.out(token('administrator')).'"><label>Email<input name="email" type="email" value="'.out($email).'" autocomplete="email" required></label><label>Password<input name="password" type="password" autocomplete="new-password" minlength="12" required></label><label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required></label><button name="action" value="administrator">Review installation</button></form>'; }
if(empty($_SESSION['install_administrator'])) render('First administrator',administrator_form());

if($_SERVER['REQUEST_METHOD']!=='POST'||($_POST['action']??'')!=='install') render('Ready to install','<p class="notice">The installer will create the local configuration, database, initial public-content settings and first administrator.</p><form method="post"><input type="hidden" name="_csrf" value="'.out(token('install')).'"><button name="action" value="install">Install now</button></form>');
if(!valid_token('install',$_POST['_csrf']??null)) problem('csrf_invalid','install');
$database=INSTALL_ROOT.'/storage/database.sqlite';$local=INSTALL_ROOT.'/config/local.php';
if(is_file($database)||is_file($local)) problem('installation_already_started','database or local configuration exists',409);
$config=$_SESSION['install_configuration'];$admin=$_SESSION['install_administrator'];
try {
    $php="<?php\nreturn ".var_export($config+['rate_limit_max_attempts'=>5,'rate_limit_window_seconds'=>3600],true).";\n";
    $temporary=tempnam(INSTALL_ROOT.'/config','local-');if($temporary===false||file_put_contents($temporary,$php,LOCK_EX)===false||!@rename($temporary,$local)) { if(is_file($temporary??''))@unlink($temporary); throw new RuntimeException('Could not write local configuration.'); }
} catch(Throwable $e) { problem('configuration_write_failed',$e->getMessage(),500); }
try {
    $db=database_connection($database);$db->beginTransaction();run_migrations($db);
    $settings=$db->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?)');foreach(['site_name','public_url','timezone'] as $key)$settings->execute([$key,(string)$config[$key]]);$initialContent=file_get_contents(INSTALL_ROOT.'/data/public-content.json');if($initialContent===false||json_decode($initialContent,true)===null)throw new RuntimeException('Initial public content is invalid.');$settings->execute(['public_content_source','data/public-content.json']);$settings->execute(['initial_public_content',$initialContent]);
    $insert=$db->prepare('INSERT INTO admin_users(email,password_hash,created_at,updated_at) VALUES(?,?,?,?)');$now=gmdate('c');$insert->execute([$admin['email'],$admin['password_hash'],$now,$now]);content_seed($db);$db->commit();
} catch(Throwable $e) { if(isset($db)&&$db->inTransaction())$db->rollBack();error_log('installer.database '.$e->getMessage());if(is_file($database))@unlink($database);if(is_file($local))@unlink($local);problem(str_contains($e->getMessage(),'migration')?'migration_failed':'database_initialization_failed',$e->getMessage(),500); }
if(file_put_contents(INSTALL_ROOT.'/storage/installed.lock',json_encode(['installed_at'=>gmdate('c')])."\n",LOCK_EX)===false) problem('configuration_write_failed','Could not create installed lock.',500);
unset($_SESSION['install_key_verified'],$_SESSION['install_configuration'],$_SESSION['install_administrator'],$_SESSION['install_csrf']);
render('Installation complete','<p class="notice">The application is installed successfully.</p><p>Delete <code>config/install.php</code> now. It is no longer required.</p><p><a class="button" href="/">Open site</a> <a class="button" href="/admin/login.php">Admin login</a></p>');
