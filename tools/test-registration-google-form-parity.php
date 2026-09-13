<?php
declare(strict_types=1);
function fail_registration(string $message): never {fwrite(STDERR,"test-registration-google-form-parity: $message\n");exit(1);}function expect_registration(bool $value,string $message): void {if(!$value)fail_registration($message);}
$setup=(string)file_get_contents(__DIR__.'/../app/workshop_cms_setup.php');
$admin=(string)file_get_contents(__DIR__.'/../admin/submissions.php');
$public=(string)file_get_contents(__DIR__.'/../assets/public.js');
$css=(string)file_get_contents(__DIR__.'/../assets/registration.css');
$qr=(string)file_get_contents(__DIR__.'/../assets/media/pix-workshop.svg');
foreach(['Nome Completo','CPF','Whatsapp com DDD','E-mail','Instagram','Qual o tamanho do suporte que você quer receber?','Endereço completo','Cidade/UF','CEP','Disponibilidade para a turma','Forma de pagamento','Aceite'] as $label)expect_registration(str_contains($setup,$label),"missing canonical field/text: $label");
foreach(['Terças, 19h — 6, 13 e 20 de outubro','Quintas, 19h — 8, 15 e 22 de outubro','Sábados, 9h — 3, 10 e 24 de outubro','Sábados, 14h — 3, 10 e 24 de outubro'] as $date)expect_registration(str_contains($setup,$date),"missing date: $date");
foreach(['PIX - R$698,00','Cartão de Crédito à vista - R$698 + taxas Mercado Pago','Cartão de Crédito Parcelado - R$698 + taxas Mercado Pago','https://mpago.la/1xvBsPV','20.179.548/0001-58','00020101021126690014br.gov.bcb.pix0114201795480001580229INSCRICAO MINI CURSO SETEMBRO5204000053039865406698.005802BR592020 1 5 J V T SAIDLER6010PETROPOLIS62070503***6304314B'] as $value)expect_registration(str_contains($setup,$value),"missing payment datum");
foreach(["'experience'","'equipment_format'","'equipment'","'payment_preference'","'notes'"] as $removed)expect_registration(!str_contains(substr($setup,0,strpos($setup,'function workshop_registration_terms_html')),$removed),"invented field remains: $removed");
expect_registration(str_contains($admin,"payment_status='paid'")&&str_contains($admin,'Confirmar inscrição e pagamento'),'admin does not support direct payment confirmation');
expect_registration(str_contains($public,'data-copy-pix')&&str_contains($public,'card_cash')&&str_contains($public,'card_installments'),'public payment branching missing');
expect_registration(str_contains($css,'.registration-canonical-form'),'registration UX stylesheet missing');
expect_registration(str_contains($qr,'<svg')&&str_contains($qr,'QR Code Pix'),'Pix QR asset missing');
echo "Registration Google Form parity tests passed\n";
