<?php
declare(strict_types=1);
const PUBLIC_LOCALE_PT_BR='pt-BR';const PUBLIC_LOCALE_EN='en';
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function fail_public_error(string $message): never {fwrite(STDERR,"test-public-errors: $message\n");exit(1);}function expect_public_error(bool $value,string $message): void {if(!$value)fail_public_error($message);}
require __DIR__.'/../app/public_errors.php';
$pt=cms_not_found_html(null,PUBLIC_LOCALE_PT_BR);expect_public_error(str_contains($pt,'Página não encontrada'),'PT title missing');expect_public_error(str_contains($pt,'noindex,nofollow'),'404 page is indexable');expect_public_error(str_contains($pt,'href="/"'),'home action missing');
$en=cms_not_found_html(null,PUBLIC_LOCALE_EN);expect_public_error(str_contains($en,'Page not found'),'EN title missing');
$index=(string)file_get_contents(__DIR__.'/../index.php');expect_public_error(str_contains($index,"if(\$pageSlug!=='')cms_render_not_found"),'unknown CMS slugs can still fall back to legacy 200 page');
echo "Public error tests passed\n";
