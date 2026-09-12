# Deploy on HostGator

Requer Apache, PHP 8.2+, `pdo_sqlite`/`sqlite3`, extensão DOM e HTTPS. Sincronize o **conteúdo de `dist/`** diretamente com a raiz pública do domínio (`remotePath: "/"`).

## Atualização de uma instalação existente

1. Faça uma cópia de segurança de `storage/database.sqlite` antes de publicar uma versão que contenha novas migrações.
2. Execute `build-dist.cmd` na raiz do projeto. Não edite `dist/` manualmente.
3. Sincronize `dist/` preservando os caminhos persistentes: nunca sobrescreva nem apague `config/local.php`, `config/install.php`, `storage/database.sqlite`, `storage/logs/` ou `uploads/`.
4. Abra primeiro o site/admin em uma janela de teste. O bootstrap executará as migrações pendentes no banco existente, incluindo a migração 011 do CMS.
5. Entre em `/admin/` e confira **Páginas**, **Formulários** e **Respostas**.
6. Teste a página em português e a inglesa, envie uma inscrição/pesquisa de teste e confirme o registro no painel e a exportação CSV.
7. Só depois considere o deploy validado.

A migração 011 **não apaga** `content_documents` nem `interest_submissions`. Os dados anteriores ficam preservados durante a transição.

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
