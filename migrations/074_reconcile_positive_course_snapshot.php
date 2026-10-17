<?php
declare(strict_types=1);

/**
 * One-off deterministic reconciliation based on the read-only production
 * diagnostic generated at 2026-09-27T23:07:30+00:00.
 *
 * This migration deliberately does not infer relationships from titles,
 * slugs, page ancestry or form similarity. It only acts when the verified
 * identities from that snapshot still match the database:
 * - canonical course #1: public page #1 + enrollment form #1;
 * - spurious course #2: page #6, no cohorts/registrations, only lessons/material;
 * - historical/imported cohort #4: five active enrollments + import batch #1.
 */
return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    foreach(['courses','course_cohorts','course_enrollments','course_lessons','course_material_pages','course_material_sections','cohort_lesson_releases','cms_form_submissions','student_import_batches'] as $table){
        if(!$tableExists($table))return;
    }

    $one=static function(string $sql,array $args=[]) use($db): ?array {
        $q=$db->prepare($sql);$q->execute($args);$row=$q->fetch(PDO::FETCH_ASSOC);return $row?:null;
    };
    $scalar=static function(string $sql,array $args=[]) use($db): int {
        $q=$db->prepare($sql);$q->execute($args);return (int)$q->fetchColumn();
    };

    $canonical=$one('SELECT id,activity_id,status,public_page_id,registration_form_id FROM courses WHERE id=1');
    $fragment=$one('SELECT id,activity_id,status,public_page_id,registration_form_id FROM courses WHERE id=2');
    $historical=$one('SELECT id,activity_id,course_id,workshop_page_id,slug,status,is_registration_default FROM course_cohorts WHERE id=4');

    // Already reconciled, a fresh installation without this historical state,
    // or any database that no longer matches the verified snapshot: no-op.
    if(!$canonical||!$fragment||!$historical)return;
    if((int)$canonical['activity_id']!==1||(string)$canonical['status']!=='active'||(int)$canonical['public_page_id']!==1||(int)$canonical['registration_form_id']!==1)return;
    if((int)$fragment['activity_id']!==1||(string)$fragment['status']!=='active'||(int)$fragment['public_page_id']!==6||(int)$fragment['registration_form_id']!==1)return;
    if((int)$historical['activity_id']!==1||($historical['course_id']!==null&&$historical['course_id']!=='')||(string)$historical['slug']!=='26-09-quintas'||(string)$historical['status']!=='active')return;

    // The fragment must still be content-only. If someone assigned real
    // cohorts or registrations to it after the diagnostic, do not touch it.
    if($scalar('SELECT COUNT(*) FROM course_cohorts WHERE course_id=2')!==0)return;
    if($scalar('SELECT COUNT(*) FROM cms_form_submissions WHERE course_id=2')!==0)return;
    if($scalar('SELECT COUNT(*) FROM course_lessons WHERE course_id=1')!==0)return;
    if($scalar('SELECT COUNT(*) FROM course_lessons WHERE course_id=2')!==3)return;
    if($scalar('SELECT COUNT(*) FROM course_material_pages WHERE course_id=1')!==0)return;
    if($scalar('SELECT COUNT(*) FROM course_material_pages WHERE course_id=2')!==1)return;
    if($scalar("SELECT COUNT(*) FROM course_enrollments WHERE cohort_id=4 AND status='active'")!==5)return;
    if($scalar('SELECT COUNT(*) FROM student_import_batches WHERE cohort_id=4')!==1)return;
    if($scalar("SELECT COUNT(*) FROM course_cohorts WHERE course_id=1 AND slug='26-09-quintas' AND id!=4")!==0)return;

    $now=gmdate('c');
    $db->beginTransaction();
    try{
        // 1) The imported historical/current cohort belongs to the canonical
        // course. It is not promoted to the default registration cohort.
        $q=$db->prepare('UPDATE course_cohorts SET course_id=1,workshop_page_id=1,is_registration_default=0,updated_at=? WHERE id=4 AND course_id IS NULL');
        $q->execute([$now]);
        if($q->rowCount()!==1)throw new RuntimeException('course_reconciliation_cohort_not_updated');

        // 2) Course #2 was created from the caderno/material page. Move its
        // three lessons to the canonical course without changing lesson IDs,
        // so all existing release rows remain valid.
        $db->prepare('UPDATE course_lessons SET course_id=1,workshop_page_id=1,updated_at=? WHERE course_id=2')->execute([$now]);

        // 3) Move the material relation itself. The CMS page remains page #6;
        // only its course relation changes.
        $db->prepare('INSERT OR IGNORE INTO course_material_pages(course_id,page_id,sort_order,created_at,updated_at) SELECT 1,page_id,sort_order,created_at,? FROM course_material_pages WHERE course_id=2')->execute([$now]);
        $db->prepare('INSERT OR IGNORE INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) SELECT 1,page_id,section_key,lesson_id,created_at,? FROM course_material_sections WHERE course_id=2')->execute([$now]);
        $db->exec('DELETE FROM course_material_sections WHERE course_id=2');
        $db->exec('DELETE FROM course_material_pages WHERE course_id=2');

        // 4) Ensure every cohort of the canonical course has a release row for
        // every lesson. INSERT OR IGNORE preserves any release/block state that
        // already existed before the course split.
        $release=$db->prepare("INSERT OR IGNORE INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at)
            SELECT c.id,l.id,NULL,?,?
            FROM course_cohorts c CROSS JOIN course_lessons l
            WHERE c.course_id=1 AND c.status!='archived' AND l.course_id=1");
        $release->execute([$now,$now]);

        // 5) Course #2 has become an empty historical artifact. Archive it and
        // release page/form ownership so page #6 is only an ordinary CMS
        // material page associated with course #1.
        if($scalar('SELECT COUNT(*) FROM course_cohorts WHERE course_id=2')!==0
            ||$scalar('SELECT COUNT(*) FROM course_lessons WHERE course_id=2')!==0
            ||$scalar('SELECT COUNT(*) FROM course_material_pages WHERE course_id=2')!==0
            ||$scalar('SELECT COUNT(*) FROM course_material_sections WHERE course_id=2')!==0
            ||$scalar('SELECT COUNT(*) FROM cms_form_submissions WHERE course_id=2')!==0){
            throw new RuntimeException('course_reconciliation_fragment_not_empty');
        }
        $db->prepare("UPDATE courses SET status='archived',public_page_id=NULL,registration_form_id=NULL,updated_at=? WHERE id=2")->execute([$now]);

        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
};
