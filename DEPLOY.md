# Deploy on HostGator

O estado operacional canônico do projeto está em `docs/PROJECT_STATE.md`. Requer Apache, PHP 8.2+, `pdo_sqlite`/`sqlite3`, extensão DOM e HTTPS.

## Fluxo normal vigente

Depois que o self-updater está instalado na hospedagem, **o usuário atualiza a aplicação pela área administrativa do próprio site**:

`Admin → Sistema e atualizações → Instalar atualização`

O fluxo é:

1. alterações de código são integradas na branch de produção `wip/form-response-refinement-2026-07-16`;
2. o GitHub Actions valida o código e constrói `dist/`;
3. o workflow publica o pacote verificado na branch `production-dist`;
4. o painel `Sistema e atualizações` detecta a nova versão;
5. o usuário executa `Instalar atualização`;
6. o updater baixa e valida os arquivos modificados, cria backup e substitui a aplicação preservando o estado persistente;
7. a próxima requisição executa eventuais migrações pendentes.

A publicação de `production-dist` **não significa que a hospedagem já foi atualizada**. Só considerar a versão remota instalada depois da aplicação pelo painel e da verificação da versão exibida em `Sistema e atualizações`.

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

## Bootstrap inicial ou contingência

Uma instalação que ainda não possui o self-updater precisa de um deploy convencional do `dist/`. FTP/manual deploy também pode ser usado em recuperação excepcional se o updater estiver indisponível.

Para bootstrap manual:

1. faça uma cópia de segurança de `storage/database.sqlite`;
2. execute `build-dist.cmd` na raiz do projeto;
3. sincronize o **conteúdo de `dist/`** com a raiz pública;
4. preserve todos os caminhos persistentes listados acima;
5. abra o site/admin para que o bootstrap execute as migrações pendentes;
6. confira `/admin/`, páginas PT/EN, formulários, respostas e mídia.

No Windows:

```bat
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
build-dist.cmd
```

A origem remota é o **conteúdo de `dist/`**, nunca a raiz inteira do repositório.

## GitHub Actions

O workflow `.github/workflows/deploy.yml` valida a branch de produção, gera `dist/`, publica um artefato e atualiza `production-dist`, que é o canal consumido pelo updater administrativo.

O workflow ainda suporta envio FTP quando os quatro secrets abaixo estiverem configurados:

- `DEPLOY_FTP_URL`;
- `DEPLOY_FTP_USERNAME`;
- `DEPLOY_FTP_PASSWORD`;
- `DEPLOY_FTP_PATH`.

Esse FTP automático é **opcional** e não faz parte do fluxo operacional normal depois que o self-updater está instalado. A ausência desses secrets não impede o fluxo `production-dist → Admin → Sistema e atualizações`.

Quando FTP estiver habilitado, o deploy continua não destrutivo e preserva banco, configuração, logs e uploads.

## Atualizações editoriais

Depois que o CMS está instalado, alterações normais de conteúdo não dependem de Git, VS Code, FTP nem self-update.

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
8. A partir daí, use `Admin → Sistema e atualizações` para futuras versões de código.

## Conteúdo do artefato

`dist/` contém a aplicação necessária à produção, inclusive painel administrativo, editor visual, templates, assets e migrações. Não deve conter banco de desenvolvimento, credenciais locais, logs de desenvolvimento, capturas de teste nem arquivos do diretório `output/`.

O banco, uploads e configuração da instalação são persistentes e ficam fora do controle destrutivo do build.
