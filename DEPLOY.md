# Deploy on HostGator

O estado operacional canônico do projeto está em `docs/PROJECT_STATE.md`. Requer Apache, PHP 8.2+, `pdo_sqlite`/`sqlite3`, extensão DOM e HTTPS.

## Fluxo normal vigente

Depois que o self-updater está instalado na hospedagem, **toda atualização de código da aplicação é feita pela área administrativa do próprio site**:

`Admin → Sistema e atualizações → Instalar atualização`

O fluxo é:

1. alterações de código são integradas na branch de produção `wip/form-response-refinement-2026-07-16`;
2. o GitHub Actions valida o código e constrói `dist/`;
3. o workflow publica o pacote verificado na branch `production-dist`;
4. o painel `Sistema e atualizações` consulta o canal sem reutilizar uma resposta antiga de cache e detecta a nova versão;
5. o usuário executa `Instalar atualização`;
6. o updater baixa e valida os arquivos modificados, cria backup e substitui a aplicação preservando o estado persistente;
7. a próxima requisição executa eventuais migrações pendentes.

A publicação de `production-dist` **não significa que a hospedagem já foi atualizada**. Só considerar a versão remota instalada depois da aplicação pelo painel e da verificação da versão exibida em `Sistema e atualizações`.

Não existe etapa de FTP no fluxo de atualização. O workflow de produção não possui credenciais, verificação nem envio FTP.

## Consistência do canal de atualização

O canal remoto muda por branch (`production-dist`), portanto o updater não pode consultar arquivos por uma URL estável sujeita a cache intermediário.

- A consulta de status usa token novo na URL e cabeçalhos `no-cache`.
- Os arquivos de uma instalação usam o `sourceSha` do manifesto como token de URL.
- `deploy-info.json` precisa confirmar o mesmo `sourceSha` antes da troca final.
- Se o canal mudar no meio da instalação, o processo é abortado em vez de combinar arquivos de duas versões.

Isso evita o caso em que o GitHub Actions já publicou uma versão nova, mas o admin ainda enxerga o `deploy-info.json` anterior por cache do `raw.githubusercontent.com`.

## O que o updater preserva

O updater não substitui:

- `config/local.php`;
- `config/install.php`;
- `storage/database.sqlite`;
- `storage/installed.lock`;
- `storage/logs/`;
- `storage/updates/`;
- `uploads/`.

Antes de substituir arquivos gerenciados, ele verifica SHA-256 e mantém backups locais. O banco pode ser copiado como segurança antes da atualização, mas não é restaurado automaticamente durante rollback de arquivos para evitar perda de inscrições ou edições posteriores.

Os assets `.css` e `.js` publicados pela aplicação são servidos com `Cache-Control: no-cache, must-revalidate`. Depois que uma nova versão é instalada, o navegador deve revalidar esses arquivos em vez de continuar usando silenciosamente uma cópia anterior.

## Bootstrap inicial

Uma instalação completamente nova ainda precisa receber o primeiro `dist/` por algum mecanismo de hospedagem antes que o self-updater exista. Isso é bootstrap, não fluxo de atualização.

Depois que `Admin → Sistema e atualizações` estiver operacional, versões futuras de código passam exclusivamente pelo canal `production-dist` e pelo instalador administrativo.

Para preparar um bootstrap manual:

1. faça uma cópia de segurança de qualquer estado persistente existente;
2. execute `build-dist.cmd` na raiz do projeto;
3. instale o **conteúdo de `dist/`** na raiz pública pelo mecanismo inicial disponível na hospedagem;
4. preserve todos os caminhos persistentes listados acima;
5. abra o site/admin para que o bootstrap execute as migrações pendentes;
6. confira `/admin/`, páginas PT/EN, formulários, respostas e mídia;
7. a partir daí, não use sincronização manual para atualizações de código: use o painel.

No Windows, para apenas gerar e inspecionar o artefato:

```bat
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
build-dist.cmd
```

A origem do bootstrap é o **conteúdo de `dist/`**, nunca a raiz inteira do repositório.

## GitHub Actions

O workflow `.github/workflows/deploy.yml` valida a branch de produção, gera `dist/`, publica um artefato e atualiza `production-dist`, que é o único canal de versões consumido pelo updater administrativo.

O workflow não envia arquivos diretamente para a hospedagem. Sua responsabilidade termina quando o pacote validado é publicado em `production-dist`.

## Atualizações editoriais

Depois que o CMS está instalado, alterações normais de conteúdo não dependem de Git nem de self-update.

O fluxo editorial é:

1. entrar em `/admin/`;
2. usar o dashboard para acessar a página, formulário, mídia ou configuração desejada;
3. salvar o rascunho quando aplicável;
4. publicar.

A publicação editorial vale imediatamente no site. Atualização de aplicação só é necessária quando há mudança de código/capacidade do CMS.

## Instalação nova

1. Copie `dist/config/install.example.php` para `config/install.php` no servidor e configure um `install_key` longo e aleatório.
2. Deixe `storage/` e `config/` graváveis pelo PHP durante a instalação; depois apenas os caminhos que precisam persistir devem continuar graváveis.
3. Abra `/install/`, informe a chave, configure o site e crie o primeiro administrador.
4. Apague `config/install.php` quando o instalador confirmar a conclusão.
5. Entre em `/admin/`, revise as páginas iniciais PT/EN e publique o conteúdo.
6. Teste formulários públicos, painel de respostas e exportação CSV.
7. Ative HTTPS.
8. A partir daí, use `Admin → Sistema e atualizações` para todas as futuras versões de código.

## Conteúdo do artefato

`dist/` contém a aplicação necessária à produção, inclusive painel administrativo, editor visual, templates, assets e migrações. Não deve conter banco de desenvolvimento, credenciais locais, logs de desenvolvimento, capturas de teste nem arquivos do diretório `output/`.

O banco, uploads e configuração da instalação são persistentes e ficam fora do controle destrutivo do build.
