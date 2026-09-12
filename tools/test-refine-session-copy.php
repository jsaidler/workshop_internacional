<?php
declare(strict_types=1);
function fail_copy_test(string $message): never {fwrite(STDERR,"test-refine-session-copy: $message\n");exit(1);}function expect_copy(bool $value,string $message): void {if(!$value)fail_copy_test($message);}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,status TEXT,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,draft_updated_at TEXT,published_at TEXT,updated_at TEXT)');
$old='Demonstração prática ao vivo, com o maior número possível de fotografias e variação deliberada de exposição e parâmetros.';$custom='Fotografia ao vivo exatamente como eu editei manualmente.';
$q=$db->prepare('INSERT INTO cms_pages(id,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');$q->execute([1,'active',json_encode(['html'=>$old],JSON_UNESCAPED_UNICODE),json_encode(['html'=>$old],JSON_UNESCAPED_UNICODE),4,4,'','','']);$q->execute([2,'active',json_encode(['html'=>$custom],JSON_UNESCAPED_UNICODE),json_encode(['html'=>$custom],JSON_UNESCAPED_UNICODE),7,7,'','','']);
$migration=require __DIR__.'/../migrations/019_refine_live_session_copy.php';$migration($db);
$row=$db->query('SELECT * FROM cms_pages WHERE id=1')->fetch();expect_copy(str_contains((string)$row['draft_document_json'],'Produção fotográfica ao vivo'),'seed wording was not refined');expect_copy(!str_contains((string)$row['draft_document_json'],'Demonstração prática ao vivo'),'old seed wording survived');expect_copy((int)$row['draft_revision']===5&&(int)$row['published_revision']===5,'revisions were not incremented');
$customRow=$db->query('SELECT * FROM cms_pages WHERE id=2')->fetch();expect_copy(str_contains((string)$customRow['draft_document_json'],$custom),'custom wording was overwritten');expect_copy((int)$customRow['draft_revision']===7&&(int)$customRow['published_revision']===7,'custom page revision changed unexpectedly');
echo "Second-session copy migration tests passed\n";
