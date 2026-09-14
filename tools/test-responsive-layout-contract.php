<?php
declare(strict_types=1);

function responsive_contract_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"test-responsive-layout-contract: $message\n");exit(1);}}
$root=dirname(__DIR__);
$controls=(string)file_get_contents($root.'/editor/responsive-controls.js');
$css=(string)file_get_contents($root.'/assets/cms-responsive.css');
$core=(string)file_get_contents($root.'/editor/cms-editor-v3.js');

responsive_contract_expect(str_contains($controls,"const automatic=[['','Automático']]"),'empty device settings must be presented as automatic, not inherited desktop values');
responsive_contract_expect(str_contains($controls,"'cmsTabletRatio'")&&str_contains($controls,"'cmsMobileRatio'"),'column ratio must be configurable independently for tablet and phone');
responsive_contract_expect(str_contains($controls,"'cmsTabletTextAlign'")&&str_contains($controls,"'cmsMobileTextAlign'"),'text alignment must be configurable independently for tablet and phone');
responsive_contract_expect(str_contains($controls,'não copia cegamente a configuração de desktop'),'editor must explain the device-specific responsive contract');
responsive_contract_expect(str_contains($controls,"legacyMobile.closest('label').hidden=true"),'legacy phone-order control must not compete with the canonical responsive panel');
responsive_contract_expect(str_contains($controls,'ratioTarget(section)'),'ratio controls must be capability-aware instead of appearing on every component');
responsive_contract_expect(str_contains($controls,"delete target.dataset.layoutMobile"),'using the canonical phone-order setting must retire the old hidden mobile override');
responsive_contract_expect(str_contains($controls,'Proporção das colunas · Desktop'),'the old section ratio control must be explicitly identified as desktop behavior');

responsive_contract_expect(str_contains($css,'[data-layout-ratio] :is(.cms-grid,.cms-free-grid,.statement-grid,.cms-proof-grid,.cms-support-inner,.interest-inner)'),'smaller-screen automatic rules must explicitly neutralize a chosen desktop ratio');
responsive_contract_expect(str_contains($css,'[data-cms-columns] :is(.cms-grid,.cms-free-grid){grid-template-columns:1fr}'),'smaller-screen automatic rules must explicitly neutralize chosen desktop column counts');
responsive_contract_expect(str_contains($css,'data-cms-tablet-ratio="30-70"')&&str_contains($css,'data-cms-mobile-ratio="30-70"'),'device-specific ratio rules must exist');
responsive_contract_expect(str_contains($css,'.cms-card-grid')&&str_contains($css,'.cms-stats')&&str_contains($css,'.cms-gallery'),'responsive column controls must cover builder collection grids, not only generic grids');
responsive_contract_expect(str_contains($css,'data-cms-tablet-text-align')&&str_contains($css,'data-cms-mobile-text-align'),'device-specific text alignment must be rendered by CSS');
responsive_contract_expect(!str_contains($css,'!important'),'responsive system rules must remain overridable without !important');
responsive_contract_expect(str_contains($core,'data-layout-ratio'),'desktop ratio remains stored as the base/desktop setting for backward compatibility');

echo "Responsive layout contract tests passed\n";
