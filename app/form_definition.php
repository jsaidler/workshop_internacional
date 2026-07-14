<?php
declare(strict_types=1);
function interest_form_definition(): array{return public_content()['forms']['interest-form'];} function form_field_by_id(array $f):array{return array_column($f['fields'],null,'id');} function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
