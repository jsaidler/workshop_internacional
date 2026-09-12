# Deploy on HostGator

Requer Apache, PHP 8.2+, `pdo_sqlite`/`sqlite3`, extensão DOM e HTTPS. Sincronize o **conteúdo de `dist/`** diretamente com a raiz pública do domínio (`remotePath: "/"`).

## Atualização de uma instalação existente

1. Faça uma cópia de segurança de `storage/database.sqlite` antes de publicar uma versão que contenha novas migrações.
2. Execute `build-dist.cmd` na raiz do projeto. Não edite `dist/` manualmente.
3. Sincronize `dist/` preservando os caminhos persistentes: nunca sobrescreva nem apague `config/local.php`, `config/install.php`, `storage/database.sqlite`, `storage/installed.lock`, `storage/logs/` ou `uploads/`.
4. Abra primeiro o site/admin em uma janela de teste. O bootstrap executará as migrações pendentes no banco existente, incluindo a migração 011 do CMS.
5. Entre em `/admin/` e confira **Páginas**, **Formulários** e **Respostas**.
6. Teste a página em português e a inglesa, envie uma inscrição/pesquisa de teste e confirme o registro no painel e a exportação CSV.
7. Só depois considere o deploy validado.

A migração 011 **não apaga** `content_documents` nem `interest_submissions`. Os dados anteriores ficam preservados durante a transição.

## Deploy automático pelo GitHub

O workflow `.github/workflows/deploy.yml` sempre gera e valida um artefato `dist/` em cada push para a branch principal atual. Quando as credenciais FTP estiverem configuradas no repositório, o mesmo workflow envia esse artefato diretamente para a hospedagem.

Configure em **GitHub → Settings → Secrets and variables → Actions → Repository secrets**:

- `DEPLOY_FTP_URL`: URL completa do servidor, por exemplo `ftp://ftp.seudominio.com` ou `ftps://ftp.seudominio.com`;
- `DEPLOY_FTP_USERNAME`: usuário FTP;
- `DEPLOY_FTP_PASSWORD`: senha FTP;
- `DEPLOY_FTP_PATH`: diretório remoto que corresponde à raiz pública do site, por exemplo `/public_html/` ou `/` conforme a conta.

O deploy é deliberadamente não destrutivo: ele envia e substitui arquivos do artefato, mas não usa exclusão remota. Banco, configuração local, lock de instalação, logs e uploads são explicitamente excluídos.

Enquanto os quatro secrets não existirem, o workflow **não tenta conectar ao FTP**. Ele apenas valida o código, constrói `dist/` e disponibiliza o artefato para download no GitHub Actions.

## Deploy pelo repositório local / VS Code

A pasta `.vscode/` é ignorada pelo Git e pode manter a configuração FTP/SFTP local sem expor credenciais no repositório. Para atualizar uma cópia local já configurada:

```bat
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
build-dist.cmd
```

Se a extensão do VS Code estiver configurada para sincronizar `dist/`, a alteração dos arquivos gerados dispara ou permite a sincronização normal. A origem remota deve ser o **conteúdo de `dist/`**, não a raiz inteira do repositório.

## Atualizações editoriais depois do deploy

Depois que esta versão do CMS estiver instalada na hospedagem, alterações normais de conteúdo **não dependem mais de Git, VS Code ou FTP**. O painel administrativo grava páginas, formulários, respostas e mídia diretamente na instalação hospedada.

O fluxo editorial passa a ser:

1. entrar em `/admin/`;
2. editar uma página ou formulário;
3. salvar o rascunho;
4. publicar.

A publicação passa a valer imediatamente no site. FTP/Git continua necessário apenas quando houver alteração no **código da aplicação**, não para atualizar textos, páginas, imagens, campos de formulários ou respostas.

## Instalação nova

1. Copie `dist/config/install.example.php` para `config/install.php` no servidor e configure um `install_key` longo e aleatório.
2. Deixe `storage/` e `config/` graváveis pelo PHP durante a instalação (normalmente 775); depois apenas `storage/` precisa continuar gravável.
3. Abra `/install/`, informe a chave, configure o site e crie o primeiro administrador.
4. Apague `config/install.php` quando o instalador confirmar a conclusão.
5. Entre em `/admin/`, abra as páginas iniciais PT/EN, revise e publique o conteúdo.
6. Teste os formulários públicos e a exportação CSV.
7. Ative HTTPS; os cookies seguros passam a ser usados automaticamente.

## Conteúdo do artefato

`dist/` contém a aplicação necessária à produção, inclusive painel administrativo, editor visual, templates, assets e migrações. Ele não deve conter banco de desenvolvimento, credenciais locais, logs de desenvolvimento, capturas de teste nem arquivos do diretório `output/`.

O banco, os uploads e a configuração da instalação são persistentes e ficam fora do controle destrutivo do build.
