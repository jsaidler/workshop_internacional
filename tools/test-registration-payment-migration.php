<?php
declare(strict_types=1);
function fail_payment_migration(string $message): never {fwrite(STDERR,"test-registration-payment-migration: $message\n");exit(1);}function expect_payment_migration(bool $value,string $message): void {if(!$value)fail_payment_migration($message);}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY, status TEXT NOT NULL DEFAULT 'new')");
$migration=require __DIR__.'/../migrations/020_registration_google_form_parity.php';$migration($db);
$columns=[];foreach($db->query('PRAGMA table_info(cms_form_submissions)')->fetchAll() as $column)$columns[(string)$column['name']]=$column;
expect_payment_migration(isset($columns['payment_status']),'payment_status column missing');
expect_payment_migration(isset($columns['payment_confirmed_at']),'payment_confirmed_at column missing');
expect_payment_migration(isset($columns['payment_note']),'payment_note column missing');
$db->exec("INSERT INTO cms_form_submissions(id,status) VALUES(1,'new')");$row=$db->query('SELECT * FROM cms_form_submissions WHERE id=1')->fetch();
expect_payment_migration(($row['payment_status']??null)==='pending','new registrations must default to pending payment');
expect_payment_migration(($row['payment_note']??null)==='','payment note must default empty');
$migration($db);$columnsAgain=$db->query('PRAGMA table_info(cms_form_submissions)')->fetchAll();expect_payment_migration(count($columnsAgain)===count($columns),'migration must be idempotent');
echo "Registration payment migration tests passed\n";
