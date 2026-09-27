# Área do aluno — auditoria UI/UX premium — 27/09/2026

## Escopo

Auditoria visual e de interação feita sobre Cursos, índice de Testes, workflow de teste, Conta/Acesso e continuidade dentro do material protegido. A autoridade funcional definida nos blocos A1–A7 permanece inalterada: esta frente trata apresentação, affordance, feedback, consistência e qualidade de uso.

A regra de Design já existente continua soberana:

- `template/page.css` e os tokens dinâmicos da atividade são a autoridade visual;
- a Área do aluno consome `--title`, `--body`, `--mono`, `--bg`, `--surface`, `--surface-2`, `--text`, `--muted`, `--line`, `--line-strong`, `--inverse`, `--inverse-bg` e `--focus`;
- `assets/student-area.css` cobre somente layout, estados e comportamento específicos do aplicativo;
- não existe uma segunda paleta, uma segunda tipografia ou um tema exclusivo da Área do aluno.

## Achados

### 1. Continuidade visual quebrada pelo tema

`student_shell.php` forçava `data-theme="light"`. Assim, um aluno podia ler o material protegido no tema escuro do site e, ao voltar para Cursos/Testes/Conta, cair numa aplicação clara. Isso contrariava a própria autoridade global de tema e tornava as duas superfícies visualmente desconectadas.

**Correção:** a Área do aluno deixa de forçar tema, carrega o mesmo `template/page.js`, expõe o mesmo controle Auto/Light/Dark e usa a mesma preferência `workshop-theme` do site público.

### 2. Controles de formulário pareciam HTML cru

Inputs, selects e textareas tinham pouca diferenciação entre repouso, hover, foco, erro e desabilitado. Os botões tinham 46 px, abaixo do padrão de 54 px já usado no sistema público. Placeholders perdiam contraste e selects não possuíam tratamento visual consistente.

**Correção:** controles passam a ter geometria, estados e alvos de interação coerentes com o Design global; botões e captura usam 54 px; selects recebem tratamento visual próprio sem substituir a semântica nativa.

### 3. Validação dependia do balão nativo do navegador

Campos obrigatórios usavam quase exclusivamente Constraint Validation nativa. O resultado variava por navegador/SO, desaparecia sem deixar contexto e não marcava claramente o campo problemático.

**Correção:** `assets/student-area.js` faz progressive enhancement sobre a validação HTML existente. O servidor continua autoridade. No cliente, cada erro fica junto ao campo, usa `aria-invalid`, `aria-describedby`, mensagem em português e foco no primeiro campo inválido. E-mail, tamanho mínimo, padrão e confirmação de senha recebem mensagens específicas.

### 4. `datalist` do branqueador produzia uma lista visualmente incompatível

O campo Branqueador usava `input + datalist`; a lista era desenhada pelo navegador/SO e podia aparecer como um popover escuro, sem relação com o Design da página.

**Correção:** o branqueador vira uma escolha explícita e acessível com opções do workflow atual: Não informado, Solução peroxiacética e Cloreto férrico. Registros históricos com outro valor preservam esse valor como opção selecionada, evitando perda de dados.

### 5. Compartilhamento de testes era uma ação oculta e abrupta

A visibilidade de um teste era alterada por `<select onchange=requestSubmit()>`. Trocar a opção já disparava a escrita, sem etapa clara de confirmação e com o select nativo dominando visualmente a área de ações.

**Correção:** compartilhamento passa a ser escolha segmentada Privado/Turma/Curso com botão explícito **Salvar acesso**. A criação de um teste usa a mesma linguagem visual e semântica.

### 6. Hierarquia e densidade não correspondiam a um workspace premium

Títulos muito grandes, controles muito pequenos e áreas extensas com pouco peso de ação davam à interface aparência de protótipo. O workspace do curso usava links de texto como ações principais e o formulário de teste tinha pouca diferenciação entre etapas, blocos e captura de imagem.

**Correção:** títulos são contidos sem perder a tipografia institucional; status ganham presença; painéis, grupos, captura, revisão, mensagens e listas recebem ritmo, bordas e superfícies coerentes; ações de Material e Prática passam a ter affordance de botão.

### 7. A barra de contexto do material protegido era funcional, mas visualmente subdimensionada

Curso/turma e as três ações apareciam como uma faixa muito fina, com hierarquia menor que a relevância da navegação entre material e workspace.

**Correção:** a faixa passa a se comportar como contexto de aplicação: identificação mais clara, **Voltar ao curso** como ação principal e Testes/Conta como ações consistentes, preservando o tema da página e a responsividade.

### 8. Mobile precisava manter qualidade sem criar uma segunda interface

A navegação inferior e a ação sticky já eram decisões corretas. O problema estava em controles menores, stepper encostando no topo e grupos que não recebiam a mesma qualidade visual do desktop.

**Correção:** topbar e stepper passam a cooperar no sticky; alvos mantêm ao menos 48–54 px; escolhas segmentadas viram uma coluna quando necessário; cards/listas e ações não criam overflow; bottom nav continua sendo a navegação principal no telefone.

## Superfícies afetadas

- `app/student_shell.php`: tema e shell;
- `assets/student-area.css`: layout e estados da UI do aluno;
- `assets/student-area.js`: validação acessível e progressiva;
- `aluno/index.php`: hierarquia do workspace e ações;
- `aluno/testes.php`: criação, lista e compartilhamento;
- `aluno/teste.php`: workflow, escolhas e feedback;
- `aluno/login.php`, `aluno/senha.php`, `aluno/perfil.php`: validação e acabamento dos formulários;
- `assets/cms-header.css`: continuidade visual no material protegido.

## Não alterado

Esta revisão não muda matrícula, autorização, visibilidade, lifecycle de testes, armazenamento de mídia, liberação de aulas, regras de compartilhamento ou autoridade do CMS. Também não reabre A5b: recuperação de senha continua dependendo de infraestrutura canônica de e-mail transacional.

## Critério de pronto

A revisão só é considerada concluída quando:

- tema público e Área do aluno compartilham a mesma preferência;
- nenhuma tela principal usa o `datalist` problemático do branqueador;
- formulários obrigatórios apresentam erro inline acessível;
- botões e ações principais têm geometria e estados consistentes;
- desktop e telefone permanecem sem overflow horizontal;
- regressões PHP/estáticas e Playwright cobrem tema, validação, controles e responsividade;
- CI, build e dry-run passam antes do merge.
