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
