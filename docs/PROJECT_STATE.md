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
- a dúvida oferece `private`, `cohort` e `course`: a última opção alcança apenas matrículas ativas de turmas com o mesmo `course_id`, sem compartilhar a anotação privada nem seu trecho original em contexto de outra turma;
- **Salvar anotação e publicar dúvida** cria os dois registros em uma única operação transacional;
- se a dúvida falhar, a anotação nova não fica salva pela metade;
- uma anotação já existente pode usar **Transformar em dúvida** no próprio painel, sem navegar para `aluno/duvidas.php`;
- após a criação, a mesma anotação passa a exibir **Ver dúvida**;
- uma anotação só pode originar uma dúvida: tentativas repetidas reutilizam o vínculo existente em vez de duplicar a conversa;
- o fluxo AJAX mantém a página de material e a posição de leitura; nenhuma criação de dúvida exige recarregar ou reconstruir o contexto.

`tools/test-student-question-annotation.php` cobre o vínculo e a não duplicação em SQLite; `tools/browser-tests/student-inline-annotations.spec.cjs` cobre a criação anotação+dúvida sem mudança de URL; o audit visual cobre o compositor aberto em telefone e desktop.

### Auditoria do vínculo entre Dúvidas e Caderno — 10/10/2026

Uma pergunta partilhada no curso pode referenciar ficha experimental sem estender a permissão dessa ficha aos seus leitores. A auditoria identificou link direto para `/aluno/teste.php` (rota do proprietário) apresentado inclusive a outros alunos, enquanto fichas compartilhadas exigem `/aluno/teste-compartilhado.php` e fichas privadas não devem oferecer link. A correção deve usar `student_test_accessible_to_student()` no momento de renderizar a conversa e restringir o vínculo na criação à turma atual. A regressão contempla leitor autor, outra turma do mesmo curso, outra atividade, matrícula inativa e ficha privada. A matriz `docs/STUDENT_UI_STATE_MATRIX_2026-10-09.md` registra o estado de aprovação real desta tranche; não atribuir inspeção visual antes de observar as capturas.


**Achado transversal de qualidade (10/10):** o fixture `student-secondary-screens-audit.html` apresentava falsos alertas `Não foi possível enviar a resposta` em rotas alheias a Dúvidas, como Calibração, devido à condição incorreta de `applyQuestionFailureState()`. Capturas produzidas com esse defeito não comprovam fidelidade visual. A revisão deve corrigir a condição, reprovar `pageerror` e alertas espúrios no audit, regerar as capturas e inspecioná-las antes de aprovação.

### Correção do painel de anotações existentes — 09/10/2026

A inspeção visual anterior do PR #215 cobriu somente **criação de anotação** e **criação de dúvida**. A captura de produção apresentada pelo usuário revelou que **edição de uma anotação existente** não foi renderizada, inspecionada nem aprovada; o formulário de edição, o de transformação em dúvida e a criação de anotação geral estavam empilhados.

Contrato de produto para Material:
- o painel aberto por Anotações apresenta uma lista de notas existentes com texto legível e a ação explícita **Editar anotação**, sem expor todos os textareas;
- clicar no destaque de uma anotação no material abre **essa anotação em modo de edição**, com retorno **← Todas as anotações**, sem abrir editores de outras notas nem formulário geral;
- o editor mostra trecho associado (quando houver), texto e ações de salvar/gerenciar; transformação em dúvida é uma segunda etapa, exclusiva em relação ao textarea/ações da edição e com retorno que preserva o rascunho;
- **Nova anotação geral** é uma ação recolhida, não um formulário permanente abaixo dos registros; seu compositor mantém a opção de transformar em dúvida sem simultaneidade de tarefas;
- preservar vínculo com o trecho, contexto de turma, AJAX, posição de leitura e o escopo `private/cohort/course` existente;
- a auditoria deve conter casos reais de notas persistidas e longas, lista, edição, publicação de dúvida e anotação geral, em telefone e desktop. Capturas automáticas não substituem inspeção visual efetiva.

Este contrato é local ao compositor do material; não altera a navegação global (Início/Cursos/Caderno/Laboratório), suas URLs nem a política de histórico.

### Revisão corretiva da UI/UX de anotações persistidas — 09/10/2026

Esta revisão não é intercambiável com a aprovação anterior do PR #215: aquele teste cobriu só criação e publicação. O fluxo de anotação existente foi encontrado com texto, ações de edição, conversão em dúvida e anotação geral empilhados, sem inspeção do estado de edição, como comprovado por captura de produção.

Em revisão no PR #216:
- a lista apresenta prévias, identificação pelo trecho e ação editar; a edição de notas atuais e legadas ocupa um único estado focado;
- a conversão em dúvida tem estado separado, mantendo privacidade por autor/turma/curso, o vínculo com o material e o retorno do rascunho;
- `Nova anotação geral` fica no início da lista e permanece recolhida até acionamento; quando aberta, apresenta exclusivamente seu compositor e o retorno à lista, sem empilhar os registros existentes;
- erros de salvamento são exibidos com `role=alert` preservando o rascunho; exclusão requer diálogo explícito com cancelamento;
- o drawer tem altura efetivamente limitada e área de rolagem interna `position:absolute; top:48px/58px; bottom:0`. O uso de `display:grid` diretamente em `<details open>` sem contenção permitia o conteúdo escapar da viewport e tornava o último botão inacessível;
- o shell mobile compensa a barra fixa por `scroll-padding-bottom`, não ajustes contraditórios por rota; o cabeçalho e a barra persistente são opacos, sem blur do conteúdo atrás;
- testes incluem muitos registros com última ação rolável, estados de anotação/edição/dúvida/geral, erro de atualização, remoção confirmada/cancelada, 46 superfícies básicas em desktop/telefone e verificação de controles alcançáveis sem sobreposição.

A matriz de estados e respectivas lacunas obrigatórias está em `docs/STUDENT_UI_STATE_MATRIX_2026-10-09.md`. **Cobrir 46 composições de tela não significa ter avaliado todas as suas possibilidades.** Enquanto houver estado sem inspeção funcional e visual, não se deve afirmar auditoria completa da Área do aluno. A integração e atualização da hospedagem exigem gates verdes e revisão humana do material renderizado.

### Auditoria mobile — região rolável sem oclusão — 09/10/2026

O problema sistêmico da navegação inferior sobreposta ao conteúdo não é resolvido apenas por `padding-bottom` e `scroll-padding-bottom`. No shell autenticado, `.student-main` passa a ser a região rolável, terminando antes da barra global `student-mobile-nav`. No renderer de materiais CMS, o `main` e o rodapé passam para `.cms-student-reading` sem mover a barra para dentro da leitura. `student-area.css` é proprietário da geometria; CSS de experiência deixa de sobrescrever `padding-bottom` do shell.

O JavaScript de anotações restaura o scroll do material; operações AJAX no Caderno preservam o scroll da região correta no telefone, sem interferir no scroll de janela no desktop. As fixtures de material e AJAX consomem a mesma estrutura. A inspeção obrigatória fotografa início, meio e fim da região interna nas 46 superfícies mobile, além de testar foco, contexto, navegação e retorno. Este item só pode ser aprovado após CI e inspeção efetiva; a auditoria de **todos os estados de todas as telas** permanece aberta em `docs/STUDENT_UI_STATE_MATRIX_2026-10-09.md`.

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

### Hierarquia do menu público — 08/10/2026

O menu principal do site público deixa de tratar páginas editoriais como destinos equivalentes e passa a apresentar a hierarquia já existente em `cms_pages.parent_page_id`.

Contrato:

- **Páginas** continua sendo a única autoridade da relação pai/filha;
- **Navegação** continua escolhendo presença, rótulo e ordem; não ganha uma segunda árvore;
- uma subpágina selecionada para o menu é sempre apresentada dentro da página superior;
- se o pai não estiver explicitamente selecionado, o renderer inclui o ancestral necessário para preservar contexto;
- desktop apresenta submenu; telefone apresenta a mesma árvore em expansão vertical;
- a inscrição do workshop de positivo passa a ser filha da página principal do workshop, mantendo sua URL;
- novas inscrições/listas de interesse devem seguir a mesma relação com a página comercial do respectivo curso ou workshop.

A mudança não transforma a home em portal nem reorganiza automaticamente os demais cursos. Ela resolve a arquitetura do menu de forma genérica para que novas páginas de inscrição não apareçam soltas.

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
- a auditoria visual cobre explicitamente os dois estados em 390×844 e rejeita o empilhamento simultâneo dos dois formulários;
- em 09/10/2026, a separação anotação → dúvida passou a valer também no desktop, pois o painel lateral estreito não comporta os dois formulários simultaneamente; regressões exercitam também 1280×820 e a publicação em todas as turmas.

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

## Auditoria integral da área administrativa — 08/10/2026

A administração passa a tratar “inspeção visual completa” como uma matriz de produto, não como smoke da feature alterada.

Contrato vigente:

- cobrir 390×844, 768×1024, 1280×800 e 1600×900;
- incluir shell, coleções, detalhes, formulários, dialogs/disclosures e editor visual em estados capazes de alterar a composição;
- incluir Páginas, Navegação/estrutura do site, Design, SEO, Formulários, Respostas, Mídia, Métricas, Sistema, Integridade, Ensino, Operação, Laboratório e compatibilidades administrativas;
- fixtures só valem quando reproduzem o DOM, os controladores e os estados relevantes da superfície real;
- screenshots da instalação real têm precedência quando revelarem um estado ausente da fixture;
- workflow verde não autoriza a expressão “aprovado visualmente” quando a superfície/estado não foi renderizada;
- regressões devem rejeitar overflow global, controles duplicados, canvas/tarefa reduzidos a faixa residual, drawer sem Escape/backdrop/foco, tabelas operacionais mobile dependentes de scroll horizontal e alvos frequentes abaixo de 40 px no telefone;
- editor visual precisa carregar em conjunto os controladores que disputam/organizam o inspector; testar módulos isoladamente não prova idempotência integrada;
- compatibilidade antiga pode redirecionar, mas não pode manter uma segunda administração operacional alcançável.

Falhas confirmadas que abrem esta revisão: duplicação assíncrona de Audiência/Acesso no editor, CSS inválido em `editor-system.css`, breakpoint intermediário frágil, hierarquia inadequada do inspector de página, drawer administrativo mobile incompleto e cobertura visual transversal insuficiente.

A especificação detalhada e o inventário ficam em `docs/ADMIN_PRODUCT_UX_UI_AUDIT_2026-09-29.md`.

## Estado da auditoria administrativa após o PR #213 — 08/10/2026

O PR #213 foi integrado em `3617fcc69579c2439869576ee518d6730df9c39f` e a `production-dist` foi regenerada a partir desse mesmo commit. Ele implementou uma primeira tranche sistêmica da auditoria administrativa: correções no editor/inspector, responsividade intermediária, drawer administrativo, IA, compatibilidade legada e novos gates de QA.

**A auditoria de UI/UX administrativa NÃO está encerrada.**

A inspeção visual humana posterior mostrou que ainda existem incongruências e superfícies fora do padrão. A cobertura automatizada criada deve ser mantida, mas não pode ser tratada como substituta da inspeção visual do runtime real.

Regras vigentes para continuidade:

- não afirmar que a interface administrativa está “aprovada visualmente” apenas porque os workflows estão verdes;
- não reduzir o próximo trabalho às áreas corrigidas pelo PR #213;
- antes de novos ajustes, produzir inventário transversal das incongruências restantes;
- diferenciar falha sistêmica de exceção legítima por domínio;
- priorizar correção em componentes/contratos compartilhados;
- validar runtime real nos quatro viewports canônicos: 390×844, 768×1024, 1280×800 e 1600×900;
- fixtures continuam úteis para regressão, mas qualquer divergência entre fixture e instalação real deve ser resolvida a favor do comportamento real;
- a próxima conversa deve usar `docs/NEXT_CHAT_HANDOFF_ADMIN_UI_UX_2026-10-08.md` como handoff operacional.

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
### Legibilidade da navegação global mobile — 10/10/2026

Nas capturas da auditoria de 46 superfícies, as legendas do menu inferior usavam 8,5 px, sem teste explícito de legibilidade em telefones estreitos. A correção centralizada proposta para `assets/student-area.css` eleva para 11 px, preservando os quatro destinos e os alvos de 66 px já existentes; a nova regressão cobre 320/390 px nos shells de aluno e Material. A matriz `docs/STUDENT_UI_STATE_MATRIX_2026-10-09.md` registra o status real. Não atribuir aprovação nem integração antes do gate visual/funcional.

**Validação corretiva N-01 (10/10):** PR #224 removeu as declarações concorrentes de `student-rendered-fixes.css`; `student-area.css` tornou-se a única autoridade, com 11 px e `var(--body,Arial,sans-serif)`. No commit `a72b2f8`, `validate`, `student-visual-audit` e `deploy` passaram. As quatro capturas efetivas, `student-shell`/`material-reader` × 320/390 px, foram examinadas e não mostraram rótulos cortados nem sobreposição visível. Esta aprovação cobre somente a navegação móvel; a auditoria da matriz de 46 superfícies, outros estados, instalação na hospedagem e regressões adicionais permanecem abertas. PR #224 aguardava integração neste registro.

### Auditoria D-06 — contextualização dos erros de dúvidas (10/10/2026)

As capturas reais mostravam erros de publicação e resposta muito acima do formulário respectivo, separados pelo extenso cabeçalho da turma. Na branch `audit/student-question-contextual-errors-2026-10-10`, os alertas ficam imediatamente antes da tarefa, com foco não tabulável; a fixture reproduz a renderização para telefone/desktop. O controlador de POST AJAX distingue erro de aplicação recebido com HTTP 200, preserva rascunho e atribui semântica acessível de alerta ao feedback. Sem mudança de autorização ou persistência. Aprovação condicionada a CI e inspeção das novas capturas; a instalação na hospedagem não está atestada.

**Achado adicional D-06, formulário AJAX:** `HTMLFormElement.action` pode ser ocultado por `input[name=action]`; o controlador comum tentava fazer `fetch` do objeto DOM, causando 404. A solução lê/escreve atributos explícitos da tag `form`, não propriedades sujeitas a colisão. Nova regressão em `student-local-actions.spec.cjs` valida erro de resposta sem perda do texto; aguarda CI e inspeção.

**D-06 — inspeção real da correção (`e0afbdd`, 10/10):** sucesso do `student-visual-audit`; as quatro capturas de erro (criação/resposta × telefone/desktop) foram observadas e comparadas com as anteriores. O erro está contextualizado, o foco não cobre o primeiro campo e o formulário preserva o rascunho. `validate`, auditoria visual e empacotamento passaram em `5720793`, já com a correção da colisão de `form.action`. A validação funcional de `e0afbdd` ainda executava no registro. Aprovação visual restrita a esses quatro estados; não equivale à auditoria global nem à instalação na hospedagem. PR #225 permanece em revisão até CI final verde.

## Implantação informada e início da auditoria de Material — 10/10/2026

**Instalação informada pelo operador:** após o merge do PR #225, o usuário confirmou no chat que instalou a versão `b3d5cf6b9adb6ffb8e97b972e67c4d442f3c8fa3`, publicada em `production-dist` (manifesto `sourceSha` idêntico, geração 2026-10-10T17:19:18+00:00). Tratar como **instalação confirmada pelo responsável**, não como login, leitura de `deploy-info.json` na hospedagem, inspeção do banco ou teste de ponta a ponta no servidor. As regressões do PR #225 e o recorte visual D-06 foram aprovados antes do merge; **a auditoria global da área do aluno permanece aberta**.

**Nova tranche M-01, em revisão:** a captura de `student-aula3-material-audit.php` provém de documento de SQLite temporário transformado pelas migrations 085/087/089/090/091, **não** do CMS publicado na hospedagem. A fixture anterior exibia apenas um fim sintético da Aula 2 e tinha documento rolando sem o shell autenticado. A nova fixture declara explicitamente a origem sintética, adiciona contexto do curso, contêiner `cms-student-reading` e barra global fiel e testa começo/meio/fim e acesso a anotações. As sete caixas de infográfico são placeholders editoriais criados por `091_practice_infographic_placeholders.php`; não transformá-las em imagens inventadas ou certificá-las como ilustrações prontas. Testes e capturas aguardam inspeção. Conteúdo real do banco hospedado, editor/página completa da Aula 2 e mídia final permanecem sem certificação.

**Correção adicional M-01 (10/10):** a inspeção real dos screenshots do CI `bc4b595` revelou captura desktop intermediária duplicando o começo da leitura, por `window.scrollTo` seguido imediatamente de screenshot sem assegurar fim do movimento (rolagem suave). O gate agora estabelece scroll instantâneo no contêiner próprio, verifica `scrollY/scrollTop` efetivo antes de capturar e rejeita testes em documento curto que não permite verificar início/meio/fim. A geração de screenshots não atesta inspeção; reinspeção do novo pacote permanece necessária.

**Inspeção efetiva M-01 (`033fe70`, 10/10):** os oito arquivos do artefato `student-visual-audit` foram extraídos e examinados: início, meio, fim e painel demonstrativo de notas aberto em 390×844 e 1440×1100. No primeiro CI (`bc4b595`) a captura do meio no desktop duplicava o início; a correção implementou scroll instantâneo e verificações de posição real. Nas imagens de `033fe70`, meio e fim desktop exibem trechos distintos; no telefone o último controle permanece acima da barra global, e o painel aberto funciona como superfície transitória. Workflow `student-visual-audit` aprovado neste SHA; `validate` ainda em conclusão no momento do registro. Esta aprovação é estritamente **visual e de geometria da fixture derivada das migrações**, não da aula completa, dos infográficos definitivos ou do banco hospedado. O pacote instalado confirmado pelo usuário continua sendo `b3d5cf6` até nova declaração de instalação.


### Galeria e captura no registro do Caderno — 10/10/2026

**Relato do usuário após instalação confirmada de `b3d5cf6`:** os controles “Fotografar ou anexar” de `aluno/teste.php` abrem apenas a câmera no telefone e não oferecem seleção de fotos da galeria. Causa encontrada no HTML real: ambos os inputs usam `capture="environment"`, e o backend processa apenas `$_FILES['image']` singular, apesar de existir o limite global `STUDENT_TEST_MEDIA_MAX_FILES=6`.

**Correção em revisão:** controles independentes **Fotografar** (captura individual opcional no navegador) e **Escolher imagens** (sem `capture`, com `multiple`) para cena e resultado. O backend normaliza e pré-valida lotes `images[]`, verifica previamente as vagas restantes das seis imagens, mantém atribuição de fase, limita cada arquivo a 12 MB e JPEG/PNG/WebP, e tenta compensar falhas intermediárias para não persistir lote parcial. Manter upload singular anterior e mutações AJAX do shell. CI, inspeção e deploy ainda pendentes. **Nenhuma nova instalação confirmada** para esta mudança.

**Verificação G-01 da interface (`f38b471`, 10/10):** no primeiro CI os uploads de duas imagens por fase e da câmera singular passaram, mas os novos `<label>` tinham aparência de texto sem borda — detectado ao **abrir** as capturas reais, não pelo status verde. A correção em `student-area.css` define estilo próprio, largura adequada ao contexto, borda, foco visível, fonte mínima de 12 px e alvo de pelo menos 48 px. A segunda bateria do `student-visual-audit` passou no commit `f38b471`; quatro screenshots efetivos foram abertos e inspecionados (`phone-scene-buttons`, `phone-result-buttons`, `desktop-scene-buttons`, `desktop-result-buttons`). Cena e resultado mostram botões distinguíveis, rótulos completos e sem interferência da navegação móvel. O teste também valida seleção múltipla, duas imagens por fase, câmera singular e preservação de `phase` via POST simulado. **Esta inspeção não substitui teste do seletor Android real nem persistência em servidor hospedado.** O `validate` completo do commit `f38b471` estava concluindo durante o registro. A última versão de hospedagem informada pelo usuário continua `b3d5cf6` até nova confirmação.

## Revisão arquitetural mobile: gavetas contextuais (10/10/2026)

Após o relato de que o seletor de arquivos abria apenas a câmera no Android, o PR #227 corrigiu o suporte de galeria e envio de múltiplas imagens, mas expôs dois controles permanentes para uma única intenção. Isso demonstrou uma lacuna da auditoria: testes funcionais e screenshots não substituem modelagem da interação mobile. Decisão estrutural: `docs/STUDENT_MOBILE_INTERACTION_ARCHITECTURE_2026-10-10.md`. Primeira aplicação S-01 = **Adicionar imagens** → gaveta de câmera/galeria, implementada com primitiva transitória compartilhada, sem alterar backend, limite de seis imagens, mídia existente, fatos experimentais ou os quatro destinos globais. A classificação de 46 superfícies é plano de UX e não aprovação visual/funcional das mesmas. A hospedagem não foi observada com esta mudança.

**Evidência inspecionada S-01 (`5d19313`, 10/10/2026):** `student-visual-audit` aprovado e seis PNGs da fixture que reutiliza `app/student_record_media_controls.php` foram extraídos e **abertos**: telefone/desktop × gaveta de cena aberta, gaveta de resultado aberta e erro no envio. No telefone (390×844), painel repousa no rodapé, respeita largura e o texto inferior, as escolhas têm área de toque distinta, a barra global permanece no shell sob o modal como exceção transitória; no desktop (1440×1100), diálogo compacto centrado e igualmente legível. O erro foi mostrado dentro do próprio painel, que permanece aberto. O browser test simula POSTs com 2 arquivos e 1 câmera, checa fase e feedback, cancelamento por Escape e botão Fechar com devolução de foco. **Não é screenshot do registro autenticado nem garantia do seletor do Android real.** O `validate` completo ainda estava concluindo no momento do registro. S-02 (criação de registro) e S-03 (restante da área) não foram implementadas nesta tranche.

**Reinspeção de paridade do Caderno — S-01 (10/10, `5837a3c`):** após detectar a fixture de Caderno com o antigo rótulo “Fotografar ou anexar”, a auditoria foi movida para `tools/browser-fixture/student-caderno-product-audit.php`, que utiliza diretamente `student_record_media_controls()` e mantém a composição de estados no template de teste. O primeiro CI desta mudança revelou que a fixture carregava uma segunda cópia de `student-rendered-fixes.css` após `student-caderno.css`, reduzindo artificialmente os alvos da etapa de 40 para 38 px. Foi corrigida a **marcação canônica da folha na fixture**, sem baixar a exigência nem introduzir CSS corretivo no produto. Em `5837a3c`, `student-visual-audit` passou e foram **abertas e inspecionadas** as imagens `complete/phone/exposure.png`, `result.png`, `process-plan.png`, `process-choice.png`, `phone/caderno-record-empty.png`, `phone/caderno-record-plan.png` e `complete/desktop/exposure.png`, além das seis capturas da gaveta aberta/erro. Todas exibem `Adicionar imagens` em vez do controle anterior e não há oclusão visual relevante nesse recorte. A certificação alcança apenas essas geometrias de fixtures, não a hospedagem nem a matriz integral. `validate` estava em execução no momento deste registro.

**S-02 — criação do Caderno (11/10/2026, revisão):** o diálogo de criação estava centralizado mesmo no telefone; no erro de POST, a mensagem aparecia fora da camada de criação, que fechava e não preservava título/data/contexto. O formulário foi extraído para `app/student_record_create_dialog.php`, consumido tanto pela rota operacional quanto por fixture de paridade. O telefone usa gaveta curta inferior; desktop mantém o diálogo compacto. O erro de criação provoca reabertura com alerta e campos preservados/escapados; falhas de duplicação/visibilidade continuam com alerta global. Controlador restaura foco ao acionador que abriu o painel; saída Escape/Fechar preserva o rascunho em memória da página. Testes PHP/Playwright de ambos contextos, estados de erro e geometria em curso. Isso não altera a operação de laboratório, API ou autorização. **Não marcar aprovado antes de CI e imagens reais.**

**Inspeção S-02 documentada (12/10/2026, commit `8997e75`):** os três workflows do PR #229 foram concluídos com sucesso neste SHA. Extraí e examinei as seis capturas `record-create/{phone,desktop}-{course-filled,server-error,personal}.png` (390×844 e 1440×1100) e os estados básicos `complete/{phone,desktop}/new-record.png`. No telefone o diálogo é gaveta inferior, com título, data, escopo/turma e ação final visíveis na viewport capturada; o erro é apresentado dentro da gaveta, contextualizado, sem suprimir os campos. Sem matrícula, só a opção Pessoal aparece. No desktop o formulário fica em diálogo compacto centralizado. Playwright verifica fechamento por Escape e Fechar com foco devolvido a ambos os acionadores, manutenção de rascunho e foco no alerta reaberto; o teste PHP verifica escape e restauração de título/data/escopo/turma. Aprovação **delimitada à aparência e ao contrato da fixture que reutiliza o renderer PHP real**, não ao POST autenticado na hospedagem nem à matriz completa de 46 superfícies. O pacote do PR #229 ainda não estava integrado na base ao produzir esta anotação.

**S-02b — Atalhos (12/10/2026, em revisão):** a fixture `student-visual-audit.html?screen=toolbox` representava uma gaveta inexistente: inseria um **Temporizador** e apenas dois campos de Exposição equivalente, enquanto `student_shell_toolbox()` renderiza dois cálculos completos de reciprocidade/exposição, mais links para ferramentas, e não inclui timer local. A auditoria estava avaliando uma superfície fictícia. `tools/browser-fixture/student-toolbox-real-audit.php` agora chama diretamente a função PHP operacional sob permissões de ferramentas sintéticas controladas, e as duas famílias de screenshot passam a usá-la. URL antiga redireciona para a renderer canônica. **Mudança de UX:** dois cálculos ficam em disclosures nativos, inicialmente recolhidos e mutuamente exclusivos, preservando os mesmos campos, resultados e o comportamento da ferramenta; o link de Processamentos duplicado foi removido e os demais destinos mantidos. Fechar/Escape deve devolver foco ao botão Atalhos. Gates ainda pendentes: verificação PHP de permissões e paridade, Playwright do formulário real com/sem permissão, screenshots do menu aberto e cálculos expandidos (telefone/desktop) e inspeção visual, além da suíte geral de 46 superfícies. Não afirmar aprovação da UI, instalação ou uso no Android até evidência respectiva.

**Falha real encontrada no primeiro CI S-02b (12/10):** os testes móveis de Atalhos expiraram ao tentar pressionar o acionador, apesar de o diálogo existir. A causa está no produto: `assets/student-rendered-fixes.css`, `@media(max-width:820px)`, continha `.student-tools-trigger{display:none}`. A fixture sintética abria o diálogo por JS e ocultava a ausência de qualquer acesso contextual no celular. Corrigida a própria regra CSS do cabeçalho para manter **Atalhos** visível e com altura mínima de 44 px, sem introduzir quinto destino global. O teste usa clique real no acionador em **320×720, 390×844 e 1440×1100**, verifica não sobreposição da marca, ausência de overflow e abertura/modal/retorno. Uma nova rodada de CI e inspeção é obrigatória; não aprovar com a primeira execução.


**S-02b — inspeção final efetiva da gaveta Atalhos (`aee23d0`, 12/10/2026):** as suítes `validate`, `student-visual-audit` e `deploy` passaram neste SHA. Foram extraídas e examinadas as 18 capturas `toolbox-real/{phone-compact,phone,desktop}-{closed,menu,reciprocity,exposure,end,restricted}.png`, incluindo a gaveta operacional, posição final rolável e permissão restrita. O estado mobile fechado, que antes escondia `Atalhos`, agora apresenta acionador utilizável no cabeçalho em 320×720 e 390×844; o item `Conta` permanece presente e separado, embora em 320 px a marca apareça truncada para acomodar as ações. O modal abre na base em telefone e como painel lateral no desktop; os dois cálculos são disclosures inicialmente recolhidos e mutuamente exclusivos, seus resultados continuam visíveis após uso, e a ação Fechar/Escape devolve foco ao acionador. A fixture real não exibe o temporizador sintético removido e filtra links conforme permissões. Também foram inspecionados mosaicos das **46 superfícies básicas por dispositivo** do mesmo artefato, como detecção de anomalias visuais de composição, sem converter esses mosaicos em aprovação de todos os estados. **Aprovação visual restrita a S-02b/Atalhos e composição básica verificada; sem atestado de uso no Android real ou instalação na hospedagem.**

**I-01 — Recuperação de cadastro no Inventário (12/10/2026, em revisão):** no PHP operacional, falha de criação apresentava mensagem global e recolhia `data-inventory-new-panel` mesmo depois de POST contendo nome, quantidade e notas. Corrigir a arquitetura de estado antes de propor gaveta: o cadastro é um formulário longo que permanece inline/expandido; o novo renderer `app/student_inventory_create_panel.php` é compartilhado entre rota e fixtures, reabre painel ao falhar, escapa/reinsere campos e radios/seleção de unidade, expande notas quando preenchidas e mostra erro focalizável **junto da tarefa**. O controlador de disclosures passa a vincular os dois acionadores (novo e primeiro item), com `aria-expanded`, e a cancelar restaurando foco. POST AJAX já preserva campos localmente; resta validar fallback sem JS, segurança e screenshots desktop/telefone. Nenhuma versão desta tranche instalada ou aprovada ainda.


**I-01 — reinspeção real da recuperação de formulário (12/10, 36bdfec):** extrai e examinei seis PNG do CI, em viewport 390×844 e 1440×1100, cobrindo rascunho, falha de POST e falha de AJAX no formulário Novo item do Inventário. A primeira geração deixava o alerta AJAX fora da viewport após envio no fim do formulário; a nova captura mostra o aviso no campo visível acima dos controles, rascunho mantido, foco programático e barra inferior intacta. A inspeção das capturas adjacentes da lista vazia/cheia e movimento também não mostrou oclusão pela navegação. A regressão de rótulos foi ajustada para seguir o renderer PHP real, sem suprimir nomes acessíveis. Aprovação estritamente visual e do contrato de fixture; ainda não comprova persistência/autorização em hospedagem e a matriz de 46 superfícies permanece aberta.

**Achado para I-02 (não corrigido em I-01):** na rota direta `/aluno/inventario-item.php`, o formulário traz `data-student-local-form data-local-refresh="inventory-stock inventory-history"`, mas a página de edição não contém esses destinos. O interceptador AJAX pode seguir o POST com sucesso e depois alegar erro por ausência das regiões locais. Aberto como investigação seguinte: reprodução de sucesso/erro em rota direta e modal, manutenção de rascunho após POST inválido, distinção entre navegação normal e salvamento parcial; não inserir segunda lógica de persistência.

**Instalação informada pelo responsável (12/10/2026):** o usuário declarou “Instalado” após publicação do pacote `44fcc2ae794481f6952eab893642be4ebb6557d7` (PR #231; `production-dist` com o mesmo `sourceSha`). Registrar como **instalação informada pelo operador**, não como acesso à hospedagem ou leitura de versão no servidor. O PR #232 encontra-se **em revisão**, ainda não instalado. No CI inicial `71f6b65`, as duas suítes falharam no teste de redirecionamento simulado: um `route.fulfill` com 303 para o endpoint protegido fazia o navegador seguir a rota real, sem o estado instalado, chegando a `/install/`. A regressão deve apontar o 303 à fixture acessível e verificar separadamente em PHP o cabeçalho real de produção `/aluno/inventario.php?saved=1`; não substituir POST por AJAX, não alterar rota real e não registrar esse ajuste de teste como correção funcional. Verificação e inspeção visual pós-CI ainda pendentes.

**I-02 — inspeção crítica do diálogo (12/10):** no screenshot operacional da fixture fiel em 1440×1100, o saldo do item ocupava uma linha horizontal que ultrapassava a moldura, e os campos encostavam no limite esquerdo do modal. A página móvel 390×844 estava utilizável. A causa é responsabilidade visual equivocada: `student-step-fixed` pertence ao editor de etapas e não possui estilo disponível no Inventário; o formulário inserido no diálogo também não é descendente direto de `.student-create-dialog>form`. Reestruturado exclusivamente o corpo do renderer compartilhado `student_inventory_item_editor`, com resumo vertical de estoque e padding contextual do diálogo por `student-lab-stock.css`. Adicionados testes de geometria e sem overflow, mantendo a página mobile normal. Novo CI e inspeção obrigatórios antes do merge; nada publicado na hospedagem por essa revisão.

**I-02 — editor direto versus modal: recorte validado (12/10/2026, `00bb36a`):** `validate`, `student-visual-audit` e `deploy` concluídos com sucesso no mesmo SHA. Foram extraídas e inspecionadas as seis capturas `inventory-item/{phone,desktop}-{direct-error,redirect-after-save}`, `phone-dedicated-page` e `desktop-editor-modal`. No telefone, a edição longa abre como página com `← Inventário`, barra global preservada e erro contextualizado com rascunho; o Fechar duplicado no cabeçalho fica oculto apenas nessa superfície, mantendo Cancelar no formulário. No desktop, a lista abre o **mesmo renderer PHP** em modal, com Fechar visível, resumo de saldo em bloco próprio e sem overflow lateral. Playwright verifica POST nativo sem XHR na rota direta, restauração de campos após erro, retorno após 303 e AJAX no modal com atualização da lista; PHP verifica escape e contrato da rota. **Sem afirmação de persistência hospedada, instalação, execução em Android ou aprovação da matriz global.**

**I-02 — paridade da auditoria global após inspeção (12/10):** imagens específicas da edição direta/mobile e modal/desktop foram efetivamente abertas. A inspeção complementar do mosaico global de Inventário identificou que a superfície `inventory-item-editor` ainda era uma página antiga sintética, com `Fechar` visível indevidamente no telefone. O mapeamento foi substituído pela fixture PHP `student-inventory-item-direct.php?mode=editor`, que reutiliza o renderer do produto, e recebeu regressão estática para evitar retorno à representação antiga. Reinspecionar a nova captura das 46 superfícies e revalidar antes do merge do PR #232. Não declarar a instalação como concluída; a última informada pelo usuário é `44fcc2`.

**I-02 — captura global corrigida e inspecionada (12/10/2026, `1dddfafe`):** o `student-visual-audit` passou com `inventory-item-editor` agora apontando ao renderer operacional `student_inventory_item_editor()` por `student-inventory-item-direct.php?mode=editor`. Extraí e comparei as seis capturas específicas da edição com as capturas globais `complete/phone/inventory-item-editor{,-middle,-end}.png` e `complete/desktop/inventory-item-editor.png`. A página mobile demonstra leitura integral até os botões Salvar alterações e Cancelar, sem o antigo Fechar redundante, e a barra inferior segue separada da região rolável; o editor desktop em diálogo mantém campos, saldo, espaçamentos e fechamento. A página direta desktop é um fallback navegável distinto do diálogo aberto pela lista. Esta é aprovação da **composição e interação representadas nas fixtures**, não de persistência hospedada ou de autenticação real. A fixture direta ainda simula o cabeçalho e não deve ser usada para certificar a globalidade do shell. O `validate` do mesmo commit ainda terminava na hora da inspeção; integração e nova instalação permanecem pendentes.


### I-03 — integridade e UX da movimentação (preflight 12/10/2026)

A análise do código operacional de Inventário, posterior à integração do PR #232, identificou que a próxima prioridade é I-03. `student_inventory_move()` calcula saldo fora de sua transação e depois grava valor absoluto, portanto chamadas concorrentes podem perder movimentações; o POST de movimento não tem identidade de solicitação, permitindo dupla baixa por retry. A apresentação por `details` expõe campos em cada item no telefone e a fixture global de movimentação não executa o PHP verdadeiro. Decisão registrada antes da implementação em `STUDENT_MOBILE_INTERACTION_ARCHITECTURE_2026-10-10.md`: movimento curto em diálogo adaptativo; ledger e atualização atômicos e idempotência de pedido manual via UUID exclusivo existente; distinção explícita entre correção e consumo. **Status canônico: implementação e evidências no PR #233; estado atual de integração e distribuição deve ser consultado no GitHub. Instalação na hospedagem só é confirmada pelo responsável.**

### Evidências específicas de I-03 — 12/10/2026

A intervenção do PR #233 remove a atualização absoluta calculada fora da transação do Inventário; o novo ledger e o delta condicional de saldo são persistidos atomicamente. Solicitações manuais recebem identificador idempotente, com colisões não equivalentes rejeitadas. A exclusão histórica de uma etapa do Caderno preserva seu estorno mesmo se o item foi posteriormente arquivado, mas um POST manual não pode movimentar item arquivado. Na interface, a movimentação usa o renderer PHP contextual no diálogo desktop/gaveta mobile, com POST nativo, recuperação de rascunhos e foco; a página mobile mostra o primeiro item antes da navegação secundária para Predefinições. CI `validate`, `student-visual-audit` e `deploy` são gates obrigatórios; os resultados correntes e a situação de merge devem ser consultados no GitHub (PR #233). Houve inspeção humana das imagens produzidas por fixtures reais do renderer, sem teste autenticado em hospedagem nem confirmação de instalação. O registro das 46 superfícies continua parcialmente auditado e não constitui aprovação irrestrita de todos os estados.


### P-01 — continuidade de editores longos em Predefinições e Calibração (12/10/2026; decisão antes do código)

Auditoria do PHP: `aluno/preparos.php` e `aluno/calibracao.php` mantêm formulários de muitos campos dentro de `<dialog open>` também em URL direta. O estado aberto não é uma página contextual de edição, e em falha de POST o alerta aparece fora do editor e o formulário usa os dados persistidos, não o rascunho submetido. A camada compartilhada de AJAX tenta atualizar `preparations-list` / `calibrations-list` ausentes quando essas URLs diretas são editadas isoladamente, podendo reportar erro após gravação. Trata-se da mesma classe sistêmica corrigida em I-02 no Inventário.

**Decisão de produto:** nas duas famílias, o editor extenso é **página dedicada no telefone**, com URL real (`?novo=1` / `?editar=ID`), cabeçalho de retorno explícito, formulário preenchido após erro, e POST/redirect nativo. No desktop, iniciar criação/edição na listagem abre um modal contextual por `fetch` do **mesmo renderer PHP operacional**; a rota direta continua válida sem depender do modal. Não duplicar formulários nem criar versões sintéticas em fixtures. O botão Fechar aparece no modal; na rota, Voltar e Cancelar substituem Fechar para não repetir ação. A listagem permanece fora da superfície dedicada no telefone. Listagem, CRUD, CSRF, autorização, valor dos dados e distinção entre parâmetros e estoque não mudam.

**Gates:** PHP para criação/edição/erros com rascunho escapado, Playwright de 390×844, 1440×1100 e 320×720 (navegação real mobile, modal desktop, Escape, Back/Forward, POST invalido/sucesso, erro AJAX no modal, rolagem e foco), screenshots de estados normais e falha, abertura humana das imagens e da matriz de 46 composições básicas, três workflows verdes antes de merge. Nenhum teste de fixture equivale a login ou persistência hospedada. **Ainda não aprovado.**

**P-01 — inspeção visual inicial reprovada (12/10/2026):** o primeiro artefato da auditoria (run `38302503049`) foi extraído e imagens individuais de ambos os modais desktop abertas. Os campos tocavam as bordas do diálogo porque `.student-create-dialog>form` só oferece padding quando o formulário é filho direto, enquanto o renderer novo mantém `form` dentro da seção operacional. Também foi observado `Outro revelador` aparecendo sob Parodinal: regras de exibição da classe `.form-field` ultrapassavam o `hidden` sem owner explícito. O owner do editor compartilhado define o espaçamento do conteúdo do modal; `student-workbench.css` restabelece o respeito ao atributo `hidden` no campo condicional. Regressões geométricas de margens/overflow e visibilidade ao mudar o revelador foram adicionadas. **Primeiras imagens não aprovadas; exigir CI e reinspeção efetiva da correção.**

**P-01 — retificação do diagnóstico de ingredientes (12/10/2026):** a leitura completa de `app/student_auxiliary_ux.php` confirmou que `student_saved_preparation_update_guided()` já normalizava os quatro ingredientes do Caffenol. Portanto não havia a omissão alegada inicialmente no update. A falha arquitetural era a duplicação entre o fluxo de criação no controller e o helper de atualização. A revisão consolidou ambos em `student_preparation_normalize_input()` em `app/student_workbench.php`, com chamada comum nos caminhos guiados de criação e atualização e teste explícito dos ingredientes. O campo `fresh_volume` agora também conserva seu próprio valor no editor após falha, sem substituí-lo pelo volume persistido anterior. Isto é refinamento do contrato existente, não movimento de estoque físico.


### P-01 — aprovação da tranche após reinspeção (12/10/2026)

**Implementação revisada no SHA `45522248acb2138b578e035e83daa3ebe9df1ed3` (PR #235):** as operações de criação e edição de Predefinições e de Calibração usam o mesmo renderer PHP para as rotas navegáveis `?novo=1`/`?editar=ID` e para o modal contextual desktop. A rota dedicada executa POST/303 nativo, recupera rascunho e foca o erro; o modal mantém AJAX com atualizações da lista real, restaura o foco do acionador após Fechar/Escape e evita falso erro de região ausente na URL direta. Caffenol concentra normalização de ingredientes em função de domínio compartilhada, sem alterar estoque.

**Validação final:** `validate` run `38304926954`, `student-visual-audit` run `38304926986` e `deploy` run `38304927030` concluíram com **success** no mesmo SHA. O ZIP `student-visual-audit-f36b0b5278eba9ceb7acc39de30188570c57c305` (artefato `11772317065`) foi baixado e extraído. **Imagens efetivamente abertas:** `long-editors/preparation-desktop-modal.png`, `calibration-desktop-modal.png`, `preparation-compact-last-action.png`; prancha de 12 estados operacionais incluindo página mobile, falha, rolagem compacta, modal e erro nas duas famílias; mosaicos de 46 superfícies-base mobile e 46 desktop. Os recuos internos dos modais, o `hidden` de Outro revelador, os botões finais e os quatro destinos móveis foram reexaminados sem os defeitos da primeira inspeção. Os testes Playwright específicos cobrem criação/edição, POST/303 nativo e AJAX, erro mantendo rascunho, Escape, Back/Forward, foco, ausência de overflow e 390×844, 320×720 e 1440×1100.

**Escopo da aprovação:** P-01 em **fixture PHP com renderer operacional e POST simulado**, teste PHP de contratos e suíte de navegador. Não foram demonstrados autenticação e persistência reais na hospedagem; não é uma aprovação de todos os estados das 46 superfícies da Área do aluno. O workflow `deploy` de PR valida o pacote, não comprova publicação da branch `production-dist` após o merge nem instalação no servidor. O estado de integração deve ser conferido no GitHub.

**Complemento do gate P-01 — persistência SQLite (12/10):** foi incluído `tools/test-student-long-editors-db.php` na validação e no deploy para exercitar os helpers operacionais com SQLite real: criação e atualização de Caffenol (volume preparado, ingredientes, quantidades), tentativa de edição por outro estudante, rejeição de nome vazio e criação/atualização/autorização de calibração. O teste não autentica uma sessão web e não acessa a hospedagem; seus resultados dependem dos workflows do commit que o integra.


## Frente Agenda — implementação vinculada à especificação #234 (12/10/2026)

A issue #234 é contrato de aceite, **não PR de implementação**. A branch `feature/course-cohort-agenda-auditors-2026-10-12` implementa uma frente própria; este registro não equivale a merge, distribuição ou instalação na hospedagem. O editor operacional é `admin/lessons.php?course=...&cohort=...`, sem cadastro paralelo de agenda: horário, fuso, duração, acesso e liberação são configurados na mesma ficha de `course_lessons` por turma. A Área do aluno recebe `/aluno/agenda.php` como rota contextual de **Cursos**, preservando os quatro destinos globais.

`migrations/098_course_cohort_agenda.php` estende `cohort_lesson_releases` sem modificar `released_at` e cria `course_listener_reservations` separado de matrículas. `app/course_agenda.php` contém projeção de encontros, contagem de pessoas distintas, verificação de curso e reserva transacional. O gate `tools/test-course-agenda.php` cobre SQLite, vagas, espera, convocação, acesso e independência da liberação. **Status de validação e de integração devem ser verificados nos workflows e no PR**, não inferidos desta anotação. Persistem as limitações conhecidas de fixtures versus navegação autenticada real e de distribuição versus instalação.

**Auditoria visual específica da Agenda:** além da view operacional compartilhada do aluno (`app/student_agenda_view.php`), a ficha única de Aulas na Turma foi separada em `app/admin_course_agenda_cohort.php` para ser renderizada tanto pela rota administrativa quanto pela fixture `tools/browser-fixture/admin-course-agenda.php`. `tools/browser-tests/admin-course-agenda.spec.cjs` verifica os três encontros, seus estados de programação/link, um editor aberto, os campos e o POST nativo em 320, 390 e 1440 px. A fixture utiliza dados controlados, não sessão administrativa autenticada nem hospedagem. A prova visual só é considerada concluída após execução verde e inspeção efetiva das imagens geradas.

## Início mobile-first — correção de hierarquia e continuidade (12/10/2026, PR #238)

A home anterior era um redirecionador com destaque mutuamente exclusivo, omitindo a Agenda. O contrato atualizado em `docs/STUDENT_AREA_EXPERIENCE_CANONICAL_2026-09-30.md` exige visão operacional simultânea: próximo encontro efetivamente agendado e autorizado (URL somente http(s)), feedback quando existir, material disponível por turma, Caderno e ações rápidas. Nenhum progresso, presença ou leitura é inferido. A nova Home chama o domínio existente, compartilha o renderer PHP com a auditoria e preserva a barra de Início/Cursos/Caderno/Laboratório.

A criação de registro via Home não depende da página intermediária de Caderno: usa a gaveta e o serviço de criação canônicos, com `POST /aluno/`, CSRF, redirecionamento à ficha no sucesso e recuperação do rascunho e erro na própria gaveta em falha. Ações de Laboratório permanecem condicionadas às permissões. O atalho de Processamentos deve abrir sua biblioteca explicitamente, sem fingir que `/aluno/temporizador.php` executa um temporizador independente.

A auditoria `H-01` na matriz `docs/STUDENT_UI_STATE_MATRIX_2026-10-09.md` registra defeitos **efetivamente vistos** e correções realizadas. PR e screenshots de fixture não autorizam afirmar instalação da hospedagem, sessão autenticada testada ou cobertura completa dos 46 estados. PWA instalável permanece projeto separado, com políticas de segurança/caching de páginas privadas e sessão; não habilitar cache offline indiscriminadamente.


**Revisão da transição temporal da Home (12/10/2026, PR #238):** uma auditoria pós-renderização detectou que o filtro `meeting_starts_at >= now` removia um encontro da Home um minuto depois do início mesmo durante a duração registrada. A projeção agora o mantém no intervalo **explicitamente programado** e identifica “Horário previsto do encontro”, sem inferir realização, presença ou duração ausente. Regressões exercitam início, fim, ausência de duração e estado concluído. Capturas da gaveta Pessoal/Curso desabilitam transições CSS para evidência não ambígua; em 320 px o teste alcança o botão final após rolagem interna. A aprovação depende da reinspeção do SHA com CI verde.

**Paridade de contexto de múltiplas matrículas (12/10, PR #238):** atalhos de Material na Home devem identificar curso/turma quando o aluno possui mais de uma matrícula. Títulos iguais não são identificadores exclusivos; o caminho direto mantém `cohort`, e a linha de continuidade exibe o contexto apenas quando há ambiguidade possível. Há regressão Playwright no cenário `multiple`.

**Auditoria da gaveta após três CI verdes (12/10, PR #238):** o screenshot do botão final em 320×720 revelou que o teste aceitava botão só parcialmente dentro da viewport. Portanto a inspeção reprovou o recorte mesmo com workflows verdes. A regressão foi corrigida para rolar o `dialog` até o fim e exigir toda a área de toque mais margem inferior; o scroll container recebe reserva pelo seu proprietário CSS. A correção requer reinspeção do novo artefato; não usar o CI anterior como aprovação.


## Agenda e mobile — organização temporal e atalhos (12/10/2026, PR #242, aguardando validação final)

A Agenda deve destacar encontros agendados futuros e aqueles dentro de duração expressamente cadastrada, com ordenação crescente. Encontros anteriores, concluídos e cancelados permanecem acessíveis em histórico recolhido, ordenado do mais recente ao mais antigo; não apresentar acesso ativo por URL no histórico. O cadastro operacional da aula e as regras de reserva e liberação permanecem os mesmos. URLs exibidas como link de acesso devem ser HTTPS válidas. A Home oferece Agenda como primeiro atalho contextual quando há matrícula, e a gaveta geral Atalhos mantém Agenda como primeira rota. A fixture visual da Agenda usa a gaveta canônica, não um substituto vazio. Regressões precisam cobrir estado misto e histórico, além de 320/390/1440 px, antes de merge.


**Reprovação de evidência visual PR #242 (13/10/2026):** capturas `agenda/regular-*-start.png` do SHA `8bf7c319` eram feitas após abrir o cronograma e rolar a página; portanto não demonstravam a primeira dobra real, apesar do workflow verde. A regressão da fixture foi alterada para restaurar ambos os scroll containers antes de fotografar o início, produzir capturas independentes dos detalhes expandidos e verificar tema claro/escuro em estado misto. O gate exige CI do novo SHA e inspeção dessas capturas antes de aprovação.


## Retificação vinculante do redesign mobile-first — 15/10/2026

O autor esclareceu o escopo após a publicação do PR #243: **reorganizar UI/UX por ações e atividades não autoriza alterar a identidade visual já definida**. A reconstrução deve preservar tipografia editorial (tokens `--title`, `--body`, `--mono`), paleta/tema e controles de Design do CMS, primitives `ui-core.css`, e os proprietários CSS existentes. Novo CSS somente para geometria/comportamento específico das novas composições, sem duplicar botões, cards, tipografia, paletas ou componentes compartilhados. O registro histórico de 13/10 abaixo não constitui autorização para substituir o design system. Este escopo substitui especificamente a frase histórica sobre não ficar preso aos contratos visuais anteriores; a navegação e a organização orientada às tarefas permanecem aprovadas.

Correção em branch isolada `fix/student-ui-respect-design-system-2026-10-15` (PR #245 draft); não publicar em `production-dist` antes de CI, avaliação visual e integração autorizada. A primeira tranche removeu a paleta/sobrescrita de tokens do protótipo, voltou aos headings e botões compartilhados e consolidou o CSS de geometria do shell em `assets/student-area.css`, eliminando `assets/student-app-shell.css` como autoridade paralela. Os arquivos de feature restantes não criam novos tokens de identidade. A publicação anterior de `557b7b3e` foi confirmada no canal de atualização, **não** comprova instalação na hospedagem.

## Reconstrução da Área do aluno — shell e Início mobile-first v2 (13/10/2026; branch isolada)

Decisão explícita do autor: reconstruir a UX sem ficar preso aos contratos visuais anteriores. A arquitetura adotada no protótipo aprovado tem **Início / Agenda / Cursos / Caderno / Laboratório**. Na branch `feat/student-mobile-first-rebuild-2026-10-13`, a primeira tranche modifica o shell PHP global, adiciona ícones e barra inferior em cinco colunas, navegação lateral no desktop e altera a projeção visual de Início com dados reais existentes (reunião autorizada, materiais, registros e retorno). Mantém CSRF e fluxo de criação de registro, autorização de aulas, gravações e edição das outras áreas como estão.

A navegação do leitor CMS autenticado também consome os mesmos cinco links (via `student_shell_nav()`); o contrato agora mapeia `agenda.php` para contexto global próprio. Não inferir que a entrega está pronta somente porque esses links existem: a migração de Agenda/Cursos/Caderno/Laboratório e a inspeção em navegador/instalação são fases subsequentes. CSS novo em `assets/student-app-shell.css` pertence à composição do novo shell e Home; a limpeza do CSS legado das telas filhas deve ocorrer por migração de responsabilidade, não patch cronológico. É proibido merge antes dos gates do SHA final, screenshots de 320/390/desktop, Back/Forward, estados vazio/múltiplo, tema e controle de foco.


## Área do aluno — Cursos mobile-first v2 (13/10/2026; PR #243, não publicado)

Na branch `feat/student-mobile-first-rebuild-2026-10-13`, a reconstrução migrou Home e Agenda e agora Cursos. A rota `aluno/cursos.php` preserva as consultas e escopos de matrículas, material por turma, status de liberação e retornos do professor; a apresentação é `app/student_courses_view.php`, com CSS canônico `assets/student-courses.css`. Há estados de ausência de matrícula, seleção explícita para várias matrículas, workspace direto para matrícula única, páginas CMS com estados reais, retornos contextualizados e liberações recolhidas. Regressões de fonte e navegador seguem o renderer real e os mesmos links por `cohort_uuid`. Inspeção visual local em 320/390/1440 sem overflow. **Não é aprovação para merge nem validação em hospedagem**: CI do SHA final e fluxo autenticado ainda são gates; Caderno e Laboratório mantêm sua apresentação anterior. Especificação em `docs/STUDENT_COURSES_MOBILE_FIRST_V2_2026-10-13.md`.


## Área do aluno — Caderno mobile-first v2 (13/10/2026; PR #243, não publicado)

A coleção do Caderno foi reconstruída com `app/student_notebook_view.php`, a mesma rota `aluno/caderno.php` e CSS restrito à coleção em `assets/student-caderno.css`. Registros mantêm conteúdo não linear (exposição/processamento/resultado), contexto de turma ou pessoal e controles secundários de comparar, duplicar, partilhar e excluir. A criação continua com o diálogo compartilhado e validação/CSRF existentes. A comparação temporária fica depois de Novo registro inclusive na ordem de foco. Há fixture PHP com o mesmo renderer e testes de tela em 320/390/1440, além da auditoria de rotas de edição anterior. Não afirmar que o Laboratório ou os editores detalhados foram redesenhados nesta tranche. Documento: `docs/STUDENT_NOTEBOOK_MOBILE_FIRST_V2_2026-10-13.md`. Gate de CI e produto segue obrigatório para merge.


## Área do aluno — Laboratório mobile-first v2 (13/10/2026; PR #243, não publicado)

A entrada do Laboratório é reconstruída no renderer `app/student_laboratory_view.php`, com autorização e receitas derivadas da rota `aluno/ferramentas.php` e CSS canônico `assets/student-laboratory.css`. Operações (processamentos, inventário, preparos, calibração) precedem calculadoras (reciprocidade, exposição equivalente, receitas); os cálculos são disclosure nativo mantendo data-attributes e serviços existentes, e anchors legadas revelam a seção correta. Fixture PHP do mesmo renderer e testes em 320/390/1440 cobrem escopos de autorização, acessos, cálculo e estados sem ferramentas. É somente a entrada, não os editores internos. Critérios de liberação em `docs/STUDENT_LABORATORY_MOBILE_FIRST_V2_2026-10-13.md`; não fazer merge enquanto falta CI e auditoria de fluxo real.

## Auditoria transversal mobile-first v2 — 13/10/2026 (PR #243, em revisão)

O SHA `dd6ade4` concluiu `student-visual-audit` e `deploy` com sucesso, mas `validate` falhou em 3 de 884 testes por falsa renderização de Dúvidas no percurso: a navegação simulava a URL canônica e entregava por engano a tela padrão de Perfil. A tranche posterior corrige a seleção de lista/conversa na fixture, testa títulos, links de turma, conversa, Back/Forward e acesso direto, e integra capturas dos percursos no workflow visual. O código de negócio e a autenticação da rota real `aluno/duvidas.php` não foram alterados. Não confundir os testes com login real, POST SQLite ou avaliação do CMS hospedado. Reexecutar todos os gates e abrir as imagens do SHA novo antes de considerar esta tranche verificada; PR permanece draft, sem merge ou atualização da hospedagem.

**Paridade Dúvidas — sequência da auditoria transversal do PR #243:** a inspeção de screenshots anteriores identificou cabeçalho desktop legado na fixture sintética de Dúvidas. O corpo de produção foi extraído para `app/student_questions_view.php` e reutilizado por uma fixture PHP com `student_shell_start/end` reais e dados de teste controlados. O contrato do domínio e o endpoint autenticado permanecem inalterados, com testes fonte adaptados ao renderer extraído. As capturas da auditoria básica de Dúvidas passam a vir desse renderer. É obrigatório reinspecionar imagens e CI do SHA final; testes de fixture não comprovam login e persistência na hospedagem.

**Dúvidas — ampliação das capturas e dos percursos no PR #243:** após inspeção das capturas de lista/conversa com renderer PHP e shell canônico, migrou-se a bateria ampliada de formulário, falha de criação/resposta, lista longa, estados vazios, visibilidade e registro relacionado para a mesma fixture PHP compartilhada, eliminando a divergência de HTML interno. O teste PHP transversal de ações locais passou a ler rota mais renderer. Necessários novo `validate`, auditoria visual e inspeção de imagens do SHA resultante; não há comprovação de login ou POST no servidor hospedado.

**Laboratório → Inventário no PR #243 (tranche em validação):** a auditoria encontrou fixture de movimentação com barra antiga de quatro destinos, apesar do shell real de cinco. Substituído o invólucro inventado pelo `student_shell_start/end` canônico sem alterar `student_inventory_move_panel` ou qualquer regra de estoque. A suíte transversal verifica navegação real a partir do Laboratório, área ativa, histórico e retorno em 320/390/1440 px. Screenshots e workflows do SHA da mudança precisam ser examinados antes de aprovação; demais telas internas de laboratório permanecem abertas.

**Caderno — extensão do percurso PHP no PR #243:** a passagem Agenda → Caderno agora verifica, na gaveta real compartilhada, preservação de título/turma ao fechar/reabrir, foco devolvido, ausência de poluição no histórico e menu contextual por registro (comparação, duplicação, visibilidade e exclusão). Persistência, autorização e confirmação destrutiva precisam de testes operacionais separados; esta evidência é de navegação e interação de fixture. Imagens e gates do SHA correspondente permanecem obrigatórios.

**Inspeção de movimentação com shell canônico:** screenshot do CI `b0ba3b3` aberto, título do diálogo cabe em duas linhas a 390 px, botão Fechar compacto e dentro do painel. A falha de teste que exigia cabeçalho abaixo de 108 px era uma métrica indevida para um título que pode quebrar. Substituída por verificação geométrica de ausência de colisão e contenção; o limite compacto do botão permanece. Exigir novo CI e reinspeção no último SHA.

**Regressão de shell/fixtures de Predefinições e Calibração:** ao extrair shell real, a fixture redeclarou `student_tools_for_student()` além de `student_workbench.php` e passou a devolver erro de PHP, exibindo páginas vazias. Corrigida a redefinição, substituída por schema SQLite mínimo apenas da fixture, mantendo a implementação real da função. Auditoria de telas agora rejeita respostas diferentes de 200 e ausência de formulário nessas superfícies. Sem efeito sobre permissões reais; aguardar resultados do SHA novo.

**13/10/2026 — Revisão de qualidade visual e hierarquia dos editores laboratoriais (tranche pendente de CI):** a inspeção do artefato `6640e9d` constatou apresentação administrativa dos formulários mesmo com shell novo. Calibração agora prioriza o tempo do branco, nome e condições experimentais expansíveis. Predefinições agrupa identificação do banho, composição/volume, tempo/temperatura e agitação opcional. Todos os controles operacionais, validação e POST continuam no renderer compartilhado. `student-lab-stock.css` responde pela apresentação contextual e `student-app-shell.css` reserva a trilha de scroll real nos diálogos desktop. Testes de navegador agora devem provar ordem e geometria a 320/390 px, comportamento de disclosure e recuperação de erro, além de validar o domínio. Não considerar completa sem screenshots e workflows do SHA final.

**Revisão task-first do Laboratório — correção de regressão de seletor:** a nova regra `display:grid` fez o label `hidden` de Outro revelador aparecer. Corrigida precedência de visibilidade na classe de componente, sem remover testes. Detectada também instabilidade de `select.value` ao reparentar options em optgroups; `student-process-ux.js` agora mantém explicitamente a seleção. Testes de 320/390 px exigem Parodinal inicial, campo customizado oculto, alternância Outro e retorno. Capturas do primeiro SHA não são aprovação. Aguardar novo SHA.

**13/10/2026 — Escopo correto do redesign v2:** a objeção de UX envolve todo o sistema autenticado, não apenas Caderno e Laboratório. Criado `docs/STUDENT_PRODUCT_WIDE_UX_REVIEW_2026-10-13.md` com auditoria transversal de capturas básicas e critérios de aceite para Início, Agenda, Cursos, Material, Dúvidas, Caderno/Processamento/Resultado, Laboratório, Inventário, editores, Conta e overlays. Primeira implementação pontual em `aluno/teste.php` reorganiza três contextos independentes por hash nativo e preserva toda a lógica de servidor. **Não declarar o produto aprovado por uma tranche ou por CI verde.** Manter PR #243 draft até a evidência transversal, incluindo telas filhas e sessão autenticada.

**13/10/2026 — Processamentos, editor de etapas task-first (PR #243, não publicado):** extraído `student_process_render_step_form` para componente único PHP que a rota real e a fixture visual compartilham. Tipos de etapa, revelador/predefinição, tempo/temperatura são controles de primeiro plano; composição, agitação e observações passam a grupos expansíveis (abertos se preenchidos). O JS continua enviando apenas grupos aplicáveis, com revelador personalizado realmente condicional e valor preservado. Adicionadas regressões PHP e Playwright na pipeline; aguardar CI/inspeção final, sem transferir autorização ou cálculo ao browser. Isto é parte da revisão transversal documentada em `docs/STUDENT_PRODUCT_WIDE_UX_REVIEW_2026-10-13.md`, não sua conclusão.

**13/10/2026 — Conta task-first (PR #243, ainda não publicado):** o perfil saiu da composição de cadastro longo e ganhou um renderer único `app/student_profile_view.php` compartilhado com fixture PHP que carrega shell real. Identificação obrigatória (nome/e-mail/CPF) em evidência; contato e endereço opcionais em disclosures reversíveis. POST conserva CSRF, autorização, updates, rotas de senha e logout; falha mantém valores enviados pelo usuário e expande os controles para recuperação. CSS isolado em `assets/student-profile.css`. Suíte `student-profile.spec.cjs` e `test-student-profile-view.php` passam a cobrir a experiência. **Pendente:** aprovação visual e CI na mesma SHA e auditoria autenticada. As antigas capturas de perfil de `student-secondary-screens-audit.html` são legado e não servem para homologar esse novo estado.

**13/10/2026 — Scrollbar do shell mobile:** a região `.student-main` tinha `scrollbar-gutter:stable` no desktop, mas voltava a `auto` no mobile. Corrigida a causa na folha proprietária `student-app-shell.css`: gutter estável nas duas apresentações, trilho fino, sem esconder overflow. Testes de Registro, Processamentos e Conta passaram a exigir propriedade efetiva `stable` em navegador e afastamento geométrico do conteúdo. A tarefa permanece em PR #243 draft, sem publicar a hospedagem.

**13/10/2026 — Biblioteca de Processamentos task-first:** as ações de manutenção por roteiro foram agrupadas em disclosure nativo, preservando o acesso principal ao runner e os POSTs originais. O renderer de cartão salvo foi extraído para `app/student_process_saved_card.php`, compartilhado entre rota real e fixture PHP fiel ao shell. Telas de associação só apresentam "Associar"; roteiros sem etapas continuam não executáveis. Testes PHP/Playwright e capturas novas incorporados ao CI. PR #243 permanece draft sem publicar a hospedagem; homologação condicionada à inspeção visual final.

**13/10/2026 — Origem de CSS legado removida no Laboratório:** screenshot de menu de roteiro em 390px expôs botões de 8px. `student-quality-pass.css` ainda redefinia ações de Processamentos e vencia a feature. Removida a propriedade duplicada e consolidada UI contextual em `student-processes.css` (controles 44px, 13px, ação principal em linha própria); testes agora medem estilos reais. Pendente nova auditoria visual completa.
\n**13/10/2026 — Incidente real de remanejamento:** investigação de matrícula/Agenda revelou que admin/students.php só movimentava inscrições associadas a `source_submission_id`; estudantes manuais/CSV apenas podiam ser desativados. Criado o caminho de transferência manual atômica em `app/admin_student_cohort_transfer.php` com regras canônicas de curso/turma/vagas e reconciliação da Agenda; UI administrativa expõe ação unificada de `Mudar de turma`. Testes SQLite/Agenda/rollback de matrícula e redirecionamento incluídos. Sem merge, hospedagem ou alteração de aluno real.\n
### Inscrições pagas: atribuição isolada e Agenda — 13/10/2026 (PR #243, sem merge)

Auditoria de `course_registration_assign_cohort`: a reconciliação de todas as inscrições de uma mesma activity durante um único remanejamento tinha efeitos laterais em matrículas de terceiros; o destino aceitava turma `closed`; `ON CONFLICT` podia reescrever a proveniência de matrícula manual ou de outra submissão. O serviço passa a exigir turma ativa, detectar conflitos de origem, reconciliar apenas o ID operado, verificar matrícula efetiva quando a inscrição é confirmada e operar sob lock transacional da Agenda. `course_registration_unassign_cohort` passa a usar lock e transação; `admin/registrations.php` deixa de executar nova reconciliação global após atribuir/desvincular. Os fluxos de confirmação de pagamento foram posteriormente corrigidos nesta branch para operar transacionalmente apenas a inscrição selecionada. Regressões SQLite em `tools/test-course-registration-transfer.php`: paga com/sem turma, isolamento, capacidade, rollback, ouvintes, Agenda, proveniência, destino inválido, pendente, idempotência e desvinculação. Esta documentação refere-se à branch draft, não à hospedagem.

**Fechamento de caminhos secundários da atribuição:** `student_enrollment_install_reconciliation_hook` ignora atribuição/desvinculação na rota de inscrições, porque a rota já processa a submissão pontualmente; `student_account_reconcile_confirmed_registrations` delega ao serviço canônico quando o domínio de Curso está instalado, evitando que login/ativação atribuam uma turma padrão a inscrição paga ainda sem turma. O fallback sem domínio `courses` permanece por compatibilidade. Teste de autoridade incluído em `test-course-registration-transfer.php`.

**Regressão adicional da reconciliação financeira:** a passagem de uma inscrição previamente confirmada para pagamento pendente desativa sua matrícula e reprocessa a lista de ouvintes da turma liberada; o teste SQLite reproduz a promoção do primeiro elegível sem reatribuir matrículas alheias.

**Redirecionamento do operador:** ao atribuir turma pelo painel Inscrições, a navegação segue a turma de destino com filtro de inscritos; ao desvincular, limpa o filtro de turma. Em caso de falha preserva o contexto original. O mesmo fluxo no painel de Alunos já redirecionava ao destino.

### Consistência financeira no painel Inscrições — 13/10/2026 (PR #243 draft)

Confirmar pagamento, marcar pendente e cancelar inscrição eram escritas imediatas seguidas de reconciliação em lote cujos erros eram ignorados, permitindo mensagem de sucesso mesmo com matrícula recusada. `course_registration_change_payment` agora mantém pagamento e matrícula na mesma transação sob lock SQLite da Agenda, reconcilia apenas a inscrição operada, desfaz o pagamento em caso de falta de vagas, conflito de origem ou erro e preserva o estado pago sem turma. `admin/registrations.php` usa os serviços canônicos sem reconciliação global adicional; o hook de shutdown não atua nessa rota. `admin_registration_delete` exclui matrícula de origem/inscrição e atualiza fila e capacidade numa mesma transação, sem apagar conta e registros independentes. Regressões de pagamento, rollback, ouvinte, proveniência e exclusão incorporadas em `tools/test-course-registration-transfer.php`. Sem instalação ou teste autenticado da hospedagem.

**Alinhamento de regressão administrativa:** o teste `test-task-centric-admin.php` deixa de exigir `payment_status='paid'` diretamente no controller; exige botão primário de confirmação ligado a `course_registration_change_payment()` e validação do estado persistido no serviço canônico. A verificação operacional da transação permanece em `test-course-registration-transfer.php`.

**Contrato de paridade da inscrição:** `test-registration-google-form-parity.php` verifica agora a presença do CTA de confirmação no painel e a operação financeira no serviço canônico de Curso, mantendo CMS como origem dos dados Pix/cartão. Não exige SQL financeiro no controller; a regressão transacional segue coberta em SQLite.

**Compatibilidade de exclusão com schema legado:** `admin_registration_delete()` usa `s.*` e acessa `student_id`/`cohort_id` apenas quando presentes; a fixture histórica `test-admin-lifecycle-and-student-design.php` não contém essas colunas e deve continuar validando a exclusão. Em schemas atuais, os vínculos informam a reconciliação da Agenda; a alteração não cria colunas nem exige migração para apenas excluir inscrição legada.

**13/10/2026 — Fonte das capturas da auditoria transversal (PR #243 draft):** inventariadas 46 superfícies, com 20 origens PHP e 26 HTML. Quatro estados migrados para renderizadores PHP operacionais (curso com atenção, Dúvidas com avaliação, biblioteca de Processamentos e Conta), sem injeção corretiva de CSS e com checagem de cinco destinos, shell e scroll. Restam 22 entradas HTML a revisar; resultados de fixtures não substituem homologação autenticada. Sem merge/hospedagem.

**13/10/2026 — Renderer canônico de autenticação no PR #243:** o HTML de `aluno/login.php` e `aluno/senha.php` foi extraído sem alterar autenticação, validação, POST ou CSRF, para `app/student_auth_view.php`. `student-auth-operational.php` usa esse renderer e o shell real com estados controlados, não credenciais/dados de produção. O próximo commit integrará a auditoria completa, o teste e a contagem de fontes operacionais; a homologação autenticada continua pendente.

**Compatibilidade da suíte de autenticação:** `test-student-account-experience.php` e `test-student-login-experience.php` agora verificam conjuntamente a rota PHP e o renderer operacional de formulários, sem exigir que apresentação e autenticação permaneçam no mesmo arquivo. A ordem, acessibilidade, CSRF, retorno a Conta e distinção entre acesso/login não mudaram.

**Paridade da validação global da UI:** `test-student-premium-ui.php` consulta tanto as rotas de login/senha quanto `student_auth_view.php`; mantém a exigência de `data-ui-validate` e botões globais sem impor HTML duplicado na rota. A regra de validação efetiva permanece a mesma.

**Paridade de primitivas globais na autenticação:** `test-global-ui-consumption.php` passa a verificar `form-field`, botões e `data-ui-validate` na composição da rota com o renderer partilhado `student_auth_view.php`, preservando o mesmo contrato usado para perfil/Caderno, em vez de exigir markup duplicado na rota de autenticação.

**Isolamento da verificação CSRF de senha:** a suíte de navegador do formulário compartilhado inicialmente contava os tokens de todos os formulários do documento e encontrou também o token legítimo da ação de logout do shell autenticado. O teste foi corrigido para contar exclusivamente o token de `student-login-card form` na alteração de senha (e dos dois formulários de acesso em `student-auth-grid` no login). Não remover CSRF legítimo nem suprimir a ação de logout para satisfazer a fixture.

**14/10/2026 — Laboratório, listas operacionais na auditoria transversal do PR #243:** as apresentações de `aluno/preparos.php` e `aluno/calibracao.php` foram isoladas em `app/student_lab_catalog_views.php`, para reuso exato entre a rota autorizada e a fixture visual. Autorização de ferramenta, consulta por aluno, POST/CSRF, gravações e redirecionamentos continuam exclusivamente nas rotas existentes. Os dois catálogos preservam edição dedicada, listas vazias, exclusão de predefinições e vínculos contextuais. A tranche exige migrar a fixture, confrontar os testes de consumo dos renderizadores, executar os quatro gates e inspecionar screenshots. A branch segue draft e a hospedagem não foi atualizada.

**14/10/2026 — Regressão de bibliotecas compartilhadas:** as fixtures de Predefinições/Calibração usam o renderer de produção para editor, listas com conteúdo e vazio, sem construir uma biblioteca HTML alternativa. Smoke PHP `test-student-lab-catalog-views.php` protege escape, formulário CSRF, navegação, vazio e a autoridade das rotas; Playwright testa 320/390/1440, navegação de cinco destinos, geometria e vazios. A documentação de auditoria contabiliza provisoriamente 29 fontes PHP/17 HTML em 46 estados. Falta validar no SHA final e inspecionar capturas; PR draft e nenhuma instalação.

**14/10/2026 — Regressão de contratos de laboratório após extração:** `test-student-process-notes-ux.php` verifica a composição de `aluno/preparos.php` e `aluno/calibracao.php` com `student_lab_catalog_views.php`, onde agora reside o markup de lista e editor. As mesmas asserções de edição, persistência, semântica de catálogo e serviços continuam obrigatórias; não exigir markup duplicado nas rotas.

**14/10/2026 — Interações locais e mecânica das bibliotecas após extração:** `test-student-interaction-continuity.php` e `test-student-mechanics-audit.php` passaram a ler o controller mais o renderer canônico `student_lab_catalog_views.php` antes de verificar editores contextuais, regiões AJAX, navegação e suporte a revelador personalizado. Critérios não foram removidos: a composição é a superfície real. Nenhuma gravação/serviço foi transferida para a fixture.

**14/10/2026 — Regressão do percurso Laboratório → Novo cadastro:** `student-cross-route-journeys.spec.cjs` usa a projeção `mode=new` para uma URL `/aluno/preparos.php?novo=1` ou `/aluno/calibracao.php?novo=1`, enquanto os testes isolados usam `mode=create`. Após substituir o HTML demonstrativo pelo renderer canônico, a fixture deve reconhecer ambos como abertura do editor real; o primeiro ciclo de navegador revelou essa divergência. Corrigida a aliasagem na fixture e adicionada regressão de formulário/retorno para ambos os valores, sem mudar a rota nem enfraquecer o teste de percurso.

**14/10/2026 — Simplificação visual da lista de Predefinições:** a inspeção de 390/1440 px revelou três explicações sucessivas do mesmo contraste Predefinição × Inventário. O renderer de produção/fixture preserva o aviso de que predefinição não é estoque, link direto para registrar solução preparada e consequência de cópia sem baixa no Inventário, com texto condensado e sem terceiro bloco de explicação. Regressões mantêm essa semântica. Reinspecionar nova captura antes de aceitar a mudança; PR #243 permanece draft.

**14/10/2026 — Teste de campos da calibração:** `student-field-language.spec.cjs` exige que a rota delegue a `student_lab_calibrations_view()` e que o renderer operacional chame `student_calibration_editor($editing...)`. O componente compartilhado também passa a integrar a auditoria textual de rótulos e controles sem nome acessível. O contrato de acessibilidade permanece obrigatório; não exigir markup duplicado no controller.

**14/10/2026 — Caderno, telas filhas operacionais da auditoria #243 (em validação):** a apresentação integral de `aluno/teste-etapa.php` (edição de etapa livre) e `aluno/excluir-teste.php` (confirmação destrutiva) é delegada a `app/student_record_auxiliary_views.php`. As rotas preservam consulta restrita ao aluno, validação do registro/etapa, bloqueio de revisão, verificações de POST/CSRF, persistência, redirecionamento e exclusão real. A fixture visual deve usar o mesmo renderer com IDs e dados fictícios, incluindo o shell legítimo, em vez dos componentes HTML demonstrativos. Testes e imagens ainda são necessários antes de considerar o lote aprovado. PR permanece draft sem merge ou instalação na hospedagem.

**14/10/2026 — Contratos de edição de etapa compartilhada:** `test-student-process-notes-ux.php` e `test-student-mechanics-audit.php` verificam a composição da rota autorizada com `student_record_auxiliary_views.php`, conservando o contrato de edição livre independente e movimentação de estoque explícita. `student-field-language.spec.cjs` inclui o novo renderer na varredura de terminologia/rótulos, exige a delegação do controlador e preserva o campo Anotações em sua origem real. Nenhum critério de domínio ou acessibilidade foi removido.

**14/10/2026 — Contraste da primitiva destrutiva:** screenshot operacional de exclusão do Caderno revelou `button-danger` escuro ilegível, herança de `assets/ui-core.css` (`#8b2727` transparente e hover com `var(--inverse)`). Corrigida a autoridade CSS global para texto `var(--text)`, fundo sutilmente tingido e hover vermelho com texto branco. Playwright testa razão WCAG AA >=4,5:1 em dark/light, em telefones 320/390 e desktop 1440; smoke global exige fonte canônica. A mudança não autoriza exclusão nem modifica domínio. Inspeção após CI permanece obrigatória.

**Complemento de acessibilidade do botão destrutivo:** o mesmo proprietário CSS global estabelece `font-size:12px` e `min-height:44px`, e o Playwright verifica tipografia/toque além do contraste dark/light; a leitura de `color(srgb ...)` ou `rgb(...)` é normalizada antes do cálculo de luminância. Sem alteração de autorização/POST.

**14/10/2026 — Falha visual da variante destrutiva na cascata:** apesar da correção do contraste, `student-visual-audit` mediu fonte real de 10 px no botão após regras genéricas posteriores `.button` em `student-experience.css`/`student-rendered-fixes.css`. Ajustada no proprietário `ui-core.css` a autoridade semântica `.button.button-danger` (classe base+variante), sem seletor de página ou `!important`; smoke global e Playwright seguem exigindo a variante correta, contraste AA, altura ≥44 px e fonte ≥12 px. Aguardam-se CI e reinspeção do HEAD.

**14/10/2026 — Rascunho da edição de etapa preservado em falha:** a extração do renderer permitiu corrigir a perda de valores de `teste-etapa.php` em POST mal sucedido. `$error` aciona projeção apenas dos campos escalares conhecidos de `$_POST` para a apresentação, sem alterar `$step` persistido; edição bloqueada nunca recebe esse rascunho. Testes PHP e navegador conferem valores de label/tempo/notas diferentes do banco e escaping. Exige CI do SHA final.

**14/10/2026 — Fidelidade de contraste entre temas:** `student-record-actions-operational.php` projeta duas paletas de tokens de teste, escura e clara, dentro da mesma implementação canônica do shell; `student-record-actions-operational.spec.cjs` exige que o token CSS computado `--surface` realmente mude entre os temas antes de aceitar duas medições de contraste. Sem esse requisito, a fixture antiga retornava a mesma paleta escura nos dois estados e o teste produzia confiança indevida. Não altera os tokens reais da instalação.

**14/10/2026 — Comparação descritiva no renderer canônico (PR #243 draft):** `aluno/comparar-processos.php` mantém ownership por estudante, seleção de até dois IDs, validação CSRF e criação da variação por `student_research_fork`, além das consultas e cálculos de diferenças de exposição/processamento e mídia. A apresentação foi isolada em `app/student_research_comparison_view.php` (seleção, comparação analítica, resultados, evidências, continuidade). A mesma apresentação poderá ser usada por fixture PHP sintética na matriz, sem refazer algoritmos ou persistência. Exigir regressão e inspeção no SHA final; sem merge nem publicação na hospedagem.

**14/10/2026 — Rascunho no formulário de comparação:** quando a tentativa de criar a próxima variação falha, a rota retém apenas a intenção textual escalar e a base ainda autorizada nos dois registros comparados; o renderer compartido escapa o valor e mantém o rádio escolhido. GET e operações válidas não recebem esse rascunho. Testes PHP/Playwright devem exigir a preservação após erro, sem inventar causalidade ou copiar etapas executadas.

**14/10/2026 — Paridade da comparação do Caderno no PR #243:** a matriz principal migra `compare-select` e `compare-records` para o renderer real `student_research_comparison_view.php` com diferenças calculadas pelo serviço `student_research.php`, dados de teste sintéticos e shell de cinco destinos. Testes PHP e Playwright incluem contexto, mídia vazia explicitamente, diferença/nenhuma diferença, campos do POST e rascunho preservado com escape no erro. O domínio/ownership/POST real permanecem no controller, sem confirmação de dados hospedados. Matriz 33 PHP /13 HTML.

**14/10/2026 — Comparação evidencial responsiva, PR #243 draft:** o screenshot de exposição em 390 px revelou cabeçalho 3 colunas com célula vazia e A/B apertados; os dados usavam 11 px. A autoridade CSS `student-experience.css` apresenta duas colunas em ≤560 px, identificação acessível Registro A/B no renderer e fonte mínima 12 px para valores; desktop preserva três colunas. Regressões PHP e Playwright protegem estrutura A/B e ausência de overflow. Exige CI e comparação visual final; sem merge nem hospedagem.

**14/10/2026 — Superfície canônica do registro, auditoria do Caderno no PR #243:** a composição de `aluno/teste.php` (cabeçalho, contextos Exposição/Processamento/Resultado, formulários, roteiro e etapas, mídia, observações e avaliação) foi extraída sem reformular markup para `app/student_record_workspace_view.php`. A rota mantém na íntegra autenticação/ownership por estudante, status e bloqueio, consultas, POST/CSRF, todas as mutações e redirects. O renderer recebe explicitamente uma projeção das 20 variáveis já calculadas pelo controller; não abre banco nem emite novos efeitos. Fixtures de auditoria devem usar esse renderer operacional com dados controlados e o mesmo shell de cinco destinos, sem reimplementar partes em HTML. Tranche pendente de CI, inspeção e paridade real; não integrar ou instalar.

**14/10/2026 — Paridade do contrato de navegação do registro após extração:** `tools/test-student-record-workspace.php` conserva testes de âncoras nativas, três contextos, mídia, ordem de campos e serviços protegidos, mas consulta a composição controller + `student_record_workspace_view.php` onde agora vive o HTML operacional. Exige também a delegação explícita da rota. Não duplicar markup para satisfazer teste antigo.

**14/10/2026 — Contratos canônicos do Caderno após extração:** os testes de UX transversal, Caderno, processos, mecânica e percursos passam a ler a apresentação do registro na composição controller+`student_record_workspace_view.php`, sem retirar exigências de HTML, escopo, ações nem histórico factual.

**14/10/2026 — Prova de execução da fixture operacional:** `test-global-ui-consumption.php` passa a ler o controller `aluno/teste.php` mais `student_record_workspace_view.php` e exige delegação explícita, sem reduzir requisitos de primitivas compartilhadas. A fixture `student-record-workspace-operational.php`, usada pelas seis novas telas, passa também a ser executada diretamente no gate PHP (`ci.yml` e `deploy.yml`) para capturar erro real de bootstrap/renderização antes do Playwright e não acumular 500 repetidos nas 46 telas. O retorno HTTP da fixture precisa ser 200 antes de qualquer alegação visual.

**14/10/2026 — Falha HTTP 500 da fixture e catálogo canônico isolado:** o gate de execução direta revelou redeclaração fatal de `student_tools_for_student()` ao incluir `student_workbench.php` inteiro na fixture. A função pura `student_process_stage_catalog()` foi movida, byte a byte na sua definição, para `app/student_process_stage_catalog.php`; o antigo `student_workbench.php` exige esse único catálogo via `require_once`. Fixture e smoke de registro agora exigem apenas o catálogo puro, sem carregar serviço/infraestruturas de ferramentas e sem redefinições conflitantes. Domínio, rótulos, ordem e operações de produção são preservados, e a execução direta da fixture continua obrigatória antes do Playwright. PR draft, sem deploy hospedado.

**14/10/2026 — Contrato transversal de três contextos:** `test-admin-student-ux-audit.php` exige Exposição, Processamento e Resultado na composição real de `teste.php` com `student_record_workspace_view.php`, mantendo exatamente o requisito de três partes na mesma página. O markup não retorna ao controller nem recebe uma implementação paralela.

**14/10/2026 — Contrato de processos após extração do workspace:** `test-student-process-manager.php` verifica o controlador mais o renderer canônico do Caderno para as ações independentes `Marcar ✓`, `Editar`, `Timer/Abrir etapa` e `Adicionar etapa ao roteiro`, bem como ausência de estados artificiais. Critérios de estado e comportamento permanecem inalterados.

**14/10/2026 — Regressão de progresso físico proibido e do contexto da bancada:** `test-student-process-recording-modes.php` e `test-student-platform-coherence.php` verificam o controller e o renderer compartilhado do registro juntos; conservam a proibição de modos temporais fictícios e a existência real de checks, edição, timer e roteiro independente. A mudança não reintroduz um wizard nem inicia processo ao associar roteiro.

**14/10/2026 — Coerência do catálogo após extração pura:** `test-student-platform-coherence.php` exige todas as entradas químicas e nomes históricos do catálogo (incluindo Banho de amônia e Segunda revelação) no serviço `student_workbench.php` mais a fonte canônica `student_process_stage_catalog.php`. A verificação de termos não foi removida: apenas acompanha o novo proprietário puro consumido por produção e fixture.

**14/10/2026 — Regressão de linguagem após extração integral do registro:** `student-field-language.spec.cjs` passa a verificar todos os rótulos de Exposição, Processamento e Resultado no renderer operacional `app/student_record_workspace_view.php`, que também entra na varredura de terminologia legada e controles sem nome. A rota `aluno/teste.php` deve continuar delegando para `student_record_render_workspace()`. Nenhum rótulo, requisito de acessibilidade ou fluxo foi relaxado; o teste deixa de exigir HTML duplicado no controlador. Corrige as duas falhas de `validate` observadas no HEAD `1c240fa` sem alterar produção.

**14/10/2026 — Auditoria de roteiro não linear do Caderno:** nova projeção de `process-plan` e `process-partial` na fixture `student-record-workspace-operational.php`, consumindo a mesma apresentação do controlador `aluno/teste.php`. Nove etapas sintéticas, das quais cinco marcações não consecutivas no estado parcial, devem preservar a distinção entre check documental e progressão física. `process-plan-edit` usa o formulário de edição de etapa real, sem gravação. Reutiliza os utilitários reais de tempo e JSON, sem duplicação de algoritmos; não toca serviços/persistência. Prevista matriz 41 PHP/5 HTML, sujeita aos gates e inspeção. PR continua draft, sem hospedagem.

**14/10/2026 — Gates de roteiro associado e parcial:** matriz transversal migrada para a mesma fonte PHP operacional do Caderno (duas entradas; 41 PHP/5 HTML). O contrato documental não linear protege as nove etapas individualmente selecionáveis, inclusive cinco checks alternados, com `Timer`, `Editar`, `Trocar roteiro-base` e movimentação de estoque independente. Smoke renderizado com dados controlados, Playwright (320/390/1440) e estado adicional de editor inline. Sem POST nem banco na fixture. Exigir CI e capturas da SHA de aceite.

**14/10/2026 — Ajuste de botões dos nove estágios após inspeção real:** o screenshot de 390 px revelou rótulos das ações `Marcar/Editar/Timer` com 8,5 px em alvos 40 px. O proprietário `student-caderno.css` amplia fonte para 12 px e alvo 44 px em `.student-notebook-route-step-actions .button.button-compact`, no desktop e no telefone; testes Playwright exigem fonte, altura, largura mínima e ausência de corte em 320/390/1440. Nenhum controle novo ou status automático; aguardar gates e capturas da SHA nova.

**14/10/2026 — Ajuste canônico dos controles de etapa no desktop (#243):** a suíte operacional do Caderno identificou nos três estados de roteiro (`process-plan`, `process-partial`, `process-plan-edit`) o link Timer abaixo de 62 px em 1440 px, consequência de `min-width:0` na variante `.student-notebook-route-step-actions .button.button-compact` em `student-caderno.css`. Alterada essa única autoridade para `min-width:62px`, mantendo fonte de 12 px e altura de 44 px e a grade mobile de três ações. A suíte Playwright continua exigindo os 62 px, controles independentes, ausência de clipping e layout 320/390/1440. Sem mudanças no fluxo físico de revelação, no cadastro ou em dados. Requer CI e inspeção; não houve merge nem deploy hospedado.

**14/10/2026 — Evitar título de contexto parcialmente encoberto no Caderno:** ao rever os screenshots do HEAD `d555c5e`, as três ações da etapa passaram, mas a âncora `#processamento` posicionou o kicker sob parte da barra contextual sticky em desktop. O proprietário `.student-app .student-record-section[data-record-workspace-panel]` em `student-caderno.css` eleva `scroll-margin-top` de 76 para 100 px. O Playwright verifica a separação real de pelo menos 4 px entre barra contextual e kicker nas três partes e três larguras, preservando âncoras nativas e navegação não linear. Ainda requer CI e screenshot final, sem merge nem instalação.

**14/10/2026 — Registro compartilhado no Caderno (PR #243, em auditoria):** a rota `aluno/teste-compartilhado.php` mantém autenticação, verificação contextual de acesso em `student_test_accessible_to_student`, redirecionamento da ficha própria, leitura de mídia e conversa. Sua apresentação somente leitura foi movida, sem mudar o HTML ou comportamento, para `app/student_shared_record_view.php`, de modo que a mesma fonte possa ser usada em fixture sintética de auditoria. Não existe gravação, seleção de turma ou elevação de privilégio no renderer. A migração da matriz e o aceite dependem de testes e screenshots no HEAD final, sem merge nem instalação hospedada.

**14/10/2026 — Registro compartilhado na matriz operacional:** a entrada `shared-record` foi mapeada para `student-shared-record-operational.php`, que usa o renderer PHP canônico `student_shared_record_view.php` extraído da rota `teste-compartilhado.php`. Smoke PHP exige escape de dados e mensagens, mídia/etapa/empty states, read-only e preservação do filtro `student_test_accessible_to_student` no controlador; Playwright cobre 320/390/1440, menu 5 destinos, ausência de ações de mutação, scroll início/meio/fim. A origem dos 46 estados muda para 42 PHP/4 HTML. Autorização, matrícula, mídia real e navegação em hospedagem não foram exercitadas pela fixture; exigir gates da SHA final e inspeção antes de considerar a tranche validada. PR draft, sem merge ou deploy hospedado.

**14/10/2026 — Contrato do registro compartilhado após extração:** `test-student-test-workflow.php` inspeciona a composição do controlador de `teste-compartilhado.php` com `student_shared_record_view.php`: a autorização continua na rota e o link de retorno continua no renderer. O teste preserva ambos os requisitos e não admite duplicar o HTML no controlador. Não afeta visibilidade nem direito de acesso de turmas.

**14/10/2026 — Regressão transversal do renderer compartilhado:** `test-student-area-transversal.php` inspeciona a composição de `teste-compartilhado.php` com `student_shared_record_view.php`, exigindo `student_test_message_date` e `student_review_value` da camada canônica e retorno ao Caderno. Os helpers não são redefinidos na rota e o contrato de campos/avaliação permanece obrigatório; não retornar HTML ao controlador.

**14/10/2026 — Coerência da conversa compartilhada:** `test-student-platform-coherence.php` passa a inspecionar a composição controller+renderer real de `teste-compartilhado.php`. Continua exigindo a consulta autorizada `student_test_messages`, a explicação de que a conversa acompanha o registro e a proibição de formulário de postagem por outro aluno. Os requisitos de isolamento e visibilidade não são relaxados.

**14/10/2026 — Campos e rótulos do registro compartilhado:** `student-field-language.spec.cjs` exige os rótulos de EI, Abertura e tempos de exposição no proprietário `app/student_shared_record_view.php`, exige a delegação em `teste-compartilhado.php` e inclui o renderer na auditoria completa de terminologia e controles acessíveis. Os campos não foram removidos nem duplicados no controlador.

**14/10/2026 — Causa da grade mobile comprimida nas fichas de revisão compartilhada:** screenshots de 320/390 px mostram Exposição e Processamento em duas colunas estreitas, mesmo que `student-area.css` já defina a grade responsiva de uma coluna em `@media(max-width:820px)`. Localizada e retirada cópia byte-equivalente do bloco `.student-review-grid`/`.student-review-media` em `student-experience.css`, carregado depois e responsável por anular a media query anterior. Os estilos de base e responsividade permanecem em um único proprietário `student-area.css`; não é adicionado override de página ou `!important`. Playwright exige 1 coluna em 320/390 e 2 no desktop em ambos os conjuntos, além dos testes de scroll. Exige screenshots novos e 4 gates; sem instalação hospedada.

**14/10/2026 — Proveniência do registro derivado (#243 draft):** `research-derived` migra do HTML inventado da matriz para a lista real do Caderno `student_notebook_view.php`, alimentada pela fixture `student-notebook.php?state=derived` com origem e registro próprios sintéticos. Regras de pesquisa (`source_test_id`, `research_intent`, ligação ao registro original, comparação contextual) não são alteradas; variantes sem origem/sem intenção testam não inventar informação. A matriz fica 43 origens PHP / 3 HTML dos mesmos 46 estados após validação. Exigir Playwright, screenshot e quatro gates no SHA final; hospedagem inalterada.


**15/10/2026 — editor completo de Processamentos para renderer PHP compartilhado (#243 draft, sem merge):** `aluno/processamentos.php` delega a edição integral do roteiro a `app/student_process_template_editor_view.php`, mantendo no controlador autenticação, autoria, CSRF, POST/303, mutações e serviço de duração. O formulário canônico das etapas permanece em `app/student_process_template_step_editor.php`. A fixture `student-process-editor.php` projeta 9 etapas fictícias no mesmo renderer, bloqueia POST e cobre edição, leitura, vazio, associação explícita e etapa personalizada; `student-complete-area-audit.spec.cjs` migra `process-editor` para essa fonte e adiciona regressão PHP/Playwright. **Matriz projetada: 44 PHP / 2 HTML, total 46 estados**. Aprovação depende dos quatro gates no mesmo SHA e inspeção real das capturas 320/390/1440 incluindo topo/meio/fim. O PR continua draft, sem instalação nem homologação autenticada; próximo foco: `lab-runner`, depois Material.


**15/10/2026 — laboratório operacional em view PHP compartilhada (#243 draft, sem instalação):** `aluno/processar.php` passa a delegar sua apresentação de roteiro/timer/etapa a `app/student_process_runner_view.php`; todas as ações de timer e de gravação factual permanecem no controlador e nos serviços. `student-process-runner-operational.php` usa a mesma view com 9 etapas fictícias e POST desabilitado. `lab-runner` migra na matriz de 46 estados: projeção **45 PHP/1 HTML**, restando Material. Playwright específico verifica tempos e check independentes em 320/390/1440 px e gera capturas topo/meio/fim. Estado sujeito a CI, inspeção das imagens e posterior homologação autenticada; PR draft e hospedagem intacta.


**15/10/2026 — matriz transversal totalmente em fontes PHP (PR #243 draft; sujeito a gates):** migrado o último estado `material` para fixture `student-material-cms-operational.php`, que delega a `cms_render_public_page` e preserva autenticação/conteúdo do CMS, contexto de matrícula, filtro de liberação por aula e painel canônico de anotações. Foram tratados separadamente `student-shell` (aplicação) e `cms-student-material` (leitura CMS), incluindo skip-link acessível por teclado. Cenário sintético, imutável, com matrícula/curso/4 páginas/4 aulas/2 notas e POST negado. **46/46 fontes PHP e nenhuma HTML na matriz**, mas isso não representa homologação de acesso real, publicação editorial ou inspeção concluída das capturas. CI e evidências do SHA final ainda condicionam aceite; PR mantido draft, sem merge nem instalação.


**15/10/2026 — correção CMS descoberta na auditoria (PR #243 draft):** a segunda chamada a `cms_page_document` em `cms_render_public_page` descartava o JSON de anotações gerado por `student_material_render_notebook`. Parâmetro opcional `preparedDocument` preserva esse payload somente quando o HTML foi previamente sanitizado e instrumentado pelo `index.php`; a sanitização continua padrão para documentos crus/edição. Verificação PHP + Playwright requer script JSON final e duas notas sintéticas, sem alterações em dados da instalação.


**15/10/2026 — CSS canônico do Editor:** problema visual confirmado por Playwright: folha posterior `student-quality-pass.css` sobrepunha o componente com 8 px/30 px. Eliminada a regra na origem e consolidados controles 11 px/44 px em `student-processes.css` com inspeção responsiva obrigatória no SHA final. Não há CSS de fixture ou `!important`.


**15/10/2026 — contraste do ato destrutivo:** etiqueta `Remover` do Editor deixou cor vermelha absoluta inadequada ao tema claro; o CSS canônico usa mistura com `--text` para adaptação de contraste, com regressão calculada nos dois temas (limiar AA 4,5:1). Aferição do Playwright do SHA final ainda necessária.
