# CMS — estrutura interna de conteúdo

## Objetivo

Uma seção não é um template fechado. Ela é um contêiner editorial que pode receber novos blocos e novos grupos de layout sem que o usuário precise trocar a seção inteira.

O editor distingue três níveis:

1. **Seção** — unidade editorial de alto nível da página.
2. **Contêiner / grupo de colunas** — estrutura interna reutilizável.
3. **Componente** — conteúdo: parágrafo, título, imagem, carrossel, botão, divisor ou espaço.

A estrutura pode ser aninhada, por exemplo:

`seção → grupo de 2 colunas → coluna → título → parágrafo → imagem`

ou

`seção → contêiner vertical → parágrafo → carrossel → grupo de 3 colunas`.

## Contrato de marcação

- componentes criados no editor usam `data-cms-component`;
- contêineres usam `data-cms-container="stack|columns"`;
- colunas usam `data-cms-column`;
- nós estruturais recebem `data-cms-node-id` para seleção e restauração do estado do editor;
- o documento HTML da página continua sendo a única fonte de verdade; não há uma segunda árvore armazenada no banco.

## Edição

Ao selecionar uma seção, o inspetor permite adicionar componentes e estruturas internas.

Ao selecionar um texto ou imagem existente, o usuário pode inserir novos componentes antes ou depois daquele ponto.

Contêineres, colunas e componentes criados no editor aparecem numa árvore estrutural. Os nós podem ser selecionados, movidos, duplicados e removidos. Drag-and-drop permite mover componentes entre colunas ou reordená-los.

## Responsividade

Desktop, tablet e celular são contratos independentes.

Um grupo de colunas pode definir separadamente:

- quantidade de colunas;
- proporção quando houver duas colunas;
- espaçamento entre colunas.

`Automático` significa usar a regra responsiva daquele viewport. Não significa herdar silenciosamente a configuração de desktop.

Exemplo: um grupo de `2 colunas / 30–70` no desktop cai para uma coluna no tablet e celular quando estes estão em `Automático`. Se o usuário escolher explicitamente duas colunas no tablet, apenas então a proporção de tablet passa a ser aplicável.

## Compatibilidade

As seções legadas continuam válidas e não são convertidas automaticamente. Novos componentes podem ser inseridos ao redor do conteúdo existente. A estrutura de primeira classe é aplicada somente aos nós criados ou reorganizados pelo editor.

Formulários continuam isolados do construtor de conteúdo: os campos e validações são gerenciados no editor de formulários.

## Regra de produto

Novas capacidades de layout não devem ser introduzidas com comportamento responsivo implícito dependente de uma escolha feita em outro viewport. Se uma propriedade puder variar por dispositivo e essa variação for relevante, ela deve ter regra automática explícita ou controle específico por viewport.
