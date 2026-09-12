<?php
declare(strict_types=1);

const PUBLIC_LOCALE_EN='en';
const PUBLIC_LOCALE_PT_BR='pt-BR';

function normalize_public_locale(mixed $value): ?string {
    if (!is_string($value)) return null;
    $value=strtolower(trim(str_replace('_','-',$value)));
    if ($value==='pt'||str_starts_with($value,'pt-')) return PUBLIC_LOCALE_PT_BR;
    if ($value==='en'||str_starts_with($value,'en-')) return PUBLIC_LOCALE_EN;
    return null;
}

function preferred_public_locale(string $header): string {
    $choices=[];
    foreach (explode(',',$header) as $position=>$part) {
        $bits=array_map('trim',explode(';',$part));
        $locale=normalize_public_locale($bits[0]??'');
        if ($locale===null) continue;
        $quality=1.0;
        foreach (array_slice($bits,1) as $parameter) if (preg_match('/^q=([01](?:\.\d+)?)$/i',$parameter,$match)) $quality=(float)$match[1];
        if ($quality>0) $choices[]=['locale'=>$locale,'quality'=>$quality,'position'=>$position];
    }
    usort($choices,fn(array $a,array $b)=>$b['quality']<=>$a['quality'] ?: $a['position']<=>$b['position']);
    return $choices[0]['locale']??PUBLIC_LOCALE_EN;
}

function public_locale(): string {
    static $locale;
    if ($locale!==null) return $locale;
    $explicit=normalize_public_locale($_POST['_locale']??$_GET['lang']??null);
    return $locale=$explicit??preferred_public_locale((string)($_SERVER['HTTP_ACCEPT_LANGUAGE']??''));
}

function public_locale_query(string $locale): string { return $locale===PUBLIC_LOCALE_PT_BR?'pt-br':'en'; }
function public_language_label(string $locale): string { return $locale===PUBLIC_LOCALE_PT_BR?'Português (Brasil)':'English'; }

function public_page_texts(string $locale): array {
    if ($locale!==PUBLIC_LOCALE_PT_BR) return [];
    return [
        'brand-title'=>'Positivo direto em filme de raios X',
        'hero-kicker'=>'Lista de interesse · Primeira turma em português',
        'hero-title'=>'<span>Positivo</span><span>Direto</span><span>em Filme de Raios X</span>',
        'hero-intro'=>'Um workshop on-line e ao vivo para entender, testar e controlar o positivo direto em filme de raios X.',
        'hero-format'=>'3 encontros ao vivo',
        'hero-group-size'=>'Máximo de 10',
        'hero-language'=>'Português',
        'hero-price'=>'US$ 195',
        'hero-cta'=>'Entrar na lista de interesse <span aria-hidden="true">↘</span>',
        'hero-microcopy'=>'Sem pagamento nesta etapa. Entrar na lista não garante uma vaga.',
        'hero-caption-primary'=>'Medusa · Filme de raios X positivo direto',
        'hero-caption-secondary'=>'Highly Commended · Siena Creative Photo Awards 2025',
        'workshop-label'=>'01 / O workshop',
        'workshop-title'=>'A imagem acontece ao vivo, do clique ao positivo.',
        'workshop-body-1'=>'Nada de aula gravada ou resultado preparado de antemão. Você acompanha, em tempo real, as escolhas de exposição, o preparo da química e a transformação do filme até a imagem positiva.',
        'workshop-body-2'=>'O encontro é voltado a quem fotografa — ou quer começar a fotografar — com filme plano, grande formato e processos alternativos. Mais do que repetir uma receita, a proposta é entender o que acontece em cada etapa para tomar decisões com autonomia.',
        'camera-label'=>'02 / Equipamento',
        'camera-title'=>'A câmera faz parte do processo — e ajuda a enxergá-lo.',
        'camera-body'=>'A NINA é uma câmera artesanal de grande formato criada para trabalhar com positivo direto em filme de raios X. Corpo, obturador, foco e processamento à luz do dia foram pensados como partes do mesmo fluxo fotográfico.',
        'camera-note'=>'A proposta não é ensinar a construir a câmera. Ela entra no workshop porque deixa visíveis as escolhas que formam a imagem.',
        'process-label'=>'03 / Da exposição ao positivo',
        'process-title'=>'Da medição da luz à leitura do positivo final.',
        'format-label'=>'04 / Formato previsto',
        'format-title'=>'Uma turma pequena para acompanhar cada resultado de perto.',
        'format-intro'=>'As vagas serão limitadas para que todo mundo tenha espaço para perguntar, comparar resultados e discutir problemas reais do processo.',
        'about-label'=>'05 / O fotógrafo',
        'about-title'=>'João Saidler',
        'about-lead'=>'Fotógrafo e pesquisador independente. Desde 2018, desenvolve imagens com câmeras artesanais de grande formato, filme de raios X e química fotográfica acessível.',
        'about-body'=>'Seu trabalho parte da imagem e das perguntas que ela impõe. Câmeras, obturadores, tanques e métodos de processamento são ferramentas de uma prática que busca manter exposição, química e objeto final sob o controle de quem fotografa.',
        'about-caption'=>'João Saidler · Fotógrafo e pesquisador independente',
        'interest-label'=>'06 / Lista de interesse',
        'interest-title'=>'Esse workshop faz sentido para você?',
        'interest-intro'=>'Conte um pouco sobre seu interesse e sua disponibilidade. As respostas vão orientar as datas e a formação da primeira turma em português. Não há pagamento nem reserva de vaga nesta etapa.',
        'footer-location'=>'João Saidler · Petrópolis, Brasil',
        'footer-description'=>'Positivo direto em filme de raios X · Pesquisa de interesse',
        'footer-privacy'=>'Privacidade',
    ];
}

function public_localized_html(string $id,string $fallback,string $locale): string {
    return public_page_texts($locale)[$id]??$fallback;
}

function public_localized_image_alt(string $id,string $fallback,string $locale): string {
    if ($locale!==PUBLIC_LOCALE_PT_BR) return $fallback;
    return ['hero-image'=>'Medusa, retrato em preto e branco feito como positivo direto em filme de raios X','author-portrait'=>'Fotógrafo João Saidler'][$id]??$fallback;
}

function public_localized_media_caption(string $id,string $fallback,string $locale): string {
    if ($locale!==PUBLIC_LOCALE_PT_BR) return $fallback;
    return ['nina-video'=>'NINA · Câmera artesanal de grande formato','process-video'=>'Catedral · Petrópolis'][$id]??$fallback;
}

function public_localize_template(string $html,string $locale): string {
    if ($locale!==PUBLIC_LOCALE_PT_BR) return $html;
    return strtr($html,[
        '<html lang="en">'=>'<html lang="pt-BR">',
        'Interest survey for a fully live online workshop on direct-positive X-ray film, exposure, reversal processing and tonal control.'=>'Pesquisa de interesse para um workshop on-line ao vivo sobre positivo direto em filme de raios X, exposição, processamento por reversão e controle tonal.',
        '<title>Direct Positive X-Ray Film — Live Online Workshop</title>'=>'<title>Positivo Direto em Filme de Raios X — Workshop On-line ao Vivo</title>',
        '>Skip to content<'=>'>Pular para o conteúdo<',
        'aria-label="Primary navigation"'=>'aria-label="Navegação principal"',
        '>Camera<'=>'>Câmera<',
        '>Process<'=>'>Processo<',
        '>Interest survey<'=>'>Pesquisa de interesse<',
        'aria-label="Color theme"'=>'aria-label="Tema de cores"',
        '>Light<'=>'>Claro<',
        '>Dark<'=>'>Escuro<',
        '<dt>Format</dt>'=>'<dt>Formato</dt>',
        '<dt>Group</dt>'=>'<dt>Turma</dt>',
        '<dt>Language</dt>'=>'<dt>Idioma</dt>',
        '<dt>Planned price</dt>'=>'<dt>Preço previsto</dt>',
        'alt="Medusa, a black-and-white direct-positive portrait made on X-ray film"'=>'alt="Medusa, retrato em preto e branco feito como positivo direto em filme de raios X"',
        'data-caption-fallback="NINA · Handmade large-format camera"'=>'data-caption-fallback="NINA · Câmera artesanal de grande formato"',
        '>NINA · Handmade large-format camera<'=>'>NINA · Câmera artesanal de grande formato<',
        'aria-label="Reserved media position for the Cathedral of Petrópolis process reel"'=>'aria-label="Espaço reservado para o vídeo do processo da Catedral de Petrópolis"',
        '03 / Process reel'=>'03 / Processo completo',
        'Exposure · Processing · Final positive<br/>Selected reel to be inserted'=>'Da exposição ao positivo final<br/>Vídeo do processo completo',
        '<h3>Exposure</h3><p>Metering, working EI, tonal placement and reciprocity compensation.</p>'=>'<h3>Exposição</h3><p>Como medir a luz, escolher o índice de exposição e posicionar os tons antes do clique.</p>',
        '<h3>First development</h3><p>Time, temperature, movement and the formation of the final tonal scale.</p>'=>'<h3>Primeira revelação</h3><p>Como tempo, temperatura e agitação constroem a escala tonal da imagem.</p>',
        '<h3>Reversal</h3><p>Bleaching, clearing, re-exposure and second development.</p>'=>'<h3>Reversão</h3><p>O papel de cada banho, da reexposição e da segunda revelação na formação do positivo.</p>',
        '<h3>Reading the result</h3><p>Shadow density, midtone progression, highlights and process defects.</p>'=>'<h3>Leitura do resultado</h3><p>Como avaliar sombras, meios-tons, altas-luzes e reconhecer falhas do processo.</p>',
        '<h3>Three live online sessions</h3><p>Exposure and image formation; live processing; analysis of participants’ results.</p>'=>'<h3>Três encontros on-line ao vivo</h3><p>Um percurso da exposição ao processamento, com espaço para analisar os resultados da turma.</p>',
        '<h3>Maximum 10 participants</h3><p>A full group for the demonstrations, with structured time for questions and result analysis.</p>'=>'<h3>Até 10 participantes</h3><p>Uma turma enxuta, com tempo de verdade para perguntas, trocas e análise de imagens.</p>',
        '<h3>No recordings</h3><p>The workshop exists as a live encounter with the process, the objects and the participants.</p>'=>'<h3>Encontros sem gravação</h3><p>A experiência acontece ao vivo, com o processo, os materiais e as dúvidas de quem participa.</p>',
        '<h3 data-workshop-price-title>Planned price: US$195</h3><p>The survey measures serious interest before dates and enrollment are opened.</p>'=>'<h3 data-workshop-price-title>Preço previsto: US$ 195</h3><p>A pesquisa mede o interesse real antes da definição das datas e da abertura das inscrições.</p>',
        '<dt>International recognition</dt><dd>Two distinctions at the Siena Creative Photo Awards, including Highly Commended in the People category in 2025.</dd>'=>'<dt>Reconhecimento internacional</dt><dd>Duas distinções no Siena Creative Photo Awards, incluindo Highly Commended na categoria People em 2025.</dd>',
        '<dt>Editorial presence</dt><dd>Repeated selections and features by STRKNG and recognition in the ArtLimited Portraiture Awards.</dd>'=>'<dt>Presença editorial</dt><dd>Seleções e destaques recorrentes na STRKNG e reconhecimento no ArtLimited Portraiture Awards.</dd>',
        'alt="Photographer João Saidler"'=>'alt="Fotógrafo João Saidler"',
        '<span>3 live sessions</span>'=>'<span>3 encontros ao vivo</span>',
        '<span>Maximum 10</span>'=>'<span>Máximo de 10</span>',
        '<span>Workshop in English</span>'=>'<span>Workshop em português</span>',
        '<span data-workshop-price-summary>US$195 planned</span>'=>'<span data-workshop-price-summary>US$ 195 previstos</span>',
    ]);
}

function public_form_translations(string $locale): array {
    if ($locale!==PUBLIC_LOCALE_PT_BR) return [];
    return [
        'submitLabel'=>'Quero receber as informações',
        'fields'=>[
            'name'=>['label'=>'Nome'],
            'email'=>['label'=>'E-mail'],
            'country'=>['label'=>'País'],
            'city'=>['label'=>'Cidade'],
            'timezone'=>['label'=>'Fuso horário'],
            'experience'=>['label'=>'Qual é a sua experiência com fotografia analógica?'],
            'preferred_days'=>['label'=>'Quais dias funcionam melhor para você?'],
            'preferred_time'=>['label'=>'Qual horário funciona melhor?'],
            'price_response'=>['label'=>'Você participaria do workshop por US$ 195?','options'=>['yes'=>'Sim — Tenho interesse em participar.','maybe'=>'Talvez — Depende das datas.','no'=>'Não — Esse valor não funciona para mim.']],
            'main_interest'=>['label'=>'O que você gostaria de aprender ou destravar no processo?'],
            'consent'=>['label'=>'Quero receber informações sobre este workshop.'],
        ],
    ];
}

function form_field_visible_in_locale(array $field,string $locale): bool {
    $audience=$field['audience']??'both';
    return $audience==='both'||$audience===$locale;
}

function localize_interest_form(array $form,string $locale): array {
    $translations=public_form_translations($locale);
    if($translations){
        $form['submitLabel']=$translations['submitLabel'];
        foreach ($form['fields'] as &$field) {
            $translated=$translations['fields'][$field['id']]??null;
            if (!$translated) continue;
            $field['label']=$translated['label'];
            if (isset($translated['options'],$field['options'])) foreach ($field['options'] as &$option) if (isset($translated['options'][$option['value']])) $option['label']=$translated['options'][$option['value']];
            unset($option);
        }
        unset($field);
    }
    $form['fields']=array_values(array_filter($form['fields'],fn(array $field)=>form_field_visible_in_locale($field,$locale)));
    return $form;
}

function public_price_question(string $locale,string $price): string {
    return $locale===PUBLIC_LOCALE_PT_BR?'Você participaria do workshop por '.$price.'?':'Would you seriously consider joining at '.$price.'?';
}

function workshop_price_apply_to_form(array $form,string $locale,string $price): array {
    if(!isset($form['fields'])||!is_array($form['fields']))return $form;
    foreach($form['fields'] as &$field)if(($field['id']??null)==='price_response')$field['label']=public_price_question($locale,$price);
    unset($field);
    return $form;
}

function public_message(string $key,string $locale): string {
    $messages=[
        'en'=>[
            'form_failed'=>'Unable to submit the form. Please try again.','form_summary'=>'Please correct the highlighted fields.','sending'=>'Sending…',
            'registered'=>'Interest registered','thanks'=>'Thank you.','confirmation'=>'Your interest was registered. This does not reserve a place and no payment has been made.','return'=>'Return to workshop',
        ],
        'pt-BR'=>[
            'form_failed'=>'Não foi possível enviar o formulário. Tente novamente.','form_summary'=>'Corrija os campos destacados.','sending'=>'Enviando…',
            'registered'=>'Interesse recebido','thanks'=>'Obrigado pelo interesse.','confirmation'=>'Recebemos seus dados. Este envio não garante uma vaga e nenhum pagamento foi realizado.','return'=>'Voltar ao workshop',
        ],
    ];
    return $messages[$locale][$key]??$messages['en'][$key]??$key;
}

function public_validation_message(string $key,string $locale): string {
    $messages=[
        'en'=>['invalid'=>'Invalid value.','long'=>'This value is too long.','valid_days'=>'Choose valid days.','one_day'=>'Choose at least one day.','required'=>'This field is required.','email'=>'Enter a valid email address.','timezone'=>'Choose a valid time zone.','time'=>'Enter a valid time in 24-hour format.','option'=>'Choose a valid option.','consent'=>'Please confirm this field.'],
        'pt-BR'=>['invalid'=>'Valor inválido.','long'=>'Este valor é muito longo.','valid_days'=>'Escolha dias válidos.','one_day'=>'Escolha pelo menos um dia.','required'=>'Este campo é obrigatório.','email'=>'Informe um e-mail válido.','timezone'=>'Escolha um fuso horário válido.','time'=>'Informe um horário válido no formato de 24 horas.','option'=>'Escolha uma opção válida.','consent'=>'Confirme este campo.'],
    ];
    return $messages[$locale][$key]??$messages['en'][$key]??$key;
}
