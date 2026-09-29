# Polimento operacional da área administrativa — 2026-09-29

## Escopo

Esta etapa aplica a auditoria transversal de produto/UI/UX às quatro coleções que formam o fluxo operacional principal:

- Inscrições
- Turmas
- Alunos
- Pessoas

A meta é reduzir ambiguidade, preservar contexto, tornar estados legíveis e aproximar as quatro telas de um único sistema de interação.

## Contratos aplicados

### Filtros

- filtros continuam server-side e compatíveis com volume;
- filtros ativos aparecem explicitamente depois da toolbar;
- cada filtro pode ser removido isoladamente;
- `Limpar tudo` remove todo o contexto de filtro;
- busca, paginação e relacionamentos preservam a maior quantidade possível de contexto.

### Estados

Valores internos não devem aparecer crus quando existe um rótulo de produto. Estados de pagamento, matrícula, conta e turma passam por helpers compartilhados do shell e usam badges com texto, não apenas cor.

### Navegação relacional

O fluxo deve permitir seguir relações reais entre entidades:

- matrícula pode abrir a inscrição que a originou;
- aluno pode abrir a pessoa correspondente;
- pessoa mostra matrículas e inscrições relacionadas;
- turma leva à lista de alunos já filtrada;
- curso continua sendo uma dimensão de filtro e navegação, não uma árvore paralela.

### Criação de turma

Criar uma turma não depende mais de o administrador descobrir que precisa primeiro filtrar a coleção por curso. O formulário de criação permite escolher o curso explicitamente.

### Detalhes

Detalhes operacionais usam uma composição compartilhada, preservam contexto de retorno e mantêm ações destrutivas separadas das ações cotidianas.

## CSS

`assets/admin-operations.css` é a folha compartilhada desta superfície funcional. Ela não corrige o shell nem redefine componentes globais: contém apenas composição operacional comum às quatro coleções.

`admin-registration.css` continua específico do detalhe de inscrição e agora é carregado pelo shell, não injetado depois do conteúdo da página.

## Regressão

`tools/test-admin-operational-ux.php` garante que:

- as quatro rotas usem a folha operacional compartilhada;
- filtros ativos permaneçam reversíveis;
- tabelas com overflow sejam focáveis por teclado;
- estados sejam humanizados por helpers compartilhados;
- a origem da matrícula continue navegável;
- a criação de turma aceite seleção explícita do curso;
- o detalhe de Pessoa permaneça antes da coleção no fluxo de leitura;
- CSS específico não volte a ser injetado pelo corpo da página;
- `!important` não seja introduzido na camada operacional.
