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
