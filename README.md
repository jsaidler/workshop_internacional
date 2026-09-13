# Direct Positive Workshop — CMS

Site do workshop **Positivo Direto em Filme de Raios X / Direct Positive X-Ray Film**. A aplicação roda em PHP 8.2+ com SQLite e mantém a linguagem visual da landing page original, mas o conteúdo público deixa de depender de uma página fixa traduzida em código.

## Leia primeiro

O estado operacional canônico do projeto está em `docs/PROJECT_STATE.md`. Antes de alterar código, conteúdo, deploy ou fluxo administrativo, leia também `docs/CMS_PROFESSIONAL.md`, `docs/CMS_V3_DEPLOYMENT.md`, `DEPLOY.md` e `AGENTS.md`.

## Arquitetura atual

O CMS possui quatro áreas principais:

- **Dashboard administrativo**: `/admin/` é a entrada do sistema e apresenta publicação, alterações pendentes, inscrições, páginas, formulários, mídia, saúde/armazenamento, atividade recente e atalhos. A edição da home é uma ação do dashboard, não o destino automático.
- **Páginas**: páginas independentes por idioma, com URL, menu, rascunho e publicação próprios.
- **Editor WYSIWYG**: texto editado diretamente sobre a página real, biblioteca de seções, imagens, ordenação, duplicação e remoção de blocos, visualização desktop/tablet/mobile e edição visual dos formulários inseridos na página. Rótulos de campos, rótulos de opções, textos de ajuda e texto do botão são editados clicando diretamente no texto renderizado; o formulário não é tratado como um único bloco textual nem exige selecionar o campo em um dropdown lateral.
- **Formulários e respostas**: inscrições e pesquisas próprias do site, com campos adicionáveis, removíveis e reordenáveis; cada formulário possui rascunho/publicação e as respostas ficam armazenadas no SQLite com status, notas e CSV. Alterações estruturais continuam no editor completo de formulários.

Português e inglês são documentos editoriais independentes. A versão brasileira pode operar como página de inscrição de uma nova turma enquanto a versão inglesa continua como pesquisa de interesse para a primeira turma em inglês.

## Conteúdo inicial

Na primeira execução após a migração 011, cada atividade recebe duas páginas iniciais e dois formulários:

- PT-BR: nova turma em português + formulário de inscrição;
- EN: primeira turma em inglês + pesquisa de interesse.

Os conteúdos são apenas sementes. Depois disso o banco de dados passa a ser a fonte de verdade para páginas e formulários publicados.

## Arquivos principais

- `docs/PROJECT_STATE.md`: estado canônico e regras operacionais do projeto.
- `docs/CMS_PROFESSIONAL.md`: capacidades e princípios atuais do CMS/admin.
- `docs/CMS_V3_DEPLOYMENT.md`: funcionamento do self-updater e persistência.
- `DEPLOY.md`: bootstrap, contingência e fluxo de atualização.
- `app/cms_pages.php`: páginas, rascunhos, publicação e conteúdo inicial.
- `app/cms_forms.php`: schemas de formulário, validação, renderização e submissões.
- `app/cms_renderer.php`: renderização pública das páginas e inserção dos formulários.
- `editor/`: editor visual WYSIWYG.
- `admin/index.php`: dashboard administrativo.
- `admin/pages.php`: gerenciamento de páginas.
- `admin/forms.php`: gerenciamento de formulários.
- `admin/submissions.php`: acompanhamento das respostas.
- `admin/system.php`: saúde, canal de produção, atualização e rollback de arquivos.
- `migrations/011_cms_pages_forms.php`: tabelas do novo CMS.
- `template/page.css`: base visual aprovada que continua sendo reutilizada.
- `assets/cms.css`: complementos visuais do CMS e dos formulários.

O sistema anterior (`content_documents`, `interest_submissions` e a tradução fixa em `public_locale.php`) permanece no repositório por compatibilidade e para preservar dados existentes durante a transição, mas a rota pública principal usa `cms_pages` e `cms_forms`.

## Desenvolvimento

A branch de produção atual é `wip/form-response-refinement-2026-07-16`. GitHub Actions valida sintaxe PHP e JavaScript, testes do CMS, build e distribuição.

Antes de integrar uma mudança, confirme que todos os checks estão verdes e atualize os documentos canônicos quando a mudança alterar comportamento, arquitetura, conteúdo estrutural ou operação.

## Reconstruindo `dist`

`dist/` é artefato gerado. Não edite seus arquivos manualmente.

No Windows:

```bat
build-dist.cmd
```

Para inspecionar antes de alterar `dist/`:

```bat
build-dist.cmd --dry-run
```

Para validar um `dist/` já gerado:

```bat
build-dist.cmd --verify
```

O build preserva os caminhos persistentes da instalação (`config/local.php`, banco SQLite, uploads e logs). O manifesto dos arquivos administrados pelo build fica em `.build/dist-managed-files.json`.

## Atualização da hospedagem

Depois que o self-updater está instalado, o fluxo normal é:

1. integrar a mudança na branch de produção;
2. o CI gera e publica o pacote verificado em `production-dist`;
3. o usuário abre `Admin → Sistema e atualizações`;
4. o usuário executa `Instalar atualização`;
5. o updater valida checksums, faz backup e substitui os arquivos da aplicação preservando banco, uploads, configuração e logs;
6. eventuais migrações rodam no bootstrap da requisição seguinte.

`production-dist` atualizada não significa hospedagem atualizada. FTP/manual deploy é bootstrap ou contingência, não o fluxo normal após a instalação do updater. Consulte `DEPLOY.md` e `docs/CMS_V3_DEPLOYMENT.md`.

## Atualizações editoriais

Alterações normais de texto, páginas, formulários, imagens, navegação e design são feitas diretamente no CMS e não dependem de Git, FTP ou atualização da aplicação.
