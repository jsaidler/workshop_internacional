# PROJECT STATE — Direct Positive Workshop CMS

Este é o documento canônico de estado operacional do projeto. Leia antes de alterar código, conteúdo, deploy ou fluxo administrativo. Atualize este arquivo sempre que uma decisão estrutural, operacional ou editorial mudar.

**Regra global anterior a qualquer trabalho:** ler `docs/GLOBAL_DOCUMENTATION_CONTRACT_2026-10-07.md`. Se existir documentação aplicável à tarefa, ela deve ser descoberta e consultada antes de qualquer decisão ou alteração. Essa obrigação vale para qualquer projeto e não apenas para este repositório.

## Estado em 20/09/2026

- Branch de produção: `wip/form-response-refinement-2026-07-16`.
- O CI gera e valida o pacote de produção e publica o resultado na branch `production-dist`.
- A hospedagem NÃO é atualizada automaticamente pelo simples fato de `production-dist` ter sido publicada.
- O fluxo normal de atualização da aplicação hospedada é feito pelo próprio usuário em `Admin → Sistema e atualizações → Instalar atualização`.
- FTP/manual deploy é apenas bootstrap inicial ou contingência. Não é o fluxo operacional normal depois que o self-updater está instalado.
- O updater preserva `storage/database.sqlite`, `uploads/`, `config/local.php`, `config/install.php`, `storage/logs/` e `storage/updates/`, cria backup antes da troca e verifica checksums SHA-256.
- Migrações pendentes são executadas pelo bootstrap na primeira requisição após a atualização.

## Estado canônico do Caderno de Processos em 06/10/2026

A revisão estrutural do Caderno foi integrada pelo PR #199, merge commit `7ffd448ebe765963f87fe4c01e00d9dda1816c27`.

O Caderno é um registro experimental, não um workflow de laboratório.

- Exposição, Processamento e Resultado são partes independentes do mesmo registro e permanecem acessíveis sem sequência obrigatória.
- O sistema não distingue “vou revelar”, “estou revelando” e “já revelei”. Ele apenas registra dados e oferece ferramentas.
- Associar um roteiro adiciona uma referência ao registro; todas as etapas ficam disponíveis imediatamente e podem ser abertas em qualquer ordem.
- Uma etapa marcada como concluída é apenas um dado reversível. O check não libera, bloqueia, cria pendência nem modifica a permissão das demais etapas.
- O timer é uma ferramenta reutilizável da etapa. Pode ser aberto quando quiser e, ao chegar ao fim, pode marcar a etapa como concluída. Depois disso continua reutilizável.
- Dados de uma etapa permanecem editáveis mesmo quando ela está marcada como concluída.
- Um experimento pode ser interrompido em qualquer ponto. Etapas não marcadas não representam tarefas pendentes nem tornam o registro incompleto.
- Resultado pode ser registrado a qualquer momento e pode ser uma observação experimental, inclusive quando o processo foi abandonado antes do fim.
- Estoque é independente de check e timer. Uso, retorno, perda, descarte e demais movimentações são registros explícitos e não são inferidos a partir da conclusão de uma etapa.
- Tudo que seria trivial fazer em um caderno deve continuar trivial. O digital acrescenta roteiro reutilizável, timer, cálculos, estoque, imagens, busca, filtros, comparações e persistência sem reduzir a liberdade do registro.
- A camada de Análise é separada do ato de registrar: o Caderno registra; a Análise interroga o conjunto de registros estruturados. Filtros, relações, comparações e gráficos não dependem de um “processo completo” e não devem inferir causalidade além do que os dados sustentam.

Regra de produto para novas mudanças: se uma funcionalidade obriga o usuário a explicar ao sistema o que está fazendo antes de deixá-lo registrar alguma coisa, a funcionalidade provavelmente está errada.

A validação da revisão incluiu `deploy`, `student-visual-audit`, `validate`, 347 regressões de navegador e inspeção visual manual em desktop e telefone. Durante a inspeção foram corrigidos o layout das etapas do roteiro, a fixture de registro vazio e o carregamento dos estilos da tela `registro-roteiro.php`.

### Regra de validação visual do Caderno

A correção posterior do PR #200 mostrou que um screenshot de fixture pode passar visualmente e ainda assim não representar a composição real de `aluno/teste.php`. Portanto:

- fixture visual só é evidência válida quando reproduz os elementos relevantes do DOM real daquela superfície;
- elementos omitidos da fixture, inclusive disclosures, campos opcionais, estados vazios e ações condicionais, invalidam qualquer conclusão de “aprovado visualmente” sobre a tela real;
- quando um defeito for reportado por captura da instalação real, a captura real tem precedência sobre a fixture e deve orientar a correção;
- o estado visual equivalente ao defeito precisa ser reproduzido no audit antes do merge;
- aprovação visual de fixture deve ser descrita como aprovação da composição renderizada do repositório, não como prova de que a hospedagem instalada já está correta;
- a hospedagem só pode ser considerada verificada depois da instalação da versão correspondente e de nova observação da superfície real quando necessário.

O PR #200 removeu a duplicação visual de Processamento no registro sem roteiro, eliminou a ação duplicada de Adicionar etapa, passou a manter o formulário de etapa recolhido por padrão, compactou Dados opcionais e estabeleceu a hierarquia `PROCESSAMENTO → Roteiro e etapas`.


### Anotação e dúvida no mesmo contexto — 07/10/2026

Anotação e dúvida são ações correlatas de leitura e não podem exigir navegação entre telas distantes.

Contrato vigente:
- ao criar uma anotação sobre um trecho ou sobre a página, o aluno pode abrir **Também é uma dúvida?** no mesmo compositor;
- o texto da anotação é reutilizado como corpo da dúvida; o aluno informa apenas título e visibilidade;
- **Salvar anotação e publicar dúvida** cria os dois registros em uma única operação transacional;
- se a dúvida falhar, a anotação nova não fica salva pela metade;
- uma anotação já existente pode usar **Transformar em dúvida** no próprio painel, sem navegar para `aluno/duvidas.php`;
- após a criação, a mesma anotação passa a exibir **Ver dúvida**;
- uma anotação só pode originar uma dúvida: tentativas repetidas reutilizam o vínculo existente em vez de duplicar a conversa;
- o fluxo AJAX mantém a página de material e a posição de leitura; nenhuma criação de dúvida exige recarregar ou reconstruir o contexto.

`tools/test-student-question-annotation.php` cobre o vínculo e a não duplicação em SQLite; `tools/browser-tests/student-inline-annotations.spec.cjs` cobre a criação anotação+dúvida sem mudança de URL; o audit visual cobre o compositor aberto em telefone e desktop.

### Roteiro editável dentro do registro — 07/10/2026

O roteiro associado ao Caderno é uma cópia contextual daquele registro, não um fluxo controlado pelo sistema. A própria folha do registro expõe, para cada etapa, ações independentes de **Marcar/Desmarcar**, **Editar** e **Timer**.

Contrato vigente:
- editar nome, tempo, temperatura, solução/revelador, volumes, agitação e anotações diretamente em `aluno/teste.php`;
- editar uma etapa não altera automaticamente seu check e não muda o roteiro-modelo da biblioteca;
- reordenar etapas com Subir/Descer sem conceito de “etapa atual”;
- adicionar etapas à cópia do roteiro do registro;
- o check é reversível e grava explicitamente no registro corrente;
- timer, check, edição e estoque permanecem independentes;
- nenhuma dessas ações impõe sequência, desbloqueia etapa ou significa que o processo físico foi iniciado/finalizado.

A regressão funcional do check passa a ser coberta por `tools/test-student-process-notebook-runtime.php`, que executa marcação, releitura, edição preservando check, desmarcação, reordenação, adição de etapa e verificação de ownership em SQLite real. O audit visual inclui o estado `record-plan-edit` em desktop e telefone.

### Auditoria transversal do Caderno — 06/10/2026

A revisão posterior ao PR #200 não se limita ao defeito mostrado em uma captura. O PR #201 audita o Caderno e as superfícies diretamente associadas em desktop e telefone.

- `aluno/teste.php`: registro vazio, Dados opcionais aberto, etapa livre aberta, roteiro associado, roteiro parcialmente marcado, Resultado com contexto fechado e aberto;
- `aluno/registro-roteiro.php`: associação/troca de roteiro;
- `aluno/teste-etapa.php`: edição independente de etapa livre;
- `aluno/processar.php`: consulta de qualquer etapa, timer, check reversível e edição dos dados da etapa;
- `aluno/processamentos.php`: biblioteca e editor de roteiros-modelo;
- inventário/estoque continuam ações independentes do check e do timer.

Correções sistêmicas resultantes:
- removido de `processamentos.php` o resíduo `intent=live` e toda diferenciação “usar agora” × “associar ao registro”; links antigos passam pelo comportamento neutro;
- associação de roteiro sempre retorna ao registro; o sistema não interpreta a associação como início de processamento;
- `Iniciar no laboratório` foi substituído por `Abrir no laboratório`; “Iniciar” permanece apenas como verbo do cronômetro;
- navegação Exposição / Processamento / Resultado passa a ocupar uma única linha no telefone;
- ações de cada etapa do roteiro foram compactadas sem reduzir a área de toque abaixo de 38 px;
- o resumo de Exposição e Processamento dentro de Resultado passa a ser informação opcional recolhida, evitando repetir por padrão o que está imediatamente acima;
- fixtures antigas de edição de etapa e runner temporal foram removidas/alinhadas ao DOM e ao comportamento atuais.

A cobertura visual específica do Caderno inclui também disclosures abertos. Um audit verde que só renderize estados recolhidos não é suficiente. A inspeção humana do artifact continua obrigatória antes de merge de alterações visuais.

### Navegação sistêmica da Área do aluno — 07/10/2026

A implementação passa a materializar o contrato de `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md` em uma única autoridade compartilhada.

- os quatro destinos globais são **Início / Cursos / Caderno / Laboratório**, nesta ordem, tanto no telefone quanto no desktop;
- `app/student_shell.php` é a autoridade dos destinos e da classificação semântica do contexto; o estado ativo não é mais decidido por simples correspondência de substring no pathname;
- uma rota reutilizada pode ter contexto diferente conforme o objeto aberto: `processar.php?test=...` permanece em **Caderno**, enquanto o mesmo instrumento aberto de forma autônoma permanece em **Laboratório**;
- Conta e atalhos de ferramentas continuam secundários e não competem com os quatro destinos globais;
- páginas CMS de material didático continuam usando o renderer CMS normal e o mesmo filtro server-side de conteúdo, mas, quando existe `materialContext` autenticado, consomem a navegação global da Área do aluno: logo para `/aluno/`, **Cursos** ativo e barra inferior persistente no telefone;
- o site público sem contexto de material autenticado mantém sua navegação pública e sua própria semântica de marca;
- telas filhas precisam de destino lógico explícito em `href`, inclusive em deep link; editores que também aparecem em dialog não podem depender exclusivamente do fechamento do dialog;
- links HTTP normais continuam sendo o mecanismo preferencial de deslocamento e, portanto, de histórico. Microestados locais não criam entradas artificiais;
- a regressão de navegador cobre Back, Forward, deep links, preservação de contexto e ausência de poluição do histórico por disclosures;
- o audit transversal mobile não possui mais exceção para Material: toda superfície autenticada normal precisa provar chrome global persistente e reserva do espaço da barra inferior.

Esta implementação não cria um renderer paralelo de material nem uma segunda topbar. O conteúdo editorial permanece CMS; apenas o chrome autenticado passa a obedecer à mesma arquitetura global da Área do aluno.

### Anotações mobile-first e liberação de áreas sensíveis — 07/10/2026

A camada de anotações do material foi corrigida a partir do uso real em telefone:

- ao iniciar uma anotação de seleção, a introdução do painel sai do caminho e o formulário assume o foco;
- o trecho selecionado tem altura limitada e rolagem própria, impedindo que uma citação longa consuma a tela;
- **Salvar / Cancelar** dividem a mesma linha de ação no telefone; nenhuma ação secundária é espremida para fora da viewport;
- **Também é uma dúvida?** continua secundário e recolhido por padrão;
- ao abrir a dúvida no telefone, o compositor entra em um segundo estado: trecho, textarea e ações da anotação saem de cena e aparecem somente título, visibilidade e publicação da dúvida;
- o retorno **← Voltar à anotação** restaura o primeiro estado e preserva o rascunho;
- a composição usa altura dinâmica de viewport e reserva de safe area;
- a auditoria visual cobre explicitamente os dois estados em 390×844 e rejeita o empilhamento simultâneo dos dois formulários.

A liberação de conteúdo sensível também passa a ser autorização server-side, não simples ocultação de links:

- seções CMS continuam vinculáveis a aulas e são filtradas no servidor conforme a liberação da aula para a turma;
- áreas não-CMS da Área do aluno podem usar `student_tool_courses.release_lesson_id`;
- uma ferramenta vinculada a uma aula não é retornada por `student_tools_for_student()` antes de essa aula estar efetivamente liberada para uma das matrículas elegíveis;
- deep links obedecem ao mesmo gate por meio de `student_tool_require()`;
- o atalho e o conteúdo de **Receitas / Preparo de soluções** não são renderizados antes da autorização;
- `solution_prep` é vinculada por padrão à primeira aula dos cursos existentes e também quando a primeira aula de um novo curso é criada;
- em **Curso → Aulas**, o administrador escolhe para cada área se ela fica disponível desde a matrícula ou a partir da liberação de uma aula;
- em **Turma → Aulas e acesso**, liberar/agendar essa aula controla simultaneamente seções de material e áreas vinculadas.

O objetivo é impedir que uma matrícula recém-confirmada dê acesso antecipado a receitas ou outros conteúdos proprietários. O aluno pode ter conta e navegar pela plataforma antes do curso; o conteúdo marcado como sensível simplesmente não é autorizado até o momento pedagógico definido.

## Regra operacional obrigatória

Para qualquer alteração:

1. aplicar primeiro `docs/GLOBAL_DOCUMENTATION_CONTRACT_2026-10-07.md`: descobrir e ler integralmente toda documentação canônica aplicável ao escopo antes de decidir ou modificar qualquer coisa;
2. ler este documento e os documentos relacionados ao subsistema;
3. **se a mudança afetar a Área do aluno, ler integralmente `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md` antes de qualquer decisão de UI/UX ou alteração de código;**
4. trabalhar em branch/PR quando a mudança for estrutural ou de comportamento;
5. manter CI verde;
6. integrar na branch de produção;
7. confirmar que `production-dist` foi gerada com o commit correto;
8. informar ao usuário que a nova versão está disponível no painel;
9. o usuário aplica a atualização em `Admin → Sistema e atualizações`;
10. só considerar a hospedagem atualizada depois dessa aplicação e da verificação da versão instalada.

Nunca confundir `production-dist` atualizada com hospedagem atualizada.

### Preflight obrigatório de UI/UX da Área do aluno

`docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md` é o contrato prioritário de navegação da Área do aluno.

Antes de alterar qualquer arquivo em `aluno/**`, `app/student_*.php`, `assets/student-*.css`, `assets/student-*.js` ou fixtures/tests correspondentes, a implementação precisa conferir explicitamente:

- mobile como primeira viewport de decisão;
- barra inferior persistente nas rotas mobile afetadas e vizinhas;
- destino da logo autenticada sempre em `/aluno/`;
- estado ativo da navegação por contexto de produto;
- retorno contextual explícito em telas filhas;
- breadcrumb apenas como contexto terciário, nunca como mecanismo necessário;
- mesma arquitetura de informação no desktop.

Uma correção local que introduza exceção de navegação sem alterar primeiro o contrato canônico é inválida.

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

Checkbox e radio são um único componente nativo em três contextos: site público, preview/editor e administração. A geometria deve permanecer 18 × 18 px em todos eles. Regras genéricas de `input` não podem lhes impor largura total, altura de campo de texto, padding ou flex expansivo. Essa geometria pertence ao proprietário CSS canônico de cada superfície; o shell não contém patch visual inline nem duplicação estrutural do componente.

A captura de 13/09 mostrou um detalhe importante: o círculo/quadrado nativo podia parecer pequeno, mas o elemento `input` continuava ocupando a largura inteira da linha por causa do CSS legado de campos de texto. O sintoma visual era o marcador centralizado e o texto empurrado para a direita. Portanto o critério de correção não é apenas o diâmetro visível; a caixa do próprio `input` precisa estar efetivamente limitada a 18 × 18 px.

Como uma atualização de CSS pode ficar mascarada por cache antigo do navegador, os renderers público, editor e admin versionam seus assets com a versão instalada (`deploy-info.json`). Invariantes visuais críticos são implementados no proprietário CSS canônico, com escopo semântico e sem `!important`; CSS inline visual em shells não é um mecanismo aceito de precedência.

### Arquitetura CSS canônica

O saneamento estrutural de 28/09/2026 substitui a antiga cascata cronológica por autoridades explícitas. A especificação completa está em `docs/CSS_SYSTEM_SANITIZATION_2026-09-28.md`.

- `assets/ui-core.css` contém primitivas compartilhadas.
- `assets/admin-system.css` é a autoridade global da administração; `assets/admin-media.css` é feature CSS consumida apenas pela mídia.
- `assets/student-area.css` é a autoridade da área do aluno.
- `template/page.css`, `assets/cms-core.css`, `assets/cms-editorial.css` e as features públicas explícitas compõem o CMS dentro de `cms-system`.
- `editor/editor-system.css` é a autoridade do shell do editor; folhas adicionais só permanecem para features reais.
- Folhas de correção cronológica (`v2`, `v3`, `fix`, `hotfix`, `override`, `refinement` e equivalentes) são proibidas.
- `tools/test-css-architecture.php` bloqueia `!important`, autoridades aposentadas e regressões de carregamento.
- `tools/audit-css-ownership.php --strict` bloqueia redefinição da mesma propriedade no mesmo seletor e contexto de cascata. `tools/consolidate-css-ownership.php --check` bloqueia consolidações determinísticas pendentes e `tools/consolidate-css-selectors.php --check` bloqueia divisões de seletor que ainda podem ser reunidas sem alterar a ordem efetiva da cascata.

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
- Nenhum CSS autoral versionado usa `!important`, inclusive invariantes funcionais. Geometria, acessibilidade, segurança e estado devem ser resolvidos por DOM, escopo, seletor e proprietário canônico; `!important` não é ferramenta de precedência aceita no projeto.
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

### PT-BR — Fotografia Experimental em Grande Formato

- A página existente `/pinhole-lambe-lambe` passa a representar a formação **Fotografia Experimental em Grande Formato**. O slug é preservado para não quebrar links e referências já existentes; título público e navegação passam a usar o novo nome.
- O produto não é mais a antiga oficina curta de Pinhole Lambe-Lambe. É uma formação on-line e ao vivo de **8 encontros de aproximadamente 1h30**, totalizando 12 horas ao vivo, com trabalho experimental entre os encontros.
- Problema principal da oferta: permitir a entrada na fotografia de grande formato sem exigir que o aluno comece comprando câmera, objetiva, chassis, acessórios e uma infraestrutura separada de laboratório.
- A câmera ensinada **já nasce como câmera-laboratório**. O espaço protegido de manipulação e processamento é parte do projeto desde o início e não é um acessório acrescentado depois.
- O curso inclui duas soluções construtivas do mesmo sistema: materiais acessíveis e madeira. Elas pertencem ao mesmo curso; não são versões vendidas separadamente.
- O percurso cobre projeto/construção, operação e exposição, produção e leitura do negativo, cópia por contato, cianotipia, impressão em clorofila diretamente em folhas e revisão dos resultados.
- A lógica pedagógica do positivo neste curso é `negativo → nova exposição por contato → positivo`. O negativo é tratado como matriz capaz de originar objetos positivos diferentes conforme suporte e processo.
- O curso **não ensina nenhum conteúdo técnico do Workshop Positivo Direto em Filme de Raio-X**. Não entram reversal, branqueamento, limpeza, reexposição, receitas, parâmetros ou qualquer outra parte reservada àquele produto.
- Os dois cursos permanecem independentes. A experiência de produzir negativo e depois uma segunda geração positiva pode tornar perceptível o tempo e a duplicação de etapas do processo, mas a página de Grande Formato não usa o outro curso como upsell nem entrega amostra de seu conteúdo.
- Valor de lançamento da primeira turma: **R$ 1.290 no Pix**. Valor regular de referência: **R$ 1.490**. A data ainda não está definida; a página opera como lista de interesse até a abertura.
- A comunicação pública segue diagnóstico de necessidade antes da oferta: interesse → obstáculo → consequência → critério de solução → adequação → oferta. A sequência de “sins” só é usada quando confirma problemas e desejos reais, não como fabricação de concordância.
- O formulário histórico `pinhole-interest` é preservado para manter continuidade e respostas anteriores, mas passa a coletar a principal barreira para entrar no grande formato e os resultados que a pessoa gostaria de alcançar. A escolha antiga entre versões de R$ 98 é removida.
- Não há placeholders públicos de mídia. Enquanto não existir mídia editorial válida da nova câmera/protótipo, a página usa somente os componentes visuais globais já existentes no CMS.
- A especificação detalhada da oferta e da metodologia de comunicação está em `docs/EXPERIMENTAL_LARGE_FORMAT_COURSE_2026-10-06.md`.
- A migração `093_experimental_large_format_course_page.php` substitui deliberadamente o conteúdo editorial da antiga página Pinhole pelo novo produto, preservando o slug, o ID da página, a chave do formulário e o histórico de submissões.
- A migração `094_large_format_reuse_canonical_components.php` corrige a composição visual sem criar CSS próprio: a página passa a respeitar os contratos dos componentes globais existentes e remove variantes/classes inexistentes ou estruturas incompletas.
- A migração `095_large_format_restore_visual_placeholders.php` restaura os slots editoriais de mídia removidos indevidamente e mantém hero, câmera-laboratório, processos positivos e comparação construtiva com placeholders até a entrada de mídia definitiva.
- QA visual de página pública passa a ser obrigatório: render real em desktop/telefone, claro/escuro quando aplicável, screenshots diagnósticos e testes de hover/focus dos componentes usados.

## Estado do CMS/admin

- `/admin/` é dashboard. Não deve redirecionar diretamente para o editor.
- O editor da home é uma ação do dashboard, não a própria página inicial administrativa.
- Dashboard atual: publicação, alterações pendentes, novas inscrições, páginas, formulários, mídia, armazenamento/saúde e atividade recente.
- Consultar/listar páginas e formulários, abrir suas telas administrativas ou gerar sitemap não deve criar conteúdo. Uma atividade vazia permanece vazia até uma ação editorial explícita ou aplicação deliberada de template.
- A criação de um novo curso/workshop usa template explícito. `Em branco` é o estado neutro e não herda nem recebe automaticamente conteúdo do primeiro workshop; `Positivo direto` aplica deliberadamente os defaults desse produto.
- O template `Positivo direto` é operação de inicialização. Se a atividade já possuir página ou formulário CMS ativo, o setup não altera nem republica o conteúdo existente.
- `cms_pages_seed()` e `cms_forms_seed()` permanecem apenas como primitivas transitórias de instalação/template/migração. Não podem ser chamadas por listagem, lookup, sitemap, renderer ou simples abertura de tela administrativa.
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

## Atualização estrutural em 26/09/2026 — identidade localizada e páginas

A terceira tranche da auditoria sistêmica introduz autoridades aditivas para identidade localizada de cursos e estrutura editorial de páginas, sem retirar o legado no mesmo release.

- `activity_locales(activity_id, locale, public_title, ...)` passa a ser a autoridade nova para o nome público localizado da `activity`.
- `activities.public_title` permanece preservado como fallback/compatibilidade e não é mais atualizado pelas edições localizadas feitas no admin.
- A criação de uma nova activity exige o idioma do primeiro nome público e grava simultaneamente a identidade localizada; a coluna legada continua preenchida apenas porque ainda é `NOT NULL` e precisa sustentar consumidores não migrados.
- `cms_pages.parent_page_id` passa a representar a hierarquia editorial. Essa relação não altera slug, URL, ID, UUID, documento, revisão nem a configuração de Navegação.
- `cms_pages.translation_group_uuid` passa a representar equivalência explícita entre versões em idiomas diferentes. PT e EN equivalentes não dependem de possuir o mesmo slug.
- Troca pública de idioma e sitemap/hreflang usam primeiro o grupo explícito e mantêm temporariamente o comportamento antigo por home/slug quando ainda não existe grupo, para permitir migração gradual.
- A hierarquia e a equivalência são editadas na interface canônica `Páginas`; títulos localizados continuam na interface canônica `Cursos e workshops`. Não existe interface paralela.
- A migração `068_activity_locales_page_hierarchy.php` é aditiva e não reparenta nem agrupa páginas existentes por inferência.
- A regressão `tools/test-activity-locales-page-hierarchy.php` valida preservação de slugs, rejeição de ciclos, equivalência com slugs diferentes, precedência da nova autoridade e ausência de writes no título legado durante edição localizada.
- O documento detalhado desta tranche é `docs/LOCALIZED_ACTIVITY_IDENTITY_PAGE_HIERARCHY_2026-09-26.md`.

Próximo trabalho sistêmico após estabilização e observação desta tranche: finalidade explícita de formulários e remoção das automações escondidas por `form_key='registration'`, seguida do lifecycle próprio de matrículas e continuação da consolidação dos renderers/editor conforme a auditoria.

## Tranche 2026-09-26 — finalidade de formulário como autoridade operacional

Implementada em `audit/form-purpose-enrollment-authority-2026-09-26`: `cms_forms.purpose` é a autoridade explícita para comum/interesse/inscrição/inscrição que gera matrícula. O comportamento de matrícula deixou de ser decidido por `form_key='registration'`; a migração 069 preserva todas as chaves, IDs, submissões e o comportamento do formulário legado por backfill para `enrollment`. A interface permanece em `Admin → Formulários`. Ver `docs/FORM_PURPOSE_ENROLLMENT_AUTHORITY_2026-09-26.md`.