<?php
declare(strict_types=1);
function public_activity():array {global $public_activity_override;static $a;if(isset($public_activity_override)&&is_array($public_activity_override))return $public_activity_override;if($a!==null)return $a;return $a=activity_for_request(database());}
function public_content(): array { global $content_document_override; if(isset($content_document_override))return $content_document_override; static $c; if($c!==null)return $c; return $c=content_for_public(database(),(int)public_activity()['id']); }
