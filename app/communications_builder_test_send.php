<?php
declare(strict_types=1);
require_once __DIR__.'/communications_builder_renderer.php';
require_once __DIR__.'/communications_builder_drafts.php';
require_once __DIR__.'/communications.php';

/**
 * Sends a visual-email proof exclusively to the authenticated administrator.
 * The HTML and plain text are generated from validated MJML on the server;
 * no caller can specify another recipient through the HTTP endpoint.
 */
function communication_builder_send_admin_test(
    PDO $db,int $activityId,int $campaignId,string $mjml,array $mediaRefs,
    ?callable $sender=null
):array {
    $admin=current_admin();
    $email=trim((string)($admin['email']??''));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strpbrk($email,"\r\n")!==false)
        throw new RuntimeException('A conta administrativa não possui endereço de e-mail válido.');
    if($activityId<1)throw new RuntimeException('Área administrativa não encontrada.');
    $subject='[TESTE VISUAL] Construtor de mensagens';
    if($campaignId>0){
        $campaign=communication_builder_campaign($db,$activityId,$campaignId);
        $subject='[TESTE VISUAL] '.trim((string)$campaign['subject']);
    }
    if(mb_strlen($subject,'UTF-8')>180)throw new RuntimeException('Assunto de teste muito extenso.');
    $render=communication_builder_render_email($db,$mjml,$mediaRefs);
    $payload=[
        'name'=>$email,'email'=>$email,'subject'=>$subject,
        'body'=>$render['text']."\n\n[Envio de teste visual. Nenhum aluno foi notificado.]",
        'server_rendered_html'=>$render['html'],'activity_id'=>$activityId,
    ];
    $send=$sender??static fn(array $out):array=>communication_send_message($db,$out);
    $reply=$send($payload);
    if(!is_array($reply))throw new RuntimeException('O serviço de e-mail não retornou uma confirmação válida.');
    return ['accepted'=>true,'html_sha256'=>$render['sha256'],'recipient'=>$email];
}
