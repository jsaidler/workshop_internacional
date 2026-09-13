# Direct Positive Workshop — CMS

Site do workshop **Positivo Direto em Filme de Raios X / Direct Positive X-Ray Film**. A aplicação roda em PHP 8.2+ com SQLite e mantém a linguagem visual da landing page original, mas o conteúdo público deixa de depender de uma página fixa traduzida em código.

## Leia primeiro

O estado operacional canônico do projeto está em `docs/PROJECT_STATE.md`. Antes de alterar código, conteúdo, deploy ou fluxo administrativo, leia também `docs/CMS_PROFESSIONAL.md`, `docs/CMS_V3_DEPLOYMENT.md`, `DEPLOY.md` e `AGENTS.md`.

## Arquitetura atual

O CMS possui quatro áreas principais:

- **Dashboard administrativo**: `/admin/` é a entrada do sistema e apresenta publicação, alterações pendentes, inscrições, páginas, formulários, mídia, saúde/armazenamento, atividade recente e atalhos. A edição da home é uma ação do dashboard, não o destino automático.
- **Páginas**: páginas independentes por idioma, com URL, menu, rascunho e publicação próprios.
- **Editor WYSIWYG**: texto editado diretamente sobre a página real, biblioteca de seções, imagens, ordenação, duplicação e remoção de blocos, visualização desktop/tablet/mobile e edição visual dos formulários inseridos na página. Rótulos de campos, rótulos de opções, textos de ajuda, texto do botão e conteúdo editorial intercalado no formulário são editados clicando diretamente no que está renderizado; o formulário não é tratado como um único bloco textual nem exige selecionar o campo em um dropdown lateral. Formulários já expandidos pertencem ao controlador visual de formulário; o editor central só trata o placeholder ainda não renderizado como entidade de formulário.
- **Formulários e respostas**: inscrições e pesquisas próprias do site, com campos adicionáveis, removíveis e reordenáveis; cada formulário possui rascunho/publicação e as respostas ficam armazenadas no SQLite com status, notas e CSV. O mesmo schema pode conter blocos editoriais posicionados entre campos, inclusive conteúdo condicional, áreas opcionais e imagens administradas pela biblioteca. Alterações estruturais continuam no editor completo de formulários.

Na inscrição brasileira, programação, condições, pagamento Pix/cartão e QR Code pertencem ao conteúdo do formulário no CMS. Os estados condicionais e áreas explicitamente ocultas continuam visíveis no editor, mas no site público obedecem à regra configurada e ao controle de exibição. Assim, trocar o QR Code, o código Pix, o link do Mercado Pago, o texto de um painel, sua condição ou se ele deve ser exibido não exige alteração de código.

A migração desse conteúdo para o CMS deve preservar a apresentação pública que já estava aprovada. `migrations/023_repair_registration_editorial_blocks.php` corrige de forma não destrutiva instalações em que a migração anterior deixou o conjunto de blocos editoriais incompleto: blocos ausentes são repostos por identidade, enquanto conteúdo já editado é preservado.

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
- `app/cms_forms.php`: schemas de formulário, blocos editoriais, validação, renderização e submissões.
- `app/workshop_registration_content.php`: conteúdo inicial da inscrição brasileira que é migrado para o schema e depois administrado pelo CMS.
- `app/cms_renderer.php`: renderização pública das páginas e inserção dos formulários.
- `editor/cms-form-editor.js`: controlador único da edição visual de campos e conteúdo editorial dos formulários expandidos.
- `editor/`: editor visual WYSIWYG.
- `admin/index.php`: dashboard administrativo.
- `admin/pages.php`: gerenciamento de páginas.
- `admin/forms.php`: gerenciamento estrutural de formulários.
- `admin/submissions.php`: acompanhamento das respostas.
- `admin/system.php`: saúde, canal de produção, atualização e rollback de arquivos.
- `migrations/011_cms_pages_forms.php`: tabelas do novo CMS.
- `migrations/022_registration_native_editorial_content.php`: migração da inscrição híbrida para conteúdo editorial nativo do formulário.
- `migrations/023_repair_registration_editorial_blocks.php`: reparo não destrutivo de blocos editoriais ausentes/incompletos da inscrição.
- `template/page.css`: base visual aprovada que continua sendo reutilizada.
- `assets/cms.css`: complementos visuais do CMS e dos formulários.

O sistema anterior (`content_documents`, `interest_submissions` e a tradução fixa em `public_locale.php`) permanece no repositório por compatibilidade e para preservar dados existentes durante a transição, mas a rota pública principal usa `cms_pages` e `cms_forms`.

## Desenvolvimento

A branch de produção atual é `wip/form-response-refinement-2026-07-16`. GitHub Actions valida sintaxe PHP e JavaScript, testes do CMS, interação crítica do editor em Chromium/Playwright, build e distribuição.

A regressão de formulário inclui um cenário com `cms-form-editor.js` e `cms-editor-v3.js` carregados simultaneamente, porque a propriedade da interação no formulário só é validada corretamente quando os dois controladores reais estão ativos.

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

Alterações normais de texto, páginas, formulários, imagens, navegação, design, conteúdos condicionais, áreas opcionais e dados de pagamento são feitas diretamente no CMS e não dependem de Git, FTP ou atualização da aplicação.
