# Direct Positive Workshop — CMS

Site do workshop **Positivo Direto em Filme de Raios X / Direct Positive X-Ray Film**. A aplicação roda em PHP 8.2+ com SQLite e mantém a linguagem visual da landing page original, mas o conteúdo público deixa de depender de uma página fixa traduzida em código.

## Arquitetura atual

O CMS possui quatro áreas principais:

- **Páginas**: páginas independentes por idioma, com URL, menu, rascunho e publicação próprios.
- **Editor WYSIWYG**: texto editado diretamente sobre a página real, biblioteca de seções, imagens, ordenação, duplicação e remoção de blocos, visualização desktop/tablet/mobile e edição dos formulários inseridos na página.
- **Formulários**: inscrições e pesquisas próprias do site, com campos adicionáveis, removíveis e reordenáveis; cada formulário possui rascunho e publicação independentes.
- **Respostas**: submissões armazenadas em SQLite, com status, notas e exportação CSV.

Português e inglês são documentos editoriais independentes. A versão brasileira pode operar como página de inscrição de uma nova turma enquanto a versão inglesa continua como pesquisa de interesse para a primeira turma em inglês.

## Conteúdo inicial

Na primeira execução após a migração 011, cada atividade recebe duas páginas iniciais e dois formulários:

- PT-BR: nova turma em português + formulário de inscrição;
- EN: primeira turma em inglês + pesquisa de interesse.

Os conteúdos são apenas sementes. Depois disso o banco de dados passa a ser a fonte de verdade para páginas e formulários publicados.

## Arquivos principais

- `app/cms_pages.php`: páginas, rascunhos, publicação e conteúdo inicial.
- `app/cms_forms.php`: schemas de formulário, validação, renderização e submissões.
- `app/cms_renderer.php`: renderização pública das páginas e inserção dos formulários.
- `editor/`: editor visual WYSIWYG.
- `admin/pages.php`: gerenciamento de páginas.
- `admin/forms.php`: gerenciamento de formulários.
- `admin/submissions.php`: acompanhamento das respostas.
- `migrations/011_cms_pages_forms.php`: tabelas do novo CMS.
- `template/page.css`: base visual aprovada que continua sendo reutilizada.
- `assets/cms.css`: complementos visuais do CMS e dos formulários.

O sistema anterior (`content_documents`, `interest_submissions` e a tradução fixa em `public_locale.php`) permanece no repositório por compatibilidade e para preservar dados existentes durante a transição, mas a rota pública principal passa a usar `cms_pages` e `cms_forms`.

## Desenvolvimento

A branch de refatoração possui GitHub Actions para validar sintaxe PHP e JavaScript, testes do build e `build-dist.php --dry-run`.

Antes de publicar, confirme que todos os checks estão verdes.

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

## Deploy

O deploy continua sendo feito sincronizando **o conteúdo de `dist/`** com a raiz pública do domínio. Consulte `DEPLOY.md`.

A migração 011 é aplicada pelo sistema de migrações existente; não apague o banco atual. Respostas do formulário anterior permanecem acessíveis como dados legados no painel enquanto as novas submissões passam a ser gravadas em `cms_form_submissions`.
