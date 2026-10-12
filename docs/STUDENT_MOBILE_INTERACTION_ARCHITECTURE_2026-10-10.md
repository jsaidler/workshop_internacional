# Arquitetura de interação mobile — Área do aluno
Data: 10/10/2026
Status: **decisão canônica aprovada para implementação incremental; NÃO significa auditoria concluída**.
Autoridades superiores: `STUDENT_NAVIGATION_CANONICAL_2026-10-07.md` (navegação e Back/Forward), `STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md` (modelagem e inspeção), `STUDENT_UI_STATE_MATRIX_2026-10-09.md` (estados e evidência). Este documento não cria um quinto destino global.

## Problema e regra de decisão
O trabalho anterior validou responsividade de controles sem questionar se o telefone deveria apresentar tantas ações permanentes. O relato de galeria forçada originou dois botões expostos na página, mas esta é uma escolha secundária dentro da intenção única **adicionar imagens**. O produto deve oferecer a intenção, só então pedir o meio.

**Antes de introduzir um controle**, distinguir:
1. **Página dedicada:** leitura extensa, fluxo laboratorial, documentação de exposição/processamento/resultado, editor de muitas etapas, comparação detalhada, operações que devem sobreviver à saída da página.
2. **Ação primária inline:** é usada frequentemente no contexto, executa-se sem escolha adicional e precisa permanecer visível. Ex.: Salvar, Iniciar/Pausar cronômetro, acesso à seção relevante.
3. **Gaveta inferior transitória:** escolha contextual curta (normalmente 2–5 opções), formulário pequeno ou configuração simples sem necessidade de rota própria. Uma única ação visível abre as alternativas; nunca usar gaveta apenas para esconder um formulário longo.
4. **Diálogo de confirmação:** ação destrutiva/irreversível ou consequência material; decisão explícita e contextual, sem confundir com menu de escolhas.
5. **Disclosure inline:** detalhes auxiliares que precisam permanecer comparáveis ao documento, sem bloquear leitura nem requerer tomada de decisão imediata.

## Contrato compartilhado da gaveta transitória
- Em tela estreita (<=760 px), abrir na base da viewport, acima do shell no **top layer nativo de `dialog.showModal()`**. O backdrop isola a atividade secundária; a barra de quatro destinos continua no DOM, com estado e ordem invariantes, retomando após fechar.
- No desktop, o mesmo conteúdo pode abrir em diálogo compacto centralizado: a arquitetura semântica permanece uma única ação e a mesma lista de opções; não criar segunda implementação.
- Usar `button type=button` real como acionador, `dialog` com nome acessível, foco inicial em controle adequado, ação Fechar, fechamento via Escape/Cancel (e backdrop quando permitido), e restauração de foco para quem abriu. Não criar `history.pushState` fictício para cada gaveta.
- Conteúdo interno com rolagem, limite pela altura dinâmica e `safe-area-inset-bottom` e sem botão/controle ocluído pelo teclado. Um painel aberto não deve impedir rolagem na parte visível do próprio painel.
- Antes de fechar uma gaveta com texto não salvo, oferecer decisão apropriada ou mantê-la aberta em falha. Seleção cancelada no picker nativo não envia nada nem fecha a gaveta.
- Não empilhar gavetas. Se uma escolha exigir etapa longa ou outro editor, fechar a gaveta e navegar para página dedicada; tratar confirmação destrutiva separadamente.
- Uma ação no servidor continua submetida a autorização, CSRF, limites, persistência e validação existentes. A gaveta não inventa segunda API nem estado de laboratório.
- `prefers-reduced-motion` deve ser respeitado. Não introduzir animação obrigatória, dependência externa ou bloqueio de teclado.

## Aplicação nas 46 superfícies da matriz (decisão arquitetural, NÃO atestado visual)
| Família/estados | Superfície principal | Gaveta quando melhora a tarefa | Manter fora de gavetas |
|---|---|---|---|
| Acesso e conta (login, ativação, perfil, senha) | Páginas e formulários de identidade | Nenhuma inicialmente | Senha, dados pessoais, recuperação, expiração |
| Início e seus estados (4) | Feed de orientação e tarefas | Somente escolha contextual curta comprovada | Aulas e avaliações |
| Cursos e estados (4) | Seleção de turma e visão geral | Filtros compactos, se necessários | Curso, aulas, material, dúvidas |
| Material e notas | Leitor CMS próprio; painel de notas já existente | Ação curta de anotação/encaminhamento onde não conflita com seleção | Leitura, lista/edição longa das anotações, seleção de texto |
| Dúvidas (4) | Lista e conversa; composição com rascunho | Filtros/visibilidade curta se justificar | Escrever uma dúvida/resposta longa, erros do formulário |
| Caderno (4) | Lista; registro; exclusão contextual | **Criação curta de registro** pode usar sheet se preservar campos e contexto; opções de ação | Destruição com confirmação; edição da ficha completa |
| Exposição e processamento (5) | Três partes independentes de registro e editor de etapas | **Adicionar imagens** (escolher câmera/galeria) | Sequência, cronômetro, registro retroativo e histórico factual |
| Resultado e avaliação (4) | Conteúdo de resultado e conversa | **Adicionar imagens** | Enviar para avaliação, observações longas |
| Comparações/linhagem (3) | Seleção e comparação detalhada | Critérios curtos de seleção se comprovados | Diferenças e leitura lado a lado |
| Processamentos/laboratório (3) | Biblioteca/editor/runner dedicado | Ações rápidas não destrutivas em um item | Editar nove etapas; operar timer; declarar posição factual |
| Inventário (4) | Lista, edição e movimentação | Filtros ou movimento **curto** somente após auditar erros/capacidade | Histórico, reconciliação, quantidade negativa e conflitos |
| Predefinições/calibração (4) | Lista e editores próprios | Escolha de predefinição quando contextual | Calibração e formulários extensos |
| Ferramentas (2) | Laboratório/bancada; toolbox já usa `dialog` | Toolbox existente pode ter geometria de gaveta, sem duplicação | Ferramenta de muitos campos e timer ativo |

## Entrega em fases, bloqueios e critérios de aceite
**S-01 (agora):** ação única **Adicionar imagens** nas duas partes do Caderno → sheet `Fotografar` e `Escolher da galeria` (`multiple` e sem `capture`), reutilizando backend de upload já validado. Aplicar shell transiente reutilizável com foco, fechamento, feedback e retorno. Testes em 390×844 e 1440×1100: aberto, fechado/ESC, falha, seleção múltipla, câmera singular, botão visível, sem oclusão.
**S-02:** revisar criação de registro, toolbox e menus rápidos conforme tamanho do formulário e fluxo do aluno, sem mudar direitos ou dados.
**S-03:** depois de comparar os demais 46 estados com DOM real, decidir filtros/contextuais, sem transformar rascunhos longos ou tarefas laboratoriais em gavetas.
**Gate:** as 46 superfícies e seus estados aplicáveis continuam sob `STUDENT_UI_STATE_MATRIX_2026-10-09.md`; screenshot verde e fixture de rota não autenticada não equivalem à inspeção integral. Em hospedagem é obrigatória confirmação real do picker Android, inclusive seleção múltipla, cancelamento, falha de upload, Back/Forward e atualização parcial.

## Evidência e corte da implementação S-01 (10/10)

A primeira aplicação do contrato usa um só botão **Adicionar imagens** por parte da ficha e dois inputs dentro do diálogo compartilhado. Em testes de Chromium a 390×844 e 1440×1100, estados abertos/erro foram renderizados e inspecionados: `sheets/*-scene-open.png`, `sheets/*-result-open.png`, `sheets/*-error.png` (seis imagens). A mudança preserva os endpoints e o limite de seis imagens; só altera apresentação e integração de erro/retorno na interface. A auditoria integral de 46 superfícies continua aberta. A verificação com picker nativo Android e gravação real depende de atualização e uso na hospedagem.

### Auditoria de paridade visual do Caderno — atualização de 10/10

O componente `student_record_media_controls()` deve abastecer tanto o PHP operacional quanto o renderer `student-caderno-product-audit.php`. O HTML de composição da fixture usa marcadores por fase e não pode reproduzir botão de imagem por texto independente. A inspeção do CI `5837a3c` confirmou isso em estados mobile de Exposição, Resultado, processamento com/sem roteiro e versão desktop de Exposição; ver matriz G-01/S-01. Uma falha secundária expôs stylesheet duplicado e fora de ordem na fixture; corrigiu-se sua marcação de carregamento para preservar o CSS canônico, sem alterações compensatórias de estilo. Essa evidência não completa a auditoria dos 46 estados, que permanece aberta.

### S-02 — Formulário curto de criação de registro (11/10/2026)

O diálogo `Novo registro` passa a ser gaveta inferior no telefone e permanece central no desktop. Os campos não se transformam em escolhas fragmentadas; são um formulário único, em contexto. Erro de POST de criação deve reabrir o mesmo formulário com dados anteriores e alerta focalizado, em vez de deixar uma mensagem no topo do Caderno. Cancelar/Escape não descarta o rascunho enquanto a página permanece aberta. Ação de criar continua POST/redirect do Caderno. Toolbox é uma camada transitória existente e permanece em análise; editores longos não recebem geometria de gaveta por efeito colateral. A alteração só pode ser aprovada após testes e inspeção visual real.

**Inspeção S-02 documentada (12/10/2026, commit `8997e75`):** os três workflows do PR #229 foram concluídos com sucesso neste SHA. Extraí e examinei as seis capturas `record-create/{phone,desktop}-{course-filled,server-error,personal}.png` (390×844 e 1440×1100) e os estados básicos `complete/{phone,desktop}/new-record.png`. No telefone o diálogo é gaveta inferior, com título, data, escopo/turma e ação final visíveis na viewport capturada; o erro é apresentado dentro da gaveta, contextualizado, sem suprimir os campos. Sem matrícula, só a opção Pessoal aparece. No desktop o formulário fica em diálogo compacto centralizado. Playwright verifica fechamento por Escape e Fechar com foco devolvido a ambos os acionadores, manutenção de rascunho e foco no alerta reaberto; o teste PHP verifica escape e restauração de título/data/escopo/turma. Aprovação **delimitada à aparência e ao contrato da fixture que reutiliza o renderer PHP real**, não ao POST autenticado na hospedagem nem à matriz completa de 46 superfícies. O pacote do PR #229 ainda não estava integrado na base ao produzir esta anotação.

**S-02b — Atalhos (12/10/2026, em revisão):** a fixture `student-visual-audit.html?screen=toolbox` representava uma gaveta inexistente: inseria um **Temporizador** e apenas dois campos de Exposição equivalente, enquanto `student_shell_toolbox()` renderiza dois cálculos completos de reciprocidade/exposição, mais links para ferramentas, e não inclui timer local. A auditoria estava avaliando uma superfície fictícia. `tools/browser-fixture/student-toolbox-real-audit.php` agora chama diretamente a função PHP operacional sob permissões de ferramentas sintéticas controladas, e as duas famílias de screenshot passam a usá-la. URL antiga redireciona para a renderer canônica. **Mudança de UX:** dois cálculos ficam em disclosures nativos, inicialmente recolhidos e mutuamente exclusivos, preservando os mesmos campos, resultados e o comportamento da ferramenta; o link de Processamentos duplicado foi removido e os demais destinos mantidos. Fechar/Escape deve devolver foco ao botão Atalhos. Gates ainda pendentes: verificação PHP de permissões e paridade, Playwright do formulário real com/sem permissão, screenshots do menu aberto e cálculos expandidos (telefone/desktop) e inspeção visual, além da suíte geral de 46 superfícies. Não afirmar aprovação da UI, instalação ou uso no Android até evidência respectiva.


**S-02b — recorte aprovado para integração (12/10, `aee23d0`):** em Atalhos, manter um único acionador contextual no cabeçalho, independentemente de largura mobile, em vez de inventar quinto destino global; o próprio diálogo PHP é fonte da verdade. Dois cálculos de acesso menos frequente iniciam recolhidos e são mutuamente exclusivos, sem alterar parâmetros nem resultados. A revisão das 18 capturas (320/390/1440 × fechado/menu/reciprocidade/exposição/final/permissão restrita) não detectou corte de opções ou perda dos destinos globais. Em tela de 320 px, a marca sofre truncamento visual controlado pela largura do cabeçalho, ponto de polimento a considerar separadamente; não ocultar Conta nem Atalhos para preservar o texto integral. Auditoria completa das 46 superfícies e funcionamento real na hospedagem ainda abertos.

### I-01 — cadastro do Inventário (12/10/2026, em revisão)
O inventário não precisa de gaveta para cada formulário. O cadastro de um item inclui nome, classe, unidade, quantidade, categoria, lote, datas, limite, localização e anotações — extenso demais para uma escolha rápida. Mantê-lo em disclosure inline, mas corrigir perda de trabalho após erro de servidor: reabrir, restaurar todas as entradas, expor alerta contextualizado e restaurar foco após cancelamento. Somente a movimentação (direção/quantidade/motivo) é candidata a modal contextual, mediante auditoria da persistência e dos erros. O renderer do formulário deve ser compartilhado com a auditoria para não mascarar falta de campos.


**I-01 — Formulário longo de Inventário (12/10):** decidiu-se manter a criação de item na própria página, em vez de transformar classificação, quantidades, lote, datas e localização em uma gaveta alta e pouco utilizável. O renderer PHP compartilhado preserva o POST inválido e a opção de estoque; o controlador comum foca o aviso AJAX e faz scroll até ele. Seis capturas visualmente inspecionadas em telefone e desktop confirmaram a correção do alerta fora do viewport. A aprovação é limitada ao formulário e aos estados renderizados em fixture; a publicação depende do gate final. **Próxima investigação I-02:** editor de item acessado por deep link, que hoje tenta aplicar atualização parcial a uma lista ausente na rota própria; corrigir a continuidade entre modal e página sem impor drawer para editor extenso.


**I-02 — edição de item, em revisão (12/10):** a edição extensa é página dedicada no telefone, com retorno explícito. No desktop continua disponível como editor contextual da lista. Ambos usam o mesmo renderer PHP e o mesmo endpoint. A URL direta usa POST/redirect nativos, evitando AJAX que exige regiões de listagem inexistentes; erro de formulário preserva valores e fica em contexto, sem alterar o estoque por efeitos de interface. A implantação e o teste Android não estão atestados.

**I-02 — refinamento de retorno contextual (12/10):** a captura da página dedicada de edição em telefone mostrou duas ações equivalentes — `← Inventário` e `Fechar`, ambas retornando à mesma lista —, enquanto no diálogo desktop `Fechar` é necessário. O renderer continua único, mas a superfície direta suprime apenas a ação duplicada por seletor contextual proprietário `student-lab-stock.css`; a página conserva o retorno explícito e o diálogo conserva o fechamento. A regressão distingue ambos os contextos. Integração e instalação ainda pendentes.
