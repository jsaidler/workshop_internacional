<?php
declare(strict_types=1);
function fail_mechanics_map(string $message): never {fwrite(STDERR,"student-area-mechanics-map: $message\n");exit(1);}
function must_mechanics_map(bool $ok,string $message): void {if(!$ok)fail_mechanics_map($message);}
$doc=(string)file_get_contents(dirname(__DIR__).'/docs/STUDENT_AREA_MECHANICS_FULL_AUDIT_2026-10-01.md');
foreach(['Acesso e conta','Início','Curso e material','Dúvidas','Caderno','Exposição','Processamento','Temporizador','Resultado e avaliação','Ferramentas','Inventário','Predefinições de revelação','Referências de calibração','Rotas legadas'] as $section)must_mechanics_map(str_contains($doc,'### '.$section),'audit does not cover '.$section);
echo "student-area-mechanics-map: ok\n";
