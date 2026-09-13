# Deploy on HostGator

Requer Apache, PHP 8.2+, `pdo_sqlite`/`sqlite3`, extensão DOM e HTTPS. Sincronize o **conteúdo de `dist/`** diretamente com a raiz pública real do domínio.

Banco, uploads e configuração da instalação são persistentes. Não confunda três estados distintos: código no GitHub, pacote gerado em `production-dist` e arquivos efetivamente servidos pela hospedagem.

## Atualização de uma instalação existente

1. Faça uma cópia de segurança de `storage/database.sqlite` antes de publicar qualquer versão com novas migrações.
2. Atualize a branch de produção e gere o pacote.
3. Sincronize o conteúdo de `dist/` preservando os caminhos persistentes.
4. Abra o site/admin em uma janela de teste para que as migrações pendentes sejam executadas.
5. Valide a instalação hospedada antes de considerar a atualização concluída.

No PowerShell:

```powershell
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
.\build-dist.cmd
```

**Importante:** em PowerShell, `build-dist.cmd` sem `.\` não executa automaticamente um script existente no diretório atual. Use sempre `.\build-dist.cmd`.

Preserve sempre:

- `config/local.php`;
- `config/install.php`;
- `storage/database.sqlite`;
- `storage/installed.lock`;
- `storage/logs/`;
- `storage/updates/`;
- `uploads/`.

Não edite `dist/` manualmente.

## Migração atual da inscrição brasileira

A atualização atual usa `migrations/020_registration_google_form_parity.php`.

Ela acrescenta em `cms_form_submissions`:

- `payment_status` com padrão `pending`;
- `payment_confirmed_at`;
- `payment_note`.

A migração reaplica a configuração canônica da inscrição brasileira para que a página/formulário hospedados passem a usar os dados do fluxo real de inscrição e pagamento. Faça backup do banco imediatamente antes de instalar essa versão.

## Checklist específico após instalar a migração 020

1. Abra `/inscricao/?lang=pt-br`.
2. Confirme que o formulário contém somente: Nome Completo, CPF, Whatsapp com DDD, E-mail, Instagram, tamanho do suporte, endereço, Cidade/UF, CEP, disponibilidade, forma de pagamento e aceite.
3. Confirme os tamanhos de suporte `4x5"` e `5x7"`.
4. Confirme as quatro opções de outubro:
   - Terças, 19h — 6, 13 e 20 de outubro;
   - Quintas, 19h — 8, 15 e 22 de outubro;
   - Sábados, 9h — 3, 10 e 24 de outubro;
   - Sábados, 14h — 3, 10 e 24 de outubro.
5. Selecione Pix e confirme que aparecem R$ 698,00, chave, QR e código copia-e-cola.
6. Selecione cada opção de cartão e confirme que ambas usam o link de cartão de crédito do Mercado Pago: `https://mpago.la/1xvBsPV`.
7. Envie uma inscrição de teste.
8. Em `/admin/submissions.php`, confirme que ela aparece como **Aguardando pagamento**.
9. Registre uma informação de pagamento e use **Confirmar inscrição e pagamento**.
10. Confirme que o registro passa para **Confirmada** com data/hora de confirmação.
11. Teste retornar a inscrição para aguardando pagamento e cancelar.
12. Exclua ou arquive o registro de teste conforme apropriado.

## Verificação do editor após deploy

Também faça um teste real do editor de páginas:

1. abra uma página;
2. selecione um texto;
3. altere o conteúdo;
4. salve;
5. recarregue;
6. publique.

Houve ocorrência real do erro `Cannot set properties of null (setting 'textContent')` ao tentar editar página. Não considere o editor validado apenas porque o build passa.

Na conferência visual, títulos multilinha devem permanecer com `line-height >= 1`; o valor de referência atual é `1.02`.

## Deploy automático pelo GitHub

O workflow `.github/workflows/deploy.yml` valida o código e gera um artefato `dist/` em pushes para a branch de produção atual. O mesmo workflow publica o pacote verificado na branch `production-dist`.

O envio direto para a hospedagem só ocorre quando os quatro secrets FTP estiverem configurados:

- `DEPLOY_FTP_URL`;
- `DEPLOY_FTP_USERNAME`;
- `DEPLOY_FTP_PASSWORD`;
- `DEPLOY_FTP_PATH`.

Enquanto eles não existirem, o workflow pode ficar verde porque valida e gera o pacote, mas **não atualiza a HostGator**. Não interpretar “workflow concluído” como “site hospedado atualizado”.

Quando FTP estiver configurado, o deploy continua não destrutivo para os caminhos persistentes.

## Self-updater

Depois do bootstrap inicial, atualizações de código podem ser feitas em:

`Admin → Sistema e atualizações`

O updater lê `production-dist`, baixa apenas arquivos administrados alterados, verifica SHA-256, faz backup dos arquivos substituídos/removidos e preserva estado persistente.

Se o repositório e `production-dist` estiverem atualizados mas o site continuar exibindo interface antiga, investigue primeiro:

- raiz remota incorreta;
- outra cópia do site sendo servida;
- cache da hospedagem/proxy;
- bootstrap/updater ainda não instalado na cópia pública.

Não refaça builds sucessivamente antes de excluir esses problemas de caminho/deploy.

## Atualizações editoriais depois do deploy

Depois que o CMS estiver instalado, alterações normais de conteúdo não dependem mais de Git, VS Code ou FTP. Páginas, formulários, mídia, navegação e design são gravados diretamente na instalação hospedada.

O fluxo editorial normal é:

1. entrar em `/admin/`;
2. editar;
3. salvar o rascunho;
4. publicar.

Código/deploy continua necessário apenas para novas capacidades, correções e migrações.

## Instalação nova

1. Copie `dist/config/install.example.php` para `config/install.php` no servidor e configure um `install_key` longo e aleatório.
2. Deixe `storage/` e `config/` graváveis pelo PHP durante a instalação; depois apenas os diretórios persistentes necessários devem continuar graváveis.
3. Abra `/install/`, informe a chave, configure o site e crie o primeiro administrador.
4. Apague `config/install.php` quando o instalador confirmar a conclusão.
5. Entre em `/admin/`, revise páginas PT/EN e publique.
6. Teste formulários, mídia, editor e exportação CSV.
7. Ative HTTPS.

## Conteúdo do artefato

`dist/` contém a aplicação necessária à produção, inclusive painel administrativo, editor visual, templates, assets e migrações. Não deve conter banco de desenvolvimento, credenciais locais, logs de desenvolvimento, capturas de teste nem arquivos de `output/`.
