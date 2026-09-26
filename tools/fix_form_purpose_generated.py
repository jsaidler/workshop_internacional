from pathlib import Path

p=Path('tools/test-form-purpose-enrollment-authority.php')
s=p.read_text()
bad="fp_must(str_contains($submissions,'cms_form_purpose($row)==='enrollment''),'admin registration workflow must use explicit purpose');"
good='fp_must(str_contains($submissions,"cms_form_purpose(\\$row)===\'enrollment\'"),\'admin registration workflow must use explicit purpose\');'
if bad not in s:
    raise SystemExit('regression repair anchor missing')
p.write_text(s.replace(bad,good,1))

p=Path('migrations/069_form_purpose_authority.php')
s=p.read_text()
old="""    if(!in_array('purpose',$names,true))$db->exec(\"ALTER TABLE cms_forms ADD COLUMN purpose TEXT NOT NULL DEFAULT 'common'\");
    $db->exec(\"UPDATE cms_forms SET purpose='enrollment' WHERE form_key='registration' AND (purpose='' OR purpose='common')\");
    $db->exec(\"UPDATE cms_forms SET purpose='interest' WHERE form_key='interest' AND (purpose='' OR purpose='common')\");
    $db->exec(\"UPDATE cms_forms SET purpose='common' WHERE purpose NOT IN ('common','interest','registration','enrollment') OR purpose IS NULL OR purpose=''\");
"""
new="""    $added=!in_array('purpose',$names,true);
    if($added)$db->exec(\"ALTER TABLE cms_forms ADD COLUMN purpose TEXT NOT NULL DEFAULT 'common'\");
    if($added){
        $db->exec(\"UPDATE cms_forms SET purpose='enrollment' WHERE form_key='registration'\");
        $db->exec(\"UPDATE cms_forms SET purpose='interest' WHERE form_key='interest'\");
    }
    $db->exec(\"UPDATE cms_forms SET purpose='common' WHERE purpose NOT IN ('common','interest','registration','enrollment') OR purpose IS NULL OR purpose=''\");
"""
if old not in s:
    raise SystemExit('migration hardening anchor missing')
p.write_text(s.replace(old,new,1))

Path('tools/fix_form_purpose_generated.py').unlink(missing_ok=True)
