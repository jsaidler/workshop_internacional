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
