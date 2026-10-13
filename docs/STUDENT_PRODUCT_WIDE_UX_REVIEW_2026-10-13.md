# Auditoria transversal de experiência — Área do Aluno (13/10/2026)

**Escopo real: todo o produto autenticado.** PR #243 continua em rascunho, branch `feat/student-mobile-first-rebuild-2026-10-13`, sem merge e sem publicação da hospedagem.

## Correção de diagnóstico

A crítica do usuário é ao **sistema inteiro**, não ao Caderno. A arquitetura das cinco áreas globais não basta para produzir uma experiência mobile-first se cada superfície filha mantiver densidade, formulários, navegação e hierarquia de painel administrativo. Uma revisão localizada em Calibração, Predefinições ou Caderno é apenas parte da correção sistêmica, nunca certificado do produto.

## Evidência observada antes de novas mudanças

Foram extraídas e abertas capturas `complete/phone/*` do artefato `student-visual-audit` no SHA `bc94bbcc`, reunidas em três mosaicos de leitura: **Entrada/Cursos/Material/Dúvidas**; **Caderno/registro/processamento/resultado/comparação**; **Laboratório/Inventário/Predefinições/Calibração/Conta**. A matriz de 46 superfícies do projeto existe, mas seus screenshots incluem fixtures antigas; devem ser confrontadas com renderers e navegação operacionais antes de qualquer homologação. As observações abaixo são diagnósticos de composição, não confirmação de execução autenticada.

| Família | Achado de produto | Decisão necessária |
|---|---|---|
| Início, Agenda, Cursos | As entradas v2 são mais orientadas a próximos objetivos e listas. Porém capturas de telas filhas de Curso e estados de feedback ainda mostram dois padrões tipográficos/visuais distintos no mesmo produto. | Convergência da hierarquia de curso/material/dúvidas, sem nivelar o conteúdo didático a cartões de dashboard. |
| Material e Dúvidas | As telas profundas preservam cabeçalhos extensos, múltiplos rótulos em caixa alta, links funcionais competindo com conteúdo, e layouts de captura que ainda se parecem com desktop comprimido. | Inspecionar leitura, anotação, dúvida, edição e retorno como uma sessão contínua no telefone. |
| Caderno/registro | O registro fotográfico apresentava Exposição, Processamento e Resultado em sequência, todos montados em página longa; seus três links eram saltos de rolagem. O primeiro viewport era ocupado pela referência de cena e título repetido antes dos dados operacionais. | Três contextos independentes com endereços/Back/Forward nativos; uma parte em foco, sem destruir rascunhos ou impor ordem física. Priorizar dados de exposição, tornar foto da cena contextual. |
| Processamentos e temporização | Biblioteca, editor, roteiro e timer exibem padrões de ações heterogêneos. Capturas base não provam manutenção de temporizador ou retorno entre registros. | Caminho Caderno ↔ associação/troca de roteiro ↔ execução ↔ registro; validação com timer em andamento, pausado e concluído. |
| Laboratório, Estoque, Conta | A entrada Laboratório segue visual v2, mas bibliotecas e editores descendentes ainda mostram estética legada de formulário/controle. Predefinições e Calibração receberam revisão task-first, sem resolver o inventário inteiro. Conta ainda é sequência de campos técnicos. | Definir estrutura própria para inventário, perfis, decisões de estoque, configurações e long editors. Não usar gavetas como depósito de formulários. |
| Componentes transversais | Botões, espaçamentos, títulos e superfícies variam substancialmente entre rotas; algumas fixtures mostram barra antiga de quatro destinos. Barras de rolagem, overlays e limites de campos carecem de inspeção em middle/end state. | Exigir paridade de shell real, hierarquia tipográfica, foco, toque, scroll track, teclado, contraste e screenshots início/meio/fim. Corrigir autoridade de componente, não empilhar overrides. |

## Contrato de aceite transverso

1. **Modelo mental:** cada tela declara uma tarefa central; controles para operações não relacionadas não competem na primeira dobra.
2. **Carga cognitiva:** dados essenciais/ações principais aparecem antes dos secundários; detalhes progressivos preservam dados, erros, autorização e acessibilidade.
3. **Navegação:** cinco destinos globais idênticos, retorno contextual canônico, deslocamentos verdadeiros em histórico nativo e nenhuma mudança de URL para microestados efêmeros.
4. **Regiões e rolagem:** conteúdo termina antes da barra global; trilho de scroll não invade área de texto/botão; modal possui scroll próprio e reserva de largura real; teclado/320px/390px/1440px sem oclusão.
5. **Feedback/erros:** falha no contexto da ação, foco útil, POST inválido sem perda de rascunho, sucesso sem dupla navegação; estado indisponível e vazio correspondem a recursos/permissões reais.
6. **Paridade:** fixture usa o PHP operacional relevante; nenhuma captura de fixture obsoleta vale como prova de design do produto real.
7. **Inspeção:** abrir imagens de começo/meio/fim, estados expandido/erro/modal, claro/escuro; testes funcionais e screenshot da mesma SHA, completados com auditoria autenticada antes do merge.

## Plano de implementação transversal, sem redesenhar a entrada aprovada

- **Fundação:** reconciliar CSS, cabeçalhos, rolagem, botões e navegação entre todas as rotas filhas; inventariar fixtures antigas de quatro destinos.
- **Caderno/Processamento:** entregar a primeira alteração operacional de composição, mas nunca apresentá-la como solução global; testar chegada/retorno, formulário, foto, roteiro e execução.
- **Cursos/Material/Dúvidas:** simplificar hierarquia de ações, leitura, resposta e notas; focar continuidade do estudo.
- **Inventário/Biblioteca/Conta:** priorizar consulta e decisões operacionais, manter editores longos com informação progressiva quando semanticamente legítimo.
- **Gate:** manter PR draft até as evidências do conjunto, sem declarar aprovação estética por workflows verdes.

Os contratos de domínio e segurança de `docs/STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md` continuam superiores a ajustes cosméticos. Esta auditoria não autoriza remover dados, campos válidos, estados de timer, autorização ou validações.

## Não conformidade mensurada nas fixtures do inventário básico

Uma amostra orientada às superfícies `student-visual-audit.html`, `student-caderno-product-audit.template.html`, `student-pedagogical-feedback-audit.html`, `student-notebook-compare.html`, `student-process-product-audit.html` e `student-process-routes-audit.html` mostrou **quatro links no `student-mobile-nav`, sem Agenda, sem `student-app-shell.css` e sem `body.student-app`**. Duas outras fixtures amostradas (`student-secondary-screens-audit.html`, `student-material-context.html`) mostram cinco links, mas ainda não usam o shell v2 integral. Portanto a matriz de 46 screenshots **mistura projetos de shell diferentes**. Não há fundamento para chamar essa matriz de "46 telas reais do produto atual" e não bastaria acrescentar um quinto link visualmente: a prova exige os mesmos renderers, fontes, scroll ownership e permissões da aplicação. As capturas antigas permanecem úteis somente como inventário de conteúdo/ações legadas; a inspeção estética deve priorizar fixtures PHP operacionais e percursos com shell real.

**Bloqueio de homologação:** confrontar e substituir as fixtures legadas antes da aceitação final das 46 superfícies. Não relaxar as checagens de navegação de 5 destinos nem fingir que imagens de template antigo representam a interface real.

**Inspeção transversal adicional:** no detalhe de Registro (um dos 46 estados) a captura real encontrou a aba sticky sobrepondo o kicker e um botão sticky sobrepondo outro input. Corrigir prioridade de regiões, não simplesmente esconder scrollbar/overflow. Ação fixa requer reserva de espaço real no layout; caso contrário, ação flui após o formulário. Adicionar verificação de bounding rectangles no navegador. O mesmo critério vale para Material, formulários de Dúvidas, Inventário e conta, ainda não homologados.

## Recorte operacional Processamentos — editor de etapas (13/10/2026)

Primeiro alvo posterior à revisão Caderno: `aluno/processamentos.php` continha um formulário de etapa com revelador, predefinição, volumes, nome alternativo, tempo, temperatura, agitação, intervalos e notas lado a lado; sem priorização. O renderer da etapa foi extraído (sem duplicá-lo) para `app/student_process_template_step_editor.php`, consumido também por fixture PHP com o shell real. A hierarquia concentra a tarefa e mantém detalhes técnicos em disclosures reversíveis, com campos originais e todos os POST/ownership no servidor. CSS proprietário: `assets/student-processes.css`. JS de operação: `assets/student-process-manager.js`. Testes `student-process-step-editor.spec.cjs` e `test-student-process-step-editor.php` são gates do recorte. Validação e screenshots do novo SHA são pendentes; a biblioteca de Processamentos, o timer, o Inventário e as demais famílias continuam em auditoria. A presença de dados no editor expande seus detalhes, não os apaga.

## Conta — organização orientada a identificação (13/10/2026, aguardando CI)

O perfil autenticado `aluno/perfil.php` tinha um formulário de oito campos numa única sequência, seguido por dois painéis administrativos de segurança e sessão. O renderer foi consolidado em `app/student_profile_view.php`; a rota continua executando validação CSRF, `student_account_update_profile`, leitura de perfil, autenticação e cabeçalhos privados. A apresentação agora expõe diretamente nome, e-mail e CPF (obrigatórios); contato/Instagram e endereço/localização/CEP ficam em disclosures nativos distintos. O formulário mantém **todos os mesmos `name`, `required`, `pattern`, `autocomplete` e POST**, sem criar API alternativa nem preencher automaticamente dados não informados. As seções opcionais reabrem após erro. Uma falha de POST preserva os campos recebidos através da projeção escapada do rascunho `$_POST`, evitando perda do trabalho do aluno. Senha e encerramento da sessão são ações contextuais separadas, com CSRF e rotas originais.

A folha `assets/student-profile.css` é carregada somente para `/aluno/perfil.php`; a fixture `student-profile.php` invoca `student_shell_start/end` e o mesmo renderer PHP, sem copiar formulário fictício. Playwright verifica 320/390/1440 px, primeiro campo, disclosures, rascunho, barra de cinco destinos, geometrias, erro e imagens de primeira dobra/fim da área rolável. Smoke PHP verifica escape, todos os campos, segurança e renderer operacional. A implementação é outro **recorte da auditoria transversal**, não homologação do restante do sistema.

**Correção por inspeção (Processamentos):** as primeiras imagens do componente PHP comprovaram que a redução do número de campos simultâneos não bastou. Ainda havia título duplicado, rótulos miúdos em caixa alta e parâmetros temporais depois de composição. Ajustadas ordem, tipografia e primeira dobra, com captura obrigatória antes de qualquer interação. A mudança permanece estritamente no editor de etapa e não redefine os modelos do Inventário ou do Caderno. Perfil tem renderer específico, evitando aplicar estilo de processo a dados de conta.

## Scroll track como responsabilidade do shell — 13/10/2026

O shell desktop já reservava `scrollbar-gutter:stable` em `.student-main`, mas o breakpoint mobile revertia explicitamente a propriedade para `auto`. Essa inconsistência permite que scrollbars clássicas disputem espaço com a borda do formulário ao testar janelas estreitas, exatamente o tipo de falha visual denunciada. Alterado o proprietário canônico `assets/student-app-shell.css` para reservar trilho `stable` também no painel mobile, mantendo `scrollbar-width:thin`, rolagem real, paddings e separação física da barra de cinco destinos. Não se oculta scrollbar nem se aplica `overflow:hidden` a formulários. Os percursos com shell real — Registro, Editor de Processamentos e Conta — agora exigem `scrollbarGutter` estável e verificam overflow/folga em 320/390/1440 px. Em dispositivos com scrollbars overlay, a reserva efetiva é gerida pelo navegador; a regra permanece semanticamente correta e não deve ser transformada em compensação fixa artificial.
