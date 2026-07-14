## Ambiente Windows e codificação

Quando executar comandos no PowerShell antes de manipular arquivos com acentuação, configurar a sessão com:

```powershell
chcp 65001
[Console]::InputEncoding=[Text.UTF8Encoding]::new($false)
[Console]::OutputEncoding=[Text.UTF8Encoding]::new($false)
$OutputEncoding=[Text.UTF8Encoding]::new($false)