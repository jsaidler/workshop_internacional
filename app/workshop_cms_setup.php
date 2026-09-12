<?php
declare(strict_types=1);

/**
 * Project-specific CMS defaults for the Direct Positive X-Ray Film workshop.
 *
 * The generic CMS seeds stay generic enough for the editor. This layer applies
 * the actual commercial structure used by this project: a dedicated Brazilian
 * registration page and the existing English interest survey.
 */

function workshop_registration_schema(): array {
    return cms_validate_form_schema([
        'version'=>1,
        'submitLabel'=>'Enviar minha inscrição',
        'successTitle'=>'Inscrição recebida',
        'successMessage'=>'Recebi sua inscrição. Vou cruzar as disponibilidades da turma e entrar em contato com a definição da data e as orientações de pagamento.',
        'fields'=>[
            [
                'id'=>'name','type'=>'text','label'=>'Nome completo','required'=>true,
                'autocomplete'=>'name','width'=>'half',
            ],
            [
                'id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,
                'autocomplete'=>'email','width'=>'half',
            ],
            [
                'id'=>'phone','type'=>'tel','label'=>'WhatsApp / telefone','required'=>true,
                'autocomplete'=>'tel','width'=>'half',
                'help'=>'Use um número em que eu consiga falar com você sobre a formação da turma.',
            ],
            [
                'id'=>'city','type'=>'text','label'=>'Cidade / UF','required'=>true,
                'autocomplete'=>'address-level2','width'=>'half',
            ],
            [
                'id'=>'instagram','type'=>'text','label'=>'Instagram','required'=>false,
                'placeholder'=>'@seuusuario','width'=>'half',
            ],
            [
                'id'=>'experience','type'=>'textarea','label'=>'Qual é a sua experiência com fotografia analógica?','required'=>false,
                'rows'=>3,'width'=>'full',
            ],
            [
                'id'=>'equipment_format','type'=>'checkbox-group','label'=>'Que formato você pretende usar no workshop?','required'=>false,
                'width'=>'full','help'=>'Marque todas as opções que fazem sentido para os seus testes.',
                'options'=>[
                    ['value'=>'35mm','label'=>'35 mm'],
                    ['value'=>'medium','label'=>'Médio formato'],
                    ['value'=>'large','label'=>'Grande formato'],
                    ['value'=>'other','label'=>'Outro / ainda não definido'],
                ],
            ],
            [
                'id'=>'equipment','type'=>'textarea','label'=>'Que câmera, filme ou material você pretende usar?','required'=>false,
                'rows'=>3,'width'=>'full',
                'help'=>'Não é necessário usar o mesmo equipamento ou os mesmos materiais que eu uso.',
            ],
            [
                'id'=>'availability','type'=>'checkbox-group','label'=>'Disponibilidade para os encontros','required'=>true,
                'width'=>'full',
                'help'=>'Marque todas as possibilidades em que você consegue participar. A ideia é reunir a turma no mesmo horário sempre que possível.',
                'options'=>[
                    ['value'=>'tuesday_19','label'=>'Terça-feira · 19h'],
                    ['value'=>'thursday_19','label'=>'Quinta-feira · 19h'],
                    ['value'=>'other','label'=>'Outro dia ou horário'],
                ],
            ],
            [
                'id'=>'other_availability','type'=>'text','label'=>'Se marcou outro dia ou horário, qual?','required'=>false,
                'width'=>'full','placeholder'=>'Ex.: quarta à noite, sábado pela manhã…',
            ],
            [
                'id'=>'payment_preference','type'=>'radio','label'=>'Forma de pagamento que você provavelmente usaria','required'=>false,
                'width'=>'full','options'=>[
                    ['value'=>'pix','label'=>'Pix · R$ 698'],
                    ['value'=>'card','label'=>'Cartão · inclusive parcelado, com as taxas da plataforma'],
                    ['value'=>'undecided','label'=>'Ainda não decidi'],
                ],
            ],
            [
                'id'=>'notes','type'=>'textarea','label'=>'Há alguma dúvida ou informação que eu deva considerar?','required'=>false,
                'rows'=>4,'width'=>'full',
            ],
            [
                'id'=>'terms','type'=>'consent',
                'label'=>'Li as condições apresentadas nesta página e estou ciente do formato ao vivo, da dinâmica de definição das datas e do valor do workshop. Concordo em receber contato por e-mail ou WhatsApp sobre esta inscrição.',
                'required'=>true,'width'=>'full',
            ],
        ],
    ]);
}

function workshop_registration_page_document(): array {
    $html=<<<'HTML'
<section class="section" data-cms-section="registration-intro" data-cms-section-name="Abertura da inscrição">
  <p class="section-label" data-cms-editable>INSCRIÇÃO · NOVA TURMA</p>
  <div class="statement-grid">
    <h2 data-cms-editable>Positivo direto em filme de raios X</h2>
    <div class="statement-copy">
      <p data-cms-editable>Esta é a inscrição para a nova turma em português do workshop on-line e ao vivo. São três encontros: preparação e exposição; demonstração prática completa com comparação de resultados; e análise das imagens produzidas pelos participantes.</p>
      <p data-cms-editable><strong>Valor: R$ 698 via Pix.</strong> Também é possível pagar no cartão, inclusive parcelado, com as taxas da plataforma.</p>
      <p><a class="button button-secondary" href="/?lang=pt-br" data-cms-editable>Voltar ao workshop <span aria-hidden="true">↗</span></a></p>
    </div>
  </div>
</section>
<section class="format" data-cms-section="registration-conditions" data-cms-section-name="Condições da inscrição">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>ANTES DE ENVIAR</p>
    <div class="format-heading">
      <h2 data-cms-editable>A inscrição também organiza a formação da turma.</h2>
      <p data-cms-editable>Marque toda a sua disponibilidade real. Quanto mais opções você indicar, mais fácil é reunir os participantes no mesmo grupo.</p>
    </div>
    <div class="format-grid">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>3 encontros ao vivo</h3><p data-cms-editable>O workshop é on-line, acontece ao vivo e não é gravado.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Definição das datas</h3><p data-cms-editable>A turma acontece na opção que reunir mais participantes. Uma turma alternativa só é aberta quando houver pelo menos 3 participantes com disponibilidade comum em outra data.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Pagamento</h3><p data-cms-editable>R$ 698 via Pix. No cartão, é possível parcelar; as taxas da plataforma são acrescentadas ao pagamento.</p></article>
      <article class="format-card"><span class="number">04</span><h3 data-cms-editable>Suporte incluído</h3><p data-cms-editable>Os participantes da turma em português recebem o suporte dobrável desenvolvido para reduzir o contato da dupla emulsão do filme de raios X com a bandeja durante o processamento.</p></article>
    </div>
  </div>
</section>
<section class="interest cms-form-section" id="formulario" data-cms-section="registration-form" data-cms-section-name="Formulário de inscrição">
  <div class="interest-inner">
    <div class="interest-intro">
      <p class="section-label" data-cms-editable>FORMULÁRIO DE INSCRIÇÃO</p>
      <h2 data-cms-editable>Se você quer entrar na nova turma, preencha aqui.</h2>
      <p data-cms-editable>O envio do formulário não realiza cobrança. Depois de cruzar as disponibilidades, eu entro em contato para confirmar a formação da turma, a data e as orientações de pagamento.</p>
    </div>
    <div data-cms-form-key="registration"></div>
  </div>
</section>
HTML;
    return cms_page_document([
        'theme'=>'auto',
        'meta'=>[
            'title'=>'Inscrição — Positivo Direto em Filme de Raios X',
            'description'=>'Inscrição para a nova turma em português do workshop on-line e ao vivo de positivo direto em filme de raios X.',
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
      <p data-cms-editable>A inscrição agora acontece em uma página própria, com as condições da turma, disponibilidade e os dados necessários para organizar os encontros.</p>
    </div>
    <div class="interest-confirmation">
      <p class="section-label" data-cms-editable>INSCRIÇÃO</p>
      <h2 data-cms-editable>R$ 698 via Pix</h2>
      <p data-cms-editable>Também é possível pagar no cartão, inclusive parcelado, com as taxas da plataforma.</p>
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

    // Ensure the generic tables have initial records, then project them onto the
    // structure actually used by this workshop.
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

    // The dedicated /inscricao/ endpoint is intentionally stable while the
    // landing page renderer is being validated separately.
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
