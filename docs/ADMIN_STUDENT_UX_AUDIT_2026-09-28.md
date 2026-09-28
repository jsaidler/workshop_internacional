# Auditoria UX — Administração e Área do Aluno — 28/09/2026

## Estado de partida

A reconciliação de domínio foi concluída antes desta revisão. O estado instalado confirmado possui um curso canônico, quatro turmas, nove matrículas ativas, três aulas e uma página CMS de material, sem turmas, matrículas, aulas ou inscrições órfãs.

Esse estado é apenas um **snapshot de validação de integridade**. Ele não é premissa de projeto da interface. A administração deve continuar legível e operacional com dezenas de cursos e centenas ou milhares de inscrições, pessoas e matrículas.

Esta etapa não altera a modelagem de dados. Ela reorganiza a experiência de uso sobre a autoridade já corrigida.

## Objetivo

A interface deve refletir a lógica do domínio sem exigir que administrador ou aluno compreendam tabelas, IDs, relações legadas ou decisões internas do sistema.

A hierarquia deve ser percebida pela navegação, pelo agrupamento visual e pelo espaçamento antes mesmo da leitura detalhada.

## Administração — arquitetura de informação canônica

### Coleções são o eixo primário

A administração não usa o curso como árvore de navegação. `Curso` é uma entidade de catálogo e uma dimensão de filtragem aplicada às coleções operacionais e pedagógicas.

Isso evita que `Inscrições`, `Alunos`, `Turmas`, `Aulas` e `Material` existam simultaneamente como áreas globais e como subáreas paralelas de cada curso. Com vários cursos, essa duplicação obrigaria o administrador a escolher primeiro uma hierarquia artificial antes de conseguir localizar o registro que procura.

A barra lateral é a única árvore global e é agrupada pela natureza do trabalho:

- **Principal**
  - Visão geral
- **Operação**
  - Inscrições
  - Turmas
  - Alunos
  - Pessoas
- **Ensino**
  - Cursos
  - Aulas
  - Material
- **Site**
  - Páginas
  - Formulários
  - Outras respostas
  - Navegação
  - Visual
  - SEO
- **Biblioteca**
  - Mídia
- **Análise**
  - Métricas
- **Sistema**
  - Sistema e atualizações
  - Estrutura do site
  - Integridade

`Integridade` é diagnóstico técnico e não participa do fluxo operacional cotidiano.

A navegação suporta crescimento vertical e rolagem. Novas funções não criam uma segunda barra horizontal, uma terceira camada paralela ou uma árvore repetida dentro de cada curso.

### Curso como catálogo e filtro

A tela **Cursos** é um catálogo pesquisável e paginado. Cada linha mostra informação comparável e contagens de relações relevantes. Abrir um curso apresenta uma síntese e links para as coleções globais já filtradas pelo curso.

Assim:

- `Inscrições` abre `/admin/registrations.php?course=...`;
- `Turmas` abre `/admin/cohorts.php?course=...`;
- `Alunos` abre `/admin/students.php?course=...`;
- `Aulas` abre `/admin/lessons.php?course=...`;
- `Material` abre `/admin/material.php?course=...`.

A página do curso conserva apenas o que realmente pertence ao registro do curso: síntese, relação com página pública, relação com formulário de inscrição e acesso às coleções relacionadas. Não existe mais navegação horizontal `Visão geral / Inscrições / Turmas / Alunos / Aulas / Material`.

A associação de uma página CMS a um novo curso continua sendo operação rara de configuração e permanece visualmente secundária.

### Regra para volume de dados

Listagens administrativas são projetadas para o volume futuro, e não para caber no conjunto atual.

Invariantes:

- coleções potencialmente grandes usam **busca + filtros + paginação no servidor**;
- o servidor consulta apenas a página necessária em vez de carregar uma coleção inteira e ocultar o excedente;
- não existem limites silenciosos como `LIMIT 500` apresentados ao usuário como se fossem a coleção completa;
- páginas de listagem usam densidade informacional de tabela/lista, não uma grade de cards por entidade;
- ações principais permanecem na linha do registro;
- contagens e resumos evitam ciclos de consulta por item quando uma agregação resolve o conjunto;
- curso e turma são filtros explícitos quando pertencem à dimensão da coleção;
- filtros, busca e página atual são preservados ao abrir e operar um registro;
- no mobile, tabelas podem rolar horizontalmente, mas hierarquia, filtros e paginação continuam utilizáveis.

`Cursos`, `Inscrições`, `Turmas`, `Alunos`, `Pessoas`, `Aulas` e `Material` obedecem a esse padrão. A mesma coleção pode ser aberta globalmente ou já filtrada a partir de um curso sem mudar de arquitetura.

### Inscrições

`Inscrições` é uma coleção operacional global. Curso, turma, estado e busca são filtros da coleção, e não pré-requisitos de navegação.

A tela responde imediatamente:

- quantas inscrições ativas existem no recorte atual;
- quantas aguardam pagamento;
- quantas estão confirmadas sem turma;
- quantas já possuem turma.

Pagamento, confirmação da inscrição e atribuição de turma são dimensões independentes.

A agregação de disponibilidade depende de um curso selecionado, porque opções de formulários diferentes não devem ser somadas como se fossem semanticamente idênticas. Quando o curso é filtrado, essa agregação continua sendo ferramenta operacional para formação de turmas.

Abrir uma inscrição preserva filtros, busca e página da coleção. O detalhe fica subordinado à listagem, sem transformar a tela em caixa de entrada.

### Turmas

`Turmas` é uma coleção global filtrável por curso, estado e busca. A contagem de alunos é calculada no conjunto, sem uma consulta adicional para cada linha.

Criar uma turma exige um curso selecionado, porque a criação modifica essa relação específica. A necessidade de contexto para uma ação não transforma a coleção inteira em subpágina do curso.

### Alunos e Pessoas

`Aluno` e `Pessoa` não são sinônimos.

`student_users` permanece a autoridade global de identidade. **Pessoas** apresenta a identidade permanente, conta e histórico relacionado. Uma mesma pessoa pode aparecer em múltiplas matrículas sem ser duplicada.

**Alunos** apresenta participação educacional: matrícula, curso, turma, estado e origem. Curso e turma são filtros dessa coleção. Cada linha pode abrir a identidade correspondente em Pessoas.

Diagnóstico de integridade permanece ação secundária e não participa do texto ou da navegação principal da operação.

### Aulas e Material

**Aulas** é uma coleção pedagógica global e filtrável. Sem filtro de curso, a tabela permite localizar e comparar aulas entre cursos. Com curso selecionado, a mesma superfície passa a oferecer o controle de liberação por turma e a criação de uma aula para aquele curso.

**Material** lista relações entre cursos e páginas CMS. O conteúdo continua pertencendo ao CMS; a administração pedagógica controla somente associação e liberação. O filtro de curso habilita operações contextuais como associar uma página ou mapear seções a aulas.

Nenhum conteúdo editorial é duplicado para sustentar a arquitetura administrativa.

## Área do aluno — arquitetura de informação canônica

O curso é o contexto principal. `Testes` deixa de ser uma área global paralela ao curso.

A estrutura é:

- **Meus cursos**
  - Curso selecionado
    - Visão geral
    - Material
    - Testes
- **Conta**

Quando houver apenas uma matrícula, o sistema pode entrar diretamente no curso. Quando houver várias, a pessoa escolhe o curso/turma e o contexto é preservado nas páginas seguintes.

### Barra superior

Existe uma única barra superior global canônica para o aluno.

Ela contém somente identidade/navegação global: marca, Meus cursos, Conta, tema e saída.

O nome do curso, turma, estado e navegação `Visão geral / Material / Testes` pertencem ao conteúdo e formam um **cabeçalho contextual do curso**. Esse cabeçalho não é sticky e não deve parecer uma segunda topbar.

O retorno ao nível global usa uma única formulação: `← Meus cursos`.

No mobile, a navegação inferior também é global e não duplica `Testes`, que pertence ao contexto do curso.

## Ritmo vertical e espaçamento

Espaçamento é responsabilidade do sistema global, não de correções locais espalhadas por telas.

Escala de referência:

- 4 px — micro ajuste;
- 8 px — elementos intimamente ligados;
- 12 px — controles compactos;
- 16 px — componentes relacionados;
- 24 px — grupos dentro de uma seção;
- 32 px — blocos de conteúdo;
- 48 px — seções principais;
- 64 px ou mais — mudança grande de contexto.

Valores intermediários arbitrários como 10, 14 e 18 px não devem reaparecer em primitivas compartilhadas quando a mesma relação já é representada pela escala.

A administração estabelece o ritmo entre blocos de primeiro nível em `.admin-content`; componentes deixam de depender de margens incidentais de cada tela. Cards e formulários usam a mesma escala para padding, gaps, cabeçalhos e ações.

Invariantes:

- controles de filtro formam uma faixa única e previsível antes da coleção;
- resumo do recorte aparece entre filtros e dados, sem card ornamental;
- tabela/lista é o corpo principal das coleções;
- nenhum título de seção fica colado no bloco anterior;
- nenhum botão de ação fica colado ao último campo;
- labels, inputs, ajuda e erro preservam relação visual;
- fieldsets consecutivos mantêm espaço consistente;
- cards consecutivos e estados vazios têm separação explícita;
- ações destrutivas ficam visualmente separadas das ações de fluxo normal;
- breakpoints mobile não podem zerar o ritmo vertical.

## Hierarquia visual

A área do aluno mantém a identidade visual do site, mas funciona como aplicação recorrente. Títulos de aplicação não devem herdar automaticamente a escala monumental de uma landing page.

Na administração, coleções são superfícies de trabalho e não vitrines. O padrão principal é:

**título → filtros/ações → resumo do recorte → dados → paginação → detalhe contextual quando aberto**.

Cards altos ficam reservados para sínteses, configuração ou decisão isolada. Entidades comparáveis permanecem em linhas densas.

Prioridades visuais:

1. contexto atual;
2. ação principal;
3. conteúdo disponível;
4. estado/progresso;
5. metadados.

## Política de implementação

Antes de criar qualquer componente local:

**procurar → consumir → identificar lacuna → ampliar globalmente somente se necessário → consumir**.

Primitivas de espaçamento, cards, formulários, alertas, estados, tabelas, busca, filtros, paginação e navegação pertencem ao sistema global. Código local contém composição ou fluxo específico da área.

Esta revisão reutiliza `admin-data-toolbar`, `admin-data-table`, `admin-list-summary`, `admin-pagination`, `admin-stat-grid`, `admin-card`, `admin-form-grid` e `admin-inline-actions`; não cria uma nova folha corretiva para a arquitetura de coleções.

## Critérios de regressão

A revisão deve cobrir desktop e mobile e verificar:

- uma única árvore global de navegação administrativa, organizada em Operação, Ensino, Site, Biblioteca, Análise e Sistema;
- curso não volta a funcionar como árvore horizontal de navegação;
- Cursos abre as coleções relacionadas por filtros explícitos;
- cursos, inscrições, turmas, alunos, pessoas, aulas e material continuam utilizáveis com volume por meio de busca/filtros e paginação;
- nenhuma listagem canônica finge completude por meio de um teto silencioso de 500 registros;
- Inscrições funciona sem obrigar a escolher um curso antes de listar dados;
- disponibilidade agregada só combina respostas quando existe um curso definido;
- Pessoa continua identidade global e Aluno continua participação educacional;
- ritmo vertical de primeiro nível permanece controlado globalmente;
- uma única topbar global do aluno;
- Testes subordinado ao curso;
- curso preservado ao navegar entre visão geral e testes;
- Conta global;
- formulários sem campos/botões/títulos colados;
- nenhuma regressão de acesso ao material;
- mesma pessoa em múltiplos cursos sem duplicação;
- inscrições pagas sem turma continuam válidas;
- alunos históricos importados continuam visíveis em Alunos e em Pessoas.
