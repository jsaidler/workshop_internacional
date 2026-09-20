# PROJECT STATE — Direct Positive Workshop CMS

Este é o documento canônico de estado operacional do projeto. Leia antes de alterar código, conteúdo, deploy ou fluxo administrativo. Atualize este arquivo sempre que uma decisão estrutural, operacional ou editorial mudar.

## Estado em 20/09/2026

- Branch de produção: `wip/form-response-refinement-2026-07-16`.
- O CI gera e valida o pacote de produção e publica o resultado na branch `production-dist`.
- A hospedagem NÃO é atualizada automaticamente pelo simples fato de `production-dist` ter sido publicada.
- O fluxo normal de atualização da aplicação hospedada é feito pelo próprio usuário em `Admin → Sistema e atualizações → Instalar atualização`.
- FTP/manual deploy é apenas bootstrap inicial ou contingência. Não é o fluxo operacional normal depois que o self-updater está instalado.
- O updater preserva `storage/database.sqlite`, `uploads/`, `config/local.php`, `config/install.php`, `storage/logs/` e `storage/updates/`, cria backup antes da troca e verifica checksums SHA-256.
- Migrações pendentes são executadas pelo bootstrap na primeira requisição após a atualização.

## Regra operacional obrigatória

Para uma alteração de código:

1. ler este documento e os documentos relacionados antes de modificar o projeto;
2. trabalhar em branch/PR quando a mudança for estrutural ou de comportamento;
3. manter CI verde;
4. integrar na branch de produção;
5. confirmar que `production-dist` foi gerada com o commit correto;
6. informar ao usuário que a nova versão está disponível no painel;
7. o usuário aplica a atualização em `Admin → Sistema e atualizações`;
8. só considerar a hospedagem atualizada depois dessa aplicação e da verificação da versão instalada.

Nunca confundir `production-dist` atualizada com hospedagem atualizada.

### Detecção do canal de produção

A tela `Sistema e atualizações` não pode depender de uma resposta possivelmente antiga do cache de `raw.githubusercontent.com` para decidir se existe versão nova.

- Toda consulta ao canal `production-dist` usa URL com token de cache (`?v=...`) e cabeçalhos `Cache-Control: no-cache` / `Pragma: no-cache`.
- A consulta de status usa token novo a cada verificação, portanto uma publicação recém-gerada deve ficar visível sem aguardar expiração do cache intermediário.
- Durante a instalação, o manifesto é lido uma única vez e o `sourceSha` desse manifesto passa a ser o token dos arquivos da mesma atualização.
- Antes da substituição final, `deploy-info.json` deve declarar o mesmo `sourceSha` do manifesto. Se o canal mudar durante o download, a instalação é abortada com `update_channel_changed` em vez de misturar versões.
- `tools/test-update-service.php` e `tools/test-update-restore.php` fazem parte da validação de PR e do deploy de produção.

## Regra de escopo das correções

Defeitos que aparecem em várias páginas, idiomas ou instâncias de um mesmo componente são defeitos sistêmicos e devem ser corrigidos na camada compartilhada responsável pelo comportamento.

- Não corrigir um problema sistêmico com CSS, HTML, JavaScript ou conteúdo específico de uma página, locale, formulário ou bloco individual.
- Preferir regras globais de componente ou da aplicação que cubram todas as páginas que usam aquele elemento.
- Adicionar teste/regressão no mesmo nível de escopo da correção para impedir que o problema reapareça em outra página.
- Uma exceção só é aceitável quando o comportamento diferente daquela página for deliberado e documentado como tal.

Checkbox e radio são um único componente nativo em três contextos: site público, preview/editor e administração. A geometria deve permanecer 18 × 18 px em todos eles. Regras genéricas de `input` não podem lhes impor largura total, altura de campo de texto, padding ou flex expansivo. A área administrativa aplica essa regra globalmente em `body.admin-page`, com limites mínimos e máximos e uma garantia estrutural no shell, não apenas no editor de formulários.

A captura de 13/09 mostrou um detalhe importante: o círculo/quadrado nativo podia parecer pequeno, mas o elemento `input` continuava ocupando a largura inteira da linha por causa do CSS legado de campos de texto. O sintoma visual era o marcador centralizado e o texto empurrado para a direita. Portanto o critério de correção não é apenas o diâmetro visível; a caixa do próprio `input` precisa estar efetivamente limitada a 18 × 18 px.

Como uma atualização de CSS pode ficar mascarada por cache antigo do navegador, os renderers público, editor e admin versionam seus assets com a versão instalada (`deploy-info.json`). Invariantes visuais críticos podem ter uma regra estrutural inline no shell correspondente, sempre em escopo global da aplicação e nunca por página específica.

### Identidade de cache das derivadas de imagem

O original enviado é imutável e as derivadas responsivas são reconstruíveis. Corrigir uma derivada não pode significar sobrescrever bytes diferentes sob o mesmo URL, porque navegador ou CDN podem continuar entregando a resposta antiga mesmo depois de o arquivo no servidor ter sido substituído.

- Toda regeneração bem-sucedida publica as derivadas em um diretório final novo `responsive-<token>`.
- O banco só passa a referenciar esse diretório depois que todas as novas derivadas foram gravadas e verificadas.
- Diretórios antigos são removidos apenas depois do commit da troca de referências.
- Imagens pequenas que não exigem derivadas voltam ao original imutável.
- Quando uma reconstrução de reparo falha, as referências de derivadas daquela versão são invalidadas para que o renderer use o original em vez de manter um URL possivelmente defeituoso.
- Para PNGs transparentes, preservar a existência do canal alpha não é validação suficiente: se o original possui pixels visíveis, cada derivada precisa continuar contendo pixels com opacidade maior que zero. Uma derivada totalmente transparente é considerada corrompida e nunca pode substituir as referências válidas no banco.
- Se o writer genérico do ImageMagick produzir uma derivada totalmente transparente, a regeneração tenta novamente sem reativar nem reescrever o canal alpha. Se o conteúdo visível continuar perdido, a operação falha antes da troca de referências.
- A migração `025_republish_png_derivatives_with_new_urls.php` repassa PNGs existentes pelo fluxo de URLs imutáveis.
- A migração `026_repair_transparent_png_regeneration.php` repassa novamente PNGs existentes pelo regenerador com verificação de conteúdo visível; se um arquivo não puder ser reconstruído com segurança, suas derivadas são invalidadas e a entrega volta ao original preservado.

### Autoridade do CSS adicional

`Design → CSS adicional` é a camada editorial final de estilo. Essa precedência é semântica, não apenas ordem física de tags no `<head>`.

- CSS visual do template, do CMS, responsividade e tokens gerados pelo painel pertencem à camada CSS inferior `cms-system`.
- O CSS adicional permanece sem camada e é emitido por último; portanto uma declaração normal do usuário vence uma declaração normal do sistema mesmo quando o seletor do sistema é mais específico.
- O CSS adicional pertence ao site/atividade inteira. Não é configuração de uma página e não é configuração separada por idioma: qualquer página e qualquer locale do mesmo site lê o mesmo payload canônico.
- Tokens de design como cores, tipografia e layout podem continuar específicos por locale, mas `advanced.customCss` é persistido em um escopo compartilhado do site (`cms_design_settings.locale = '__site__'`). Salvar o CSS adicional a partir de PT ou EN atualiza esse mesmo valor global.
- A migração `027_site_wide_additional_css.php` promove para o escopo compartilhado um CSS adicional legado já existente, priorizando conteúdo não vazio para não apagar uma personalização válida durante a atualização.
- A prévia ao vivo não escreve tokens de Design com `root.style.setProperty()`, porque estilo inline ultrapassa um stylesheet normal. Tokens ao vivo são emitidos em stylesheet dentro de `cms-system` e resíduos inline de versões antigas são removidos.
- Regras visuais controláveis pelo usuário não usam `!important`. O uso de `!important` fica restrito a invariantes funcionais deliberados, como o honeypot.
- Ajuste e ponto focal de mídia gerenciada viajam como custom properties no elemento e são consumidos por CSS em `cms-system`; `object-fit` e `object-position` não são gravados como propriedades inline que bloqueiem o CSS adicional.
- A regressão dessa precedência é validada em Chromium com `getComputedStyle()`, tanto na prévia de Design quanto em uma página pública. O escopo global do CSS adicional é validado também entre PT/EN e entre atividades distintas para impedir vazamento entre sites.

## Regra de edição de mídia no editor de páginas

Imagem e vídeo são tipos de mídia diferentes e não devem compartilhar o mesmo controle editorial como se fossem equivalentes.

- Imagens continuam usando o inspector de imagem: texto alternativo, ajuste, ponto focal e biblioteca de imagens.
- Qualquer elemento `<video>` dentro da página editável deve ser selecionável como vídeo, independentemente de ser um componente novo, o vídeo do processo, um vídeo legado ou possuir atributos editoriais antigos.
- A seleção de vídeo deve acontecer sobre o próprio elemento `<video>`. O editor intercepta `pointerdown`/`click` em fase de captura, cancela a ação padrão dos controles nativos e interrompe a propagação antes que a seção ou o player processem a interação.
- Não inserir botão, overlay ou outro elemento editável sobre o vídeo para simular seleção.
- O inspector de vídeo é próprio e controla origem, biblioteca de vídeos, upload, URL externa e comportamento de reprodução.
- A capa/poster é propriedade separada do asset de vídeo; editar capa não equivale a trocar o vídeo.
- O controlador dedicado de vídeo deve ser carregado antes dos hooks legados do `cms-pro-editor.js`.

## Regra de integração dos formulários com o editor da página

O editor completo de formulários continua existindo para operações estruturais, mas a edição cotidiana do conteúdo visível do formulário deve acontecer diretamente na página.

- O formulário NÃO é tratado como uma única entidade textual dentro do WYSIWYG.
- Não deve existir dropdown lateral para escolher “qual campo editar” quando o texto já está visível na página.
- Rótulos de campo, rótulos de opções, textos de ajuda, controles de campo e texto do botão são subalvos independentes dentro do formulário.
- O usuário clica exatamente no texto exibido e esse texto entra em `contenteditable` no próprio lugar, seguindo a mesma lógica de edição direta dos demais textos da página.
- Inputs, selects, textareas, checkboxes e radios também são selecionáveis como contexto do campo; não podem virar áreas mortas só porque não são texto editável.
- O resolvedor não deve depender apenas do `span` interno recebido como `event.target`: ele precisa reconhecer também `label` de radio/checkbox, `legend`, `.cms-field`, `.cms-consent`, controles e botão.
- O editor central da página NÃO instala handlers de texto, imagem nem seleção de “formulário inteiro” dentro de `[data-cms-form-block]`. Formulários expandidos pertencem exclusivamente a `editor/cms-form-editor.js`.
- O editor central só pode tratar como entidade de formulário um placeholder ainda não expandido: `[data-cms-form-key]:not([data-cms-form-block])`.
- O handler de seção também deve ignorar cliques oriundos de um formulário expandido para não roubar a interação.
- Clicar em texto ou controle visível de um formulário nunca deve encaminhar automaticamente para `/admin/forms.php`. O editor completo é apenas uma ação secundária explícita para mudanças estruturais.
- É proibido cancelar genericamente todo clique ocorrido dentro de `[data-cms-form-block]`. Somente subalvos reconhecidos recebem interceptação; os demais eventos continuam para os mecanismos normais do editor.
- Os subalvos do formulário devem ser preparados quando o documento do iframe é instalado. O controlador precisa funcionar tanto no `load` do iframe quanto quando o documento já existir no momento em que o script é carregado.
- O cursor deve ser posicionado no ponto clicado. `Enter` conclui a edição; `Esc` cancela a alteração corrente.
- Os alvos editoriais são decorados apenas no preview do editor; atributos e wrappers auxiliares não fazem parte do HTML público persistido da página.
- As alterações são gravadas no rascunho do formulário pela API canônica `cms-form-save.php`; `cms-form-publish.php` continua sendo a publicação explícita do formulário.
- Propriedades não visíveis podem aparecer no inspector de forma contextual. Exemplo: ao clicar o rótulo de uma opção, o inspector pode expor apenas o valor interno daquela opção, porque esse valor não aparece na página.
- O inspector não deve duplicar o rótulo visível em outro campo de texto. O texto visível é editado exclusivamente no próprio preview.
- Tipo, identificador, ordem, criação/remoção de campos, obrigatoriedade, lógica condicional de campos e fluxo após envio permanecem no editor completo. O inspector mantém uma ação secundária explícita para esse editor.
- A edição visual e o editor completo usam o mesmo schema e as mesmas APIs; não existe uma segunda estrutura de formulário.

### Conteúdo editorial intercalado no formulário

A página de inscrição possui conteúdo que não é um campo de resposta, mas faz parte do fluxo visual do formulário: cabeçalhos, programação, instruções de pagamento, QR Code, condições de participação e textos dependentes de uma opção. Esse conteúdo não pode ficar hardcoded em PHP operacional nem ser montado/movido por JavaScript público.

- O conteúdo editorial do formulário é persistido no mesmo schema canônico, em `settings.contentBlocks`.
- Cada bloco possui identidade própria, HTML sanitizado e posição relativa a um campo (`afterField`).
- Um bloco pode ter regra de exibição pública (`condition`), com campo de origem, operador e valor.
- Cada bloco também precisa permitir controle editorial explícito de exibição pública. Um bloco oculto continua aparecendo no editor, identificado como oculto, mas não aparece para o visitante.
- No site público, a condição e o estado de exibição controlam visibilidade. No editor, todos os estados condicionais e ocultos permanecem visíveis para que possam ser selecionados e alterados sem simular respostas no formulário.
- Textos internos dos blocos marcados como editáveis são alterados diretamente no preview e serializados de volta ao schema do formulário.
- Imagens internas marcadas como mídia do CMS usam a mesma biblioteca de mídia. O QR Code do Pix é uma imagem editorial comum: clicar nele no editor permite substituí-lo sem alterar código.
- Links presentes nesses blocos expõem o destino como propriedade contextual; portanto a URL de pagamento por cartão também é editável sem deploy.
- A condição, o estado de exibição e a posição do bloco são propriedades editoriais contextuais. Alterar essas propriedades não exige abrir código nem esconder o bloco do próprio editor.
- O documento da página contém apenas a estrutura da página e o placeholder do formulário. Programação, pagamento e termos não são duplicados no HTML da página.
- O renderer pode substituir o formulário expandido pelo placeholder ao serializar a página sem perder programação, pagamentos ou termos, porque esses conteúdos pertencem ao schema do formulário.
- `editor/cms-form-editor.js` é o controlador único dessa superfície visual. O controlador anterior em camadas não deve ser carregado em paralelo.

### Preservação visual e reparo da inscrição

Tornar conteúdo editável não autoriza redesenhar nem reorganizar uma página que já estava correta. A apresentação da página de inscrição anterior à refatoração é referência visual e funcional a preservar; a mudança de arquitetura deve trocar a fonte de verdade, não a aparência aprovada.

- Programação, condições, Pix/cartão, QR Code e textos passam ao CMS mantendo a mesma posição e apresentação pública que possuíam antes.
- A estrutura visual de pagamento mantém o wrapper `registration-payment-source` usado pela página aprovada.
- A migração `023_repair_registration_editorial_blocks.php` existe para instalações que passaram pela migração 022 com `contentBlocks` parcialmente preenchidos. Ela repõe apenas blocos canônicos ausentes, preserva blocos e textos existentes e restaura o wrapper visual de pagamento quando necessário.
- O reparo nunca deve substituir todo o schema do formulário por defaults nem apagar edição feita pelo usuário.
- A operação editorial posterior ocorre no banco/CMS; os defaults em PHP servem apenas para seed/migração e não são fonte de verdade depois da instalação.

### Validação em navegador

Interações críticas do editor visual não podem ser consideradas validadas apenas por `str_contains`, lint ou teste PHP. O CI mantém teste real em Chromium/Playwright que deve provar, no mínimo: clique e edição de rótulo, edição de opção, acesso a todos os estados condicionais, edição de conteúdo condicional, troca do QR pela biblioteca, controle de exibição pública, salvamento e persistência depois de recarregar a página.

Além do teste isolado do controlador de formulário, existe regressão com `cms-form-editor.js` e `cms-editor-v3.js` carregados juntos. Esse teste é obrigatório porque a falha real ocorreu por disputa de propriedade entre o editor central e o editor do formulário e não aparecia no fixture isolado.

### Falhas já observadas e que não podem regredir

- Um `MutationObserver` do inspector que re-renderizava o próprio inspector criou loop de microtasks e congelou o editor; esse padrão é proibido.
- Um handler posterior tentou “proteger” o formulário cancelando todo `pointerdown`/`click` que ocorresse dentro do bloco quando o alvo textual não fosse reconhecido. O resultado foi exatamente o contrário: o formulário inteiro virou uma área não selecionável. O wrapper nunca deve bloquear genericamente seus descendentes.
- O mecanismo legado do editor central selecionava `[data-cms-form-block]` como entidade única e podia ser reinstalado depois das tentativas de neutralização feitas pelo controlador do formulário. A correção canônica é de propriedade: o editor central não registra handlers em formulários expandidos; somente placeholders não expandidos podem usar o inspector de formulário inteiro.
- Programação, condições e pagamento já foram montados fora do schema do formulário e reposicionados pelo JavaScript público. Esse modelo híbrido é proibido porque cria duas fontes de verdade e torna conteúdo visível impossível de administrar pelo CMS.
- A migração 022 considerava qualquer array não vazio de `contentBlocks` como suficiente. Em uma instalação com blocos parcialmente existentes, isso podia remover o HTML legado da página sem repor blocos de pagamento ausentes. O reparo deve mesclar por `id`, não usar a existência de um único bloco como prova de completude.
- Uma regeneração de PNG transparente já produziu arquivos que tecnicamente mantinham canal alpha, mas ficaram visualmente vazios porque todos os pixels terminaram transparentes. Verificar somente “há transparência” é insuficiente; é obrigatório verificar também “há conteúdo visível”.
- `CSS adicional` já foi tratado como configuração associada ao locale. Isso contraria seu papel de escape hatch global do site. A regra vigente é escopo por site/atividade, compartilhado por todas as páginas e idiomas.

## Regra de documentação obrigatória

Depois de cada mudança relevante:

- atualizar este arquivo quando o estado canônico mudar;
- atualizar `docs/CMS_PROFESSIONAL.md` quando mudar arquitetura ou comportamento do admin/CMS;
- atualizar `docs/CMS_V3_DEPLOYMENT.md` e `DEPLOY.md` quando mudar build, atualização ou deploy;
- atualizar `README.md` quando mudar a visão geral, arquivos canônicos ou fluxo principal;
- não criar documentos paralelos para substituir decisões vigentes sem necessidade; corrigir o documento canônico existente.

## Estado editorial público

Português e inglês são documentos editoriais independentes.

### PT-BR

Objetivo atual: converter uma nova turma em português.

Hierarquia vigente da home:

1. workshop;
2. processo/revelação, com o vídeo do processo;
3. prova da primeira turma;
4. equipamento/NINA como ferramenta da pesquisa, não como tema central;
5. suporte incluído;
6. formato;
7. trajetória de João Saidler;
8. inscrição.

### EN

Objetivo atual: formar interesse para a primeira turma em inglês.

Hierarquia vigente:

1. workshop;
2. processo/revelação;
3. apparatus;
4. format;
5. research and photographer;
6. prova da turma em português;
7. interest list.

A página inglesa não deve ser mera tradução da brasileira.

### PT-BR — Oficina Pinhole Lambe-Lambe

- Nova página própria em `/pinhole-lambe-lambe`, independente da home do workshop de positivo direto.
- Nesta primeira fase, a página é de pré-lançamento/lista de interesse: não cobra pagamento e não anuncia preço ou data ainda não fechados.
- A página usa placeholders deliberados para a imagem/vídeo do protótipo e para a prévia do PDF; esses espaços serão substituídos no CMS quando o projeto físico estiver pronto.
- O produto é um encontro on-line e ao vivo de 2 a 3 horas, focado na construção e operação introdutória de uma Pinhole Lambe-Lambe autoral.
- O PDF entregue contém lista de materiais e projeto imprimível para colar no papel paraná e recortar; não é tratado como simples resumo de aula.
- A demonstração é feita por etapas, com modelos preparados em diferentes estágios. O participante escolhe se monta junto ou constrói depois.
- Não há fotografia nem revelação durante a oficina. O conteúdo teórico é introdutório: princípio da pinhole, o que esperar da câmera, falha de reciprocidade e operação da câmera/laboratório.
- Materiais-base comunicados: papel paraná, lata de refrigerante, fita isolante e, se o projeto final pedir, ímãs. A construção é enquadrada como trabalho de papelaria, sem ferramentas de oficina.
- O valor pago na oficina poderá virar crédito integral em um único curso elegível posterior: Positivo Direto em Filme de Raio-X ou o futuro curso completo de Construção de Câmeras. Regras e prazo só serão publicados quando fechados.
- O formulário próprio `pinhole-interest` coleta nome, e-mail, contato opcional, interesses e consentimento para aviso da primeira turma.
- A migração `034_pinhole_lambe_lambe_landing.php` cria e publica a página e o formulário somente quando ainda não existem, sem sobrescrever uma versão editorial já presente no banco. Depois do seed, ambos continuam sob autoridade do CMS.
- Não existe contraparte EN desta página neste momento; PT e EN permanecem independentes.

## Estado do CMS/admin

- `/admin/` é dashboard. Não deve redirecionar diretamente para o editor.
- O editor da home é uma ação do dashboard, não a própria página inicial administrativa.
- Dashboard atual: publicação, alterações pendentes, novas inscrições, páginas, formulários, mídia, armazenamento/saúde e atividade recente.
- Checkbox e radio devem manter 18 × 18 px no site público, preview e em toda a administração.
- O shell administrativo carrega CSS/JS locais com `?v=<versão instalada>` e mantém a geometria crítica de checkbox/radio independentemente de cache de stylesheet.
- O editor completo de formulários não deve comprimir configuração, lista de campos, propriedades e preview em colunas concorrentes. Configuração fica em faixa superior; lista de campos e propriedades recebem o espaço principal; preview fica em bloco separado abaixo.
- No editor da página, textos visíveis do formulário e dos seus blocos editoriais são editados diretamente no preview, como elementos independentes; não há seletor de campo para reproduzir no inspector o conteúdo já visível.
- Controles de formulário permanecem selecionáveis como contexto e o editor central não instala seleção de formulário inteiro em `data-cms-form-block`.
- O inspector da edição visual fica reservado a estado/publicação e propriedades não visíveis, como valor interno de opção, destino de link, condição, posição de bloco, estado de exibição pública e propriedades de mídia.
- Conteúdo condicional ou explicitamente oculto permanece visível no editor mesmo quando não aparece para o visitante.
- Programação, termos, instruções de pagamento e QR Code da inscrição pertencem ao formulário no CMS, não a funções PHP operacionais nem a reposicionamento por JavaScript público.
- Todos os CSS/JS públicos carregados pelo renderer recebem `?v=<versão instalada>`; a mesma política vale para editor e admin.
- `Design → CSS adicional` é configuração global do site/atividade e deve produzir o mesmo CSS em todas as páginas e idiomas desse site.
- A configuração Apache continua usando `Cache-Control: no-cache, must-revalidate` para `.css` e `.js` como defesa adicional.
- Alterações editoriais, visuais, estruturais e comerciais normais devem ser possíveis pelo CMS. Código deve ser necessário para novas capacidades, não para operação editorial cotidiana.

## Conteúdo e pesquisa que não devem regredir

- O processo/revelação tem precedência editorial sobre a NINA.
- O vídeo do processo deve permanecer associado à seção de processo; o CMS tenta preservar/reconciliar a mídia gerenciada existente.
- A apresentação de João deve explicitar quase oito anos de pesquisa em fotografia química e que ele projeta/constrói câmeras de grande formato, obturadores, tanques e sistemas de processamento.
- A comunicação pública não deve expor fórmulas, receitas internas ou detalhes químicos que foram definidos como conteúdo reservado ao workshop.

## Documentos que devem ser lidos antes de trabalhar

1. `docs/PROJECT_STATE.md` — estado canônico e fluxo operacional;
2. `docs/CMS_PROFESSIONAL.md` — capacidades e princípios do CMS;
3. `docs/CMS_V3_DEPLOYMENT.md` — self-update e persistência;
4. `README.md` — arquitetura geral;
5. `DEPLOY.md` — bootstrap, fallback e atualização;
6. `AGENTS.md` — regras de operação no repositório.