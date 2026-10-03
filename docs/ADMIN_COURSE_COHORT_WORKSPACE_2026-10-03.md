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

## Inscrições

Inscrições deixam de misturar cursos por padrão. Um curso é obrigatório antes da operação da coleção.

O estado pago/confirmado sem turma permanece válido e explícito. A interface nunca escolhe uma turma automaticamente. A atribuição continua sendo uma decisão administrativa.

## Turmas

A administração volta a permitir:

- criar turma;
- editar nome e período;
- arquivar sem apagar histórico;
- reativar turma arquivada;
- abrir a turma como workspace operacional.

O `slug` e o conceito legado de turma padrão deixam de ser decisões ordinárias da interface. Nenhum fluxo reintroduz matrícula automática em turma padrão.

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

A administração passa a oferecer `Visualizar como esta turma`.

A prévia não simula acesso apenas no cliente. Ela usa `cms_access_filter_html()` com `course_id`, `cohort_id` e `material_page_id` explícitos, portanto o HTML mostrado passa pelo mesmo filtro de acesso usado pela experiência real do aluno.

## Alunos e identidade

`Alunos` é a entrada global da operação educacional. Sem curso selecionado, funciona como busca global das identidades com participações. O histórico completo de identidade continua disponível em `Pessoas`, mas deixa de ser uma decisão de navegação principal para o operador.

Dentro de curso/turma, Alunos opera matrículas. Quando a matrícula nasceu de uma inscrição, mudança de turma e demais decisões preservam a inscrição original como autoridade de ciclo de vida. Matrículas importadas/manuais podem ser desativadas diretamente sem apagar o histórico da pessoa.

## Importação histórica

A importação CSV volta ao fluxo atual em `Turma → Alunos → Importar CSV`.

O endpoint valida explicitamente que a turma pertence ao curso atual e retorna para o workspace da turma, incluindo relatório do lote. Ele não retorna mais à antiga `student-area.php`.

## Testes e dúvidas

Testes voltam à administração corrente, com:

- fila por curso ou turma;
- filtro por estado e busca;
- leitura dos dados técnicos e imagens;
- mudança do estado de avaliação;
- conversa privada professor/aluno.

Dúvidas e Testes formam o Acompanhamento do curso e também aparecem diretamente no workspace de cada turma.

## Compatibilidade

`admin/student-area.php` e `admin/student-operations.php` permanecem apenas como redirecionadores de compatibilidade. Rotas antigas de testes, alunos/importação, turmas, aulas e material convergem para os workspaces atuais.

## Regressões obrigatórias

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
- nenhuma folha de correção cronológica, CSS inline visual ou `!important` introduzido por esta revisão.
