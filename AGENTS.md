# Regras de operação no repositório

## Leitura obrigatória antes de trabalhar

Antes de alterar código, conteúdo, CMS, deploy ou fluxo administrativo, ler nesta ordem:

1. `docs/PROJECT_STATE.md`;
2. `docs/CMS_PROFESSIONAL.md`;
3. `docs/CMS_V3_DEPLOYMENT.md`;
4. `README.md`;
5. `DEPLOY.md`.

Não reconstruir decisões pela memória quando os documentos vigentes disserem algo diferente. Quando uma decisão estrutural, editorial ou operacional mudar, atualizar os documentos canônicos no mesmo trabalho; não deixar a documentação para uma etapa futura.

Quando um defeito se reproduzir em várias páginas, idiomas ou instâncias de um componente, tratar como problema sistêmico. Corrigir a camada compartilhada responsável e adicionar regressão nesse mesmo nível. Não usar CSS, HTML, JavaScript ou conteúdo específico de página, locale, formulário, ID ou bloco para mascarar um problema global, salvo quando a exceção for deliberada e documentada.

## Segurança de interação no editor visual

O editor WYSIWYG deve ser dirigido pela interação direta do usuário, não por ciclos automáticos de re-renderização do inspector.

- Não usar `MutationObserver` do inspector para reescrever o próprio inspector como mecanismo de seleção ou sincronização.
- Não encadear `Promise.resolve(...).then(...)` ou callbacks de carregamento que chamem novamente a mesma função de renderização sem uma mudança explícita de estado; isso pode criar loop de microtasks e congelar a interface.
- Um clique em texto editável deve ser resolvido diretamente a partir do elemento clicado, interceptando o evento antes do wrapper apenas quando necessário.
- Formulários inseridos na página não devem ser convertidos em uma única entidade textual. Rótulos, opções, ajuda e texto do botão são alvos independentes.
- Alterações no preview usadas apenas para edição não podem persistir no HTML público da página.

A hospedagem é atualizada normalmente pelo usuário em `Admin → Sistema e atualizações`. O CI publica o pacote em `production-dist`; isso não significa, por si só, que a hospedagem já foi atualizada.

## Ambiente Windows e codificação

Quando executar comandos no PowerShell antes de manipular arquivos com acentuação, configurar a sessão com:

```powershell
chcp 65001
[Console]::InputEncoding=[Text.UTF8Encoding]::new($false)
[Console]::OutputEncoding=[Text.UTF8Encoding]::new($false)
$OutputEncoding=[Text.UTF8Encoding]::new($false)
```
