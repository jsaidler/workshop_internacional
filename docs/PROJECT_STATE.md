# PROJECT STATE — Direct Positive Workshop CMS

Este é o documento canônico de estado operacional do projeto. Leia antes de alterar código, conteúdo, deploy ou fluxo administrativo. Atualize este arquivo sempre que uma decisão estrutural, operacional ou editorial mudar.

## Estado em 13/09/2026

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
- A captura de `pointerdown`/`click` dos subalvos ocorre antes do handler do bloco do formulário, para impedir que o wrapper roube a seleção do alvo específico.
- O resolvedor não deve depender apenas do `span` interno recebido como `event.target`: ele precisa reconhecer também `label` de radio/checkbox, `legend`, `.cms-field`, `.cms-consent`, controles e botão.
- Um formulário já expandido no preview (`data-cms-form-block`) não pode ser selecionado pelo mecanismo legado de “formulário inteiro”. O `onclick` legado do wrapper deve ser neutralizado depois que os hooks centrais do iframe forem instalados.
- Clicar em texto ou controle visível de um formulário nunca deve encaminhar automaticamente para `/admin/forms.php`. O editor completo é apenas uma ação secundária explícita para mudanças estruturais.
- É proibido cancelar genericamente todo clique ocorrido dentro de `[data-cms-form-block]`. Somente subalvos reconhecidos recebem interceptação; os demais eventos continuam para os mecanismos normais do editor.
- Os subalvos do formulário devem ser preparados quando o documento do iframe é instalado. O controlador precisa funcionar tanto no `load` do iframe quanto quando o documento já existir no momento em que o script é carregado.
- O cursor deve ser posicionado no ponto clicado. `Enter` conclui a edição; `Esc` cancela a alteração corrente.
- Os alvos editoriais são decorados apenas no preview do editor; atributos e wrappers auxiliares não fazem parte do HTML público persistido da página.
- As alterações são gravadas no rascunho do formulário pela API canônica `cms-form-save.php`; `cms-form-publish.php` continua sendo a publicação explícita do formulário.
- Propriedades não visíveis podem aparecer no inspector de forma contextual. Exemplo: ao clicar o rótulo de uma opção, o inspector pode expor apenas o valor interno daquela opção, porque esse valor não aparece na página.
- O inspector não deve duplicar o rótulo visível em outro campo de texto. O texto visível é editado exclusivamente no próprio preview.
- Tipo, identificador, ordem, criação/remoção de campos, obrigatoriedade, lógica condicional e fluxo após envio permanecem no editor completo. O inspector mantém uma ação secundária explícita para esse editor.
- A edição visual e o editor completo usam o mesmo schema e as mesmas APIs; não existe uma segunda estrutura de formulário.

### Falhas já observadas e que não podem regredir

- Um `MutationObserver` do inspector que re-renderizava o próprio inspector criou loop de microtasks e congelou o editor; esse padrão é proibido.
- Um handler posterior tentou “proteger” o formulário cancelando todo `pointerdown`/`click` que ocorresse dentro do bloco quando o alvo textual não fosse reconhecido. O resultado foi exatamente o contrário: o formulário inteiro virou uma área não selecionável. O wrapper nunca deve bloquear genericamente seus descendentes.
- O mecanismo legado do editor central ainda sabia selecionar `[data-cms-form-block]` como uma entidade única e mostrar o caminho para o editor completo. Em formulários já renderizados esse fallback deve ser desativado; só placeholders ainda não expandidos podem continuar usando a seleção de bloco inteiro.

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

## Estado do CMS/admin

- `/admin/` é dashboard. Não deve redirecionar diretamente para o editor.
- O editor da home é uma ação do dashboard, não a própria página inicial administrativa.
- Dashboard atual: publicação, alterações pendentes, novas inscrições, páginas, formulários, mídia, armazenamento/saúde e atividade recente.
- Checkbox e radio devem manter 18 × 18 px no site público, preview e em toda a administração.
- O shell administrativo carrega CSS/JS locais com `?v=<versão instalada>` e mantém a geometria crítica de checkbox/radio independentemente de cache de stylesheet.
- O editor completo de formulários não deve comprimir configuração, lista de campos, propriedades e preview em colunas concorrentes. Configuração fica em faixa superior; lista de campos e propriedades recebem o espaço principal; preview fica em bloco separado abaixo.
- No editor da página, textos visíveis do formulário são editados diretamente no preview, como elementos independentes; não há seletor de campo para reproduzir no inspector o conteúdo já visível.
- Controles de formulário permanecem selecionáveis como contexto e nenhum handler do wrapper pode tornar os descendentes inertes nem devolver o clique ao inspector legado do formulário inteiro.
- O inspector da edição visual fica reservado a estado/publicação e propriedades não visíveis, como o valor interno de uma opção clicada.
- Todos os CSS/JS públicos carregados pelo renderer recebem `?v=<versão instalada>`; a mesma política vale para editor e admin.
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
