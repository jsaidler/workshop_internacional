# Workspace administrativo por curso e turma — 03/10/2026

## Autoridade desta decisão

Este documento é a referência canônica da arquitetura de informação da administração de cursos a partir de 03/10/2026.

Ele substitui, **somente para a administração de cursos**, a decisão anterior de “coleções globais como eixo primário” registrada em `docs/ADMIN_STUDENT_UX_AUDIT_2026-09-28.md`. As decisões daquele documento sobre Área do Aluno, sistema visual, ritmo, responsividade e consumo de componentes compartilhados continuam válidas onde não conflitarem com esta revisão.

O domínio de dados não muda: curso, inscrição, turma, matrícula, aula e material continuam obedecendo às autoridades já definidas em `docs/COURSE_DOMAIN_REGISTRATION_MATERIAL_ARCHITECTURE_2026-09-27.md`.

## Motivo

A auditoria funcional da administração identificou que a modelagem canônica de curso, turma, matrícula, aula e material estava correta, mas a interface ainda obrigava o operador a reconstruir o contexto por filtros e telas globais. Também havia regressões concretas na migração da antiga Área do Aluno administrativa: edição do ciclo de turma, importação histórica por CSV, avaliação de testes e prévia efetiva do conteúdo por turma haviam ficado órfãs ou inacessíveis.

Esta revisão altera a experiência administrativa, não a modelagem canônica do domínio.

## Arquitetura operacional

A barra lateral permanece a árvore global da aplicação. Dentro de Ensino, as entradas globais são `Cursos` e `Alunos`.

Ao abrir um curso, o contexto passa a ser persistente e apresenta as áreas:

- Visão geral;
- Inscrições;
- Turmas;
- Alunos;
- Conteúdo;
- Acompanhamento.

A visão geral do curso prioriza trabalho em aberto: pagamentos pendentes, inscrições pagas ainda sem turma, testes aguardando avaliação e dúvidas abertas.

Ao abrir uma turma, ela passa a ser a unidade operacional concreta e apresenta:

- Visão geral;
- Alunos;
- Aulas e acesso;
- Dúvidas;
- Testes.

O contexto de turma não cria nova entidade ou nova política de acesso. Ele apenas opera o domínio já existente `course_cohorts`, `course_enrollments`, `course_lessons`, `cohort_lesson_releases` e o filtro canônico de material.

Curso e turma exibem localização hierárquica explícita. Uma turma sempre oferece retorno visível a `Turmas`; o operador não depende do botão Voltar do navegador para sair de um contexto.

## Inscrições

Inscrições deixam de misturar cursos por padrão. Um curso é obrigatório antes da operação da coleção.

O estado pago/confirmado sem turma permanece válido e explícito. A interface nunca escolhe uma turma automaticamente. A atribuição continua sendo uma decisão administrativa.

Abrir uma inscrição é um estado focado: a ficha aparece diretamente em vez de ser acrescentada depois de dezenas de linhas da coleção. Trocar turma é uma atribuição atômica para a nova turma. `Retirar da turma` é uma ação distinta, explícita e preserva a inscrição.

## Turmas

A administração permite:

- criar turma;
- editar nome e período;
- arquivar sem apagar histórico;
- reativar turma arquivada;
- abrir a turma como workspace operacional.

O `slug` e o conceito legado de turma padrão deixam de ser decisões ordinárias da interface. Nenhum fluxo reintroduz matrícula automática em turma padrão.

Criação e edição usam estados focados. Configuração e ação destrutiva não ocupam permanentemente a visão operacional da turma.

## Aulas e material

A estrutura das aulas pertence ao curso. Nome e ordem das aulas podem ser corrigidos no contexto do curso.

A disponibilidade pertence à turma. Em `Aulas e acesso`, cada aula mostra:

- estado efetivo na turma;
- data de liberação/agendamento;
- quantidade de seções e páginas de material afetadas;
- conteúdo afetado sob demanda;
- ações Bloquear, Liberar agora e Agendar.

A tela Material administra somente a estrutura: quais páginas pertencem ao curso e a partir de qual aula cada seção se torna disponível. Ela não se apresenta mais como tela de “liberação”.

## Prévia como turma

A administração oferece `Visualizar como esta turma`.

A prévia não simula acesso apenas no cliente. Ela usa `cms_access_filter_html()` com `course_id`, `cohort_id` e `material_page_id` explícitos, portanto o HTML mostrado passa pelo mesmo filtro de acesso usado pela experiência real do aluno.

A prévia permite navegar entre as páginas de material associadas ao curso sem perder o contexto da turma e oferece uma saída explícita de volta à administração.

## Alunos e identidade

`Alunos` é a entrada global da operação educacional. Sem curso selecionado, funciona como busca global das identidades com participações. O histórico completo de identidade continua disponível em `Pessoas`, mas deixa de ser uma decisão de navegação principal para o operador.

Dentro de curso/turma, Alunos opera matrículas. Quando a matrícula nasceu de uma inscrição, mudança de turma e demais decisões preservam a inscrição original como autoridade de ciclo de vida. Matrículas importadas/manuais podem ser desativadas diretamente sem apagar o histórico da pessoa.

Links entre Pessoa, Inscrição e matrícula carregam o `course_id` necessário e mostram o nome da turma em vez de expor identificadores internos como informação principal.

## Importação histórica

A importação CSV volta ao fluxo atual em `Turma → Alunos → Importar CSV`.

O endpoint valida explicitamente que a turma pertence ao curso atual e retorna para o workspace da turma, incluindo relatório do lote. Ele não retorna mais à antiga `student-area.php`.

## Testes e dúvidas

Testes ficam na administração corrente, com:

- fila por curso ou turma;
- filtro por estado e busca;
- leitura dos dados técnicos e imagens;
- mudança do estado de avaliação;
- conversa privada professor/aluno.

Ações de avaliação dependem do estado atual: a interface não oferece simultaneamente transições contraditórias. Dúvidas e Testes formam o Acompanhamento do curso e também aparecem diretamente no workspace de cada turma.

## Responsividade administrativa

Coleções operacionais de Cursos, Turmas, Inscrições, Alunos, Dúvidas, Testes e Material não usam como solução mobile uma tabela desktop de largura fixa escondida em um scroller horizontal.

Essas coleções mantêm a semântica tabular no desktop e, em telas estreitas, cada registro vira uma unidade vertical com rótulos explícitos para cada valor e sua ação dentro do viewport. Tabelas realmente técnicas, nas quais a comparação horizontal é necessária, podem continuar usando scroller local.

## Shell e CSS

`assets/admin-system.css` permanece a autoridade global do admin. Defeitos sistêmicos de navegação, contraste e sintaxe são corrigidos nessa camada, sem folha `fix`, `hotfix`, `v2/v3`, `!important` ou patch visual inline.

O destino ativo da barra lateral e das navegações contextuais possui contraste explícito entre texto e fundo. Declarações fundidas ou sintaticamente inválidas no CSS são regressões bloqueantes.

## Compatibilidade

`admin/student-area.php` e `admin/student-operations.php` permanecem apenas como redirecionadores de compatibilidade. Rotas antigas de testes, alunos/importação, turmas, aulas e material convergem para os workspaces atuais.

Um `cohort_id` inválido nunca amplia silenciosamente a operação para todas as turmas. O operador é devolvido a um contexto seguro e explícito.

## Validação obrigatória

A validação administrativa deve garantir:

- curso como contexto obrigatório de inscrições;
- turma como workspace editável e arquivável;
- ausência de matrícula automática em turma padrão;
- estrutura de aulas separada da disponibilidade por turma;
- consequência sobre material visível junto à liberação de aula;
- prévia de turma passando pelo filtro real de acesso;
- avaliação de testes disponível na administração atual;
- importação CSV retornando ao fluxo atual;
- identidade global preservada sem obrigar o operador a navegar por Pessoa como coleção principal;
- navegação hierárquica reversível e links profundos preservando contexto;
- coleções operacionais utilizáveis sem overflow horizontal em celular;
- nenhuma folha de correção cronológica, CSS inline visual ou `!important` introduzido por esta revisão.

A aprovação visual do admin não pode ser inferida a partir do `student-visual-audit`. `tools/test-admin-navigation-integrity.php` protege os contratos estruturais; `tools/browser-fixture/admin-teaching-product-audit.php` e `tools/browser-tests/admin-teaching-product-audit.spec.cjs` renderizam estados representativos da administração em desktop e celular, verificam overflow, contraste, retorno hierárquico e ações dentro do viewport e preservam screenshots em `test-results/admin-visual-audit`.

O gate só é concluído depois da inspeção visual humana dessas imagens. Um workflow verde que não renderize a superfície alterada não constitui aprovação visual.
