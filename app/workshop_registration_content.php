<?php
declare(strict_types=1);

function workshop_registration_content_blocks(): array {
    return [
        [
            'id'=>'heading_data',
            'label'=>'Cabeçalho — dados pessoais',
            'afterField'=>'',
            'html'=><<<'HTML'
<div class="registration-form-heading">
  <p class="section-label" data-cms-editable>01 / DADOS</p>
  <h2 data-cms-editable>Seus dados</h2>
</div>
HTML,
        ],
        [
            'id'=>'heading_support',
            'label'=>'Cabeçalho — suporte e endereço',
            'afterField'=>'instagram',
            'html'=><<<'HTML'
<div class="registration-form-heading">
  <p class="section-label" data-cms-editable>02 / ENVIO DO PRESENTE</p>
  <h2 data-cms-editable>Suporte e endereço</h2>
  <p data-cms-editable>Você receberá um suporte exclusivo para usar no processamento do filme de raio-X. Escolha o tamanho correspondente às chapas que pretende usar.</p>
</div>
HTML,
        ],
        [
            'id'=>'heading_availability',
            'label'=>'Cabeçalho — disponibilidade',
            'afterField'=>'postal_code',
            'html'=><<<'HTML'
<div class="registration-form-heading">
  <p class="section-label" data-cms-editable>03 / INSCRIÇÃO</p>
  <h2 data-cms-editable>Disponibilidade</h2>
  <p data-cms-editable>Marque todas as opções em que você poderia participar dos três encontros. A turma será formada pelo maior cruzamento de disponibilidade entre participantes com pagamento confirmado.</p>
</div>
HTML,
        ],
        [
            'id'=>'program',
            'label'=>'Programação do workshop',
            'afterField'=>'availability',
            'html'=><<<'HTML'
<section class="registration-program">
  <p class="section-label" data-cms-editable>PROGRAMAÇÃO</p>
  <article><h3 data-cms-editable>Encontro 1 — Filme de raio-X, exposição e preparação para o processo</h3><p data-cms-editable>Características do filme de raio-X, exposição pensando no positivo e decisões anteriores à revelação.</p></article>
  <article><h3 data-cms-editable>Encontro 2 — Química, revelação e evolução do processo</h3><p data-cms-editable>Fotografia e processamento ao vivo, com variação deliberada de exposição e parâmetros para comparar os resultados e compreender a formação do positivo.</p></article>
  <article><h3 data-cms-editable>Encontro 3 — Análise dos resultados</h3><p data-cms-editable>Depois dos dois primeiros encontros, os participantes produzem seus próprios testes. No terceiro encontro, analisamos os resultados, as escolhas feitas e o que cada imagem indica para o teste seguinte.</p></article>
</section>
HTML,
        ],
        [
            'id'=>'payment_pix',
            'label'=>'Pagamento — Pix',
            'afterField'=>'payment_method',
            'showOnSuccess'=>true,
            'condition'=>['source'=>'payment_method','operator'=>'equals','value'=>'pix'],
            'html'=><<<'HTML'
<div class="registration-payment-source">
  <section class="registration-payment-panel">
    <p class="section-label" data-cms-editable>PIX</p>
    <h3 data-cms-editable>R$ 698,00</h3>
    <div class="registration-pix-layout">
      <img src="/assets/media/pix-workshop.svg" alt="QR Code Pix para pagamento de R$ 698,00" data-cms-image>
      <div>
        <p><strong data-cms-editable>Chave Pix</strong><br><code data-cms-editable>20.179.548/0001-58</code></p>
        <p><strong data-cms-editable>Copia e cola</strong></p>
        <code class="registration-pix-code" data-pix-copy-value data-cms-editable>00020101021126690014br.gov.bcb.pix0114201795480001580229INSCRICAO MINI CURSO SETEMBRO5204000053039865406698.005802BR592020 1 5 J V T SAIDLER6010PETROPOLIS62070503***6304314B</code>
        <button class="button button-secondary" type="button" data-copy-pix data-cms-editable>Copiar código Pix</button>
      </div>
    </div>
  </section>
</div>
HTML,
        ],
        [
            'id'=>'payment_card',
            'label'=>'Pagamento — cartão',
            'afterField'=>'payment_method',
            'showOnSuccess'=>true,
            'condition'=>['source'=>'payment_method','operator'=>'contains','value'=>'card_'],
            'html'=><<<'HTML'
<div class="registration-payment-source">
  <section class="registration-payment-panel">
    <p class="section-label" data-cms-editable>CARTÃO DE CRÉDITO · MERCADO PAGO</p>
    <h3 data-cms-editable>Pagamento por cartão de crédito</h3>
    <p data-cms-editable>Use o link do Mercado Pago para pagamento à vista ou parcelado. As taxas da plataforma são acrescentadas ao pagamento.</p>
    <p><a class="button" href="https://mpago.la/1xvBsPV" target="_blank" rel="noopener" data-cms-editable>Pagar com cartão no Mercado Pago <span aria-hidden="true">↗</span></a></p>
  </section>
</div>
HTML,
        ],
        [
            'id'=>'terms_copy',
            'label'=>'Condições de inscrição e participação',
            'afterField'=>'payment_method',
            'html'=><<<'HTML'
<section class="registration-terms">
  <p class="section-label" data-cms-editable>CONDIÇÕES DE INSCRIÇÃO E PARTICIPAÇÃO</p>
  <ol>
    <li><strong data-cms-editable>Inscrição e reserva de vaga</strong><p data-cms-editable>A inscrição no site não confirma a reserva da vaga. A inscrição é confirmada somente após a realização e a confirmação do pagamento. O preenchimento deste formulário, sem pagamento, não reserva a vaga.</p></li>
    <li><strong data-cms-editable>Formação das turmas</strong><p data-cms-editable>Os horários serão definidos conforme a disponibilidade e o número de inscritos com pagamento confirmado. Uma turma adicional poderá ser aberta quando houver pelo menos 3 participantes com pagamento confirmado e disponibilidade comum para o mesmo horário.</p></li>
    <li><strong data-cms-editable>Disponibilidade informada</strong><p data-cms-editable>Ao marcar um horário como disponível, o participante declara possuir disponibilidade para os três encontros correspondentes àquele horário. É possível indicar mais de uma opção.</p></li>
    <li><strong data-cms-editable>Confirmação da turma</strong><p data-cms-editable>A turma será considerada formada quando houver participantes com pagamento confirmado e disponibilidade compatível. Caso não seja possível formar a turma em nenhum dos horários indicados pelo participante, o valor pago será devolvido integralmente.</p></li>
    <li><strong data-cms-editable>Formato do curso</strong><p data-cms-editable>O curso acontece on-line e ao vivo, em três encontros. Eventuais ajustes de horário serão comunicados aos participantes.</p></li>
    <li><strong data-cms-editable>Materiais e equipamentos</strong><p data-cms-editable>O participante trabalha com o próprio equipamento e é responsável pelos materiais necessários para seus testes. O suporte indicado neste formulário será enviado para o endereço informado.</p></li>
    <li><strong data-cms-editable>Segurança</strong><p data-cms-editable>O participante é responsável por seguir as orientações de segurança para manipulação de produtos e materiais utilizados durante os processos fotográficos.</p></li>
    <li><strong data-cms-editable>Conduta e material didático</strong><p data-cms-editable>Os materiais disponibilizados durante o curso destinam-se ao uso pessoal do participante e não devem ser reproduzidos ou comercializados sem autorização.</p></li>
    <li><strong data-cms-editable>Dados pessoais</strong><p data-cms-editable>Os dados fornecidos neste formulário serão utilizados para organizar a inscrição, o pagamento, a participação no workshop e o envio do suporte.</p></li>
  </ol>
  <p><strong data-cms-editable>Cancelamento</strong></p>
  <p data-cms-editable>O participante poderá comunicar a desistência. Casos de cancelamento serão tratados conforme o estágio de formação da turma e as despesas já realizadas.</p>
</section>
HTML,
        ],
    ];
}

function workshop_registration_schema_with_content(array $schema): array {
    $schema['settings']=is_array($schema['settings']??null)?$schema['settings']:[];
    $schema['settings']['contentBlocks']=workshop_registration_content_blocks();
    return cms_validate_form_schema($schema);
}

function workshop_registration_strip_legacy_page_content(string $html): string {
    if(trim($html)==='')return $html;
    $previous=libxml_use_internal_errors(true);
    $dom=new DOMDocument('1.0','UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="registration-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $xpath=new DOMXPath($dom);
    foreach(['data-registration-program','data-registration-payment-source','data-registration-terms'] as $attribute){
        foreach(iterator_to_array($xpath->query('//*[@'.$attribute.']')?:[]) as $node)$node->parentNode?->removeChild($node);
    }
    $root=$dom->getElementById('registration-root');$out='';
    if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);
    libxml_clear_errors();libxml_use_internal_errors($previous);
    return $out;
}
