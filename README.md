# Direct Positive Workshop — CMS

Site do workshop **Positivo Direto em Filme de Raios X / Direct Positive X-Ray Film**. A aplicação roda em PHP 8.2+ com SQLite e mantém a linguagem visual editorial da landing page original, com conteúdo público e operação comercial administráveis pelo CMS.

## Arquitetura atual

O sistema está dividido em áreas complementares:

- **Páginas**: páginas independentes por idioma, com URL, menu, rascunho, publicação e histórico próprios.
- **Editor visual**: edição de texto sobre a página, biblioteca de seções/componentes, imagens e vídeos, ordenação, duplicação, controles de layout e visualização desktop/tablet/mobile.
- **Design**: sistema visual global para cores, tipografia, larguras, espaçamentos e botões.
- **Site e navegação**: identidade, header, footer, navegação, CTA e SEO global.
- **Formulários**: inscrições e pesquisas próprias do site, com schemas editáveis, rascunho/publicação e validação.
- **Inscrições/Respostas**: submissões em SQLite, status operacional, dados de pagamento quando aplicável, notas e exportação CSV.
- **Mídia**: biblioteca de imagens e vídeos com metadata, tags, ponto focal, versões, derivados responsivos e rastreamento de uso.
- **Sistema e atualizações**: atualização do código publicado a partir do pacote verificado em `production-dist`, preservando dados persistentes.

Português e inglês são documentos editoriais independentes. A versão brasileira opera como inscrição efetiva de turma; a versão inglesa permanece como pesquisa de interesse para a primeira turma em inglês.

## Regra canônica da inscrição brasileira

O formulário brasileiro deve espelhar o Google Form utilizado na operação real. Não devem ser adicionadas perguntas de marketing, diagnóstico pedagógico ou equipamento sem necessidade operacional.

Dados atuais da inscrição:

- Nome Completo;
- CPF para emissão de NF;
- Whatsapp com DDD;
- E-mail;
- Instagram;
- tamanho do suporte: `4x5"` ou `5x7"`;
- Endereço completo;
- Cidade/UF;
- CEP;
- disponibilidade para a turma;
- Forma de pagamento;
- aceite das condições de inscrição e participação.

Disponibilidades da turma atual:

- Terças, 19h — 6, 13 e 20 de outubro;
- Quintas, 19h — 8, 15 e 22 de outubro;
- Sábados, 9h — 3, 10 e 24 de outubro;
- Sábados, 14h — 3, 10 e 24 de outubro.

A pessoa pode marcar mais de uma opção. A formação da turma considera apenas participantes com pagamento confirmado. Uma segunda turma exige pelo menos 3 participantes com pagamento confirmado e disponibilidade comum.

## Pagamento da turma brasileira

Valor base: **R$ 698,00**.

- Pix: R$ 698,00.
- Cartão de crédito à vista: R$ 698 + taxas do Mercado Pago.
- Cartão de crédito parcelado: R$ 698 + taxas do Mercado Pago.

Cartão de crédito usa o link do Mercado Pago:

`https://mpago.la/1xvBsPV`

Pix:

- chave: `20.179.548/0001-58`;
- copia e cola: `00020101021126690014br.gov.bcb.pix0114201795480001580229INSCRICAO MINI CURSO SETEMBRO5204000053039865406698.005802BR592020 1 5 J V T SAIDLER6010PETROPOLIS62070503***6304314B`.

O envio do formulário não reserva vaga. A inscrição é confirmada somente depois da confirmação do pagamento.

No painel, o fluxo deliberadamente simples é: receber a inscrição como **Aguardando pagamento**, conferir/registrar informação do pagamento e usar **Confirmar inscrição e pagamento**. Também é possível retornar para aguardando pagamento ou cancelar a inscrição.

## Estrutura pedagógica que deve permanecer correta no site

O workshop é on-line, ao vivo e dividido em 3 encontros:

1. filme de raio-X, exposição e decisões anteriores à revelação;
2. fotografia e processamento ao vivo, produzindo o maior número de imagens possível e variando deliberadamente exposição e parâmetros para comparar os resultados;
3. análise dos testes produzidos pelos próprios participantes e das decisões para o teste seguinte.

O segundo encontro não deve ser descrito apenas como “demonstração”: o valor está na produção ao vivo, no volume de imagens e na comparação deliberada entre variações.

Cada participante da turma brasileira recebe o suporte dobrável desenvolvido especificamente para o processamento de chapas de raio-X, reduzindo o contato da dupla emulsão com a bandeja.

## Arquivos principais

- `app/cms_pages.php`: páginas, documentos, rascunhos e publicação.
- `app/cms_forms.php`: schemas de formulário, validação, renderização e submissões.
- `app/form_workflow.php`: condições de campos, redirecionamento e notificações.
- `app/cms_renderer.php`: renderização pública das páginas e inserção dos formulários.
- `app/workshop_cms_setup.php`: estrutura específica do workshop, incluindo inscrição brasileira.
- `editor/`: editor visual.
- `admin/pages.php`: gerenciamento de páginas.
- `admin/forms.php`: gerenciamento de formulários.
- `admin/submissions.php`: operação das inscrições/respostas e confirmação de pagamento.
- `admin/design.php`: sistema visual global.
- `admin/site.php`: identidade, header, footer e navegação.
- `admin/media.php`: biblioteca de mídia.
- `admin/system.php`: atualizações e saúde da instalação.
- `migrations/011_cms_pages_forms.php`: fundação de páginas/formulários do CMS.
- `migrations/015_professional_cms_foundation.php`: fundação do CMS profissional.
- `migrations/020_registration_google_form_parity.php`: paridade da inscrição brasileira e controle de pagamento.
- `template/page.css`, `assets/cms.css`, `assets/cms-v3.css`, `assets/cms-pro.css`: base visual pública.

O sistema anterior (`content_documents`, `interest_submissions` e a tradução fixa em `public_locale.php`) permanece por compatibilidade e preservação de dados durante a transição.

## Invariantes de UI

A linguagem visual editorial existente deve ser preservada. Em especial, títulos multilinha não devem usar `line-height < 1`; o valor de referência é `1.02`.

Foi observado em uso real o erro `Cannot set properties of null (setting 'textContent')` ao tentar editar uma página. Mudanças no editor precisam ser testadas abrindo uma página real, selecionando conteúdo, editando, salvando, recarregando e publicando; não basta validar apenas sintaxe ou testes isolados.

## Desenvolvimento

A branch de produção é `wip/form-response-refinement-2026-07-16`. Alterações devem passar pelos checks de PHP, JavaScript e testes específicos antes de serem levadas à produção.

`dist/` é artefato gerado. Não edite seus arquivos manualmente.

No PowerShell:

```powershell
git switch wip/form-response-refinement-2026-07-16
git pull --ff-only
.\build-dist.cmd
```

PowerShell exige o prefixo `.\` para executar um script presente no diretório atual.

Para inspecionar o pacote sem publicar:

```powershell
php tools/build-dist.php --dry-run
```

## Deploy e atualização

O primeiro bootstrap de uma instalação antiga exige sincronizar **o conteúdo de `dist/`** com a raiz pública correta do domínio, preservando configuração, banco, uploads e logs. Consulte `DEPLOY.md` e `docs/CMS_V3_DEPLOYMENT.md`.

Depois que o updater estiver instalado, o workflow da branch de produção publica o pacote verificado em `production-dist`, e atualizações de código podem ser instaladas em **Admin → Sistema e atualizações**.

Alterações editoriais normais são gravadas diretamente pelo CMS e não exigem FTP/Git.
