<?php
declare(strict_types=1);

/**
 * Review revision of the X-ray positive supplies CMS page.
 *
 * Never publishes the new content. Revokes the old published content,
 * keeps the course association and existing release/lesson mappings intact.
 * The bibliography is intentionally not touched.
 */
return static function(PDO $db): void {
    $tableExists=static fn(string $table):bool=>(bool)$db->query(
        "SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($table)
    )->fetchColumn();
    if(!$tableExists('cms_pages')||!$tableExists('course_material_pages')||!$tableExists('courses'))return;

    $html=<<<'REVIEW_CONTENT_HTML'
<section class="section" data-cms-section="materiais-introducao" data-cms-section-name="Materiais para o laboratório">
<div class="section-inner">
<p class="section-label" data-cms-editable>Positivo direto em filme de raio-X</p>
<h1 data-cms-editable>Materiais para o laboratório</h1>
<p data-cms-editable>Esta lista reúne os equipamentos, utensílios e produtos químicos necessários para começar a produzir positivos diretamente em filme de raio-X.</p>
<p data-cms-editable>Boa parte dos materiais pode ser encontrada em supermercados, lojas de embalagens, farmácias ou lojas de produtos para piscina. Não é necessário comprar os reagentes de todos os processos: escolha os que pretende experimentar.</p>
</div></section>
<section class="section" data-cms-section="materiais-laboratorio" data-cms-section-name="01 — Equipamentos e utensílios">
<div class="section-inner">
<p class="section-label" data-cms-editable>01 — Equipamentos e utensílios</p><h2 data-cms-editable>Equipamentos e utensílios</h2><div class="cms-resource-item">
<h3 data-cms-editable>Bandejas plásticas para marmita</h3>
<p data-cms-editable><strong>Quantidade sugerida:</strong> 4 unidades.</p>
<p data-cms-editable>Bandejas plásticas simples, sem divisórias, com capacidade para aproximadamente 600 ml de solução. São econômicas, fáceis de encontrar e podem ser utilizadas como bandejas de revelação.</p>
<p data-cms-editable>Confira as dimensões internas antes de comprar. Para trabalhar com filmes de 13 × 18 cm, a bandeja precisa acomodar a chapa inteira e deixar espaço para movimentá-la. Não utilize bandejas de alumínio.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de embalagens, descartáveis e <a href="https://lista.mercadolivre.com.br/bandeja-plastica-pp-750ml" target="_blank" rel="noopener noreferrer">Mercado Livre — bandejas plásticas para marmita</a>.</p>
<figure class="media-figure"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem: bandeja plástica retangular, sem divisórias</span></div></figure>
</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Béquer ou copo medidor</h3>
<p data-cms-editable><strong>Quantidade:</strong> 1 unidade de 600 ml.</p>
<p data-cms-editable>Pode ser de plástico, preferencialmente polipropileno, com graduação para medir os volumes das soluções. Não é necessário comprar vidraria sofisticada.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de materiais para laboratório ou <a href="https://lista.mercadolivre.com.br/bequer-polipropileno-600ml" target="_blank" rel="noopener noreferrer">Mercado Livre — béquer de plástico 600 ml</a>.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Termômetro digital culinário</h3>
<p data-cms-editable><strong>Quantidade:</strong> 1 unidade.</p>
<p data-cms-editable>O termômetro culinário do tipo espeto é suficiente para acompanhar a temperatura da água e do revelador.</p>
<p data-cms-editable><strong>Onde comprar:</strong> <a href="https://produto.mercadolivre.com.br/MLB-1573276562-termmetro-culinario-digital-espeto-alimento-cozinha-tp101-_JM" target="_blank" rel="noopener noreferrer">Exemplo de termômetro digital TP101</a>.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Balança digital de precisão</h3>
<p data-cms-editable><strong>Quantidade:</strong> 1 unidade.</p>
<p data-cms-editable>Recomendo uma balança digital com resolução de 0,01 g e capacidade de 500 g. É pequena, relativamente barata e permite pesar os produtos utilizados nas receitas.</p>
<p data-cms-editable><strong>Onde comprar:</strong> <a href="https://produto.mercadolivre.com.br/MLB-5075663941-mini-balanca-digital-de-alta-preciso-001g-500g-portatil-_JM" target="_blank" rel="noopener noreferrer">Exemplo de balança digital 500 g / 0,01 g</a>.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Garrafas de vidro âmbar de 1 litro</h3>
<p data-cms-editable><strong>Quantidade:</strong> até 3 unidades, conforme os processos utilizados.</p>
<p data-cms-editable>Os frascos têm finalidades diferentes:<ul><li>1 para a solução de cloreto férrico.</li><li>1 para a solução de amônia.</li><li>1 recipiente específico para a solução peracética, caso esse processo seja utilizado.</li></ul></p>
<p data-cms-editable>Para cloreto férrico e amônia, escolha frascos com fechamento compatível com as soluções. A solução peracética exige cuidados adicionais: seu recipiente precisa ser adequado a oxidantes e permitir alívio seguro de pressão. Uma garrafa comum hermeticamente fechada não é apropriada.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de frascos e embalagens ou <a href="https://www.adrialaboratorios.com.br/embalagem/frasco-gpp-ambar-1000ml-com-tampa-preta-e-batoque" target="_blank" rel="noopener noreferrer">exemplo de frasco âmbar de 1 litro com tampa</a>. Esse modelo serve como referência de formato, não como indicação para armazenar a solução peracética.</p>
<figure class="media-figure"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem: frasco âmbar de 1 litro</span></div></figure>
</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Garrafa de cerveja âmbar de aproximadamente 300 ml</h3>
<p data-cms-editable><strong>Quantidade:</strong> pelo menos 1 unidade.</p>
<p data-cms-editable>Para armazenar o concentrado de Parodinal. Uma garrafa de cerveja âmbar é uma alternativa barata e fácil de encontrar. A receita produz aproximadamente 250 ml de concentrado.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Supermercados, depósitos de bebidas ou garrafas reaproveitadas, desde que estejam em boas condições e perfeitamente limpas.</p>
<figure class="media-figure"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem: garrafa de cerveja âmbar pequena</span></div></figure>
</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Pipeta graduada de 25 ml e pipetador</h3>
<p data-cms-editable><strong>Opcional — 1 conjunto.</strong></p>
<p data-cms-editable>A pipeta graduada permite medir diferentes quantidades de Parodinal e transferir o concentrado para frascos menores.</p>
<p data-cms-editable>Escolha uma pipeta <strong>graduada</strong>, não uma pipeta volumétrica de volume fixo. Ela deve ser acompanhada de uma pera pipetadora ou outro pipetador compatível. Nunca pipete com a boca.</p>
<p data-cms-editable><strong>Onde comprar:</strong> <a href="https://www.rplabor.com.br/kit-1-pipeta-graduada-e-1-pera-3-vias/prod-10151580/" target="_blank" rel="noopener noreferrer">Exemplo de kit com pipeta graduada e pera de três vias — selecionar 25 ml</a>.</p>
<figure class="media-figure"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem: pipeta graduada com pera pipetadora</span></div></figure>
</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Funil e utensílios para mistura</h3>
<p data-cms-editable>Um funil pequeno e mexedores próprios para o laboratório facilitam a transferência e a preparação das soluções. Utensílios usados com produtos químicos não devem voltar à cozinha.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de utilidades ou de materiais para laboratório.</p>

</div>
</div></section>
<section class="section" data-cms-section="materiais-quimicos" data-cms-section-name="02 — Produtos químicos">
<div class="section-inner">
<p class="section-label" data-cms-editable>02 — Produtos químicos</p><h2 data-cms-editable>Produtos químicos</h2><p data-cms-editable>Os reagentes são comercializados em embalagens de diferentes tamanhos. Não é necessário encontrar exatamente a quantidade indicada em uma receita: compre a embalagem comercial disponível que melhor se ajuste ao uso previsto.</p><h3 data-cms-editable>Para preparar Parodinal</h3>
<div class="cms-resource-item">
<h3 data-cms-editable>Paracetamol</h3>
<p data-cms-editable>Comprimidos comuns, preferencialmente sem revestimento. Confira a quantidade de princípio ativo indicada na embalagem. Uma caixa com 20 comprimidos de 750 mg fornece 15 g de paracetamol.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Farmácias.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Sulfito de sódio</h3>
<p data-cms-editable>Procurar sulfito de sódio anidro. É vendido por fornecedores de produtos químicos, frequentemente em embalagens de 500 g ou 1 kg.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de reagentes químicos e materiais para laboratório.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Hidróxido de sódio</h3>
<p data-cms-editable>Soda cáustica perolizada, preferencialmente P.A., com pureza identificada. Evite substituir por produtos domésticos de composição desconhecida.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Fornecedores de reagentes químicos.</p>

</div>
<h3 data-cms-editable>Para preparar Brewed Caffenol</h3>
<div class="cms-resource-item">
<h3 data-cms-editable>Café torrado e moído extraforte</h3>
<p data-cms-editable>Café comum de supermercado, como Pilão ou Pelé. A receita utiliza café torrado e moído, não café solúvel.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Supermercados.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Carbonato de sódio — barrilha</h3>
<p data-cms-editable>É a barrilha utilizada em piscinas para elevar o pH. Embalagens comerciais de 2 kg são comuns. Confira a composição e evite produtos com outros ingredientes.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de produtos para piscina ou <a href="https://lista.mercadolivre.com.br/soda-barrilha" target="_blank" rel="noopener noreferrer">Mercado Livre — soda barrilha</a>.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Ácido ascórbico — vitamina C</h3>
<p data-cms-editable>Preferencialmente ácido ascórbico puro, em pó, sem aromatizantes ou outros aditivos.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de suplementos, farmácias ou fornecedores de reagentes.</p>

</div>
<h3 data-cms-editable>Para a solução peracética</h3>
<div class="cms-resource-item">
<h3 data-cms-editable>Vinagre de álcool</h3>
<p data-cms-editable>Vinagre branco comum.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Supermercados.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Peróxido de hidrogênio</h3>
<p data-cms-editable>O processo estudado utiliza peróxido de hidrogênio a 50%, um oxidante altamente concentrado, diferente da água oxigenada de farmácia.</p>
<p data-cms-editable>A aquisição, o manuseio e o armazenamento exigem equipamentos e condições compatíveis com o produto. Consulte a ficha de segurança do fornecedor antes de trabalhar com essa concentração.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Fornecedores especializados de produtos químicos.</p>

</div>
<h3 data-cms-editable>Para o processo com cloreto férrico</h3>
<div class="cms-resource-item">
<h3 data-cms-editable>Cloreto férrico — percloreto de ferro</h3>
<p data-cms-editable>Produto utilizado na corrosão de placas de circuito impresso. É encontrado em pó ou em solução pronta, mas as concentrações não são necessariamente equivalentes.</p>
<p data-cms-editable>Prefira comprar o produto sólido, com composição identificada, em uma embalagem comercial adequada à quantidade de testes prevista.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de componentes eletrônicos e insumos para fabricação de circuitos impressos.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Amônia — amoníaco</h3>
<p data-cms-editable>Solução de amônia sem perfume, detergentes ou outros componentes adicionados. A concentração pode variar entre os produtos comerciais.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Fornecedores de produtos químicos e lojas de insumos profissionais.</p>

</div>
<h3 data-cms-editable>Material comum aos processos</h3>
<div class="cms-resource-item">
<h3 data-cms-editable>Água destilada ou desmineralizada</h3>
<p data-cms-editable>Utilizada na preparação das soluções. Procure água sem aditivos, e não fluido pronto para radiadores.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de autopeças, supermercados ou fornecedores de materiais para laboratório.</p>

</div>
</div></section>
<section class="section" data-cms-section="materiais-opcionais" data-cms-section-name="03 — Outros materiais e cuidados">
<div class="section-inner">
<p class="section-label" data-cms-editable>03 — Outros materiais</p><h2 data-cms-editable>Outros materiais</h2><div class="cms-resource-item">
<h3 data-cms-editable>Bomba manual de vácuo para vinho, com rolha</h3>
<p data-cms-editable><strong>Opcional — 1 conjunto.</strong></p>
<p data-cms-editable>O Parodinal pode oxidar quando permanece em contato com o oxigênio do ar. Uma bomba manual de vácuo para vinho, acompanhada de uma rolha adequada à garrafa, permite retirar parte do ar do recipiente e reduzir esse contato.</p>
<p data-cms-editable>Existem conjuntos econômicos, com bomba e rolhas, por aproximadamente R$ 30. Confira a compatibilidade da rolha com a solução alcalina de Parodinal.</p>
<p data-cms-editable><strong>Uso exclusivo para o Parodinal. Não utilizar nas soluções de branqueamento, especialmente na peracética.</strong></p>
<p data-cms-editable><strong>Onde comprar:</strong> <a href="https://lista.mercadolivre.com.br/bomba-a-vacuo-vinho" target="_blank" rel="noopener noreferrer">Mercado Livre — bombas manuais de vácuo para vinho</a>.</p>
<figure class="media-figure"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Adicionar imagem: bomba manual de vinho com duas rolhas</span></div></figure>
</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Frascos âmbar pequenos para Parodinal</h3>
<p data-cms-editable><strong>Opcionais — quantidade e capacidade conforme o uso.</strong></p>
<p data-cms-editable>O concentrado também pode ser dividido em frascos menores, reduzindo a frequência com que um mesmo frasco é aberto. Escolha as capacidades de acordo com os volumes que costuma utilizar.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Lojas de embalagens farmacêuticas e frascos âmbar.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Luvas descartáveis de látex</h3>
<p data-cms-editable><strong>Opcionais para tarefas leves de manuseio.</strong></p>
<p data-cms-editable>Podem ser úteis para evitar contato direto com filmes e superfícies. Não substituem luvas de proteção química adequadas ao manuseio de substâncias corrosivas ou oxidantes.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Farmácias e lojas de material hospitalar.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Coador de tecido ou voil</h3>
<p data-cms-editable><strong>Para quem utilizar Brewed Caffenol.</strong></p>
<p data-cms-editable>Utilizado na filtragem do café durante a preparação do revelador. Reserve o material exclusivamente para o laboratório.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Supermercados, lojas de tecidos ou de utilidades domésticas.</p>

</div>
<div class="cms-resource-item">
<h3 data-cms-editable>Filme de raio-X</h3>
<p data-cms-editable>O filme utilizado é o <strong>Fuji Super HR-U</strong>, ortocromático e com emulsão nas duas faces. Escolha o formato correspondente à câmera.</p>
<p data-cms-editable><strong>Onde comprar:</strong> Distribuidores de materiais radiológicos, como a ClinRio, conforme disponibilidade de formato e estoque.</p>

</div>
<h2 data-cms-editable>04 — Cuidados no laboratório</h2>
<p data-cms-editable>Utilize recipientes separados e identificados para cada solução, trabalhe em ambiente ventilado e evite contato de produtos químicos com olhos e pele.</p>
<p data-cms-editable>Produtos corrosivos e oxidantes exigem proteção adequada, incluindo óculos contra respingos e luvas compatíveis com cada substância. Luvas descartáveis comuns não oferecem proteção universal.</p>
<p data-cms-editable>Nunca misture amônia com água sanitária, hipoclorito ou outros produtos clorados.</p>
<p data-cms-editable>O peróxido concentrado e as soluções peracéticas exigem cuidados específicos de armazenamento e controle de pressão. Não devem ser guardados em recipientes improvisados e hermeticamente vedados.</p>
</div></section>
REVIEW_CONTENT_HTML;
    $find=$db->prepare("SELECT p.* FROM cms_pages p
      WHERE p.slug='materiais-positivo-direto' AND p.locale='pt-BR'
        AND p.status!='archived'
        AND EXISTS (
          SELECT 1 FROM course_material_pages m JOIN courses c ON c.id=m.course_id
          WHERE m.page_id=p.id AND c.status!='archived'
        )
      ORDER BY p.id");
    $find->execute();
    $pages=$find->fetchAll(PDO::FETCH_ASSOC);
    $now=gmdate('c');
    $update=$db->prepare("UPDATE cms_pages SET
      draft_document_json=?,draft_revision=draft_revision+1,
      draft_updated_at=?,published_document_json=NULL,
      published_revision=NULL,published_at=NULL,show_in_nav=0,
      updated_at=? WHERE id=? AND draft_revision=?");
    foreach($pages as $page) {
        $existing=json_decode((string)($page['draft_document_json']??'{}'),true);
        if(!is_array($existing))$existing=[];
        $currentHtml=(string)($existing['html']??'');
        // A repeat execution must not destroy edits made to this review draft.
        if(str_contains($currentHtml,'data-cms-section="materiais-introducao"')
           && str_contains($currentHtml,'Bandejas plásticas para marmita')
           && str_contains($currentHtml,'Bomba manual de vácuo para vinho, com rolha'))continue;
        // Do not hijack a page created independently with the same slug.
        if(!str_contains($currentHtml,'data-cms-section="materiais-introducao"')
           ||!str_contains($currentHtml,'Materiais e compras para o laboratório'))continue;
        $existing['version']=2;
        $existing['html']=$html;
        $existing['meta']['title']='Materiais para o laboratório — Positivo Direto em Filme de Raio-X';
        $existing['meta']['description']='Lista de equipamentos, utensílios e produtos químicos para preparar o laboratório de positivo direto.';
        $json=json_encode($existing,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $update->execute([$json,$now,$now,(int)$page['id'],(int)$page['draft_revision']]);
        if($update->rowCount()!==1)throw new RuntimeException('materials_draft_revision_conflict');
    }
};
