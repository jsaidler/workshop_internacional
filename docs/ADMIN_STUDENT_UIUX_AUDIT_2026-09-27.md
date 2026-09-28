# Auditoria canônica de UI/UX — administração e área do aluno — 27/09/2026

## Estado de partida

A reconciliação de domínio foi concluída antes desta auditoria. O estado instalado verificado em 27/09/2026 possui um curso ativo, quatro turmas, nove matrículas ativas, três aulas, uma página de material e nenhum vínculo órfão de curso/turma/aula/inscrição.

Esta etapa não redefine a modelagem de dados. Ela corrige a experiência que ainda carregava a história das modelagens anteriores.

## Princípios

1. **A interface deve representar o modelo mental do usuário, não a estrutura do banco.**
2. **Curso é o contexto operacional da administração educacional.**
3. **Pessoa é identidade global; inscrição e matrícula são relações diferentes.**
4. **Material continua sendo página CMS normal.** A administração de material é apenas uma visão contextual das páginas associadas ao curso.
5. **O aluno possui uma única barra superior canônica.** Contexto de curso não pode parecer uma segunda topbar.
6. **Curso é contexto persistente na área do aluno.** Material e testes não são produtos globais paralelos ao curso.
7. **Ritmo vertical é parte do sistema.** Títulos, campos, botões, cards e seções não podem depender de margens acidentais locais.
8. **Procurar → consumir → identificar lacuna → ampliar globalmente somente se necessário → consumir.** Não criar componentes paralelos quando já existe autoridade global.

## Arquitetura administrativa

A navegação primária passa a ser:

- **Conteúdo**
  - Páginas
  - Formulários
  - Outras respostas
  - Navegação
  - Visual
  - SEO
- **Cursos**
  - Cursos
  - Inscrições
  - Pessoas
- **Mídia**
- **Métricas**
- **Configurações**
  - Sistema e atualizações
  - Estrutura do site
  - Integridade

`Integridade` é diagnóstico técnico e deixa o fluxo operacional de inscrições.

### Contexto de curso

Ao abrir um curso, o contexto deve permanecer estável em todas as operações:

- Visão geral
- Inscrições
- Turmas
- Alunos
- Aulas
- Material

`Inscrições` não deve parecer uma ferramenta externa ao curso apenas porque usa outra rota.

A Visão geral concentra o estado útil do curso: inscrições, turmas, alunos, aulas e páginas de material. Configuração de página pública e formulário existe no mesmo contexto, mas não disputa prioridade com o trabalho cotidiano.

### Inscrições

A tela deve responder primeiro:

- quantas inscrições existem;
- quantas aguardam pagamento;
- quantas estão confirmadas sem turma;
- quantas já possuem turma;
- como as disponibilidades se distribuem.

Pagamento, estado da inscrição e atribuição de turma permanecem dimensões independentes.

### Pessoas

`student_users` continua sendo a autoridade global. A tela de Pessoas apresenta identidade, conta, inscrições e matrículas. Alertas de integridade não fazem parte da linguagem normal da interface depois da reconciliação.

### Importação histórica

Importação histórica pertence ao contexto de uma turma do curso. Ela cria/reutiliza a identidade global e cria matrícula; não fabrica inscrição.

## Arquitetura da área do aluno

### Navegação global

A barra superior canônica contém somente identidade global da aplicação e navegação global:

- Meus cursos
- Conta
- aparência/sessão de forma secundária

`Testes` deixa de ser item global porque sempre pertence a um curso/turma.

No mobile, a navegação inferior segue a mesma autoridade e não cria outra taxonomia.

### Contexto de curso

Dentro de uma matrícula, o contexto é um cabeçalho de conteúdo — nunca uma segunda barra sticky:

- nome do curso;
- turma;
- estado da turma;
- Visão geral;
- Material;
- Testes.

Quando necessário, `← Meus cursos` aparece como navegação contextual, e não como variação de topbar.

### Material

`/aluno/material.php` passa a ser o índice contextual do material do curso. Ele lista as páginas CMS associadas e os estados de liberação das aulas. Abrir uma página continua usando o renderer CMS canônico e o controle de acesso existente.

### Testes

A seleção repetida de workshop/turma é removida. Sem contexto explícito e havendo mais de uma matrícula, o aluno volta para `Meus cursos` para escolher o contexto uma única vez. Dentro do curso, Testes mantém criação, registros próprios e compartilhados.

## Sistema de espaçamento

A auditoria adota uma escala de ritmo vertical, aplicada por componentes globais das duas superfícies:

- 4 px — microajuste interno;
- 8 px — controles intimamente relacionados;
- 12 px — label/controle e metadados;
- 16 px — componentes relacionados;
- 24 px — grupos e ações;
- 32 px — blocos de conteúdo;
- 48 px — seções principais;
- 64 px ou mais — mudanças grandes de contexto.

Regras obrigatórias:

- cards consecutivos têm separação explícita;
- ações de formulário nunca ficam coladas ao último campo;
- títulos de seção não ficam colados ao bloco anterior;
- alertas e estados vazios respeitam o fluxo vertical;
- ações destrutivas recebem separação visual adicional;
- breakpoints mobile não podem colapsar o espaçamento para zero;
- correções devem acontecer na camada global da superfície antes de qualquer margem local excepcional.

## Hierarquia visual

A área do aluno mantém a identidade visual do site, mas é uma aplicação recorrente, não uma landing page. Títulos de aplicação são reduzidos, a densidade útil aumenta e o contexto de curso aparece antes das ações. A hierarquia deve ser percebida sem depender da leitura integral da página.

## Segurança e regressão

A revisão visual não altera as autoridades de acesso. Material de curso continua fail-closed quando o curso é conhecido. Seções bloqueadas continuam removidas no servidor antes da entrega. A suíte deve cobrir:

- uma única topbar do aluno;
- navegação de curso persistente;
- Material como índice contextual de páginas CMS;
- Testes subordinados ao curso;
- arquitetura administrativa nova;
- contadores e disponibilidade em Inscrições;
- espaçamento global carregado após as camadas anteriores;
- ausência de regressão de acesso entre cursos/turmas.
