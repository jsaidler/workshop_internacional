<?php
declare(strict_types=1);
function csrf_token(string $s):string{$_SESSION['csrf'][$s]??=bin2hex(random_bytes(32));return $_SESSION['csrf'][$s];} function verify_csrf(string $s,?string $t):bool{return is_string($t)&&hash_equals($_SESSION['csrf'][$s]??'',$t);}
