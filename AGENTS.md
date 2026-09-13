# Regras de operação no repositório

## Leitura obrigatória antes de trabalhar

Antes de alterar código, conteúdo, CMS, deploy ou fluxo administrativo, ler nesta ordem:

1. `docs/PROJECT_STATE.md`;
2. `docs/CMS_PROFESSIONAL.md`;
3. `docs/CMS_V3_DEPLOYMENT.md`;
4. `README.md`;
5. `DEPLOY.md`.

Não reconstruir decisões pela memória quando os documentos vigentes disserem algo diferente. Quando uma decisão estrutural, editorial ou operacional mudar, atualizar os documentos canônicos no mesmo trabalho; não deixar a documentação para uma etapa futura.

A hospedagem é atualizada normalmente pelo usuário em `Admin → Sistema e atualizações`. O CI publica o pacote em `production-dist`; isso não significa, por si só, que a hospedagem já foi atualizada.

## Ambiente Windows e codificação

Quando executar comandos no PowerShell antes de manipular arquivos com acentuação, configurar a sessão com:

```powershell
chcp 65001
[Console]::InputEncoding=[Text.UTF8Encoding]::new($false)
[Console]::OutputEncoding=[Text.UTF8Encoding]::new($false)
$OutputEncoding=[Text.UTF8Encoding]::new($false)
```
