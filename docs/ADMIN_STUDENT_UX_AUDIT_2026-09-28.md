# Auditoria UX — Administração e Área do Aluno — 28/09/2026

## Estado de partida

A reconciliação de domínio foi concluída antes desta revisão. O estado instalado confirmado possui um curso canônico, quatro turmas, nove matrículas ativas, três aulas e uma página CMS de material, sem turmas, matrículas, aulas ou inscrições órfãs.

Esse estado é apenas um **snapshot de validação de integridade**. Ele não é premissa de projeto da interface. A administração deve continuar legível e operacional com dezenas de cursos e centenas ou milhares de inscrições, pessoas e matrículas.

Esta etapa não altera a modelagem de dados. Ela reorganiza a experiência de uso sobre a autoridade já corrigida.

## Objetivo

A interface deve refletir a lógica do domínio sem exigir que administrador ou aluno compreendam tabelas, IDs, relações legadas ou decisões internas do sistema.

A hierarquia deve ser percebida pela navegação, pelo agrupamento visual e pelo espaçamento antes mesmo da leitura detalhada.

## Administração — arquitetura de informação canônica

### Uma única árvore de navegação

A administração não usa mais a combinação de uma navegação primária e uma segunda barra horizontal de ferramentas. Essa composição criava dois eixos concorrentes e degradava rapidamente quando novos itens eram adicionados.

A barra lateral passa a ser a única árvore global, agrupada por função:

- **Principal**
  - Visão geral
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
- **Biblioteca**
  - Mídia
- **Análise**
  - Métricas
- **Sistema**
  - Sistema e atualizações
  - Estrutura do site
  - Integridade

`Integridade` é diagnóstico técnico e não participa do fluxo operacional cotidiano.

A navegação deve suportar crescimento vertical e rolagem sem transformar novas funções em abas comprimidas ou em uma terceira camada paralela.

### Curso como contexto de trabalho

Ao abrir um curso, a navegação contextual é única e pertence ao conteúdo do curso:

1. Visão geral
2. Inscrições
3. Turmas
4. Alunos
5. Aulas
6. Material

Ela não é uma segunda navegação global. O retorno explícito `← Todos os cursos` restabelece o nível anterior da hierarquia.

`Inscrições` não pode aparecer como botão lateral desconectado das demais áreas do curso.

A tela de Cursos prioriza cursos existentes. A associação de uma página CMS a um novo curso é operação rara de configuração e deve ter menor peso visual.

### Regra para volume de dados

Listagens administrativas são projetadas para o volume futuro, e não para caber no conjunto atual.

Invariantes:

- coleções potencialmente grandes usam **busca + paginação**;
- o servidor consulta apenas a página necessária em vez de carregar uma coleção inteira e ocultar o excedente;
- não existem limites silenciosos como `LIMIT 500` apresentados ao usuário como se fossem a coleção completa;
- páginas de listagem usam densidade informacional de tabela/lista, não uma grade de cards por entidade;
- ações principais permanecem na linha do registro, sem obrigar a percorrer cards altos;
- contagens e resumos são calculados sem um ciclo de consultas por item quando uma consulta agregada ou correlacionada pode resolver o conjunto;
- filtros, busca e página atual são preservados ao abrir e operar um registro;
- no mobile, tabelas podem rolar horizontalmente, mas a hierarquia e os controles de busca/paginação continuam utilizáveis.

A página de **Cursos** usa uma tabela paginada e pesquisável. A visão **Alunos** do curso também é paginada e pesquisável.

**Inscrições** mantém o padrão lista + detalhe, mas a lista é pesquisável, filtrável e paginada. **Pessoas** segue o mesmo princípio e não depende de um teto arbitrário de registros.

### Inscrições

A tela deve responder imediatamente:

- quantas inscrições existem;
- quantas aguardam pagamento;
- quantas estão confirmadas sem turma;
- quantas já possuem turma;
- como as disponibilidades se distribuem.

Pagamento, confirmação da inscrição e atribuição de turma são dimensões independentes.

A agregação de disponibilidade é uma ferramenta operacional para formação de turmas, não apenas uma reprodução do formulário individual.

### Pessoas

`student_users` permanece a autoridade global de identidade. A tela Pessoas deve apresentar a pessoa como entidade permanente, com conta, inscrições e matrículas, e não como uma caixa de entrada técnica.

A explicação técnica sobre tabelas e integridade não participa do texto principal da operação. Diagnóstico permanece disponível como ação secundária.

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

- nenhum título de seção fica colado no bloco anterior;
- nenhum botão de ação fica colado ao último campo;
- labels, inputs, ajuda e erro preservam relação visual;
- fieldsets consecutivos mantêm espaço consistente;
- navegação contextual não encosta no conteúdo seguinte;
- cards consecutivos e estados vazios têm separação explícita;
- ações destrutivas ficam visualmente separadas das ações de fluxo normal;
- breakpoints mobile não podem zerar o ritmo vertical.

## Hierarquia visual

A área do aluno mantém a identidade visual do site, mas funciona como aplicação recorrente. Títulos de aplicação não devem herdar automaticamente a escala monumental de uma landing page.

Na administração, coleções são superfícies de trabalho e não vitrines. Cards altos são reservados para sínteses ou decisões isoladas; listas de entidades usam linhas densas e comparáveis.

Prioridades visuais:

1. contexto atual;
2. ação principal;
3. conteúdo disponível;
4. estado/progresso;
5. metadados.

## Política de implementação

Antes de criar qualquer componente local:

**procurar → consumir → identificar lacuna → ampliar globalmente somente se necessário → consumir**.

Primitivas de espaçamento, tabs, cards, formulários, alertas, estados, tabelas, busca, paginação e navegação pertencem ao sistema global. Código local deve conter apenas composição ou fluxo específico da área.

## Critérios de regressão

A revisão deve cobrir desktop e mobile e verificar:

- uma única árvore global de navegação administrativa, sem barra horizontal concorrente de ferramentas;
- cursos, inscrições, pessoas e alunos do curso continuam utilizáveis com volume por meio de busca e paginação;
- nenhuma listagem canônica finge completude por meio de um teto silencioso de 500 registros;
- ritmo vertical de primeiro nível controlado globalmente;
- uma única topbar global do aluno;
- Testes subordinado ao curso;
- curso preservado ao navegar entre visão geral e testes;
- Conta global;
- curso administrativo com uma única navegação contextual;
- Inscrições dentro do contexto do curso;
- formulários sem campos/botões/títulos colados;
- nenhuma regressão de acesso ao material;
- mesma pessoa em múltiplos cursos sem duplicação;
- inscrições pagas sem turma continuam válidas;
- alunos históricos importados continuam visíveis no curso e em Pessoas.
