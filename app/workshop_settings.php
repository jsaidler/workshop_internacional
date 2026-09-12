<?php
declare(strict_types=1);

function workshop_price_default(string $locale): string {
    return $locale===PUBLIC_LOCALE_PT_BR?'US$ 195':'US$195';
}

function workshop_settings_seed(PDO $db,int $activityId): void {
    $query=$db->prepare('INSERT OR IGNORE INTO workshop_locale_settings(activity_id,locale,planned_price,updated_at) VALUES(?,?,?,?)');
    $now=gmdate('c');
    foreach([PUBLIC_LOCALE_EN,PUBLIC_LOCALE_PT_BR] as $locale)$query->execute([$activityId,$locale,workshop_price_default($locale),$now]);
}

function workshop_prices(PDO $db,int $activityId): array {
    $query=$db->prepare('SELECT locale,planned_price FROM workshop_locale_settings WHERE activity_id=?');
    $query->execute([$activityId]);
    $prices=[];
    foreach($query as $row)$prices[$row['locale']]=$row['planned_price'];
    if(!isset($prices[PUBLIC_LOCALE_EN],$prices[PUBLIC_LOCALE_PT_BR])){workshop_settings_seed($db,$activityId);$query->execute([$activityId]);$prices=[];foreach($query as $row)$prices[$row['locale']]=$row['planned_price'];}
    return [PUBLIC_LOCALE_EN=>$prices[PUBLIC_LOCALE_EN]??workshop_price_default(PUBLIC_LOCALE_EN),PUBLIC_LOCALE_PT_BR=>$prices[PUBLIC_LOCALE_PT_BR]??workshop_price_default(PUBLIC_LOCALE_PT_BR)];
}

function workshop_price(PDO $db,int $activityId,string $locale): string {
    $locale=normalize_public_locale($locale)??PUBLIC_LOCALE_EN;
    return workshop_prices($db,$activityId)[$locale];
}

function workshop_price_input(mixed $value): string {
    if(is_array($value))throw new InvalidArgumentException('invalid_price');
    $value=trim(preg_replace('/\s+/u',' ',(string)$value)??(string)$value);
    $length=function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value);
    if($value===''||$length>80||str_contains($value,'<')||str_contains($value,'>'))throw new InvalidArgumentException('invalid_price');
    return $value;
}

function workshop_prices_save(PDO $db,int $activityId,array $input): array {
    $prices=[PUBLIC_LOCALE_EN=>workshop_price_input($input[PUBLIC_LOCALE_EN]??''),PUBLIC_LOCALE_PT_BR=>workshop_price_input($input[PUBLIC_LOCALE_PT_BR]??'')];
    $db->beginTransaction();
    try{
        $query=$db->prepare('INSERT INTO workshop_locale_settings(activity_id,locale,planned_price,updated_at) VALUES(?,?,?,?) ON CONFLICT(activity_id,locale) DO UPDATE SET planned_price=excluded.planned_price,updated_at=excluded.updated_at');
        $now=gmdate('c');
        foreach($prices as $locale=>$price)$query->execute([$activityId,$locale,$price,$now]);
        $db->commit();
        return $prices;
    }catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
}

function workshop_settings_apply_to_document(array $document,PDO $db,int $activityId,string $locale=PUBLIC_LOCALE_EN): array {
    if(isset($document['texts']['hero-price']))$document['texts']['hero-price']['html']=workshop_price($db,$activityId,$locale);
    if(isset($document['forms']['interest-form']))$document['forms']['interest-form']=workshop_price_apply_to_form($document['forms']['interest-form'],$locale,workshop_price($db,$activityId,$locale));
    return $document;
}
