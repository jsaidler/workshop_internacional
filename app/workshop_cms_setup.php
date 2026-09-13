<?php
declare(strict_types=1);

/**
 * Project-specific CMS defaults for the Direct Positive X-Ray Film workshop.
 * The Brazilian registration mirrors the canonical Google Form used for the
 * workshop instead of collecting marketing or pedagogical profile data.
 */

const WORKSHOP_PIX_KEY='20.179.548/0001-58';
const WORKSHOP_PIX_COPY='00020101021126690014br.gov.bcb.pix0114201795480001580229INSCRICAO MINI CURSO SETEMBRO5204000053039865406698.005802BR592020 1 5 J V T SAIDLER6010PETROPOLIS62070503***6304314B';
const WORKSHOP_CARD_URL='https://mpago.la/1xvBsPV';

function workshop_registration_schema(): array {
    return cms_validate_form_schema([
        'version'=>1,
        'submitLabel'=>'Enviar inscrição',
        'successTitle'=>'Inscrição recebida',
        'successMessage'=>'Sua inscrição foi recebida. A vaga é confirmada somente após a confirmação do pagamento.',
        'fields'=>[
            ['id'=>'name','type'=>'text','label'=>'Nome Completo','required'=>true,'autocomplete'=>'name','width'=>'full'],
            ['id'=>'cpf','type'=>'text','label'=>'CPF','required'=>true,'help'=>'Para emissão de NF','width'=>'half'],
            ['id'=>'phone','type'=>'tel','label'=>'Whatsapp com DDD','required'=>true,'autocomplete'=>'tel','width'=>'half'],
            ['id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,'autocomplete'=>'email','width'=>'half'],
            ['id'=>'instagram','type'=>'text','label'=>'Instagram','required'=>true,'width'=>'half'],
            [
                'id'=>'support_size','type'=>'radio','label'=>'Qual o tamanho do suporte que você quer receber?','required'=>true,'width'=>'full',
                'options'=>[
                    ['value'=>'4x5','label'=>'4x5"'],
                    ['value'=>'5x7','label'=>'5x7"'],
                ],
            ],
            ['id'=>'address','type'=>'text','label'=>'Endereço completo','required'=>true,'autocomplete'=>'street-address','width'=>'full'],
            ['id'=>'city_state','type'=>'text','label'=>'Cidade/UF','required'=>true,'autocomplete'=>'address-level2','width'=>'half'],
            ['id'=>'postal_code','type'=>'text','label'=>'CEP','required'=>true,'autocomplete'=>'postal-code','width'=>'half'],
            [
                'id'=>'availability','type'=>'checkbox-group','label'=>'Disponibilidade para a turma','required'=>true,'width'=>'full',
                'help'=>'Marque todas as opções em que você poderia participar dos três encontros.',
                'options'=>[
                    ['value'=>'tue_19_oct_6_13_20','label'=>'Terças, 19h — 6, 13 e 20 de outubro'],
                    ['value'=>'thu_19_oct_8_15_22','label'=>'Quintas, 19h — 8, 15 e 22 de outubro'],
                    ['value'=>'sat_09_oct_3_10_24','label'=>'Sábados, 9h — 3, 10 e 24 de outubro'],
                    ['value'=>'sat_14_oct_3_10_24','label'=>'Sábados, 14h — 3, 10 e 24 de outubro'],
                ],
            ],
            [
                'id'=>'payment_method','type'=>'radio','label'=>'Forma de pagamento','required'=>true,'width'=>'full',
                'options'=>[
                    ['value'=>'pix','label'=>'PIX - R$698,00'],
                    ['value'=>'card_cash','label'=>'Cartão de Crédito à vista - R$698 + taxas Mercado Pago'],
                    ['value'=>'card_installments','label'=>'Cartão de Crédito Parcelado - R$698 + taxas Mercado Pago'],
                ],
            ],
            [
                'id'=>'terms','type'=>'consent','required'=>true,'width'=>'full',
                'label'=>'Declaro que li e estou de acordo com a programação e com as condições de inscrição e participação apresentadas neste formulário.',
            ],
        ],
    ]);
}

function workshop_registration_terms_html(): string {
    return <<<'HTML'
<section class="registration-terms" data-registration-terms hidden>
  <p class="section-label">CONDIÇÕES DE INSCRIÇÃO E PARTICIPAÇÃO</p>
  <ol>
    <li><strong>Inscrição e reserva de vaga</strong><p>A inscrição no site não confirma a reserva da vaga. A inscrição é confirmada somente após a realização e a confirmação do pagamento. O preenchimento deste formulário, sem pagamento, não reserva a vaga.</p></li>
    <li><strong>Formação das turmas</strong><p>Os horários serão definidos conforme a disponibilidade e o número de inscritos com pagamento confirmado. Uma turma adicional poderá ser aberta quando houver pelo menos 3 participantes com pagamento confirmado e disponibilidade comum para o mesmo horário.</p></li>
    <li><strong>Disponibilidade informada</strong><p>Ao marcar um horário como disponível, o participante declara possuir disponibilidade para os três encontros correspondentes àquele horário. É possível indicar mais de uma opção.</p></li>
    <li><strong>Confirmação da turma</strong><p>A turma será considerada formada quando houver participantes com pagamento confirmado e disponibilidade compatível. Caso não seja possível formar a turma em nenhum dos horários indicados pelo participante, o valor pago será devolvido integralmente.</p></li>
    <li><strong>Formato do curso</strong><p>O curso acontece on-line e ao vivo, em três encontros. Eventuais ajustes de horário serão comunicados aos participantes.</p></li>
    <li><strong>Materiais e equipamentos</strong><p>O participante trabalha com o próprio equipamento e é responsável pelos materiais necessários para seus testes. O suporte indicado neste formulário será enviado para o endereço informado.</p></li>
    <li><strong>Segurança</strong><p>O participante é responsável por seguir as orientações de segurança para manipulação de produtos e materiais utilizados durante os processos fotográficos.</p></li>
    <li><strong>Conduta e material didático</strong><p>Os materiais disponibilizados durante o curso destinam-se ao uso pessoal do participante e não devem ser reproduzidos ou comercializados sem autorização.</p></li>
    <li><strong>Dados pessoais</strong><p>Os dados fornecidos neste formulário serão utilizados para organizar a inscrição, o pagamento, a participação no workshop e o envio do suporte.</p></li>
  </ol>
  <p><strong>Cancelamento</strong></p>
  <p>O participante poderá comunicar a desistência. Casos de cancelamento serão tratados conforme o estágio de formação da turma e as despesas já realizadas.</p>
</section>
HTML;
}

function workshop_registration_payment_html(): string {
    $pix=h(WORKSHOP_PIX_COPY);$key=h(WORKSHOP_PIX_KEY);$card=h(WORKSHOP_CARD_URL);
    return <<<HTML
<div class="registration-payment-source" data-registration-payment-source hidden>
  <section class="registration-payment-panel" data-registration-payment="pix" hidden>
    <p class="section-label">PIX</p>
    <h3>R$ 698,00</h3>
    <div class="registration-pix-layout">
      <img src="/assets/media/pix-workshop.svg" alt="QR Code Pix para pagamento de R$ 698,00">
      <div>
        <p><strong>Chave Pix</strong><br><code>{$key}</code></p>
        <p><strong>Copia e cola</strong></p>
        <code class="registration-pix-code" data-pix-copy-value>{$pix}</code>
        <button class="button button-secondary" type="button" data-copy-pix>Copiar código Pix</button>
      </div>
    </div>
  </section>
  <section class="registration-payment-panel" data-registration-payment="card" hidden>
    <p class="section-label">CARTÃO DE CRÉDITO · MERCADO PAGO</p>
    <h3>Pagamento por cartão de crédito</h3>
    <p>Use o link do Mercado Pago para pagamento à vista ou parcelado. As taxas da plataforma são acrescentadas ao pagamento.</p>
    <p><a class="button" href="{$card}" target="_blank" rel="noopener">Pagar com cartão no Mercado Pago <span aria-hidden="true">↗</span></a></p>
  </section>
</div>
HTML;
}

function workshop_registration_program_html(): string {
    return <<<'HTML'
<section class="registration-program" data-registration-program hidden>
  <p class="section-label">PROGRAMAÇÃO</p>
  <article><h3>Encontro 1 — Filme de raio-X, exposição e preparação para o processo</h3><p>Características do filme de raio-X, exposição pensando no positivo e decisões anteriores à revelação.</p></article>
  <article><h3>Encontro 2 — Química, revelação e evolução do processo</h3><p>Fotografia e processamento ao vivo, com variação deliberada de exposição e parâmetros para comparar os resultados e compreender a formação do positivo.</p></article>
  <article><h3>Encontro 3 — Análise dos resultados</h3><p>Depois dos dois primeiros encontros, os participantes produzem seus próprios testes. No terceiro encontro, analisamos os resultados, as escolhas feitas e o que cada imagem indica para o teste seguinte.</p></article>
</section>
HTML;
}

function workshop_registration_page_document(): array {
    $payment=workshop_registration_payment_html();
    $program=workshop_registration_program_html();
    $terms=workshop_registration_terms_html();
    $html=<<<HTML
<section class="registration-page" data-cms-section="registration-form" data-cms-section-name="Inscrição">
  <div class="registration-page-inner">
    <header class="registration-page-header">
      <p class="section-label" data-cms-editable>WORKSHOP</p>
      <h1 data-cms-editable>Positivo Direto em Filme de Raio-X</h1>
      <div class="registration-intro-copy">
        <p data-cms-editable>Mini workshop online e ao vivo sobre produção de positivos diretos em filme de raio-X.</p>
        <p data-cms-editable>São três encontros. Nos dois primeiros percorremos as características do filme, exposição, química, revelação e produção do positivo direto. Depois, cada participante produz seus próprios testes e retorna para um terceiro encontro dedicado à análise dos resultados.</p>
        <p data-cms-editable>Cada aluno recebe o suporte para chapas desenvolvido especificamente para o processo.</p>
        <p data-cms-editable><strong>O valor da inscrição é de R$ 698,00 via Pix.</strong> Também é possível pagar por cartão de crédito, à vista ou parcelado, com as taxas do Mercado Pago.</p>
        <p data-cms-editable><strong>O envio deste formulário não reserva a vaga. A inscrição é confirmada somente após a confirmação do pagamento.</strong></p>
        <p data-cms-editable>A data da turma será definida de acordo com a disponibilidade dos participantes com pagamento confirmado. Uma segunda turma poderá ser aberta quando houver pelo menos 3 participantes com pagamento confirmado e disponibilidade comum.</p>
      </div>
    </header>
    <div class="registration-form-shell" data-registration-form-shell>
      <div data-cms-form-key="registration"></div>
      {$program}
      {$payment}
      {$terms}
    </div>
  </div>
</section>
HTML;
    return cms_page_document([
        'theme'=>'auto',
        'meta'=>[
            'title'=>'Inscrição — Positivo Direto em Filme de Raio-X',
            'description'=>'Inscrição para o workshop on-line e ao vivo de positivo direto em filme de raio-X.',
        ],
        'html'=>$html,
    ]);
}

function workshop_home_registration_cta(string $url): string {
    $safe=h($url);
    return <<<HTML
<section class="interest" id="inscricao" data-cms-section="registration" data-cms-section-name="Inscrição">
  <div class="interest-inner">
    <div class="interest-intro">
      <p class="section-label" data-cms-editable>08 / Nova turma</p>
      <h2 data-cms-editable>Quer participar da próxima turma?</h2>
      <p data-cms-editable>A inscrição acontece em uma página própria. A vaga é confirmada após a confirmação do pagamento.</p>
    </div>
    <div class="interest-confirmation">
      <p class="section-label" data-cms-editable>INSCRIÇÃO</p>
      <h2 data-cms-editable>R$ 698 via Pix</h2>
      <p data-cms-editable>Cartão de crédito também disponível, à vista ou parcelado, com as taxas do Mercado Pago.</p>
      <a class="button" href="{$safe}">Abrir formulário de inscrição <span aria-hidden="true">↗</span></a>
    </div>
  </div>
</section>
HTML;
}

function workshop_replace_registration_section(string $html,string $replacement): string {
    $pattern='~<section\b[^>]*data-cms-section="registration"[^>]*>.*?</section>~is';
    $updated=preg_replace($pattern,$replacement,$html,1,$count);
    if($count>0&&is_string($updated))return $updated;
    return rtrim($html)."\n".$replacement;
}

function workshop_cms_setup_activity(PDO $db,int $activityId): void {
    $activity=activity_by_id($db,$activityId);
    if(!$activity)return;

    cms_forms_seed($db,$activityId);
    cms_pages_seed($db,$activityId);
    $now=gmdate('c');

    $form=cms_form_by_key($db,$activityId,PUBLIC_LOCALE_PT_BR,'registration');
    if(!$form)$form=cms_form_create($db,$activityId,PUBLIC_LOCALE_PT_BR,'Inscrição — nova turma','registration','registration');
    $registrationSchema=workshop_registration_schema();
    $schemaJson=json_encode($registrationSchema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $formRevision=max((int)$form['draft_revision'],(int)($form['published_revision']??0))+1;
    $q=$db->prepare('UPDATE cms_forms SET title=?,draft_schema_json=?,published_schema_json=?,draft_revision=?,published_revision=?,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?');
    $q->execute(['Inscrição — nova turma',$schemaJson,$schemaJson,$formRevision,$formRevision,$now,$now,$now,(int)$form['id']]);

    $q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND locale=? AND slug='inscricao' AND status!='archived' LIMIT 1");
    $q->execute([$activityId,PUBLIC_LOCALE_PT_BR]);
    $registrationPage=$q->fetch()?:null;
    $pageDoc=workshop_registration_page_document();
    $pageJson=json_encode($pageDoc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if(!$registrationPage){
        $q=$db->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM cms_pages WHERE activity_id=? AND locale=?');
        $q->execute([$activityId,PUBLIC_LOCALE_PT_BR]);
        $sort=(int)$q->fetchColumn();
        $insert=$db->prepare('INSERT INTO cms_pages(page_uuid,activity_id,locale,slug,title,nav_title,status,is_home,show_in_nav,sort_order,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,?,"active",0,1,?,?,?,?,?,?,?,?,?)');
        $insert->execute([cms_page_uuid(),$activityId,PUBLIC_LOCALE_PT_BR,'inscricao','Inscrição — nova turma','Inscrição',$sort,$pageJson,$pageJson,1,1,$now,$now,$now,$now]);
        $registrationPage=cms_page_by_id($db,(int)$db->lastInsertId());
    }else{
        $pageRevision=max((int)$registrationPage['draft_revision'],(int)($registrationPage['published_revision']??0))+1;
        $update=$db->prepare('UPDATE cms_pages SET title=?,nav_title=?,show_in_nav=1,draft_document_json=?,published_document_json=?,draft_revision=?,published_revision=?,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?');
        $update->execute(['Inscrição — nova turma','Inscrição',$pageJson,$pageJson,$pageRevision,$pageRevision,$now,$now,$now,(int)$registrationPage['id']]);
        $registrationPage=cms_page_by_id($db,(int)$registrationPage['id']);
    }
    if(!$registrationPage)return;

    $registrationUrl='/inscricao/?lang=pt-br';
    $cta=workshop_home_registration_cta($registrationUrl);
    $home=cms_page_home($db,$activityId,PUBLIC_LOCALE_PT_BR);
    if(!$home)return;

    $updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $json=$home[$column]??null;
        if(!is_string($json)||$json==='')continue;
        $doc=json_decode($json,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=str_replace('href="#inscricao"','href="'.$registrationUrl.'"',$doc['html']);
        $html=workshop_replace_registration_section($html,$cta);
        $doc['html']=$html;
        $updates[$column]=json_encode(cms_page_document($doc),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }
    if($updates){
        $homeRevision=max((int)$home['draft_revision'],(int)($home['published_revision']??0))+1;
        $sets=[];$args=[];
        foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
        $sets[]='draft_revision=?';$args[]=$homeRevision;
        $sets[]='published_revision=?';$args[]=$homeRevision;
        $sets[]='draft_updated_at=?';$args[]=$now;
        $sets[]='published_at=?';$args[]=$now;
        $sets[]='updated_at=?';$args[]=$now;
        $args[]=(int)$home['id'];
        $stmt=$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?');
        $stmt->execute($args);
    }
}
