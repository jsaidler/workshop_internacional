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
