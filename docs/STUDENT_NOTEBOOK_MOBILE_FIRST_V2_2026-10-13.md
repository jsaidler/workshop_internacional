# Caderno — mobile-first v2 (13/10/2026)

**Estado:** branch isolada `feat/student-mobile-first-rebuild-2026-10-13`, PR #243 em rascunho; nenhuma alteração na hospedagem.

## Princípio editorial

O Caderno registra processos fotográficos, não prescreve a ordem do trabalho. Exposição, processamento e resultado são partes independentes do mesmo registro. A nova tela de coleção substitui a aparência de painel de progresso por uma listagem de objetos de pesquisa com conteúdo e ações factuais.

- **Ação prioritária:** criar registro, abrir um registro existente ou criar o primeiro caso não haja nenhum.
- **Informações do cartão:** contexto pessoal ou curso/turma, título, atualização, data declarada, presença de informações de exposição, etapas de processamento e resultado. Esses dados não são transformados em score, porcentagem ou requisito de avanço.
- **Ações secundárias:** comparar com outro registro ou com a origem; duplicar; alterar visibilidade somente quando o contexto for de curso; excluir via rota de confirmação existente. Ações secundárias ficam em disclosure, não competem com a abertura do registro.
- **Comparação de dois registros:** modo temporário de seleção conduzido por `assets/student-experience.js`; criação deve preceder comparação tanto na ordem visual quanto no DOM.
- **Registros compartilhados:** área distinta e condicionada à existência de itens que outras pessoas autorizaram; a rota `/aluno/teste-compartilhado.php?id=...` mantém o controle de acesso do servidor.
- **Criação:** utiliza sem alterações `student_record_create_dialog`, CSRF `student-process-notebook`, validação e preservação do draft após erro. O formulário curto não substitui as rotas de edição de exposição/processamento/resultado.

## Arquitetura e autoridade

- `aluno/caderno.php` continua sendo a única rota operacional autenticada. Reconciliação, consulta de registros próprios e compartilhados, tratamento de `create`, `duplicate` e `visibility` não mudaram.
- `app/student_notebook_view.php` é o novo componente HTML da coleção. Continua consumindo as mesmas consultas e projeções de conteúdo por registro, identificadas pelo ID do proprietário.
- `assets/student-caderno.css` permanece **a autoridade canônica** dos estilos do Caderno; as regras v2 são isoladas sob `.student-v2-notebook` e não reestilizam editores ou formulários de detalhes.
- Nenhuma alteração nos serviços `student_process_*`, `student_test_set_visibility`, `student_research_source` ou nos endpoints de exclusão. Não transferir essas operações ao navegador.
- `tools/browser-fixture/student-notebook.php` reutiliza o componente da aplicação com dados simulados sem acessar a base real.
- `tools/browser-tests/student-notebook-v2.spec.cjs` cobre lista vazia, pessoal, mista, compartilhada, muitos registros, erro de criação, controles de comparação, controles de gerenciamento e viewport 320/390/1440 em tema claro/escuro.

## Limites

A nova composição redesenha **a coleção do Caderno**; os editores de um registro individual continuam com sua lógica e disposição existentes, cobertos por `student-caderno-product-audit`. Isso não significa aprovação integral de UX das telas de detalhe, que continuam sujeitas a revisão na integração completa.

Antes do merge: `validate`, `student-visual-audit`, `deploy` verdes no mesmo SHA; inspeção do Caderno em celular; retorno real pós-criação, comparação, compartilhamento e exclusão autorizada; inspeção do Laboratório, ainda não migrado.

## Percurso Caderno a partir da navegação global (13/10/2026)

A suíte `student-cross-route-journeys.spec.cjs` passa a abrir o Caderno por navegação real a partir de Agenda e usa `app/student_notebook_view.php` e `student_record_create_dialog()` via fixture PHP. O teste preenche título e turma, fecha a gaveta por Escape, verifica retorno do foco, reabre e confere preservação do rascunho e ausência de entradas espúrias no histórico. Em seguida abre um registro, retorna à lista e confere controles reais de comparação, duplicação, visibilidade e exclusão contextual em registro de curso. O teste verifica a disponibilidade e o contexto dessas operações, não substitui o CRUD/DB nem confirma uma gravação na hospedagem. Capturas da gaveta reaberta passam a integrar os artefatos visuais. Nenhum dado factual de processamento é inferido.

## Registro individual e prioridade sistêmica — 13/10/2026

**Nota de escopo:** o problema de experiência não é exclusivo do Caderno. O diagnóstico e o plano completos pertencem a `docs/STUDENT_PRODUCT_WIDE_UX_REVIEW_2026-10-13.md`, abrangendo as cinco áreas e todas as rotas filhas. A implementação deste recorte não homologa o produto.

O detalhe operacional de `aluno/teste.php` começa a migrar de uma tela administrativa longa para três **contextos pares, não sequenciais**: Exposição, Processamento e Resultado. A navegação é renderizada por `app/student_record_workspace_navigation.php`, com links HTTP `#exposicao`, `#processamento`, `#resultado`; `assets/student-record-workspace.js` apenas realça uma seção de cada vez, sem desmontar formulários nem usar history API artificial. Se não houver JavaScript, as três continuam expostas, com âncoras utilizáveis. Deep links para `#adicionar-etapa` abrem o disclosure e selecionam Processamento, inclusive após recarga. Em erro de POST, a ação determina a parte inicial; em sucesso, continua valendo 303 + hash pré-existente.

**Exposição:** dados de exposição vêm antes da foto de referência da cena, que migra para um `details` expansível, aberto se já houver mídia. O upload e a exclusão permanecem no mesmo formulário PHP com os serviços e CSRF originais. Resultado não depende da etapa anterior e mantém sua mídia e notas. A região de ação salvar fica sticky dentro do painel de leitura (mobile) e não sobre a barra global.

**Verificação planejada:** teste unitário PHP exige reuso do nav na rota real, hooks e serviços preservados; Playwright na fixture com nav PHP real testa 320/390/1440, Back/Forward, rascunhos em DOM, deep links, fallback sem JS, estado de falha, screenshots e folga de scroll. A fixture não substitui aplicação autenticada. CI e inspeção real são condições de aceite deste SHA.

### Correção após abrir as capturas reais (13/10)

O screenshot `record-workspace/phone-exposure.png` do SHA `e74d263f` evidenciou duas falhas visuais não capturadas pelos testes de história: a aba sticky cobria parte do kicker “Exposição” ao retornar via fragmento, e a ação sticky “Salvar exposição” aparecia sobre o campo de tempo ao rolar. O primeiro ajuste foi aumentar `scroll-margin-top` da seção sob o seletor contextual. A ação salvar volta ao fluxo do formulário, evitando ocupar a região de leitura por sobreposição; a meta de ação persistente só será retomada com uma região **separada e reservada** no shell, não com overlay sobre inputs. Playwright agora exige cabeçalho totalmente abaixo das abas, `position:static` e ausência de interseção de elementos antes de fotografar o novo SHA. Esta é uma inspeção real que reprovou o primeiro resultado visual apesar de o teste de navegação ter passado.
