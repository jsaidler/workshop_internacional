# Arquitetura canônica — workshops como páginas, inscrições e alunos — 27/09/2026

Este documento corrige uma premissa estrutural anterior do projeto. Em caso de conflito com documentação mais antiga que trate uma `activity` como um curso/workshop, este documento prevalece.

## Autoridade principal

A instalação atual é **um único site/CMS**. O objeto técnico `activity` permanece como contêiner interno do site e limite de dados legado; ele **não é a entidade editorial usada para criar workshops**.

Um workshop é representado por uma **página CMS** e por sua subárvore editorial.

A regra é:

- a página raiz do workshop representa o workshop;
- a página de inscrição associada pertence à hierarquia desse workshop;
- páginas de material, apoio ou outras páginas específicas podem pertencer à mesma subárvore;
- criar outro workshop significa criar/duplicar páginas dentro do mesmo site, não criar uma nova `activity`;
- a administração normal não oferece a criação de novas activities para representar workshops;
- se no futuro for necessário outro site independente, isso é outra instalação/estrutura e não faz parte do fluxo editorial normal desta administração.

## Hierarquia das páginas

`cms_pages.parent_page_id` é a autoridade da relação editorial.

Para fins de inscrição e Área do aluno, o **workshop raiz** de uma página é o ancestral superior da sua árvore dentro da mesma `activity` e do mesmo locale. A página raiz pode ser ela própria quando não possui pai.

A hierarquia não é apenas visual: ela fornece o contexto estável para agrupar inscrição, turma, aluno, aula, material e teste.

## Inscrições

`cms_form_submissions.page_id` já registra em qual página o formulário foi enviado. Esse é o ponto de partida correto.

Para um formulário com `purpose='enrollment'`:

1. a submissão identifica a página de inscrição por `page_id`;
2. a hierarquia resolve o workshop raiz ao qual essa página pertence;
3. a matrícula é criada na turma padrão **desse workshop**, não na turma padrão da activity inteira;
4. a submissão continua preservando `activity_id` apenas como limite técnico do site/compatibilidade;
5. uma mesma identidade global de aluno pode possuir matrículas em workshops diferentes dentro do mesmo site.

Não se infere workshop por nome de formulário, slug, texto ou título. A autoridade é `page_id` + hierarquia.

## Alunos e matrículas

`student_users` continua sendo a identidade global da pessoa.

A relação educacional é:

`student_user → course_enrollment → course_cohort → workshop_page`

Portanto:

- o aluno não pertence a uma activity como se ela fosse um curso;
- uma turma pertence a um workshop;
- uma matrícula pertence a uma turma;
- aulas pertencem a um workshop;
- liberações pertencem à combinação turma + aula;
- testes pertencem à matrícula/turma e herdam o workshop pelo vínculo da turma.

`activity_id` pode permanecer durante a migração como limite técnico e compatibilidade, mas não é mais a chave semântica que separa workshops.

## Conteúdo protegido e liberação de aulas

A página protegida continua sendo renderizada pelo CMS normal. Não existe um segundo renderer de material.

O renderer público deve sempre aplicar o filtro canônico de acesso de seções antes de enviar o HTML. Uma seção vinculada a uma aula não liberada não deve apenas ser escondida por CSS/JavaScript: ela não deve ser entregue no HTML ao aluno.

Quando o aluno entra em material a partir de uma matrícula específica, o contexto dessa matrícula/turma deve dirigir a liberação. O sistema não deve escolher arbitrariamente outra turma ativa do mesmo site.

A navegação do material usa **uma única topbar global**. A Área do aluno não cria uma segunda barra visual. Em material autenticado, as ações `Voltar ao curso`, `Testes` e `Conta` entram na topbar já existente.

## Administração

A navegação administrativa deve refletir o modelo editorial real:

### Conteúdo

`Páginas` continua sendo a autoridade para criar e organizar workshops e suas páginas relacionadas.

### Inscrições

A tela de inscrições deve poder agrupar/filtrar respostas pelo workshop resolvido a partir da página que recebeu a submissão. Formulários continuam reutilizáveis; a página em que foram usados dá o contexto editorial.

### Área do aluno

A administração de turmas, alunos, testes, aulas e páginas protegidas deve operar dentro de um **workshop selecionado**, não de uma activity selecionada.

A sequência conceitual é:

`Workshop → Turmas → Matrículas/Alunos → Aulas/Liberações → Testes/Material`

Não é:

`Activity/curso → tudo dentro dela`.

## Activities legadas

A administração normal não cria activities para workshops e não deve oferecer seletor de “curso/workshop atual”. A activity raiz representa o site atual.

Activities não raiz criadas pela interpretação antiga são estado legado. Elas devem sair da navegação normal e ser tratadas por manutenção segura. Nenhuma migração pode apagar automaticamente dados editoriais ou inscrições de uma activity legada sem confirmação explícita.

## Migração do banco

A migração para o novo escopo deve ser não destrutiva.

A direção canônica é adicionar `workshop_page_id` às entidades que hoje usam apenas `activity_id` para separar cursos, começando por:

- `course_cohorts`;
- `course_lessons`.

`course_enrollments` não precisa duplicar o campo porque o workshop é determinado pela turma. `cms_form_submissions` já possui `page_id` e, portanto, já contém a origem necessária.

Índices de “turma padrão”, slug de turma e `lesson_key` precisam deixar de ser exclusivos por activity e passar a respeitar o workshop.

Estado legado sem `workshop_page_id` deve continuar legível durante a transição. Associação automática só é permitida quando for inequívoca; caso contrário, exige decisão administrativa.

## Ordem de implementação

1. corrigir o renderer para realmente aplicar o bloqueio server-side de seções/aulas;
2. eliminar a segunda barra do material e consumir a topbar global;
3. retirar criação/troca de activity do fluxo administrativo normal;
4. introduzir o escopo persistente `workshop_page_id` sem destruir dados existentes;
5. fazer reconciliação de inscrição resolver workshop pela página/hierarquia;
6. fazer turmas e aulas operarem por workshop;
7. reorganizar `Inscrições` e `Área do aluno` para seleção/agrupamento por workshop;
8. migrar regras de visibilidade de testes e de páginas protegidas de “activity inteira” para workshop;
9. somente depois da migração validada, remover compatibilidades sem uso comprovado.

## Estado de implementação em 27/09/2026

Os itens 1–3 foram concluídos no PR #138. O material protegido passou a usar uma única topbar global e o renderer aplica o filtro server-side antes de entregar o HTML.

Os itens 4–6 e a parte estrutural do item 8 foram implementados no bloco seguinte: `workshop_page_id` passa a existir em turmas e aulas; inscrições confirmadas resolvem o workshop por `page_id` + hierarquia; turmas, aulas, material, testes e compartilhamento deixam de usar `activity_id` como fronteira semântica entre workshops. O fallback legado permanece apenas para dados antigos sem associação inequívoca.

O próximo bloco é exclusivamente administrativo: **Inscrições e Área do aluno devem selecionar/agrupar por página de workshop**. Esse trabalho não cria entidade nova de workshop e não reintroduz seletor de activity. Registros cuja origem histórica não permita resolver um workshop devem aparecer explicitamente como não associados, nunca ser atribuídos por heurística.

## Regressão obrigatória

A suíte deve provar pelo menos:

- material autenticado tem uma única topbar;
- o renderer chama a autoridade server-side de acesso antes de entregar o HTML;
- aula bloqueada não aparece no HTML;
- uma inscrição enviada numa página filha é atribuída ao workshop raiz correto;
- duas páginas de workshop dentro da mesma activity podem ter turmas padrão independentes;
- um aluno matriculado no workshop A não recebe conteúdo do workshop B;
- a administração normal não oferece criação de “novo workshop” via activity;
- nenhuma correção reintroduz um segundo sistema de UI ou renderer específico da Área do aluno.
