<?php
declare(strict_types=1);
require_once __DIR__.'/activity_locales.php';

const ACTIVITY_TEMPLATE_BLANK='blank';
const ACTIVITY_TEMPLATE_DIRECT_POSITIVE='direct-positive';

function activity_template_options(): array {
    return [
        ACTIVITY_TEMPLATE_BLANK=>'Em branco',
        ACTIVITY_TEMPLATE_DIRECT_POSITIVE=>'Positivo direto',
    ];
}
function activity_template(string $value): string {
    $value=trim($value);
    if(!array_key_exists($value,activity_template_options()))throw new RuntimeException('Template de curso inválido.');
    return $value;
}
function root_activity(PDO $db): array { $r=$db->query('SELECT * FROM activities WHERE is_root=1 LIMIT 1')->fetch(); if(!$r) throw new RuntimeException('root_activity_missing'); return $r; }
function admin_activity_resolution(PDO $db): array {
    $activities=$db->query('SELECT * FROM activities ORDER BY is_root DESC, updated_at DESC, id DESC')->fetchAll();
    $requested=(int)($_GET['activity']??$_POST['activity']??0);
    $selected=$requested?activity_by_id($db,$requested):null;
    if(!$selected&&count($activities)===1)$selected=$activities[0];
    if(!$selected&&$activities){$selected=array_values(array_filter($activities,fn(array $activity)=>(int)$activity['is_root']===1))[0]??$activities[0];}
    return ['count'=>count($activities),'activity'=>$selected,'activities'=>$activities];
}
function activity_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM activities WHERE id=?');$q->execute([$id]);return $q->fetch()?:null;}
function activity_by_slug(PDO $db,string $slug): ?array {$q=$db->prepare("SELECT * FROM activities WHERE slug=? AND status='active'");$q->execute([$slug]);return $q->fetch()?:null;}
function activity_for_request(PDO $db): array { if(isset($_GET['activity'])&&is_string($_GET['activity'])&&$_GET['activity']!==''){ $a=activity_by_slug($db,$_GET['activity']);if(!$a) throw new RuntimeException('activity_not_found');return $a;} return root_activity($db); }
function admin_activity(PDO $db): array { $state=admin_activity_resolution($db);if(!$state['activity'])throw new RuntimeException('activity_not_found');return $state['activity']; }
function activity_slug(string $value): string {$s=strtolower(trim(preg_replace('~[^a-z0-9]+~i','-',$value)??''));return trim($s,'-')?:'atividade';}
function activity_update_localized_identity(PDO $db,int $id,string $adminName,array $titles): array {
    $activity=activity_by_id($db,$id)??throw new RuntimeException('Curso não encontrado.');
    $adminName=trim($adminName);if($adminName==='')throw new RuntimeException('Informe o nome administrativo.');if(mb_strlen($adminName)>160)throw new RuntimeException('Nome administrativo muito longo.');
    $normalized=[];foreach($titles as $locale=>$raw){$title=trim((string)$raw);if($title==='')continue;if(mb_strlen($title)>220)throw new RuntimeException('Nome público muito longo.');$normalized[activity_locale_normalize((string)$locale)]=$title;}
    if(!$normalized)throw new RuntimeException('Informe ao menos um nome público do curso.');
    $now=gmdate('c');
    if(activity_locales_available($db)){
        $db->beginTransaction();
        try{
            $db->prepare('UPDATE activities SET admin_name=?,updated_at=? WHERE id=?')->execute([$adminName,$now,$id]);
            foreach($normalized as $locale=>$title)activity_locale_save($db,$id,$locale,$title);
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        return activity_by_id($db,$id)??$activity;
    }
    $legacyTitle=reset($normalized);$db->prepare('UPDATE activities SET admin_name=?,public_title=?,updated_at=? WHERE id=?')->execute([$adminName,$legacyTitle,$now,$id]);
    return activity_by_id($db,$id)??$activity;
}
function activity_update_identity(PDO $db,int $id,string $adminName,string $publicTitle,string $locale='pt-BR'): array {
    $publicTitle=trim($publicTitle);if($publicTitle==='')throw new RuntimeException('Informe o nome público do curso.');
    $activity=activity_update_localized_identity($db,$id,$adminName,[$locale=>$publicTitle]);
    return activity_locales_available($db)?activity_with_locale($db,$activity,$locale):$activity;
}
function activity_create(PDO $db,string $name,string $title,string $slug,?int $copyId=null,string $template=ACTIVITY_TEMPLATE_BLANK,string $locale='pt-BR'): array {
    $template=activity_template($template);$locale=activity_locale_normalize($locale);$slug=activity_slug($slug);$base=$slug;$n=2;while(activity_by_slug($db,$slug))$slug=$base.'-'.$n++;
    $name=trim($name);$title=trim($title);if($name==='')throw new RuntimeException('Informe o nome administrativo.');if($title==='')throw new RuntimeException('Informe o nome público do curso.');if(mb_strlen($name)>160||mb_strlen($title)>220)throw new RuntimeException('Nome muito longo.');
    $source=$copyId?activity_by_id($db,$copyId):null;if($copyId&&!$source)throw new RuntimeException('Curso de origem não encontrado.');
    if($source&&$template!==ACTIVITY_TEMPLATE_BLANK)throw new RuntimeException('Cópia e template não podem ser aplicados ao mesmo tempo.');
    $hasRoot=(bool)$db->query('SELECT 1 FROM activities WHERE is_root=1 LIMIT 1')->fetchColumn();$now=gmdate('c');
    $db->beginTransaction();
    try{
        $q=$db->prepare('INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at) VALUES(?,?,?,?,?,?,?)');
        $q->execute([$name,$title,$slug,'active',$hasRoot?0:1,$now,$now]);$id=(int)$db->lastInsertId();
        if(activity_locales_available($db))activity_locale_save($db,$id,$locale,$title);
        if($source){
            $doc=content_for_draft($db,(int)$source['id']);$json=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $db->prepare('INSERT INTO content_documents(document_key,activity_id,schema_version,draft_json,published_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?,?)')->execute(['activity-'.$id,$id,$doc['schemaVersion'],$json,$json,$now,$now,$now,$now]);
        }elseif($template===ACTIVITY_TEMPLATE_DIRECT_POSITIVE){
            $doc=canonical_content();$json=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $db->prepare('INSERT INTO content_documents(document_key,activity_id,schema_version,draft_json,published_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?,?)')->execute(['activity-'.$id,$id,$doc['schemaVersion'],$json,$json,$now,$now,$now,$now]);
            workshop_settings_seed($db,$id);workshop_cms_setup_activity($db,$id);workshop_refine_live_session_copy_for_activity($db,$id);
        }
        $db->commit();$created=activity_by_id($db,$id)??throw new RuntimeException('course_create_failed');return activity_locales_available($db)?activity_with_locale($db,$created,$locale):$created;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
