# Editor — navegação estrutural e contexto

Este documento define o contrato de UX para navegação em páginas complexas no editor visual.

## Problema que este contrato resolve

À medida que uma página deixa de ser composta apenas por seções prontas e passa a conter contêineres, colunas e componentes aninhados, selecionar e localizar um elemento não pode depender de lembrar onde ele está visualmente nem de percorrer uma árvore extensa linha por linha.

O editor precisa responder continuamente a duas perguntas:

1. **onde estou?**
2. **como encontro rapidamente outro elemento?**

## Busca na estrutura

A visualização `Estrutura` possui busca própria.

- A busca filtra por nome visível e tipo do elemento.
- A comparação é insensível a maiúsculas/minúsculas e acentos.
- Um resultado aninhado nunca aparece solto: seus ancestrais estruturais permanecem visíveis para preservar contexto.
- Quando a própria seção corresponde à busca, seus descendentes permanecem visíveis.
- `Enter` abre o primeiro resultado visível.
- `Esc` limpa uma busca ativa.
- Uma busca sem correspondência mostra estado vazio explícito.
- A busca pertence à árvore estrutural; ela não é exibida no modo simples de lista de seções.

## Breadcrumb contextual

Quando uma seção, contêiner, coluna ou componente está selecionado, o inspector mostra seu caminho estrutural.

Exemplo:

`Workshop › Grupo de colunas › Coluna 2 › Imagem`

Cada nível é navegável. Selecionar um ancestral pelo breadcrumb muda a seleção real no canvas e no restante da interface; o breadcrumb não mantém uma seleção paralela.

O último item representa o contexto atual e recebe `aria-current="page"`.

## Fonte de verdade

Busca e breadcrumb não criam uma árvore própria. Ambos leem a mesma estrutura DOM usada pelo editor e pela árvore `Estrutura`:

- `[data-cms-section]`
- `[data-cms-component]`
- `[data-cms-container]`
- `[data-cms-column]`

A seleção continua sendo indicada pelas classes canônicas do editor, especialmente `.cms-structure-selected`, `.cms-section-selected`, `.cms-selection` e `.cms-editing`.

## Compatibilidade

- Conteúdo legado promovido progressivamente passa a participar automaticamente da navegação quando recebe identidade estrutural.
- Componentes ricos (`Lista`, `Citação`, `Vídeo`, `Galeria`) usam seus nomes editoriais na navegação.
- Busca e breadcrumb são UI do editor; nada é serializado para o HTML público.
- O comportamento deve permanecer funcional após mover, duplicar, remover, inserir ou recarregar componentes.

## Regressão obrigatória

A suíte de navegador deve validar pelo menos:

- busca por componente aninhado mantendo seus ancestrais visíveis;
- ocultação de elementos não relacionados ao resultado;
- estado vazio;
- `Enter` selecionando resultado;
- `Esc` limpando busca;
- breadcrumb completo de um elemento aninhado;
- navegação para um ancestral pelo breadcrumb e sincronização da seleção no canvas.
