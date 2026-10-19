<?php
declare(strict_types=1);

/**
 * Adds two ordinary CMS documents. Never changes preexisting editorial documents,
 * release timestamps, enrollment or public menu. Executed once by migration runner.
 * Source bibliography: João Saidler, "Bibliografia", Substack, 24/01/2026.
 * Source shopping channels: course email "Receitas, materiais..." 07/09/2026,
 * plus requested lab equipment; offers are retailer search references.
 */
return static function(PDO $db): void {
    $exists=static fn(string $t):bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($t))->fetchColumn();
    foreach(['activities','cms_pages','courses','course_lessons','course_material_pages','course_material_sections','cohort_lesson_releases'] as $table)if(!$exists($table))return;
    $now=gmdate('c');
    $materialsHtml=<<<'MATERIAL_HTML'
<section class="section" data-cms-section="materiais-introducao" data-cms-section-name="Antes de começar">
<div class="section-inner"><p class="section-label" data-cms-editable>Positivo direto em filme de raio-X</p>
<h1 data-cms-editable>Materiais e compras para o laboratório</h1>
<p data-cms-editable>Esta lista reúne os materiais utilizados na pesquisa e as alternativas de compra para organizar o laboratório. A liberação acompanha a primeira aula de cada turma. Você não precisa comprar reagentes de todas as rotas químicas: escolha o processo que irá experimentar com a orientação do curso.</p>
<p data-cms-editable>Os links levam a buscas de fornecedores, não a uma compra obrigatória ou recomendação comercial de vendedor. Confirme medidas, concentração, composição, compatibilidade e disponibilidade antes de comprar.</p>
<p data-cms-editable><strong>Segurança:</strong> laboratório químico não é cozinha. Nenhum utensílio dedicado aos químicos deve voltar ao uso alimentar. Produtos corrosivos e oxidantes exigem EPI, ventilação, identificação dos frascos e consulta à FISPQ. Nunca misture amônia e produtos clorados.</p></div></section>
<section class="section" data-cms-section="materiais-laboratorio" data-cms-section-name="01 / Laboratório">
<div class="section-inner"><p class="section-label" data-cms-editable>01 / Laboratório</p><h2 data-cms-editable>Bandejas, medição e armazenamento</h2><p data-cms-editable>Os recipientes precisam acomodar o filme no formato utilizado e conter a solução com margem para movimentação, sem derramamento. Capacidade nominal não é volume útil.</p>
<ul><li><strong data-cms-editable>Bandejas de marmita com capacidade de pelo menos 600 ml</strong> — <span data-cms-editable>Prefira bandejas plásticas PP ou PEAD de 750 ml ou maiores, estáveis e compatíveis com a solução utilizada; mantenha cada banho em recipiente identificado. Bandejas de alumínio não servem para soluções cáusticas, ácidas ou cloreto férrico.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de embalagens e descartáveis; <a href="https://lista.mercadolivre.com.br/bandeja-plastica-pp-750ml" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Termômetro culinário digital</strong> — <span data-cms-editable>Pode ser um modelo simples de haste, com leitura em graus Celsius; depois de destinado ao laboratório não deve voltar ao uso alimentar.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de utensílios domésticos; <a href="https://lista.mercadolivre.com.br/termometro-culinario-digital-haste" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Béquer ou copo medidor de 600 ml</strong> — <span data-cms-editable>Pode ser de plástico PP graduado; confira resistência química e graduação legível. Volume útil de pelo menos 600 ml.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de material de laboratório; <a href="https://lista.mercadolivre.com.br/bequer-polipropileno-600ml" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Garrafas de vidro âmbar de 1 litro</strong> — <span data-cms-editable>Com tampa compatível e vedação; use apenas para soluções cuja compatibilidade com vidro/tampa foi verificada, sempre identificadas.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de frascos farmacêuticos e embalagens; <a href="https://lista.mercadolivre.com.br/frasco-vidro-ambar-1000ml-tampa" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Garrafas de cerveja de vidro âmbar, cerca de 300 ml</strong> — <span data-cms-editable>Recipientes pequenos reaproveitados para soluções compatíveis, com fechamento apropriado e rótulo permanente; não reutilizar para bebidas.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de insumos cervejeiros ou garrafas âmbar reutilizadas; <a href="https://lista.mercadolivre.com.br/garrafa-cerveja-vidro-ambar-300ml" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Seringas graduadas ou pipetas para medição</strong> — <span data-cms-editable>Separar uma por produto; não compartilhar utensílios entre reagentes.</span> <span data-cms-editable><em>Onde procurar:</em> farmácias e fornecedores de laboratório; <a href="https://lista.mercadolivre.com.br/seringa-graduada-20ml-sem-agulha" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Funis, mexedores e etiquetas resistentes</strong> — <span data-cms-editable>Funis de plástico compatível, material exclusivo do laboratório e identificação de nome, concentração e data.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de utensílios e laboratório; <a href="https://lista.mercadolivre.com.br/funil-plastico-laboratorio-pp" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Luvas químicas, óculos de proteção e avental</strong> — <span data-cms-editable>Escolher EPI adequado à ficha de segurança de cada solução. Óculos fechados contra respingos são preferíveis aos óculos abertos.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de EPI; <a href="https://lista.mercadolivre.com.br/oculos-protecao-quimica-fechado" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Filme de raio-X em folha</strong> — <span data-cms-editable>Verificar formato compatível com a câmera e emulsão; o utilizado no curso é o Fuji Super HR-U.</span> <span data-cms-editable><em>Onde procurar:</em> distribuidores radiológicos; ClinRio é uma referência citada no material do curso.</span></li></ul></div></section>
<section class="section" data-cms-section="materiais-quimicos" data-cms-section-name="02 / Químicos">
<div class="section-inner"><p class="section-label" data-cms-editable>02 / Químicos</p><h2 data-cms-editable>Insumos das rotas de processamento</h2><p data-cms-editable>Parodinal, Brewed Caffenol e os branqueadores têm componentes diferentes. Os nomes abaixo identificam materiais, não constituem instruções de preparo.</p>
<ul><li><strong data-cms-editable>Água destilada ou desmineralizada</strong> — <span data-cms-editable>Base para soluções; buscar água sem aditivos, não fluido de radiador.</span> <span data-cms-editable><em>Onde procurar:</em> loja de autopeças ou distribuidor químico; <a href="https://lista.mercadolivre.com.br/agua-desmineralizada-1-litro" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Paracetamol</strong> — <span data-cms-editable>Matéria-prima do concentrado Parodinal; observar princípio ativo indicado na embalagem.</span> <span data-cms-editable><em>Onde procurar:</em> farmácia; conferir formulação e excipientes.</span></li>
<li><strong data-cms-editable>Sulfito de sódio</strong> — <span data-cms-editable>Componente do Parodinal.</span> <span data-cms-editable><em>Onde procurar:</em> fornecedor de reagentes, com identificação e ficha de segurança.</span></li>
<li><strong data-cms-editable>Hidróxido de sódio (soda cáustica perolizada P.A.)</strong> — <span data-cms-editable>Reagente corrosivo usado no Parodinal, não substituir automaticamente por soda doméstica.</span> <span data-cms-editable><em>Onde procurar:</em> fornecedor químico especializado, com FISPQ e pureza declarada.</span></li>
<li><strong data-cms-editable>Café torrado e moído extra-forte</strong> — <span data-cms-editable>Para Brewed Caffenol; o material original utiliza café torrado/moído, não solúvel.</span> <span data-cms-editable><em>Onde procurar:</em> supermercado; Pilão ou Pelé são exemplos citados pelo professor.</span></li>
<li><strong data-cms-editable>Carbonato de sódio (soda barrilha)</strong> — <span data-cms-editable>Para Brewed Caffenol; verificar composição sem aditivos.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de materiais para piscina; <a href="https://lista.mercadolivre.com.br/carbonato-de-sodio-barrilha" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Ácido ascórbico (vitamina C)</strong> — <span data-cms-editable>Para Brewed Caffenol; verificar formulação.</span> <span data-cms-editable><em>Onde procurar:</em> farmácia, loja de suplementos ou fornecedor de reagentes; <a href="https://lista.mercadolivre.com.br/acido-ascorbico-vitamina-c-po" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Vinagre de álcool</strong> — <span data-cms-editable>Matéria-prima da rota peracética; não pressupor equivalência de outras formulações.</span> <span data-cms-editable><em>Onde procurar:</em> supermercado.</span></li>
<li><strong data-cms-editable>Peróxido de hidrogênio concentrado</strong> — <span data-cms-editable>A formulação pesquisada no curso menciona 50%, produto oxidante de alto risco, não equivalente à água oxigenada de farmácia. A compra e o uso exigem orientação técnica, EPI e ficha de segurança; não é item para manipulação doméstica improvisada.</span> <span data-cms-editable><em>Onde procurar:</em> fornecedor químico habilitado; discutir alternativas menos perigosas com o professor.</span></li>
<li><strong data-cms-editable>Cloreto férrico (percloreto de ferro)</strong> — <span data-cms-editable>Para uma das rotas de branqueamento; sólido e solução comercial não são intercambiáveis em concentração.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de componentes eletrônicos e insumos para placas de circuito; <a href="https://lista.mercadolivre.com.br/cloreto-ferrico-corrosivo-pci" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Amônia simples (amoníaco/hidróxido de amônio)</strong> — <span data-cms-editable>Para a etapa de limpeza específica; sem perfumes ou detergentes. Nunca misturar com água sanitária ou produtos clorados.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de insumos profissionais que forneçam composição e ficha de segurança.</span></li></ul></div></section>
<section class="section" data-cms-section="materiais-opcionais" data-cms-section-name="03 / Complementares">
<div class="section-inner"><p class="section-label" data-cms-editable>03 / Complementares</p><h2 data-cms-editable>Itens opcionais e reutilizáveis</h2><p data-cms-editable>Compre apenas se fizerem sentido para os testes e forem compatíveis com as soluções escolhidas.</p>
<ul><li><strong data-cms-editable>Rolhas de vedação e bomba de vácuo manual para vinho</strong> — <span data-cms-editable>Somente como alternativa experimental para recipientes e líquidos compatíveis, nunca em soluções oxidantes, que liberem gases ou sejam voláteis. Não aplicar vácuo a peróxidos, peracéticos ou amônia.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de acessórios para vinho; <a href="https://lista.mercadolivre.com.br/vacu-vin-bomba-vacuo-rolhas-vinho" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Suporte para filme de dupla emulsão</strong> — <span data-cms-editable>Mantém o filme afastado do fundo da bandeja, reduz riscos e permite trabalhar as duas faces.</span> <span data-cms-editable><em>Onde procurar:</em> suporte apropriado ou confecção conforme o curso.</span></li>
<li><strong data-cms-editable>Balança de precisão e recipientes de pesagem</strong> — <span data-cms-editable>Úteis quando houver pesagem de reagentes; utensílios exclusivos do laboratório.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de instrumentos de medição; <a href="https://lista.mercadolivre.com.br/balanca-precisao-0.01g" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li>
<li><strong data-cms-editable>Coador de tecido/voil</strong> — <span data-cms-editable>Para preparo e filtração de reveladores à base de café, separado de qualquer uso alimentar.</span> <span data-cms-editable><em>Onde procurar:</em> lojas de tecidos e utilidades; <a href="https://lista.mercadolivre.com.br/tecido-voal-filtragem" target="_blank" rel="noopener noreferrer">Pesquisar opções de compra</a>.</span></li></ul></div></section>
MATERIAL_HTML;
    $bibliographyHtml=<<<'BIBLIOGRAPHY_HTML'
<section class="section" data-cms-section="bibliografia" data-cms-section-name="Bibliografia">
<div class="section-inner">
<p class="section-label" data-cms-editable>Pesquisa fotográfica · leitura e referências</p>
<h1 data-cms-editable>Bibliografia</h1>
<div class="statement-copy">
<p data-cms-editable>Em 2018 não haviam IAs disponíveis ao grande público. Pesquisar informalmente estava muito próximo ao trabalho acadêmico formal. Consultar livros, patentes e sites especializados.</p>
<p data-cms-editable>Hoje é muito mais simples perguntar para uma máquina e usar este conhecimento prévio na detecção de incoerências, afinal, como está escrito nas letras pequeninas no rodapé do Chat GPT, IAs erram.</p>
<p data-cms-editable>Continuo lendo e estudando fotografia em livros e uso a IA em pesquisas pontuais ou quando a resposta que preciso dependeria de uma leitura extensa e multidisciplinar. Você vai notar que as últimas datas são de 2022.</p>
<p data-cms-editable>Esta é a bibliografia atualizada da minha pesquisa, sempre que o conteúdo não envolve inteligências artificiais.</p>
<p data-cms-editable>TAYLOR, J. Traill. The Optics of Photography an Photographic Lenses: Third Edition Revised. New York: Whittaker &amp; Co., 1904.</p>
<p data-cms-editable>SCHRIEVER, J. B. Complete Self-Instructing Library of Practical Photography: Volume I, Elementary Photography. Scranton, PA.: American School of Art and Photography, 1909.</p>
<p data-cms-editable>SCHRIEVER, J. B. Complete Self-Instructing Library of Practical Photography: Volume VII, Studio Portraiture, Part I. Scranton, PA.: American School of Art and Photography, 1909.</p>
<p data-cms-editable>BARROWS, George L. The “AGFA”-BOOK of Photographic Formulae . New York, N.Y.: Berlin Aneline Works, 1910.</p>
<p data-cms-editable>C. E. K. MESS, D.Sc. The Fundamentals of Photography. Rochester, N.Y.: Eastman Kodak Company, 1921.</p>
<p data-cms-editable>HORDER, Alan The Manual of Photography. Formerly The Ilford Manual of Photography London, UK: Focal Press Limited, Apr. 1971.</p>
<p data-cms-editable>JACOBSON, C. I.; JACOBSON, R. E. Developing, The Negative Thecnique. London, UK: Focal Press, Mar. 1972.</p>
<p data-cms-editable>SEELENTAG, W. W.; PANZER, W. Reciprocity law failure with film/screen combinations for mammography. British Journal of Radiology, 51, 529-531, 07/1978.</p>
<p data-cms-editable>ARNOLD, Ben A., M.P.H.; EISENBERG, Harvey, M.D.; BJARNGARD, Bengt E.; Ph.D.Measurements of Reciprocity Law Failure in Green-Sensitive X-Ray Films. Radiology 126:493-498, 02/ 1978.</p>
<p data-cms-editable>RODRIGUES, César Benedito Fernandes; TAVANO, Orivaldo. Avaliação Sensitométrica de Filmes Radiográficos da Kodak Processados na Procesadora Automática Dupont Cronex T4. Utilizando Solução Kodak RP X-O-MAT em Diferentes Temperaturas. Resumo de parte da tese (Doutorado em Diagnóstico Bucal) - Faculdade de Odontologia de Bauru. USP.</p>
<p data-cms-editable>SERMAN, Neill. Processing the Radiograph. 2000.</p>
<p data-cms-editable>Application Sheet Push Processing Increasing Apparent Film Speed by Over- Development. Ilford Photo, 05/2002.</p>
<p data-cms-editable>Technical Data Sheet C-SW16-E16: Agfa Black-and-White Chemicals Film Processing. Agfa, 03/2003.</p>
<p data-cms-editable>ADAMS, Ansel. The Camera. New York: Little, Brown and Company, 2005.</p>
<p data-cms-editable>ADAMS, Ansel. The Negative. New York: Little, Brown and Company, 2005.</p>
<p data-cms-editable>DAS, Indra J et al. TG-69: Radiographic Film for Megavoltage Beam Dosimetry. American Association of Physicists in Medicine, 2007.</p>
<p data-cms-editable>NASSE, H. H. Depth of Field and Bokeh. Germany: Carl Zeiss Camera Lens Division, 2010.</p>
<p data-cms-editable>REINHOLD, G et al. The Caffenol Cookbook &amp; Bible. Community Spirit Publications, 2012.</p>
<p data-cms-editable>LEITE, Ricardo. Black and White Reversal Film Developing Process With Caffenol-C + Citric Hydrogen Peroxide Bleach. Portugal: 2013.</p>
<p data-cms-editable>SMETS, René. Making Your Bellows. Belgium: 2014.</p>
<p data-cms-editable>Development Time-Temperature Charts. DLG Eletronics, 2018.</p>
<p data-cms-editable>SWIERZBIN, John. DIY camera lens tutorial. Disponível em: <a href="https://www.flickr.com/groups/28242726@N00/discuss/72157622612617105/" target="_blank" rel="noopener noreferrer">https://www.flickr.com/groups/28242726@N00/discuss/72157622612617105/.</a> Acessado em: 03/2018.</p>
<p data-cms-editable>KUMPF, Bill. Guilloutine Shutters. Disponível em: <a href="https://www.largeformatphotography.info/guillotine-shutter/guillotine-shutter-construction.html" target="_blank" rel="noopener noreferrer">https://www.largeformatphotography.info/guillotine-shutter/guillotine-shutter-construction.html.</a> Acessado em: 03/2018.</p>
<p data-cms-editable>Shutters. Disponível em: <a href="http://www.earlyphotography.co.uk/site/shuttern.html" target="_blank" rel="noopener noreferrer">http://www.earlyphotography.co.uk/site/shuttern.html.</a> Acessado em: 03/2018.</p>
<p data-cms-editable>KOMMODORE. How to Make a 12 Leaves Mechanical Irirs. Disponível em: <a href="https://www.instructables.com/How-to-make-a-12-leaves-Mechanical-Irirs/" target="_blank" rel="noopener noreferrer">https:// www.instructables.com/How-to-make-a-12-leaves-Mechanical-Irirs/.</a> Acessado em: 03/2018.</p>
<p data-cms-editable>LUNGOV, Wagner. Como descobrir a distância focal de uma lente. Disponível em: <a href="https://apenasimagens.com/pt/medir-distancia-focal/" target="_blank" rel="noopener noreferrer">https://apenasimagens.com/pt/medir-distancia-focal/.</a> Acessado em: 09/2018.</p>
<p data-cms-editable>South Bristol Views. Calculating exposure increase with bellows extension. Disponível em: <a href="http://www.southbristolviews.com/pics/Graphic/CloseUpCalc.html" target="_blank" rel="noopener noreferrer">http://www.southbristolviews.com/pics/Graphic/CloseUpCalc.html.</a> Acessado em: 05/2019.</p>
<p data-cms-editable>Technical Information Reversal Processing Using Ilford Black &amp; White Films to Make Monochrome Transparencies. Ilford Photo, 2019.</p>
<p data-cms-editable>Technical Information Ortho Plus Black and White Orthocromatic Film. Ilford Photo, 2019.</p>
<p data-cms-editable>JOHNSON, Tim. Chemistry and the Black and White Photographic Process: The Making of a Negative. Disponível em: <a href="https://www.chem.uwec.edu/Chem115_F00/johnstim/Chemandphoto.htm" target="_blank" rel="noopener noreferrer">https://www.chem.uwec.edu/Chem115_F00/johnstim/Chemandphoto.htm.</a> Acessado em: 30/01/2021.</p>
<p data-cms-editable>KOSSOY, Boris. Hercule Florence : A Descoberta Isolada Da Fotografia No Brasil. São Paulo: EDUSP, 1ª edição (10 fevereiro 2021) .</p>
<p data-cms-editable>Google Patents: Pesquisa dos termos Shutter, Camera e Diaphragm. Disponível em:</p>
<p data-cms-editable><a href="https://patents.google.com/?q=((shutter)+(Camera)+(diaphragm))&before=priority:19301231&num=100&sort=new" target="_blank" rel="noopener noreferrer">https://patents.google.com/?q=((shutter)+(Camera)+(diaphragm))&amp;before=priority:19301231&amp;num=100&amp;sort=new</a>.</p>
<p data-cms-editable>Acessado em: 05/2021.</p>
<p data-cms-editable>Flutter: Google’s UI toolkit. Disponível em:</p>
<p data-cms-editable><a href="https://flutter.dev/" target="_blank" rel="noopener noreferrer">https://flutter.dev/</a>.</p>
<p data-cms-editable>Acessado em: 06/2021.</p>
<p data-cms-editable>MACKAY, R. M. Planck Radiation Laws: Excel, Mac or PC. Disponível em: <a href="https://serc.carleton.edu/sp/compadre/mathstatmodels/examples/PlanckXL.html" target="_blank" rel="noopener noreferrer">https://serc.carleton.edu/sp/compadre/mathstatmodels/examples/PlanckXL.html.</a> Acessado em: 08/2021.</p>
<p data-cms-editable>LUNGOV, Wagner. Parodinal, revelador caseiro em mono-doses. Disponível em: <a href="https://apenasimagens.com/pt/parodinal-em-mono-doses/" target="_blank" rel="noopener noreferrer">https://apenasimagens.com/pt/parodinal-em-mono-doses/.</a> Acessado em: 01/2022.</p>
<p data-cms-editable>BRODIE-TYRREL, William. Parodinal. Disponível em: <a href="https://www.brodie-tyrrell.org/wiki/index.php?Parodinal" target="_blank" rel="noopener noreferrer">https://www.brodie-tyrrell.org/wiki/index.php?Parodinal.</a> Acessado em: 01/2022.</p>
<p data-cms-editable>Google Patents: Pesquisa dos termos Fresnel Rear View. Disponível em: <a href="https://patents.google.com/patent/US2510344?oq=fresnel+rear+view" target="_blank" rel="noopener noreferrer">https://patents.google.com/patent/US2510344?oq=fresnel+rear+view.</a> Acessado em: 03/2022.</p>
<p data-cms-editable>WILLCOX, Frederick P. Film holder Disponível em: <a href="https://patents.google.com/patent/US2431356" target="_blank" rel="noopener noreferrer">https://patents.google.com/patent/US2431356.</a> Acessado em: 11/2022.</p>
<p data-cms-editable>CRAIG, C. C. Craig Film Holder Disponível em: <a href="https://patents.google.com/patent/US2946271" target="_blank" rel="noopener noreferrer">https://patents.google.com/patent/US2946271.</a> Acessado em: 11/2022.</p>
</div>
</div>
</section>
BIBLIOGRAPHY_HTML;
    $document=static fn(string $title,string $description,string $html):string=>json_encode([
      'version'=>2,'theme'=>'auto','meta'=>['title'=>$title,'description'=>$description],'html'=>$html
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

    $insertPage=$db->prepare("INSERT INTO cms_pages
      (page_uuid,activity_id,locale,slug,title,nav_title,status,is_home,show_in_nav,sort_order,
       draft_document_json,published_document_json,draft_revision,published_revision,
       draft_updated_at,published_at,created_at,updated_at,access_level)
       VALUES(?,?,?,?,?,?,'active',0,0,?,?,?,1,1,?,?,?,?,?)");
    $findPage=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND locale='pt-BR' AND slug=? AND status!='archived' LIMIT 1");
    $newPage=static function(int $activityId,string $slug,string $title,string $desc,string $html,string $access) use($db,$findPage,$insertPage,$document,$now):?array {
        $findPage->execute([$activityId,$slug]);$existing=$findPage->fetch(PDO::FETCH_ASSOC);
        if($existing)return $existing; // Never rewrite someone else's CMS document.
        $ord=$db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM cms_pages WHERE activity_id=? AND locale='pt-BR'");
        $ord->execute([$activityId]);
        $json=$document($title,$desc,$html);
        $insertPage->execute([bin2hex(random_bytes(16)),$activityId,'pt-BR',$slug,$title,$title,(int)$ord->fetchColumn(),$json,$json,$now,$now,$now,$now,$access]);
        $findPage->execute([$activityId,$slug]);return $findPage->fetch(PDO::FETCH_ASSOC)?:null;
    };

    // Shared document: one copy in the root site's CMS, authenticated audience,
    // intentionally NOT mapped to any course or lesson.
    $root=(int)($db->query("SELECT id FROM activities WHERE is_root=1 ORDER BY id LIMIT 1")->fetchColumn()?:0);
    if($root>0)$newPage($root,'bibliografia','Bibliografia',
       'Bibliografia da pesquisa de João Saidler em fotografia experimental e alternativa, publicada em janeiro de 2026.',
       $bibliographyHtml,'authenticated');

    // Identify the real course by established material relation or its canonical
    // distinctive title. Never infer cohorts from a fixed ID or release timestamps.
    $courses=$db->query("SELECT DISTINCT c.id,c.activity_id FROM courses c
      WHERE c.status='active'
      AND (
        (LOWER(c.title) LIKE '%positivo%' AND LOWER(c.title) LIKE '%raio%')
        OR EXISTS(SELECT 1 FROM course_material_pages mp JOIN cms_pages p ON p.id=mp.page_id
                  WHERE mp.course_id=c.id AND p.slug='caderno-positivo-direto' AND p.status!='archived')
      ) ORDER BY c.id")->fetchAll(PDO::FETCH_ASSOC);
    $findLesson=$db->prepare("SELECT id FROM course_lessons WHERE course_id=? ORDER BY sort_order,id LIMIT 1");
    $associate=$db->prepare("INSERT OR IGNORE INTO course_material_pages(course_id,page_id,sort_order,created_at,updated_at) VALUES(?,?,?,?,?)");
    $mapSection=$db->prepare("INSERT OR IGNORE INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?,?)");
    $ensureRelease=$db->prepare("INSERT OR IGNORE INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at)
      SELECT id,?,NULL,?,? FROM course_cohorts WHERE course_id=? AND status!='archived'");
    $keys=['materiais-introducao','materiais-laboratorio','materiais-quimicos','materiais-opcionais'];
    foreach($courses as $course){
        $courseId=(int)$course['id'];$activityId=(int)$course['activity_id'];
        $findLesson->execute([$courseId]);$firstLesson=(int)($findLesson->fetchColumn()?:0);
        if($firstLesson<1)continue; // No invented lesson and no accidentally public material.
        $page=$newPage($activityId,'materiais-positivo-direto','Materiais — Positivo Direto em Filme de Raio-X',
           'Lista de equipamentos, utensílios, químicos e referências de compra para o laboratório do curso.',
           $materialsHtml,'activity');
        if(!$page)continue;
        // A pre-existing, unrelated page with the same slug is never hijacked.
        if(!str_contains((string)($page['published_document_json']??''),'data-cms-section=\\"materiais-introducao\\"'))continue;
        $pageId=(int)$page['id'];
        $ord=$db->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM course_material_pages WHERE course_id=?');
        $ord->execute([$courseId]);$associate->execute([$courseId,$pageId,(int)$ord->fetchColumn(),$now,$now]);
        foreach($keys as $key)$mapSection->execute([$courseId,$pageId,$key,$firstLesson,$now,$now]);
        $ensureRelease->execute([$firstLesson,$now,$now,$courseId]);
    }
};
