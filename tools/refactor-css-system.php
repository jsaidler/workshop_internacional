<?php
declare(strict_types=1);

$root=dirname(__DIR__);

function css_path(string $relative): string {
    global $root;
    return $root.'/'.$relative;
}
function css_read(string $relative): string {
    $path=css_path($relative);
    $value=@file_get_contents($path);
    if($value===false)throw new RuntimeException('Cannot read '.$relative);
    return $value;
}
function css_write(string $relative,string $content): void {
    $path=css_path($relative);
    $dir=dirname($path);
    if(!is_dir($dir)&&!mkdir($dir,0777,true)&&!is_dir($dir))throw new RuntimeException('Cannot create '.$dir);
    if(file_put_contents($path,$content)===false)throw new RuntimeException('Cannot write '.$relative);
}
function css_strip_important(string $css): string {
    return (string)preg_replace('/\s*!important\b/i','',$css);
}
function css_segment(string $title,string $content): string {
    return "\n\n/* --------------------------------------------------------------------------\n   {$title}\n   -------------------------------------------------------------------------- */\n".trim($content)."\n";
}
function css_delete(string $relative): void {
    $path=css_path($relative);
    if(is_file($path)&&!unlink($path))throw new RuntimeException('Cannot delete '.$relative);
}
function text_replace_file(string $relative,array $replace): void {
    $content=css_read($relative);
    $updated=str_replace(array_keys($replace),array_values($replace),$content);
    if($updated===$content)throw new RuntimeException('Expected replacement not applied in '.$relative);
    css_write($relative,$updated);
}

// Shared spacing tokens belong to the primitive layer, not to a late UX patch.
$experience=css_read('assets/experience-ux.css');
$adminMarker=strpos($experience,'/* Admin shell:');
$studentMarker=strpos($experience,'/* Student shell:');
if($adminMarker===false||$studentMarker===false||$studentMarker<=$adminMarker)throw new RuntimeException('Unexpected experience-ux.css structure');
$spacing=trim(substr($experience,0,$adminMarker));
$adminExperience=trim(substr($experience,$adminMarker,$studentMarker-$adminMarker));
$studentExperience=trim(substr($experience,$studentMarker));

$uiCore=css_read('assets/ui-core.css');
if(!str_contains($uiCore,'--ux-space-1:'))$uiCore=$spacing."\n\n".$uiCore;
css_write('assets/ui-core.css',css_strip_important($uiCore));

// ADMIN: collapse chronological global layers into one canonical surface stylesheet.
$adminSystem='';
$adminSystem.=css_segment('Admin foundations, shell and legacy component baseline',css_read('admin/admin.css'));
$adminSystem.=css_segment('Administrative CMS components',css_read('admin/cms-admin.css'));
$adminSystem.=css_segment('Advanced administrative components',css_read('admin/pro-admin.css'));
$adminSystem.=css_segment('Canonical administrative application shell and component refinements',css_read('assets/admin-ux-v2.css')."\n".css_read('assets/admin-ux-v3.css'));
$adminSystem.=css_segment('Data display, forms and operational components',css_read('assets/admin-data-ux.css')."\n".css_read('assets/admin-form-ux.css'));
$adminSystem.=css_segment('Shared administrative spacing and composition',$spacing."\n".$adminExperience);
$adminSystem.=css_segment('Administrative structural invariants',<<<'CSS'
body.admin-page input[type="checkbox"],
body.admin-page input[type="radio"]{
  box-sizing:border-box;
  width:18px;
  height:18px;
  min-width:18px;
  min-height:18px;
  max-width:18px;
  max-height:18px;
  padding:0;
  margin:0;
  flex:0 0 18px;
  box-shadow:none;
  accent-color:var(--admin-focus,#186f4d);
}
.admin-sidebar .admin-wordmark-context{display:block;margin-top:1px}
CSS);
css_write('assets/admin-system.css',css_strip_important($adminSystem));

// Media is a genuine admin feature: keep its CSS out of every other admin page.
$adminMedia=css_segment('Media maintenance components',css_read('assets/admin-media-maintenance.css'))
    .css_segment('Media task interface',css_read('assets/admin-media-task.css'));
css_write('assets/admin-media.css',css_strip_important($adminMedia));

$adminShell=css_read('app/admin_shell.php');
$legacyAdminLinks=[
    '/admin/admin.css','/admin/cms-admin.css','/admin/pro-admin.css','/assets/admin-media-maintenance.css',
    '/assets/admin-ux-v2.css','/assets/admin-ux-v3.css','/assets/admin-data-ux.css','/assets/admin-form-ux.css','/assets/experience-ux.css'
];
$first=true;
foreach($legacyAdminLinks as $asset){
    $tag='<link rel="stylesheet" href="<?=h(admin_asset_url(\''.$asset.'\'))?>">';
    if($first){
        if(!str_contains($adminShell,$tag))throw new RuntimeException('Admin stylesheet tag not found: '.$asset);
        $adminShell=str_replace($tag,'<link rel="stylesheet" href="<?=h(admin_asset_url(\'/assets/admin-system.css\'))?>">',$adminShell);
        $first=false;
    }else{
        $adminShell=str_replace($tag,'',$adminShell);
    }
}
$adminShell=str_replace("$root.'/assets/admin-ux-v3.css'","$root.'/assets/admin-system.css'",$adminShell);
$adminShell=(string)preg_replace('~<style id="admin-system-choice-controls">.*?</style>~s','',$adminShell);
$adminShell=(string)preg_replace('~<style id="admin-shell-structure">.*?</style>~s','',$adminShell);
css_write('app/admin_shell.php',$adminShell);

$login=css_read('admin/login.php');
$login=str_replace('/admin/admin.css','/assets/admin-system.css',$login);
css_write('admin/login.php',$login);

$media=css_read('admin/media.php');
$media=str_replace('/assets/admin-media-task.css','/assets/admin-media.css',$media);
css_write('admin/media.php',$media);

// STUDENT: absorb the late experience patch into the canonical student surface.
$student=css_read('assets/student-area.css');
$student.=css_segment('Course context and application rhythm',$studentExperience);
css_write('assets/student-area.css',css_strip_important($student));
$studentShell=css_read('app/student_shell.php');
$studentShell=str_replace('<link rel="stylesheet" href="/assets/experience-ux.css?v=<?=h($assetVersion)?>">','',$studentShell);
css_write('app/student_shell.php',$studentShell);

// PUBLIC CMS: merge only adjacent chronological core layers; keep real feature modules semantic.
$cmsCore=css_segment('CMS public core',css_read('assets/cms.css'))
    .css_segment('CMS public core refinements',css_read('assets/cms-v3.css'));
css_write('assets/cms-core.css',css_strip_important($cmsCore));
css_write('assets/cms-editorial.css',css_strip_important(css_read('assets/cms-ui-refinements.css')));

$renderer=css_read('app/cms_renderer.php');
$renderer=str_replace("$root.'/assets/cms-v3.css'","$root.'/assets/cms-core.css'",$renderer);
$renderer=str_replace("'/assets/cms.css','/assets/cms-v3.css'","'/assets/cms-core.css'",$renderer);
$renderer=str_replace("'/assets/cms-ui-refinements.css'","'/assets/cms-editorial.css'",$renderer);
$renderer=(string)preg_replace('~<style id="cms-system-choice-controls">.*?</style>~s','',$renderer);
css_write('app/cms_renderer.php',$renderer);

// EDITOR: collapse versioned/corrective shell layers; keep actual feature modules separate.
$editorSystem=css_segment('Editor foundations',css_read('editor/cms-editor.css'))
    .css_segment('Editor advanced baseline',css_read('editor/cms-pro-editor.css'))
    .css_segment('Editor application shell',css_read('editor/cms-ux-v2.css')."\n".css_read('editor/cms-ux-v3.css'))
    .css_segment('Task-centric editor composition',css_read('editor/task-centric.css'));
css_write('editor/editor-system.css',css_strip_important($editorSystem));
$editorIndex=css_read('editor/index.html');
$editorLegacy=[
    '<link rel="stylesheet" href="/editor/cms-editor.css">',
    '<link rel="stylesheet" href="/editor/cms-pro-editor.css">',
    '<link rel="stylesheet" href="/editor/cms-ux-v2.css">',
    '<link rel="stylesheet" href="/editor/cms-ux-v3.css">',
    '<link rel="stylesheet" href="/editor/task-centric.css">',
];
foreach($editorLegacy as $i=>$tag){
    if(!str_contains($editorIndex,$tag))throw new RuntimeException('Editor stylesheet tag missing: '.$tag);
    $editorIndex=str_replace($tag,$i===0?'<link rel="stylesheet" href="/editor/editor-system.css">':'',$editorIndex);
}
css_write('editor/index.html',$editorIndex);

// Remove !important from every remaining authored stylesheet. The canonical source order now owns precedence.
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile()||strtolower($file->getExtension())!=='css')continue;
    $path=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
    if(str_starts_with($path,'dist/')||str_starts_with($path,'node_modules/'))continue;
    $css=(string)file_get_contents($file->getPathname());
    $clean=css_strip_important($css);
    if($clean!==$css)file_put_contents($file->getPathname(),$clean);
}

// Retire chronological/correction files. New code cannot accidentally start consuming them again.
foreach([
    'admin/admin.css','admin/cms-admin.css','admin/pro-admin.css',
    'assets/admin-ux-v2.css','assets/admin-ux-v3.css','assets/admin-data-ux.css','assets/admin-form-ux.css','assets/experience-ux.css',
    'assets/admin-media-maintenance.css','assets/admin-media-task.css',
    'assets/cms.css','assets/cms-v3.css','assets/cms-ui-refinements.css',
    'editor/cms-editor.css','editor/cms-pro-editor.css','editor/cms-ux-v2.css','editor/cms-ux-v3.css','editor/task-centric.css'
] as $legacy)css_delete($legacy);

// Fail the migration itself if a runtime source still points to a retired stylesheet.
$retiredNames=[
    'admin.css','cms-admin.css','pro-admin.css','admin-ux-v2.css','admin-ux-v3.css','admin-data-ux.css','admin-form-ux.css','experience-ux.css',
    'admin-media-maintenance.css','admin-media-task.css','cms-v3.css','cms-ui-refinements.css','cms-editor.css','cms-pro-editor.css','cms-ux-v2.css','cms-ux-v3.css','task-centric.css'
];
$runtimeExtensions=['php','html','js'];
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
$stale=[];
foreach($iterator as $file){
    if(!$file->isFile()||!in_array(strtolower($file->getExtension()),$runtimeExtensions,true))continue;
    $relative=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
    if(str_starts_with($relative,'tools/'))continue;
    $content=(string)file_get_contents($file->getPathname());
    foreach($retiredNames as $name)if(str_contains($content,$name))$stale[]=$relative.' -> '.$name;
}
if($stale)throw new RuntimeException("Retired stylesheet references remain:\n".implode("\n",array_unique($stale)));

echo "CSS system refactor applied.\n";
