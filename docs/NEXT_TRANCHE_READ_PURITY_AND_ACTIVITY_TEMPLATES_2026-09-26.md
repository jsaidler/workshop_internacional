# Próximo tranche — leituras puras e templates explícitos de curso

## Objetivo

Executar a próxima etapa definida em `SYSTEM_WIDE_IMPLEMENTATION_2026-09-25.md`: retirar efeitos colaterais de leitura do CMS e desacoplar a criação de um novo curso/workshop do setup específico de Positivo Direto.

## Regra canônica

> Consulta não cria conteúdo.

Abrir Páginas, Formulários, sitemap ou uma rota pública vazia deve apenas observar o estado existente. Seed é uma operação de criação deliberada e só pode acontecer na instalação ou na aplicação explícita de um template de curso.

## Escopo deste tranche

1. `cms_pages()`, `cms_page_home()` e `cms_page_by_slug()` deixam de chamar `cms_pages_seed()`.
2. `cms_forms()` e `cms_form_by_key()` deixam de chamar `cms_forms_seed()`.
3. `cms_sitemap_entries()` deixa de semear páginas ao percorrer atividades.
4. As telas administrativas de Páginas e Formulários deixam de semear conteúdo ao serem abertas.
5. A criação de atividade passa a exigir um template explícito no fluxo administrativo:
   - `blank`: curso vazio, sem páginas/formulários específicos de produto;
   - `direct-positive`: aplica os defaults do workshop de Positivo Direto.
6. `activity_create()` deixa de copiar implicitamente a atividade raiz quando nenhum `copyId` é informado.
7. O setup de Positivo Direto deve ser idempotente e `create-if-absent`: uma reaplicação nunca pode sobrescrever página ou formulário já existentes/editados.

## Compatibilidade transitória

As funções `cms_pages_seed()` e `cms_forms_seed()` permanecem neste tranche porque ainda são úteis como primitivas do template legado de Positivo Direto e em testes/migrações históricas. A diferença arquitetural é que elas deixam de ser chamadas por operações de leitura.

`content_documents`, `public_locale.php`, writers acadêmicos legados e a retirada física das tabelas antigas permanecem fora deste tranche. Não devem ser ampliados.

## Critérios de regressão

- consultar páginas de uma atividade vazia retorna lista vazia e não altera o banco;
- consultar formulário inexistente retorna `null` e não altera o banco;
- gerar sitemap não cria páginas;
- abrir as telas administrativas não depende de conteúdo pré-semeado;
- criar atividade com template `blank` não gera páginas nem formulários do primeiro workshop;
- criar atividade com template `direct-positive` cria o conjunto esperado;
- reaplicar o setup do template direto em uma atividade já editada não substitui conteúdo existente.
