<?php
declare(strict_types=1);

function fail_private_media_editor(string $message): never {fwrite(STDERR,"private-media-editor: $message\n");exit(1);}
function must_private_media_editor(bool $condition,string $message): void {if(!$condition)fail_private_media_editor($message);}

$root=dirname(__DIR__);
$api=(string)file_get_contents($root.'/admin/api/cms-private-media-slots.php');
$preview=(string)file_get_contents($root.'/editor/cms-private-media-preview.js');
$promotion=(string)file_get_contents($root.'/editor/cms-legacy-node-promotion.js');
$privacy=(string)file_get_contents($root.'/app/media_privacy.php');
$contract=(string)file_get_contents($root.'/docs/EDITOR_INTERACTION_CONTRACT_2026-09-26.md');

must_private_media_editor(str_contains($api,"verify_csrf('cms-private-media'")&&str_contains($api,"\$action==='bind'")&&str_contains($api,"\$action==='unbind'"),'editor API is not an authenticated bind/unbind authority');
must_private_media_editor(str_contains($api,'course_page_media_bind')&&str_contains($api,'course_page_media_unbind')&&str_contains($api,'media_private_images'),'editor API does not use canonical private-media services');
must_private_media_editor(str_contains($preview,'cms-private-media-dialog')&&str_contains($preview,"mutateSlot(key,'bind'")&&str_contains($preview,"mutateSlot(selectedKey,'unbind'"),'private slot UI cannot choose, bind and remove an image');
must_private_media_editor(str_contains($preview,'data-private-media-slot')&&!str_contains($preview,'data-cms-image-placeholder'),'private slot editor is falling back to the public-image component contract');
must_private_media_editor(str_contains($promotion,"node.matches('[data-private-media-slot]')"),'legacy promotion can still convert a private slot into a generic image component');
must_private_media_editor(str_contains($privacy,'course_page_media_slots')&&str_contains($privacy,"visibility='private'"),'canonical private-media persistence contract is missing');
must_private_media_editor(str_contains($contract,'O HTML canônico conserva o slot'),'editor interaction contract does not preserve canonical private slot markup');

echo "private-media-editor: ok\n";
