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

## Cache de assets após atualização

A aplicação não deve depender de o navegador descobrir sozinho que um CSS/JS mudou.

A versão instalada vem de `sourceSha` em `deploy-info.json` e é usada como query string nos assets gerenciados:

- o renderer público acrescenta `?v=<versão instalada>` aos CSS/JS públicos;
- `editor/index.php` aplica a mesma política aos CSS/JS do editor de páginas;
- `app/admin_shell.php` aplica a mesma política aos CSS/JS administrativos carregados pelo shell.

Quando uma nova versão é instalada, os URLs desses assets mudam e o navegador é obrigado a buscar a versão correspondente ao código instalado. Em ambiente sem `deploy-info.json`, cada camada usa `filemtime` de um asset estável como fallback.

A configuração Apache do pacote publicado também força revalidação de `.css` e `.js` com `Cache-Control: no-cache, must-revalidate`. A query string versionada é o mecanismo principal; a revalidação HTTP é defesa adicional.

Para invariantes visuais críticos, a correção deve estar na camada compartilhada correspondente. Checkbox/radio públicos têm invariant no renderer/CSS público; checkbox/radio administrativos têm invariant final no CSS compartilhado do admin carregado depois das folhas normais.

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
