# Progresso — polimento da área administrativa

Data: 2026-09-29

## Fase A iniciada

Esta etapa aplica a auditoria de produto já registrada em `ADMIN_PRODUCT_UX_UI_AUDIT_2026-09-29.md`.

### Shell mobile

- o cabeçalho mobile do shell passa a ser a única autoridade sticky no topo;
- a toolbar da rota deixa de ser sticky em telas até 800 px;
- o drawer lateral abre abaixo do cabeçalho mobile e possui rolagem própria;
- foi adicionada regressão de navegador que verifica a hierarquia sticky em 390 × 844.

### Visão geral

- removido o hero redundante abaixo do título `Visão geral`;
- removida a ação semanticamente incorreta `Editar nome do curso`;
- a identidade do site usa agora `Configurar site`;
- removidos atalhos duplicados e painéis redundantes;
- resumo operacional usa o padrão global `admin-stat-grid`;
- páginas e atividade recente usam o padrão global de tabela.

### Sistema e atualizações

- removido o hero redundante;
- removido todo o bloco `<style>` local;
- diagnóstico da hospedagem passou de uma grade própria de cards para tabela global de dados;
- histórico de backups passou de cards para tabela compacta;
- persistência protegida passou de quatro cards para uma tabela simples;
- timestamps ISO passaram a ser apresentados em formato legível para uso administrativo;
- ações de atualização e restauração continuam protegidas por confirmação.

### Gates

- o teste de arquitetura CSS agora falha se qualquer rota `admin/*.php` voltar a conter bloco `<style>`;
- criado `test-admin-product-ux-foundation.php` para proteger os contratos de hierarquia e nomenclatura desta etapa;
- o novo teste foi incluído no CI.

## Próxima etapa

Depois da validação desta fundação, seguir para as coleções operacionais: Inscrições, Turmas, Alunos e Pessoas, com foco em filtros ativos, densidade, ações de linha, detalhe, retorno de contexto e comportamento mobile.
