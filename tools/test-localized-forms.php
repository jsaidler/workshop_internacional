<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/public_locale.php';
require dirname(__DIR__).'/app/form_definition.php';
require dirname(__DIR__).'/app/form_validator.php';

function assert_form(bool $condition,string $message): void {
    if(!$condition)throw new RuntimeException($message);
}

$document=json_decode(file_get_contents(dirname(__DIR__).'/data/public-content.json'),true,512,JSON_THROW_ON_ERROR);
$source=$document['forms']['interest-form'];
$english=localize_interest_form($source,PUBLIC_LOCALE_EN);
$portuguese=localize_interest_form($source,PUBLIC_LOCALE_PT_BR);
$englishIds=array_column($english['fields'],'id');
$portugueseIds=array_column($portuguese['fields'],'id');

assert_form(in_array('country',$englishIds,true),'English form must show country.');
assert_form(in_array('timezone',$englishIds,true),'English form must show timezone.');
assert_form(!in_array('city',$englishIds,true),'English form must not show city.');
assert_form(in_array('city',$portugueseIds,true),'Portuguese form must show city.');
assert_form(!in_array('country',$portugueseIds,true),'Portuguese form must not show country.');
assert_form(!in_array('timezone',$portugueseIds,true),'Portuguese form must not show timezone.');

$valid=['name'=>'Ana','email'=>'ana@example.com','city'=>'Petrópolis','experience'=>'Iniciante','preferred_days'=>['saturday'],'preferred_time'=>'10:00','price_response'=>'yes','main_interest'=>'Processo','consent'=>'1'];
[$clean,$errors]=validate_interest($valid,$portuguese,PUBLIC_LOCALE_PT_BR);
assert_form($errors===[],'A valid Portuguese submission was rejected.');
assert_form($clean['city']==='Petrópolis','Portuguese city was not retained.');

$invalid=$valid;
$invalid['country']='Brasil';
[, $errors]=validate_interest($invalid,$portuguese,PUBLIC_LOCALE_PT_BR);
assert_form(isset($errors['_form']),'Portuguese form accepted an English-only field.');

echo "Localized form tests passed.\n";
