<?php
declare(strict_types=1);
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
function activity_by_slug(PDO $db,string $slug): ?array {$q=$db->prepare('SELECT * FROM activities WHERE slug=? AND status="active"');$q->execute([$slug]);return $q->fetch()?:null;}
function activity_for_request(PDO $db): array { if(isset($_GET['activity'])&&is_string($_GET['activity'])&&$_GET['activity']!==''){ $a=activity_by_slug($db,$_GET['activity']);if(!$a) throw new RuntimeException('activity_not_found');return $a;} return root_activity($db); }
function admin_activity(PDO $db): array { $state=admin_activity_resolution($db);if(!$state['activity'])throw new RuntimeException('activity_not_found');return $state['activity']; }
function activity_slug(string $value): string {$s=strtolower(trim(preg_replace('~[^a-z0-9]+~i','-',$value)??''));return trim($s,'-')?:'atividade';}
function activity_create(PDO $db,string $name,string $title,string $slug,?int $copyId=null): array { $slug=activity_slug($slug);$base=$slug;$n=2;while(activity_by_slug($db,$slug))$slug=$base.'-'.$n++;$source=$copyId?activity_by_id($db,$copyId):($db->query('SELECT * FROM activities WHERE is_root=1 LIMIT 1')->fetch()?:null);$doc=$source?content_for_draft($db,(int)$source['id']):canonical_content();$now=gmdate('c');$db->beginTransaction();try{$q=$db->prepare('INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at) VALUES(?,?,?,?,?,?,?)');$q->execute([$name,$title,$slug,'active',$source?0:1,$now,$now]);$id=(int)$db->lastInsertId();$json=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$db->prepare('INSERT INTO content_documents(document_key,activity_id,schema_version,draft_json,published_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?,?)')->execute(['activity-'.$id,$id,$doc['schemaVersion'],$json,$json,$now,$now,$now,$now]);workshop_settings_seed($db,$id);workshop_cms_setup_activity($db,$id);$db->commit();return activity_by_id($db,$id);}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}}
