<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages')||!$tableExists('course_cohorts')||!$tableExists('course_lessons'))return;

    $columns=static function(string $table) use($db): array {
        $out=[];foreach($db->query('PRAGMA table_info('.$table.')')->fetchAll(PDO::FETCH_ASSOC) as $row)$out[(string)$row['name']]=true;return $out;
    };
    if(!isset($columns('course_cohorts')['workshop_page_id']))$db->exec('ALTER TABLE course_cohorts ADD COLUMN workshop_page_id INTEGER NULL REFERENCES cms_pages(id) ON DELETE SET NULL');
    if(!isset($columns('course_lessons')['workshop_page_id']))$db->exec('ALTER TABLE course_lessons ADD COLUMN workshop_page_id INTEGER NULL REFERENCES cms_pages(id) ON DELETE SET NULL');

    $db->exec('DROP INDEX IF EXISTS idx_course_cohorts_activity_slug');
    $db->exec('DROP INDEX IF EXISTS idx_course_cohorts_default');
    $db->exec('DROP INDEX IF EXISTS idx_course_lessons_activity_key');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_workshop_slug ON course_cohorts(workshop_page_id,slug) WHERE workshop_page_id IS NOT NULL');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_legacy_activity_slug ON course_cohorts(activity_id,slug) WHERE workshop_page_id IS NULL');
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_workshop_default ON course_cohorts(workshop_page_id) WHERE workshop_page_id IS NOT NULL AND is_registration_default=1 AND status!='archived'");
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_legacy_default ON course_cohorts(activity_id) WHERE workshop_page_id IS NULL AND is_registration_default=1 AND status!='archived'");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_course_cohorts_workshop_status ON course_cohorts(workshop_page_id,status,id DESC)');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_course_lessons_workshop_key ON course_lessons(workshop_page_id,lesson_key) WHERE workshop_page_id IS NOT NULL');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_course_lessons_legacy_activity_key ON course_lessons(activity_id,lesson_key) WHERE workshop_page_id IS NULL');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_course_lessons_workshop_order ON course_lessons(workshop_page_id,sort_order,id)');

    $page=$db->prepare("SELECT id,activity_id,locale,parent_page_id,status FROM cms_pages WHERE id=? LIMIT 1");
    $rootOf=static function(int $pageId) use($db,$page): int {
        $seen=[];$currentId=$pageId;$root=0;$activityId=0;$locale='';
        while($currentId>0){
            if(isset($seen[$currentId]))return 0;$seen[$currentId]=true;
            $page->execute([$currentId]);$row=$page->fetch(PDO::FETCH_ASSOC);if(!$row||($row['status']??'')==='archived')return 0;
            if($root===0){$activityId=(int)$row['activity_id'];$locale=(string)$row['locale'];}
            elseif((int)$row['activity_id']!==$activityId||(string)$row['locale']!==$locale)return 0;
            $root=(int)$row['id'];$parent=(int)($row['parent_page_id']??0);if($parent<1)break;$currentId=$parent;
        }
        return $root;
    };

    if($tableExists('cms_form_submissions')){
        $hasSubmissionCohort=isset($columns('cms_form_submissions')['cohort_id']);
        $hasSubmissionPage=isset($columns('cms_form_submissions')['page_id']);
        if($hasSubmissionCohort&&$hasSubmissionPage){
            $cohorts=$db->query('SELECT id,activity_id FROM course_cohorts WHERE workshop_page_id IS NULL')->fetchAll(PDO::FETCH_ASSOC);
            $submissionPages=$db->prepare('SELECT DISTINCT page_id FROM cms_form_submissions WHERE cohort_id=? AND page_id IS NOT NULL');
            $set=$db->prepare('UPDATE course_cohorts SET workshop_page_id=?,updated_at=? WHERE id=? AND workshop_page_id IS NULL');
            foreach($cohorts as $cohort){
                $submissionPages->execute([(int)$cohort['id']]);$roots=[];
                foreach($submissionPages->fetchAll(PDO::FETCH_COLUMN) as $pageIdRaw){$root=$rootOf((int)$pageIdRaw);if($root>0)$roots[$root]=true;}
                if(count($roots)!==1)continue;$rootId=(int)array_key_first($roots);$page->execute([$rootId]);$rootRow=$page->fetch(PDO::FETCH_ASSOC);
                if($rootRow&&(int)$rootRow['activity_id']===(int)$cohort['activity_id'])$set->execute([$rootId,gmdate('c'),(int)$cohort['id']]);
            }
        }
    }

    if($tableExists('course_page_sections')){
        $lessons=$db->query('SELECT id,activity_id FROM course_lessons WHERE workshop_page_id IS NULL')->fetchAll(PDO::FETCH_ASSOC);
        $mappedPages=$db->prepare('SELECT DISTINCT page_id FROM course_page_sections WHERE lesson_id=?');
        $setLesson=$db->prepare('UPDATE course_lessons SET workshop_page_id=?,updated_at=? WHERE id=? AND workshop_page_id IS NULL');
        foreach($lessons as $lesson){
            $mappedPages->execute([(int)$lesson['id']]);$roots=[];
            foreach($mappedPages->fetchAll(PDO::FETCH_COLUMN) as $pageIdRaw){$root=$rootOf((int)$pageIdRaw);if($root>0)$roots[$root]=true;}
            if(count($roots)!==1)continue;$rootId=(int)array_key_first($roots);$page->execute([$rootId]);$rootRow=$page->fetch(PDO::FETCH_ASSOC);
            if($rootRow&&(int)$rootRow['activity_id']===(int)$lesson['activity_id'])$setLesson->execute([$rootId,gmdate('c'),(int)$lesson['id']]);
        }
    }
};
