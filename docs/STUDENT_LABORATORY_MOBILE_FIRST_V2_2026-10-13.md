# Laboratório — reconstrução mobile-first v2
Data: 13/10/2026. PR #243, **branch de trabalho não publicada**.

## Modelo de navegação

Laboratório é um destino global da área do aluno. Sua tela principal passa a ser um ponto de entrada **operacional**, não um painel que expõe simultaneamente todos os formulários.

- **Suas ferramentas:** Processamentos, Inventário, Predefinições de revelação e Referências de calibração. As operações liberadas pelo `student_tools_for_student` são respeitadas: Processamentos requer `lab_timer`; Inventário requer `lab_inventory`; Calibração requer `calibration`. Predefinições continua acessível conforme o contrato preexistente.
- **Cálculos e receitas:** Reciprocidade, Exposição equivalente e Receitas são `details` nativos. Esses recursos são renderizados somente se suas chaves de ferramenta estiverem liberadas. Os campos de entrada e os marcadores `data-quick-reciprocity`, `data-exposure-tool`, `data-solution-tool`, `data-solution-formula` e `data-solution-amount` permanecem ligados ao JavaScript/calculadoras existentes. Não há nova matemática ou nova receita dentro da camada visual.
- **Links legados:** `/aluno/ferramentas.php#reciprocidade`, `#exposicao` e `#receitas` continuam funcionando e expandem a calculadora correspondente. Back/Forward usa o histórico nativo.
- **Sem ferramentas liberadas:** estado explícito; não renderiza conteúdos restritos nem cálculos por padrão.

## Autoridade e segurança

`aluno/ferramentas.php` mantém autenticação, cabeçalhos privados, consulta de liberações, projeção das receitas e notas químicas autorizadas. `app/student_laboratory_view.php` só transforma essa projeção em HTML. `assets/student-laboratory.css` é a autoridade visual exclusiva da página; o shell carrega a folha apenas em sua rota.

Os serviços de roteiros de processamento, estoque, preparos, calibrações e as regras de cálculo de exposição e reciprocidade **não foram alterados**. Receitas, quantidades e avisos são provenientes de `student_solution_formulas()` e `student_experience_recipe_notes()`, sem substituição por valores fictícios em produção.

## Testes e cobertura

`tools/browser-fixture/student-laboratory.php` usa o componente real com dados controlados e identificados como ilustrativos. A suíte `student-laboratory-v2.spec.cjs` cobre vazio, liberação parcial, receitas apenas, operações apenas e acesso completo; 320 × 720, 390 × 844 e 1440 × 1100; tema claro/escuro; links e navegação; scroll e overflow; deep links; expansão das calculadoras; atualização de exposição e receita via mecânica real. A matriz de 46 telas no `student-complete-area-audit.spec.cjs` também utiliza o renderer novo na entrada Laboratório.

## Limites para integração

Esta etapa reconstrói a **página principal do Laboratório**, não todas as telas detalhadas de Processamentos, Inventário, Predefinições ou Calibrações. Suas funções e editores permanecem operacionais, mas a aceitação visual transversal deve verificar essas rotas junto com o shell novo.

O PR deve continuar em rascunho e sem merge até aprovação dos três workflows (`validate`, `student-visual-audit` e `deploy`) **no mesmo SHA final**, inspeção das capturas e verificação do percurso autenticado em ambiente de teste. O pacote gerado por `deploy` no PR não altera a hospedagem.

## Auditoria transversal Laboratório → Inventário (13/10/2026, em execução)

A fixture PHP de movimentação reutilizava o renderer real do movimento, mas continha cabeçalho sintético e navegação global antiga de **quatro** destinos. Esse arranjo não pode comprovar a arquitetura de cinco destinos aprovada. A fixture de Inventário passou a usar o próprio `student_shell_start/end`, incluindo sidebar e barra inferior, com valores físicos de teste e simulação explícita de POST. O browser audit percorre Laboratório → Inventário → histórico → Back/Forward → retorno explícito em 320, 390 e 1440 px e produz capturas de navegação. A lógica real de saldo, idempotência e autorização permanece no servidor e seus testes independentes. Este recorte não homologa estoque hospedado nem os editores de item/predefinições/calibração, que ainda devem ser percorridos com o mesmo critério.

### Reinspeção do cabeçalho de movimentação

O primeiro CI com shell real de Inventário reprovou a asserção de altura `< 108 px` no diálogo a 390 px (medidos 115,09 px). O screenshot `complete/phone/inventory-movement.png` mostra título em duas linhas e botão Fechar compacto, sem colisão. A regressão histórica era o controle Fechar ocupar a largura inteira e comprimir o título; reduzir tipografia ou forçar uma linha seria correção cosmética sem benefício funcional. A asserção foi redefinida para inspecionar ausência de interseção entre título/Fechar, contenção horizontal, largura de Fechar menor que 130 px e limite razoável de altura (< 150 px). Nova execução e captura final ainda necessárias.

### Diagnóstico de regressão das fixtures de edição (13/10/2026)

O CI do commit `6aebed1` identificou páginas de Predefinições e Calibração vazias, não um problema de estilo: a fixture incluía `app/student_workbench.php` e redeclarava `student_tools_for_student()`, causando falha fatal de execução PHP. A correção remove a função fictícia duplicada e fornece um SQLite mínimo para que o serviço canônico decida ferramentas disponíveis sem inventar permissões de curso. A auditoria geral passa a verificar HTTP 200 em todas as fixtures e existência do formulário real nas duas telas de edição. O percurso de navegação continua usando os renderers e o shell PHP de produção; dados do cenário não equivalem a login na hospedagem. CI e capturas no novo SHA seguem pendentes.

### Continuidade do editor de item em desktop

A fixture de Inventário passou a fornecer o mesmo `data-student-editor-link` e o contêiner `data-student-editor-dialog` da rota operacional. Na largura desktop o runtime real carrega o editor contextual no diálogo, preservando URL/histórico e devolvendo foco ao link ao fechar; no celular é mantida a rota dedicada com retorno explícito ao Inventário. O teste transversal distingue esses comportamentos, em vez de exigir navegação de página inteira no desktop. Novas capturas e CI continuam necessários.

## Tarefa primeiro, formulário depois — revisão de produto (13/10/2026)

A inspeção das capturas aprovadas em `6640e9d` demonstrou insuficiência qualitativa: Calibração expunha seis campos sequenciais antes do tempo do branco; Predefinições era um cadastro longo, sem hierarquia; tamanho/scroll sem overflow não certificava experiência premium. A revisão não altera contratos, entradas ou cálculos.

- **Calibração:** a medição observada (Tempo do branco) torna-se a primeira ação, com auxílio de contexto. Nome identifica a referência. Filme/revelador/diluição/temperatura/pré-banho agrupados em `details` nativo (aberto para edições, valores previamente presentes e erro de servidor); notas separadas. Todos os mesmos `name` continuam no POST, com CSRF e ownership originais.
- **Predefinições:** identificação do banho, volumes/ingredientes controlados pelo JS existente, temperatura e tempo, e agitação em disclosure opcional. Os inputs/outputs originais permanecem vinculados ao controlador de diluição e ao armazenamento. Sem wizard fictício e sem converter configuração extensa em gaveta.
- **Linguagem:** títulos orientados à tarefa, seção principal distinta dos dados secundários, campos compactos com nomes legíveis e controles de foco explícitos. CSS de componentes em `student-lab-stock.css`, não uma pilha de remendos globais.
- **Rolagem:** os painéis e diálogos mantêm propriedade explícita de scroll, com trilho fino visível e área reservada no desktop onde necessário; não há ocultação oportunista de overflow. Testes de 320/390 px verificam ordem da medição, estados de `details`, valores, largura, margens e screenshots; editor desktop, POST e falhas continuam sob regressão.

Esta revisão tem de ser julgada por capturas reais do novo SHA, inclusive em tema claro; CI verde sozinho não certifica estética. A sessão autenticada da hospedagem continua pendente.

### Correção de comportamento durante a inspeção da primeira apresentação

A primeira execução de screenshot detectou três falhas legítimas: o componente de label com `hidden` ficava visível por uma regra `display:grid` mais específica; o agrupamento visual dos `<option>` em `student-process-ux.js` podia perder o valor previamente escolhido, exibindo `Outro` na nova predefinição; e a auditoria de seleção precisava exigir a opção inicial de Parodinal. O `hidden` voltou a reger a visibilidade dos labels e `groupDeveloperSelect` preserva `select.value` durante a reorganização de optgroups. Os testes operacionais continuam exercitando a ida a Outro e o retorno para Parodinal. Não considerar a versão anterior aprovada.

### Refinamento de acabamento do diálogo

A captura de Calibração revelou separadores duplicados entre cabeçalho e formulário: `student-create-dialog-head` e `student-tool-workspace` desenhavam duas bordas, somando margem supérflua. A origem foi localizada no estilo compartilhado do workspace, corrigida no próprio componente de editor de tarefa sem alterar os demais workspaces. A verificação desktop passa a exigir apenas o divisor correto, `scrollbar-gutter:stable` e distância mínima de 12 px entre formulário e trilho de scroll. Não esconder barras com overflow indiscriminado.

## Editor de etapas de Processamentos — tarefa antes de parâmetros (13/10/2026, aguardando CI)

O editor operacional de `aluno/processamentos.php` deixa de inserir a totalidade dos controles numa longa grade inicial. A função `student_process_render_step_form` foi extraída para `app/student_process_template_step_editor.php` e é utilizada tanto pela rota autenticada quanto pela fixture PHP `tools/browser-fixture/student-process-step-editor.php`, que utiliza o `student_shell_start/end` real. O formulário mostra inicialmente operação, revelador, predefinição, tempo e temperatura. Volumes e nome personalizado são detalhes da composição; temporização de agitação e notas são detalhes secundários. Disclosures abrem automaticamente quando possuem dados em edição. A reutilização do banho na segunda revelação permanece independente, visível e acessível, sem consumo de estoque implícito.

O `student-process-manager.js` continua sendo a autoridade do estado condicional de operação/agitação e agora também controla o campo Outro revelador e abre composição ao selecioná-lo; mudança de tipo de etapa não destrói valores, apenas impede envio dos grupos não aplicáveis, como na lógica anterior. Os mesmos nomes de campos, action POST, CSRF, ID do template, ID de etapa, autorização e serviços de banco continuam sendo a autoridade. `student-processes.css` mantém a apresentação da feature, sem CSS global ou patch de shell. Smoke PHP e Playwright em 320/390/1440 px testam o renderer operacional e a visibilidade efetiva, dados existentes, ida/volta de tipos, seleção de revelador e rolagem. **Não extrapolar esta tranche para biblioteca inteira, runner ou para aprovação global do produto.**

### Inspeção efetiva do editor de etapas (13/10)

O artefato de `student-visual-audit` no SHA `90883fb5` apresentou hierarquia parcialmente melhor, mas ainda revelou um cabeçalho redundante dentro do formulário (a página já tinha título e o componente repetia Adicionar/Ajustar etapa), campos de tempo depois do detalhe de composição e rótulos em tipo mono/caixa alta com leitura comprometida no telefone. A correção remove o cabeçalho repetido, eleva duração/temperatura antes de composição e aplica tipografia legível e controles com altura mínima 46px ao editor, sem alterar semântica do POST. O Playwright registra agora também a primeira dobra **antes de manipular campos** em 320/390/1440, além das capturas em edição e preenchimento. Não aprovar apenas screenshot depois do scroll.

### Defeito de HTML detectado por Playwright (13/10/2026)

O renderer extraído em `app/student_process_template_step_editor.php` continha um fechamento sem `>` após o grupo de tempo/temperatura. O PHP era sintaticamente válido, mas o parser do navegador absorvia indevidamente o restante do formulário, ocultando agitação, reutilização e ações. A causa foi corrigida no renderer; o smoke PHP passa a recusar fechamentos incompletos e o Playwright passa a exigir a existência **dentro do formulário** dos dois `details`, reutilização do banho, agitação e notas, antes de testar visibilidade, interação ou screenshot. Não mascarar a falha em CSS ou alterar locators para fora do formulário.
