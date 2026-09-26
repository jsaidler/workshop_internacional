# CMS profissional

Estado atual da área administrativa do workshop. O estado operacional canônico do projeto está em `docs/PROJECT_STATE.md` e deve ser lido antes de qualquer alteração.

## Administração editorial

- `/admin/` é o dashboard administrativo; não redireciona diretamente para o editor;
- o dashboard concentra estado de publicação, alterações pendentes, novas inscrições, páginas, formulários, mídia, armazenamento/saúde, atividade recente e atalhos;
- editar a home é uma ação do painel, não o destino automático da administração;
- páginas independentes PT/EN com rascunho, publicação e histórico restaurável;
- editor visual com biblioteca de componentes, grades livres e controles de layout;
- blocos reutilizáveis salvos a partir de seções do próprio editor;
- gerenciamento separado de navegação, header, footer e identidade;
- sistema visual global com cores, tipografia, medidas, espaçamento e botões;
- prévia ao vivo do design em desktop, tablet e celular;
- CSS adicional como recurso avançado, sem ser necessário para a operação normal;
- CSS e JavaScript locais da administração usam a versão instalada no URL, evitando que uma atualização correta continue escondida por cache antigo.

### Inicialização de cursos e pureza das leituras

Uma atividade vazia é um estado válido do CMS. Consultar conteúdo não equivale a inicializá-lo.

- listagens e lookups de páginas não executam `cms_pages_seed()`;
- listagens e lookups de formulários não executam `cms_forms_seed()`;
- abrir `Admin → Páginas` ou `Admin → Formulários` não cria conteúdo;
- o sitemap apenas descobre páginas já existentes e publicadas; ele nunca inicializa uma atividade;
- `cms_pages_seed()` e `cms_forms_seed()` permanecem transitórios como primitivas explícitas de instalação/template/migração, não como parte de uma leitura;
- a criação de curso/workshop exige uma escolha inicial explícita: `Em branco` ou `Positivo direto`;
- `Em branco` é o default neutro e não copia a atividade raiz nem recebe páginas/formulários do primeiro workshop;
- `Positivo direto` aplica deliberadamente os defaults específicos desse produto somente numa atividade vazia;
- reaplicar o setup de Positivo Direto depois que uma página ou formulário CMS já existe é uma operação nula: conteúdo editorial existente nunca é sobrescrito por setup;
- copiar um curso e aplicar um template são operações distintas e não podem ocorrer simultaneamente.

A regra arquitetural é: **consulta não cria conteúdo; template inicializa conteúdo somente por ação explícita**.

### Identidade localizada e estrutura editorial das páginas

A instalação é globalmente `JSaidler Fotografia`; curso/workshop é uma `activity`. O nome público de uma activity não pode depender de um único valor compartilhado por todos os idiomas.

- `activity_locales(activity_id, locale, public_title, ...)` é a autoridade nova para o nome público localizado do curso;
- `activities.public_title` permanece apenas como fallback/compatibilidade durante a migração e não é atualizado pelas edições localizadas feitas no admin;
- `Admin → Cursos e workshops` é a interface canônica para nome administrativo e nomes públicos PT-BR/EN; não existe uma segunda tela de identidade por curso;
- a criação de uma activity exige o locale do primeiro nome público e continua exigindo template explícito;
- `cms_pages.parent_page_id` é a autoridade da hierarquia editorial das páginas;
- hierarquia editorial não altera `id`, `page_uuid`, slug, URL, documento, revisão nem publicação;
- a árvore editorial pertence a `Páginas`. `Navegação` continua sendo somente a apresentação curada: páginas incluídas, rótulo, ordem e links externos;
- `cms_pages.translation_group_uuid` identifica páginas equivalentes em idiomas diferentes, inclusive quando os slugs são diferentes;
- a troca pública de idioma e o sitemap/hreflang consultam primeiro essa identidade explícita;
- páginas antigas ainda sem grupo mantêm temporariamente o fallback legado por home/slug, apenas como compatibilidade de leitura;
- `Admin → Páginas` é a interface canônica para definir página superior e equivalente em outro idioma;
- relações pai/filha só são válidas na mesma activity e locale, e ciclos são rejeitados;
- nenhum parent ou grupo de tradução é inferido automaticamente na migração; páginas existentes permanecem estruturalmente intactas até uma decisão editorial explícita.

A regra arquitetural é: **identidade localizada pertence à activity; hierarquia e equivalência pertencem às páginas; menu não é uma segunda árvore**.

## Mídia

- imagens e vídeos em biblioteca própria;
- metadata, texto alternativo, legenda, descrição e tags;
- ponto focal visual para imagens;
- versões substituíveis e restauráveis sem quebrar referências estruturadas;
- derivados responsivos para imagens;
- poster de vídeo a partir de frame escolhido;
- rastreamento de uso nas páginas;
- seleção e ações em lote;
- arquivamento protegido quando o asset ainda está em uso;
- o vídeo do processo/revelação é parte da hierarquia editorial pública e deve preservar a mídia gerenciada já associada sempre que possível.

### Derivadas de imagem e cache

O original de cada versão de imagem é a fonte de verdade imutável. Derivadas responsivas são artefatos reconstruíveis e não podem depender da substituição de bytes sob um URL já publicado.

- uma regeneração publica a nova família de derivadas em um diretório final novo `responsive-<token>`;
- somente depois de todas as derivadas serem gravadas e verificadas o banco troca as referências para os URLs novos;
- diretórios antigos são eliminados depois do commit, nunca antes;
- imagens que não precisam de derivadas responsivas usam o original;
- se um reparo não conseguir reconstruir uma versão, suas referências de derivadas são invalidadas e o renderer recua para o original em vez de continuar apontando para um arquivo potencialmente defeituoso;
- em PNG transparente, a validação precisa provar duas coisas distintas: que a transparência necessária foi preservada e que conteúdo originalmente visível continua tendo pixels com opacidade maior que zero;
- uma derivada totalmente transparente é inválida mesmo que tecnicamente possua canal alpha e dimensões corretas;
- a regeneração de PNG transparente tenta um caminho conservador do ImageMagick sem reativar o canal alpha quando o writer genérico elimina o conteúdo visível. Se a segunda tentativa também ficar vazia, a nova família não é publicada;
- `migrations/025_republish_png_derivatives_with_new_urls.php` reaplica o modelo de URL imutável aos PNGs legados;
- `migrations/026_repair_transparent_png_regeneration.php` reconstrói novamente PNGs existentes com a verificação de conteúdo visível e, em caso de falha, remove as referências de derivadas para que o original preservado seja usado.

### Controles de imagem no editor de páginas

Imagem usa um inspector próprio com texto alternativo, ajuste (`cover`/`contain`), ponto focal e biblioteca de imagens. Substituir uma imagem abre somente a coleção de imagens.

Ajuste e ponto focal não são escritos como `object-fit`/`object-position` inline. O renderer transporta esses valores em custom properties e a camada CSS do sistema os consome. Assim a configuração visual do CMS funciona normalmente, mas `Design → CSS adicional` continua podendo sobrescrever essas propriedades quando o usuário deliberadamente fizer isso.

### Controles de vídeo no editor de páginas

Vídeo não reutiliza o inspector nem o seletor de imagem.

- qualquer elemento `<video>` da página é editável, inclusive vídeos antigos e o vídeo do processo, sem depender de um atributo editorial específico;
- o próprio `<video>` é a superfície de seleção: o editor intercepta `pointerdown` e `click` em captura, executa `preventDefault()` e `stopImmediatePropagation()` e abre o inspector antes que os controles nativos ou a seção processem a interação;
- não existe botão/overlay `Editar vídeo` inserido por cima do player;
- o inspector de vídeo controla origem, biblioteca, upload, URL externa e reprodução;
- capa/poster aparece como propriedade separada do vídeo;
- o mesmo controle atende todas as páginas, idiomas e componentes;
- o controlador dedicado de vídeo é carregado antes dos hooks legados de `cms-pro-editor.js`;
- CSS e JavaScript do editor recebem `?v=<versão instalada>` na entrada autenticada `editor/index.php`.

## Design e autoridade do CSS adicional

`Design → CSS adicional` é a última camada editorial de estilo do site; essa autoridade não depende apenas de o `<style>` aparecer depois de outro elemento no `<head>`.

- estilos visuais do template/CMS, responsividade, controles de layout e tokens gerados pelo painel são carregados na camada CSS inferior `cms-system`;
- `#cms-custom-css` permanece sem camada e é o último estilo autoral, de modo que declarações normais do CSS adicional prevalecem sobre declarações normais do sistema independentemente da especificidade do seletor;
- o CSS adicional é propriedade do site/atividade inteira. Não é salvo por página e não possui versões independentes para PT e EN;
- o payload canônico de `advanced.customCss` é persistido em `cms_design_settings` no escopo reservado `locale='__site__'`. Todas as páginas e todos os idiomas da mesma atividade leem esse valor;
- salvar o CSS adicional a partir da tela PT ou EN atualiza o mesmo payload compartilhado. Os demais tokens de Design podem continuar específicos por locale;
- `migrations/027_site_wide_additional_css.php` promove um CSS adicional legado existente para esse escopo compartilhado sem apagar uma personalização não vazia;
- a prévia ao vivo usa a mesma regra: tokens gerados entram em `cms-system` e o CSS adicional é reaplicado por último;
- a prévia não pode escrever tokens com `style.setProperty()` no `<html>`, porque estilos inline venceriam stylesheet normal; resíduos inline deixados por versões antigas são removidos ao aplicar a prévia;
- regras visuais do CMS não usam `!important` quando a propriedade deve permanecer editável; `!important` fica reservado a invariantes funcionais deliberados;
- a precedência é validada por testes reais em Chromium que conferem `getComputedStyle()` tanto na prévia de Design quanto numa superfície pública com seletor do sistema mais específico;
- o escopo é testado entre PT e EN do mesmo site e também contra outra atividade, para garantir simultaneamente compartilhamento interno e ausência de vazamento entre sites.

## Formulários

- formulários próprios armazenados no site;
- editor completo de campos com drag-and-drop, duplicação, largura, obrigatoriedade, ajuda e opções;
- prévia no painel;
- rascunho/publicação;
- respostas associadas ao formulário/página, status de acompanhamento, notas e CSV;
- checkbox e radio têm geometria global de 18 × 18 px em site público, preview e administração; nenhum stylesheet administrativo pode tratá-los como campos de texto;
- a administração reforça largura, altura, mínimos e máximos de checkbox/radio no shell, além do CSS compartilhado, para não depender de cache antigo;
- o editor completo de formulários prioriza a tarefa de edição: configurações gerais ficam acima, lista de campos e propriedades recebem a área principal e a prévia fica em bloco separado, em vez de quatro áreas comprimidas lado a lado;
- no editor visual da página, o formulário não é tratado como uma entidade textual única nem exige escolher um campo em um seletor lateral;
- rótulos de campo, rótulos de opções, textos de ajuda e texto do botão são alvos editoriais independentes na própria prévia: o usuário clica exatamente no texto exibido e edita `contenteditable` no lugar, como nos demais textos da página;
- inputs, selects, textareas, checkboxes e radios também são subalvos selecionáveis como contexto do campo;
- todos os subalvos são preparados quando o documento do iframe é instalado, e o controlador também tenta instalar-se imediatamente quando o documento já existe;
- o resolvedor de clique considera a estrutura real do formulário, inclusive o `label` inteiro de radio/checkbox, `legend`, `.cms-field`, `.cms-consent`, controles e botão; não depende de o navegador devolver exatamente o `span` interno como `event.target`;
- um formulário expandido (`data-cms-form-block`) pertence exclusivamente a `editor/cms-form-editor.js`; o editor central de páginas não instala handlers próprios de texto, imagem, seção ou “formulário inteiro” dentro dele;
- o editor central só trata como entidade de formulário o placeholder ainda não expandido (`[data-cms-form-key]:not([data-cms-form-block])`), necessário para escolher qual formulário inserir na página;
- o editor completo permanece acessível apenas como ação secundária explícita para mudanças estruturais; clicar em conteúdo visível do formulário nunca deve encaminhar automaticamente para `/admin/forms.php`;
- o wrapper do formulário não pode cancelar genericamente todo `pointerdown`/`click` de seus descendentes. Somente um subalvo reconhecido recebe interceptação;
- o cursor é posicionado no ponto clicado; `Enter` conclui a edição e `Esc` cancela a alteração em curso;
- alterações visuais são gravadas como rascunho do formulário pela API canônica `cms-form-save.php`; a publicação continua explícita por `cms-form-publish.php`;
- propriedades que não aparecem na página continuam contextuais: ao clicar uma opção, por exemplo, o inspector pode mostrar o valor interno daquela opção, sem duplicar o rótulo visível em um segundo campo de texto;
- o inspector não deve oferecer um dropdown para navegar pelos campos do formulário nem reproduzir os textos visíveis em caixas de edição paralelas;
- alterações estruturais — tipo, identificador, ordem, criação/remoção, obrigatoriedade, lógica condicional de campos e fluxo após envio — permanecem no editor completo, acessível por ação secundária explícita;
- alterar o valor interno de uma opção exibe aviso porque regras condicionais podem depender desse valor;
- a edição visual usa o mesmo schema e as mesmas APIs do editor completo; não existe uma segunda estrutura ou banco de formulário.

### Conteúdo editorial dentro do formulário

O formulário pode intercalar campos de resposta com blocos editoriais persistidos no próprio schema em `settings.contentBlocks`. Eles existem para conteúdo visível que participa do fluxo do formulário sem ser uma resposta: cabeçalhos, explicações, programação, termos, pagamento, imagens e conteúdo condicional.

- cada bloco possui `id`, rótulo interno, HTML sanitizado e uma posição `afterField`;
- uma condição opcional define a visibilidade no site público a partir de outro campo do formulário;
- cada bloco editorial também pode ser explicitamente exibido ou ocultado no site público pelo inspector;
- o editor visual mostra todos os blocos condicionais e também os blocos explicitamente ocultos, mesmo quando não apareceriam para o visitante; blocos ocultos ficam identificados no preview para continuarem administráveis;
- textos marcados como editáveis são alterados diretamente no ponto em que aparecem e são serializados de volta ao mesmo schema;
- imagens marcadas como mídia são trocadas pela biblioteca; o QR Code do Pix é tratado exatamente dessa forma;
- links expõem o `href` como propriedade contextual, permitindo alterar um link de pagamento sem código;
- condição, estado de exibição e posição do bloco são propriedades contextuais do próprio bloco;
- a página não mantém uma segunda cópia desses conteúdos fora do formulário, e o JavaScript público não reposiciona programação, pagamento ou termos;
- `editor/cms-form-editor.js` é o controlador único da edição visual do formulário. O controlador antigo em camadas não é carregado em paralelo.

### Página de inscrição: preservação e reparo

A página de inscrição já tinha uma apresentação pública aprovada antes de a programação, o pagamento e os termos serem transferidos para o schema do CMS. Tornar esse conteúdo administrável não autoriza alterar essa apresentação.

- a arquitetura nova preserva a posição e a apresentação pública de programação, condições e formas de pagamento;
- a estrutura visual de pagamento mantém `registration-payment-source` e `registration-payment-panel`, usadas pela página anterior;
- o QR Code, a chave Pix, o código copia-e-cola, o texto e o link do cartão passam a ser conteúdo administrável pelo CMS, sem exigir deploy para alterações ordinárias;
- os valores existentes no código servem apenas como seed/default de instalação ou fonte de reparo de migração; depois da instalação, o banco é a fonte de verdade editorial;
- `migrations/023_repair_registration_editorial_blocks.php` corrige instalações em que a migração 022 encontrou um conjunto parcialmente preenchido de `contentBlocks`: repõe apenas blocos ausentes por `id`, preserva conteúdo já editado e restaura a estrutura visual de pagamento quando necessário;
- o reparo não substitui o schema inteiro por defaults e não deve apagar alterações do usuário.

### Regressão de interação

O CI inclui teste real em Chromium/Playwright da superfície de edição visual. A validação cobre edição direta de rótulo e opção, acesso aos estados condicionais de pagamento, alteração de conteúdo, substituição do QR pela biblioteca, controle explícito de exibição pública, salvamento e persistência.

Há também uma regressão integrada que carrega `cms-form-editor.js` e `cms-editor-v3.js` ao mesmo tempo. Ela existe porque o defeito real era uma disputa de propriedade entre o editor central e o editor do formulário e não podia ser detectado de forma confiável por um fixture que carregasse apenas o controlador de formulário.

## Princípio de correção sistêmica

Quando um defeito aparece em várias páginas, idiomas ou instâncias de um componente, a correção deve ser feita na camada compartilhada correspondente. Não usar exceções pontuais por página, ID, locale ou formulário para mascarar um problema de base. A regressão também deve ser testada no mesmo nível global da correção.

## Independência editorial PT/EN

Português e inglês são documentos editoriais independentes.

- PT-BR: página orientada à inscrição de uma nova turma em português, com prova da primeira turma e suporte incluído.
- EN: página orientada à formação de interesse para a primeira turma em inglês, com maior peso para processo e pesquisa e sem reproduzir automaticamente a estrutura comercial brasileira.

O processo/revelação deve ter precedência editorial sobre a NINA. A câmera é apresentada como ferramenta desenvolvida dentro da pesquisa, não como assunto central do workshop.

## Princípio de operação

Alterações editoriais, visuais, estruturais e comerciais normais devem ser possíveis pela área administrativa. Código deve ser necessário apenas para criar novas capacidades do CMS.

## Atualizações

A branch de produção é `wip/form-response-refinement-2026-07-16`. O workflow valida PHP/JS, testes do CMS, interação crítica em navegador e o build, e publica o pacote aprovado em `production-dist`.

O fluxo operacional normal depois do bootstrap é:

1. integrar a alteração na branch de produção;
2. confirmar que `production-dist` foi gerada corretamente;
3. o usuário abre `Admin → Sistema e atualizações`;
4. o painel identifica a versão disponível;
5. o usuário executa `Instalar atualização`;
6. o updater preserva banco, uploads, configuração local e logs e cria backup antes da substituição;
7. a próxima requisição executa migrações pendentes.

A detecção de versão remota não pode depender de cache do `raw.githubusercontent.com`. O painel usa um token novo no URL de `deploy-info.json` e cabeçalhos `no-cache` a cada consulta. Durante uma instalação, o `sourceSha` do manifesto identifica os downloads daquela release e `deploy-info.json` precisa confirmar o mesmo SHA antes da troca final. Se o canal mudar durante o processo, a atualização é abortada em vez de misturar versões.

`production-dist` atualizada não significa hospedagem atualizada. Só considerar a instalação remota atual depois que a atualização for aplicada pelo painel e a versão instalada for verificada.