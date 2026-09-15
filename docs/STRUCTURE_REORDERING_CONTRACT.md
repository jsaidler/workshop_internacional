# Contrato de reordenação estrutural do editor

Este documento define o comportamento canônico para mover componentes no editor visual de páginas.

## Objetivo

A estrutura da página deve poder ser reorganizada sem depender principalmente dos botões `↑` e `↓` do inspector. O usuário precisa conseguir mover blocos diretamente pelos dois lugares onde já enxerga a estrutura: o canvas e a árvore `Estrutura`.

## Superfícies de movimento

### Canvas

Quando um componente estrutural está selecionado, o editor mostra um controle `Mover ⋮⋮` fora do conteúdo persistido da página. Esse controle é editor-only e nunca pode entrar no HTML salvo ou público.

Ao arrastar:

- sobre outro bloco, a metade superior significa inserir antes e a metade inferior significa inserir depois;
- sobre uma coluna, um bloco comum é movido para dentro dela;
- sobre um contêiner vertical, um bloco comum é movido para dentro dele;
- uma coluna só pode ser reordenada em relação a outra coluna do mesmo grupo;
- um bloco nunca pode ser movido para dentro de si próprio nem para dentro de seus descendentes.

O alvo precisa ter feedback visual inequívoco de `antes`, `depois` ou `dentro` antes da soltura.

### Árvore Estrutura

Cada linha estrutural possui uma alça de arraste própria. A semântica de destino é a mesma do canvas: antes/depois para blocos irmãos ou transferíveis, dentro para colunas e contêineres verticais.

A árvore e o canvas manipulam o mesmo DOM canônico. Não existe uma segunda representação estrutural a sincronizar.

## Persistência

Depois de um movimento válido:

1. o DOM editável é alterado;
2. o componente movido mantém ou recebe `data-cms-node-id`;
3. o editor registra esse identificador em `cms-editor-reselect`;
4. o rascunho é salvo pelo fluxo normal do editor;
5. o iframe é recarregado;
6. o componente movido volta selecionado quando possível.

Classes e controles usados apenas durante o arraste são transitórios e não fazem parte do conteúdo público.

## Compatibilidade

Os botões `↑`, `↓`, `Duplicar` e `Remover` continuam disponíveis como alternativa. A nova interação não altera a semântica de componentes existentes nem cria um formato paralelo de página.

Formulários expandidos continuam fora da movimentação estrutural genérica. A edição interna de formulário permanece responsabilidade do editor próprio de formulários.

## Regressão obrigatória

A suíte de navegador deve validar pelo menos:

- transferência de um componente entre duas colunas pela árvore;
- transferência de um componente para uma coluna pelo controle direto do canvas;
- persistência do resultado depois do salvamento/reload;
- ausência do controle de movimento dentro de `[data-cms-page-main]`.
