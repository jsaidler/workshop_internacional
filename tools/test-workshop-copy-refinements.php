<?php
declare(strict_types=1);
function fail_refinement(string $message): never {fwrite(STDERR,"test-workshop-copy-refinements: $message\n");exit(1);}function expect_refinement(bool $value,string $message): void {if(!$value)fail_refinement($message);}
require __DIR__.'/../app/workshop_copy_refinements.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER,status TEXT,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,draft_updated_at TEXT,published_at TEXT,updated_at TEXT)');
$pt='Demonstração prática ao vivo, com o maior número possível de fotografias e variação deliberada de exposição e parâmetros.';$en='Live practical demonstration with as many photographs as possible and deliberate changes in exposure and processing parameters.';$custom='Texto já editado pelo autor e que não deve ser tocado.';
$q=$db->prepare('INSERT INTO cms_pages(id,activity_id,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
$q->execute([1,10,'active',json_encode(['html'=>$pt],JSON_UNESCAPED_UNICODE),json_encode(['html'=>$pt],JSON_UNESCAPED_UNICODE),2,2,'','','']);$q->execute([2,10,'active',json_encode(['html'=>$en]),json_encode(['html'=>$en]),3,3,'','','']);$q->execute([3,10,'active',json_encode(['html'=>$custom],JSON_UNESCAPED_UNICODE),json_encode(['html'=>$custom],JSON_UNESCAPED_UNICODE),5,5,'','','']);$q->execute([4,20,'active',json_encode(['html'=>$pt],JSON_UNESCAPED_UNICODE),json_encode(['html'=>$pt],JSON_UNESCAPED_UNICODE),1,1,'','','']);
$count=workshop_refine_live_session_copy_for_activity($db,10);expect_refinement($count===2,'unexpected changed page count');
$one=$db->query('SELECT * FROM cms_pages WHERE id=1')->fetch();expect_refinement(str_contains((string)$one['draft_document_json'],'Produção fotográfica ao vivo'),'PT future activity copy not refined');expect_refinement((int)$one['draft_revision']===3&&(int)$one['published_revision']===3,'PT revisions not incremented');
$two=$db->query('SELECT * FROM cms_pages WHERE id=2')->fetch();expect_refinement(str_contains((string)$two['published_document_json'],'Live photographic production'),'EN future activity copy not refined');
$three=$db->query('SELECT * FROM cms_pages WHERE id=3')->fetch();expect_refinement(str_contains((string)$three['draft_document_json'],$custom)&&(int)$three['draft_revision']===5,'custom copy was changed');
$other=$db->query('SELECT * FROM cms_pages WHERE id=4')->fetch();expect_refinement(str_contains((string)$other['draft_document_json'],$pt),'another activity was changed');
echo "Workshop copy refinement tests passed\n";
