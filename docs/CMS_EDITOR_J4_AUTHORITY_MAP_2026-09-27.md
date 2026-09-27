# CMS/editor — J4: mapa factual de autoridades concorrentes — 27/09/2026

## Objetivo

Retomar a frente J4 depois do fechamento da auditoria da Área do aluno. Este documento mapeia o runtime real antes de remover qualquer camada. Não cria nova arquitetura por abstração: descreve quem hoje reage a seleção, hover, promoção de legado e drag-and-drop e define qual sobreposição deve ser retirada primeiro.

## Runtime observado

`editor/index.html` carrega, entre outras camadas relevantes:

- `cms-component-editor.js`;
- `cms-inline-reliability.js`;
- `cms-legacy-node-promotion.js`;
- `cms-structure-sidebar.js`;
- `cms-structure-authority.js`;
- `cms-direct-structure-dnd.js`;
- `cms-structure-navigation.js`;
- `cms-editor-consolidation.js`;
- `cms-section-coherence.js`, que carrega `cms-hover-selection.js` no runtime efetivo.

O problema do J4 não é a quantidade de arquivos em si. É quando duas ou mais camadas assumem a mesma responsabilidade por meios diferentes.

## 1. Seleção estrutural

### `cms-component-editor.js`

É a camada que efetivamente sabe transformar um nó estrutural em estado de edição. Ela:

- encontra `[data-cms-column]`, `[data-cms-container]` e `[data-cms-component]` no canvas;
- limpa seleção estrutural e seleção do núcleo;
- aplica `.cms-structure-selected`;
- monta o inspetor correspondente;
- agenda os controles inline;
- restaura seleção depois de salvar/recarregar por `data-cms-node-id`.

Portanto, **a autoridade funcional da seleção estrutural já está aqui**.

### `cms-structure-authority.js`

A barra lateral é declarada como árvore canônica e a camada remove árvores duplicadas do inspetor. Isso está correto.

A concorrência aparece na ativação: ao clicar num nó da árvore, `cms-structure-authority.js` resolve o alvo e sintetiza um `MouseEvent('click')` dentro do iframe. A seleção passa então pelo listener de clique de `cms-component-editor.js` como se o usuário tivesse clicado no canvas.

Isso significa que a árvore lateral não invoca a autoridade de seleção; ela simula outra origem de interação para fazê-la disparar.

**Classificação:** sobreposição real e primeiro candidato do J4.

**Direção:** expor uma entrada explícita e estreita de seleção estrutural pela camada que já possui a lógica (`cms-component-editor.js`) e fazer a árvore lateral chamá-la diretamente. O sidebar continua autoridade da navegação estrutural; o component editor continua autoridade da seleção. Não deve haver `MouseEvent` sintético como ponte.

## 2. Hover

### `cms-hover-selection.js`

É somente apresentação do alvo potencial. Resolve texto, imagem, vídeo, formulário, componente, coluna, contêiner e seção; desenha moldura e etiqueta; não persiste seleção nem altera documento canônico.

**Classificação:** responsabilidade própria, não concorrente com seleção enquanto permanecer apenas como preview visual.

**Regra:** J4 não deve fundir hover com seleção. A regressão deve continuar impedindo que a camada de hover se torne autoridade de clique.

## 3. Promoção de conteúdo legado

### `cms-legacy-node-promotion.js`

Escuta `pointerdown` e `focusin` em elementos antigos ainda sem `data-cms-component` e, quando seguros, promove `p`, headings, listas, citações e figuras para o modelo estrutural atual, atribuindo `data-cms-component` e `data-cms-node-id`.

Essa mutação ocorre antes ou durante a interação que também pode resultar em seleção pelo component editor.

**Classificação:** responsabilidade distinta, mas acoplada ao momento da seleção. Não remover neste primeiro PR porque a promoção ainda é a compatibilidade de documentos antigos.

**Próxima análise depois da seleção:** verificar se promoção precisa continuar ligada a dois eventos (`pointerdown` e `focusin`) ou se pode ser chamada por uma autoridade explícita sem prejudicar teclado e documentos antigos.

## 4. Drag-and-drop

### `cms-component-editor.js`

Ainda contém DnD em árvores internas do inspetor (`bindTree`, `dragNode`, `drop`).

### `cms-direct-structure-dnd.js`

Também implementa DnD estrutural no canvas e na árvore lateral canônica, com handles próprios, cálculo de alvo, feedback visual e persistência/reload.

Após J3/J4A anterior, árvores estruturais internas do inspetor são removidas por `cms-structure-authority.js`, mas o código de DnD correspondente continua dentro do component editor.

**Classificação:** dívida/concorrência histórica. Hoje a superfície canônica visível é a árvore lateral + canvas governados por `cms-direct-structure-dnd.js`, enquanto parte do DnD antigo permanece embarcada no component editor.

**Direção posterior:** provar por regressão que o DnD do inspetor não é mais alcançável no runtime e removê-lo em bloco separado. Não misturar essa limpeza com a correção da seleção.

## Ordem de J4 definida por este mapa

### J4a — ponte de seleção estrutural

- manter sidebar como autoridade da árvore;
- manter component editor como autoridade da seleção;
- eliminar `MouseEvent('click')` sintético de `cms-structure-authority.js`;
- expor somente a operação necessária de seleção, sem abrir todo o módulo como API pública;
- preservar scroll para o alvo;
- adicionar regressão estática que proíba a ponte por clique sintético;
- adicionar/ajustar regressão de navegador para provar que clicar na árvore seleciona o nó e abre o mesmo inspetor.

### J4b — DnD histórico do inspetor

Somente depois de J4a implantado:

- provar que a árvore interna do inspetor não reaparece;
- retirar handlers/estado de DnD ligados exclusivamente à árvore interna antiga;
- manter `cms-direct-structure-dnd.js` como autoridade de movimento no canvas e árvore lateral.

### J4c — promoção legada

Depois de estabilizar seleção e DnD:

- separar promoção de legado de gesto de seleção onde for possível;
- manter compatibilidade de documentos existentes;
- não promover conteúdo editorial apenas porque uma camada de hover o observou.

## Critério do próximo PR

J4a é deliberadamente pequeno. Ele não muda modelo de dados, HTML público, persistência, DnD, hover nem promoção de legado. Só troca a comunicação entre a árvore estrutural e a autoridade já existente de seleção, retirando um evento sintético intermediário.