<?php
declare(strict_types=1);
function public_content(): array { static $c; if($c!==null)return $c; return $c=json_decode(file_get_contents(dirname(__DIR__).'/data/public-content.json'),true,512,JSON_THROW_ON_ERROR); }
