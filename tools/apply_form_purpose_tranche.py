from pathlib import Path

root=Path(__file__).resolve().parents[1]
def read(path): return (root/path).read_text()
def write(path,text):
    p=root/path; p.parent.mkdir(parents=True,exist_ok=True); p.write_text(text)

write(Path('app/form_purpose.php'),'''<?php
declare(strict_types=1);

const CMS_FORM_PURPOSES=['common','interest','registration','enrollment'];

function cms_form_purpose(array $form): string {
    $purpose=trim((string)($form['purpose']??''));
    if(in_array($purpose,CMS_FORM_PURPOSES,true))return $purpose;
    // Compatibility only for pre-migration rows/snapshots. New writes persist purpose explicitly.
    $legacy=(string)($form['form_key']??'');
    if($legacy==='registration')return 'enrollment';
    if($legacy==='interest')return 'interest';
    return 'common';
}
function cms_form_purpose_label(string $purpose): string {
    return match($purpose){
        'interest'=>'Interesse',
        'registration'=>'Inscrição',
        'enrollment'=>'Inscrição + matrícula',
        default=>'Comum',
    };
}
function cms_form_set_purpose(PDO $db,int $formId,string $purpose): array {
    if(!in_array($purpose,CMS_FORM_PURPOSES,true))throw new RuntimeException('Finalidade de formulário inválida.');
    $form=cms_form_by_id($db,$formId)??throw new RuntimeException('form_not_found');
    $db->prepare('UPDATE cms_forms SET purpose=?,updated_at=? WHERE id=?')->execute([$purpose,utc_now(),$formId]);
    $form['purpose']=$purpose;
    return $form;
}
''')

write(Path('migrations/069_form_purpose_authority.php'),'''<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $exists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    if(!$exists)return;
    $columns=$db->query('PRAGMA table_info(cms_forms)')->fetchAll(PDO::FETCH_ASSOC);
    $names=array_map(static fn(array $row): string=>(string)$row['name'],$columns);
    if(!in_array('purpose',$names,true))$db->exec("ALTER TABLE cms_forms ADD COLUMN purpose TEXT NOT NULL DEFAULT 'common'");
    $db->exec("UPDATE cms_forms SET purpose='enrollment' WHERE form_key='registration' AND (purpose='' OR purpose='common')");
    $db->exec("UPDATE cms_forms SET purpose='interest' WHERE form_key='interest' AND (purpose='' OR purpose='common')");
    $db->exec("UPDATE cms_forms SET purpose='common' WHERE purpose NOT IN ('common','interest','registration','enrollment') OR purpose IS NULL OR purpose=''");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_forms_purpose ON cms_forms(activity_id,purpose,status,id)');
};
''')

p=Path('app/bootstrap.php'); text=read(p); old="'cms_forms','form_workflow','cms_pages'"
if old not in text: raise SystemExit('bootstrap anchor missing')
write(p,text.replace(old,"'cms_forms','form_purpose','form_workflow','cms_pages'",1))

p=Path('app/student_accounts.php'); text=read(p)
if text.count("f.form_key='registration'")<2: raise SystemExit('student_accounts authority anchors missing')
write(p,text.replace("f.form_key='registration'","f.purpose='enrollment'"))

p=Path('app/student_lifecycle.php'); text=read(p)
text=text.replace('SELECT s.id,f.form_key FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?','SELECT s.id,f.purpose FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?')
text=text.replace("if((string)$submission['form_key']!=='registration')throw new RuntimeException('Este registro não é uma inscrição de curso.');","if(cms_form_purpose($submission)!=='enrollment')throw new RuntimeException('Este registro não gera matrícula de curso.');")
if "form_key']!=='registration" in text: raise SystemExit('student_lifecycle purpose patch failed')
write(p,text)

p=Path('admin/submissions.php'); text=read(p)
text=text.replace('SELECT s.*,f.form_key FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?','SELECT s.*,f.form_key,f.purpose FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?')
text=text.replace('SELECT s.*,f.title form_title,f.form_key,f.locale form_locale FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.activity_id=?','SELECT s.*,f.title form_title,f.form_key,f.purpose,f.locale form_locale FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.activity_id=?')
text=text.replace('SELECT s.*,f.title form_title,f.form_key,f.locale form_locale FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?','SELECT s.*,f.title form_title,f.form_key,f.purpose,f.locale form_locale FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?')
text=text.replace("if($row['form_key']==='registration'){","if(cms_form_purpose($row)==='enrollment'){")
text=text.replace("$isRegistration=$row['form_key']==='registration'","$isRegistration=cms_form_purpose($row)==='enrollment'")
text=text.replace("$isRegistration=$selected['form_key']==='registration'","$isRegistration=cms_form_purpose($selected)==='enrollment'")
if "form_key']==='registration" in text: raise SystemExit('admin/submissions still branches on form_key')
write(p,text)

p=Path('app/workshop_cms_setup.php'); text=read(p)
anchor="$form=cms_form_by_key($db,$activityId,PUBLIC_LOCALE_PT_BR,'registration');\n    if(!$form)$form=cms_form_create($db,$activityId,PUBLIC_LOCALE_PT_BR,'Inscrição — nova turma','registration','registration');"
if anchor not in text: raise SystemExit('workshop setup anchor missing')
replacement=anchor+"\n    $form=cms_form_set_purpose($db,(int)$form['id'],'enrollment');\n    $interest=cms_form_by_key($db,$activityId,PUBLIC_LOCALE_EN,'interest');\n    if($interest)cms_form_set_purpose($db,(int)$interest['id'],'interest');"
write(p,text.replace(anchor,replacement,1))

p=Path('admin/forms.php'); text=read(p)
old="if($action==='create'){$form=cms_form_create($db,$activityId,(string)($_POST['locale']??PUBLIC_LOCALE_PT_BR),(string)($_POST['title']??'Novo formulário'),(string)($_POST['form_key']??'form'),(string)($_POST['kind']??'interest'));header('Location: /admin/forms.php?activity='.$activityId.'&form='.(int)$form['id']);exit;}"
new="if($action==='create'){$form=cms_form_create($db,$activityId,(string)($_POST['locale']??PUBLIC_LOCALE_PT_BR),(string)($_POST['title']??'Novo formulário'),(string)($_POST['form_key']??'form'),(string)($_POST['kind']??'interest'));$form=cms_form_set_purpose($db,(int)$form['id'],(string)($_POST['purpose']??'common'));header('Location: /admin/forms.php?activity='.$activityId.'&form='.(int)$form['id']);exit;}"
if old not in text: raise SystemExit('admin create anchor missing')
text=text.replace(old,new,1)
anchor="if($action==='save-workflow'){"
block="if($action==='save-purpose'){\n            $redirectForm=(int)($_POST['form_id']??0);$form=cms_form_by_id($db,$redirectForm);if(!$form||(int)$form['activity_id']!==$activityId)throw new RuntimeException('form_not_found');\n            cms_form_set_purpose($db,$redirectForm,(string)($_POST['purpose']??'common'));$_SESSION['admin_notice']='Finalidade salva.';\n        }\n        "
if anchor not in text: raise SystemExit('admin purpose action anchor missing')
text=text.replace(anchor,block+anchor,1)
anchor="<?php if($selected&&$selected['status']!=='archived'):?>\n<section class=\"admin-editor-card\">"
purpose_ui="<?php if($selected&&$selected['status']!=='archived'):?>\n<section class=\"admin-editor-card\"><div class=\"admin-editor-header\"><div><p class=\"admin-kicker\">Finalidade</p><h2>Responsabilidade do formulário</h2></div><p>A chave identifica o formulário. A finalidade define o fluxo operacional.</p></div><form method=\"post\" class=\"cms-settings-form\"><input type=\"hidden\" name=\"_csrf\" value=\"<?=h(csrf_token('cms-forms'))?>\"><input type=\"hidden\" name=\"action\" value=\"save-purpose\"><input type=\"hidden\" name=\"form_id\" value=\"<?=(int)$selected['id']?>\"><div class=\"form-grid two-columns\"><label>Finalidade<select name=\"purpose\"><?php foreach(['common'=>'Comum','interest'=>'Interesse','registration'=>'Inscrição','enrollment'=>'Inscrição que gera matrícula'] as $value=>$label):?><option value=\"<?=h($value)?>\"<?=cms_form_purpose($selected)===$value?' selected':''?>><?=h($label)?></option><?php endforeach;?></select></label><div><p class=\"admin-muted\"><strong>Inscrição que gera matrícula</strong> é a única finalidade que participa do lifecycle de conta, turma e acesso do aluno.</p></div></div><div class=\"dialog-actions\"><button class=\"admin-button\" type=\"submit\">Salvar finalidade</button></div></form></section>\n<section class=\"admin-editor-card\">"
if anchor not in text: raise SystemExit('admin selected UI anchor missing')
text=text.replace(anchor,purpose_ui,1)
anchor='<label>Modelo inicial<select name="kind"><option value="registration">Inscrição</option><option value="interest">Pesquisa de interesse</option></select></label>'
if anchor not in text: raise SystemExit('admin create UI anchor missing')
text=text.replace(anchor,anchor+'<label>Finalidade<select name="purpose"><option value="common">Comum</option><option value="interest">Interesse</option><option value="registration">Inscrição</option><option value="enrollment">Inscrição que gera matrícula</option></select></label>',1)
write(p,text)

write(Path('tools/test-form-purpose-enrollment-authority.php'),'''<?php
declare(strict_types=1);

$root=dirname(__DIR__);
function fp_must(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function fp_source(string $root,string $path): string {$s=file_get_contents($root.'/'.$path);if(!is_string($s))throw new RuntimeException('cannot read '.$path);return $s;}

$accounts=fp_source($root,'app/student_accounts.php');
$lifecycle=fp_source($root,'app/student_lifecycle.php');
$submissions=fp_source($root,'admin/submissions.php');
fp_must(!str_contains($accounts,"f.form_key='registration'"),'student account enrollment must not depend on form_key');
fp_must(!str_contains($lifecycle,"form_key']!=='registration"),'registration deletion must not depend on form_key');
fp_must(!str_contains($submissions,"form_key']==='registration"),'submissions UI must not branch on form_key');
fp_must(str_contains($accounts,"f.purpose='enrollment'"),'student enrollment reconciliation must use explicit purpose');
fp_must(str_contains($submissions,'cms_form_purpose($row)===\'enrollment\''),'admin registration workflow must use explicit purpose');

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE cms_forms(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,form_key TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active')");
$db->exec("INSERT INTO cms_forms(id,activity_id,form_key,status) VALUES(1,1,'registration','active'),(2,1,'interest','active'),(3,1,'contact','active')");
$migration=require $root.'/migrations/069_form_purpose_authority.php';$migration($db);
$rows=$db->query('SELECT id,form_key,purpose FROM cms_forms ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
fp_must($rows[0]['form_key']==='registration'&&$rows[0]['purpose']==='enrollment','existing registration identity must be preserved while purpose is backfilled');
fp_must($rows[1]['purpose']==='interest','existing interest form must be backfilled');
fp_must($rows[2]['purpose']==='common','unclassified form must remain common');
$migration($db);fp_must((int)$db->query("SELECT COUNT(*) FROM pragma_table_info('cms_forms') WHERE name='purpose'")->fetchColumn()===1,'migration must be idempotent');

require $root.'/app/form_purpose.php';
fp_must(cms_form_purpose(['purpose'=>'registration','form_key'=>'anything'])==='registration','purpose must outrank key');
fp_must(cms_form_purpose(['purpose'=>'common','form_key'=>'registration'])==='common','explicit common purpose must disable legacy registration semantics');
fp_must(cms_form_purpose(['form_key'=>'registration'])==='enrollment','legacy fallback must remain readable before migration');
echo "form purpose enrollment authority tests passed\n";
''')

write(Path('docs/FORM_PURPOSE_ENROLLMENT_AUTHORITY_2026-09-26.md'),'''# Autoridade de finalidade de formulário e matrícula — 2026-09-26

## Motivo

`form_key` é identidade editorial e não pode decidir efeitos operacionais. Até esta tranche, `registration` acionava tratamento administrativo, reconciliação de conta/matrícula e exclusão de matrícula por convenção escondida.

## Autoridade nova

`cms_forms.purpose` passa a ser a autoridade explícita, com quatro valores:

- `common`: formulário comum;
- `interest`: interesse/pesquisa;
- `registration`: inscrição sem criação automática de matrícula;
- `enrollment`: inscrição que participa do lifecycle de conta, turma e matrícula.

A chave (`form_key`) continua estável e independente da finalidade.

## Migração e preservação

A migração `069_form_purpose_authority.php` é aditiva. Ela cria a coluna, preserva IDs/chaves/submissões e faz backfill conservador: o formulário legado `registration` recebe `enrollment`, mantendo exatamente o comportamento já existente; `interest` recebe `interest`; demais formulários recebem `common`.

O fallback de `cms_form_purpose()` para chaves antigas existe apenas para compatibilidade de leitura antes da migração. Uma vez presente um valor explícito, ele sempre tem precedência — inclusive `common` numa chave historicamente chamada `registration`.

## Interfaces e efeitos

A finalidade é editada no editor canônico de `Admin → Formulários`; não foi criada interface paralela. O painel de respostas, a reconciliação de contas/matrículas, a atribuição de turma e a exclusão da inscrição consultam a finalidade `enrollment`.

O template `Positivo direto` declara explicitamente `enrollment` para o formulário de inscrição e `interest` para a pesquisa EN. Template visual inicial e finalidade operacional são decisões separadas.

## Regressão

`tools/test-form-purpose-enrollment-authority.php` prova o backfill/idempotência e bloqueia o retorno de decisões operacionais baseadas em `form_key='registration'` nos caminhos de matrícula, lifecycle e respostas.
''')

note="\n## Tranche 2026-09-26 — finalidade de formulário como autoridade operacional\n\nImplementada em `audit/form-purpose-enrollment-authority-2026-09-26`: `cms_forms.purpose` é a autoridade explícita para comum/interesse/inscrição/inscrição que gera matrícula. O comportamento de matrícula deixou de ser decidido por `form_key='registration'`; a migração 069 preserva todas as chaves, IDs, submissões e o comportamento do formulário legado por backfill para `enrollment`. A interface permanece em `Admin → Formulários`. Ver `docs/FORM_PURPOSE_ENROLLMENT_AUTHORITY_2026-09-26.md`.\n"
for doc in ['docs/PROJECT_STATE.md','docs/SYSTEM_WIDE_IMPLEMENTATION_2026-09-25.md','docs/SYSTEM_WIDE_AUDIT_2026-09-25.md']:
    p=Path(doc); text=read(p)
    if 'Tranche 2026-09-26 — finalidade de formulário como autoridade operacional' not in text: write(p,text.rstrip()+"\n"+note)

(root/'.github/workflows/apply-form-purpose-tranche.yml').unlink(missing_ok=True)
(root/'tools/apply_form_purpose_tranche.py').unlink(missing_ok=True)
