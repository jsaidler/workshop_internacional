<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $from="STRKNG Editors' Selection #88";
    $to="STRKNG Editors' Selection #88 e #89";
    $now=gmdate('c');

    $q=$db->prepare("UPDATE cms_pages
        SET draft_document_json=REPLACE(draft_document_json, ?, ?),
            published_document_json=CASE WHEN published_document_json IS NULL THEN NULL ELSE REPLACE(published_document_json, ?, ?) END,
            draft_revision=draft_revision+1,
            published_revision=CASE WHEN published_document_json IS NULL THEN published_revision ELSE COALESCE(published_revision,draft_revision)+1 END,
            draft_updated_at=?,
            published_at=CASE WHEN published_document_json IS NULL THEN published_at ELSE ? END,
            updated_at=?
        WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'
          AND (instr(draft_document_json, ?)>0 OR instr(COALESCE(published_document_json,''), ?)>0)");
    $q->execute([$from,$to,$from,$to,$now,$now,$now,$from,$from]);
};
