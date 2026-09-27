# Auditoria UI/UX — administração e área do aluno — 27/09/2026

## Escopo

Esta auditoria parte do estado pós-reconciliação do domínio de cursos. O objetivo não é criar um novo design system, mas consumir as autoridades já existentes e corrigir arquitetura de informação, hierarquia visual e fluxo operacional.

Regra de implementação: procurar → consumir → identificar lacuna → ampliar somente quando necessário. Nenhum novo componente genérico foi criado para resolver problemas locais.

## Administração — problemas encontrados

### 1. Navegação misturava domínios distintos

A área superior chamada **Inscrições** agrupava Cursos, Inscrições, Pessoas, Formulários, Outras respostas e Integridade. Isso misturava:

- domínio educacional/comercial: cursos, inscrições, turmas, pessoas;
- CMS: formulários e respostas;
- diagnóstico técnico: integridade de dados.

### Correção

A navegação principal passa a ser:

- Conteúdo
- Cursos
- Métricas
- Mídia
- Configurações

Dentro de **Cursos** ficam apenas:

- Cursos
- Inscrições
- Pessoas

**Formulários** e **Respostas** voltam ao contexto de Conteúdo. **Diagnóstico de dados** vai para Configurações.

## 2. Curso parecia ser criado como objeto paralelo à página

A tela Cursos oferecia um formulário "Associar página como curso", fazendo parecer que o curso nascia ali. Isso contrariava o modelo consolidado: a página continua sendo a unidade editorial e recebe o papel de página de curso.

### Correção

- a tela Cursos deixa de criar curso;
- a lista de Páginas mostra um chip **Curso** quando a página já possui esse papel;
- o menu da própria página oferece **Usar esta página como curso**;
- quando já vinculada, o mesmo menu oferece **Administrar curso**;
- nenhuma cópia de página, editor ou conteúdo é criada.

## 3. Tela inicial de Cursos não ajudava a decidir a próxima ação

Os cards mostravam apenas nome/página/formulário.

### Correção

Cada curso passa a mostrar indicadores operacionais de inscrições, turmas, alunos e aulas e oferece duas ações claras:

- Inscrições
- Administrar curso

## 4. Configuração expunha detalhe técnico demais

O slug interno do curso aparecia como campo editável ao lado da página CMS.

### Correção

O slug continua existindo como detalhe técnico, mas deixa de ocupar a interface. A configuração expõe apenas:

- nome usado na administração;
- página do curso;
- formulário de inscrição;
- atalhos para editar a página e o formulário nas ferramentas canônicas.

## 5. Relação Pessoa × Curso precisava ficar visualmente explícita

A lista contextual de alunos de um curso não levava à identidade global da pessoa.

### Correção

O nome do aluno passa a abrir **Pessoas**, preservando a separação:

- Pessoas = identidade global;
- Curso → Alunos = matrículas daquele curso.

---

# Área do aluno — problemas encontrados

## 1. Hierarquia visual estava editorial demais para uma aplicação

Títulos entre 54 e 86 px, uppercase e grandes vazios faziam a área se comportar como landing page. Isso reduz densidade informacional e atrasa o acesso ao que interessa: material, progresso e testes.

### Correção

- escala do título principal reduzida;
- uppercase removido dos títulos de aplicação;
- menor espaço vertical entre contexto, título e ação;
- sombras e cartões reduzidos;
- largura útil aproximada reduzida para melhorar leitura e ritmo.

## 2. Navegação usava linguagem de catálogo

A aba **Cursos** funcionava como home, mesmo quando o aluno possuía uma única matrícula.

### Correção

A navegação passa a usar:

- Início
- Testes
- Conta

No mobile os números 01/02/03 foram removidos. O seletor de tema permanece consumindo o controle global, mas com rótulos em português: Auto / Claro / Escuro.

## 3. Material não escalava para várias páginas

Cada página de material era renderizada como botão primário, o que produz competição visual quando um curso possui várias páginas.

### Correção

Material passa a ser apresentado como lista de recursos, uma linha por página CMS, com uma única ação **Abrir**.

## 4. Progresso de aula precisava ser legível como estado, não decoração

### Correção

- resumo passa a usar “N de N aulas liberadas”;
- estados usam rótulos diretos: Liberada / Agendada / Aguardando;
- grade de aulas passa a se adaptar à quantidade de aulas em vez de assumir sempre três colunas.

## 5. Texto de contexto usava jargão de implementação

Termos como “workspace” não ajudam o aluno a decidir o próximo passo.

### Correção

A cópia passa a responder diretamente:

- o que já está liberado;
- onde abrir o material;
- onde continuar os registros de prática.

---

# Limites desta rodada

Esta rodada corrige arquitetura de informação e hierarquia das superfícies principais. Não altera:

- regras de domínio;
- modelo de dados;
- editor CMS;
- design tokens globais;
- componentes genéricos de formulário, botão, validação ou tema.

Próximas auditorias visuais devem observar as telas renderizadas em desktop e mobile e tratar apenas lacunas concretas que permanecerem após esta reorganização.