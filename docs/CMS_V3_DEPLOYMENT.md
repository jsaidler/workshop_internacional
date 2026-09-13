# CMS v3 — deployment and self-updates

O estado operacional canônico do projeto está em `docs/PROJECT_STATE.md`.

A instalação pública mantém estado persistente fora do código gerado da aplicação:

- `config/local.php`
- `config/install.php`
- `storage/database.sqlite`
- `storage/installed.lock`
- `storage/logs/`
- `storage/updates/`
- `uploads/`

## Bootstrap inicial

Uma hospedagem que ainda não possui o self-updater precisa de um deploy normal do diretório `dist/`.

No Windows:

```bat
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
build-dist.cmd
```

Sincronize o **conteúdo** de `dist/` com a raiz pública preservando os caminhos persistentes listados acima.

`build-dist.cmd` gera `deploy-info.json` e `deploy-manifest.json` junto com a aplicação.

## Fluxo normal depois do bootstrap

Depois que o self-updater está instalado, o fluxo normal NÃO é FTP.

A branch de produção `wip/form-response-refinement-2026-07-16` é validada pelo CI. Um push aprovado nessa branch gera um pacote verificado e publica esse pacote em `production-dist`.

Depois disso, o usuário atualiza a hospedagem pelo próprio site em:

`Admin → Sistema e atualizações`

O painel compara a versão instalada com a versão publicada em `production-dist`. Quando houver versão nova, exibe `Instalar atualização`.

O updater:

1. lê o manifesto de produção;
2. baixa apenas os arquivos gerenciados que mudaram;
3. verifica os checksums SHA-256;
4. cria backup dos arquivos que serão substituídos ou removidos;
5. cria uma cópia consistente do SQLite antes da troca quando aplicável;
6. substitui os arquivos da aplicação;
7. nunca sobrescreve banco, uploads, configuração local, logs ou histórico de updates;
8. deixa eventuais migrações para o bootstrap da próxima requisição.

## Cache do canal `production-dist`

A detecção de atualização não pode usar diretamente uma URL estável do `raw.githubusercontent.com` sem revalidação, porque uma resposta antiga de CDN pode fazer o painel concluir incorretamente que a instalação já está atualizada logo depois de uma publicação.

Por isso:

- cada consulta de `deploy-info.json` recebe um token de cache novo em `?v=...`;
- as requisições enviam `Cache-Control: no-cache` e `Pragma: no-cache`;
- ao iniciar uma instalação, o manifesto é baixado uma vez e seu `sourceSha` passa a identificar os downloads daquela atualização;
- arquivos do pacote usam esse `sourceSha` como token de URL, evitando reutilizar conteúdo de uma versão anterior;
- antes de gravar a nova versão local, `deploy-info.json` é conferido e precisa declarar o mesmo `sourceSha` do manifesto;
- se `production-dist` mudar durante a atualização, o processo falha com `update_channel_changed` e não mistura arquivos de releases diferentes.

Esse comportamento é coberto por `tools/test-update-service.php`, que deve rodar tanto no CI de pull request quanto na validação de produção.

## Cache de assets após atualização

A aplicação não deve depender de o navegador descobrir sozinho que um CSS/JS mudou.

O renderer público lê `sourceSha` de `deploy-info.json` e acrescenta `?v=<versão instalada>` aos URLs dos CSS e do JavaScript público. Quando uma nova versão é instalada, o URL do asset muda e o navegador é obrigado a buscar a versão correspondente ao código instalado. Em ambiente sem `deploy-info.json`, o renderer usa `filemtime` do CSS como fallback.

A configuração Apache do pacote publicado também força revalidação de arquivos `.css` e `.js` com `Cache-Control: no-cache, must-revalidate`. A query string versionada é o mecanismo principal para troca de versão; a revalidação HTTP é uma defesa adicional.

Para invariantes visuais críticos de formulário, como a geometria de checkbox/radio, o renderer pode manter uma regra estrutural inline compartilhada. Isso evita que um cache externo antigo devolva uma página funcionalmente atual com um controle visualmente regressivo.

## Regra de estado

`production-dist` atualizada significa apenas que uma nova versão está disponível para instalação.

Não afirmar que a hospedagem foi atualizada enquanto o usuário não tiver executado `Instalar atualização` no painel e a versão instalada não tiver sido confirmada.

## FTP e deploy manual

FTP/manual deploy fica reservado a:

- bootstrap de uma instalação que ainda não possui self-updater;
- recuperação/contingência quando o updater não puder ser utilizado;
- manutenção excepcional explicitamente decidida.

Não tratar ausência de secrets FTP como impedimento para o fluxo normal de atualização do projeto.

## Alterações editoriais

Alterações normais de páginas, design, formulários, mídia e navegação são gravadas diretamente pelo CMS e não exigem deploy nem atualização de código.
