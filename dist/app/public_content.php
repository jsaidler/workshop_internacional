<?php
declare(strict_types=1);
function public_content(): array { global $content_document_override; if(isset($content_document_override))return $content_document_override; static $c; if($c!==null)return $c; return $c=content_for_public(database()); }
