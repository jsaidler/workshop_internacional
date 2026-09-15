# Direção do editor de conteúdo

O editor passa a tratar seções como contêineres editoriais, não como templates fechados.

A base implementada estabelece:

- componentes inseríveis dentro de seções;
- contêiner vertical;
- grupos de 2, 3 ou 4 colunas;
- colunas como alvos de inserção;
- árvore estrutural para seleção e drag-and-drop;
- configurações independentes para desktop, tablet e celular nos grupos de colunas;
- largura, fundo, borda, padding e alinhamento dos contêineres;
- alinhamento interno, alinhamento de texto, ordem e visibilidade das colunas por viewport;
- breakpoints canônicos únicos para estruturas novas e legadas: desktop a partir de 1025 px, tablet de 721 a 1024 px e celular até 720 px;
- compatibilidade com seções legadas sem migração destrutiva;
- promoção progressiva de conteúdo legado quando ele é tocado no editor;
- inserção direta no canvas e movimentação por drag-and-drop no canvas e na árvore;
- componentes de lista, citação, vídeo e galeria;
- busca, nomes editoriais e breadcrumb na árvore;
- histórico transacional de Desfazer/Refazer para operações estruturais.

## Consolidação da interface

A árvore **Estrutura** é a navegação principal para novas sessões de edição. A visualização **Seções** continua disponível como uma visão simplificada, mas deixa de ser o ponto de partida conceitual.

A ação **Adicionar seção** passa a abrir uma única biblioteca. O antigo botão global “+ Componentes”, que na prática criava seções inteiras, deixa de aparecer. Na biblioteca, os presets continuam sendo apresentados como **Seções prontas** e os blocos reutilizáveis permanecem separados em **Blocos salvos**.

Novas “grades livres” no modelo antigo deixam de ser oferecidas. A alternativa passa a ser **Seção livre**, já criada com o modelo atual de contêiner e componentes.

Quando uma seção antiga usa `cms-free-grid`, seus controles originais ficam recolhidos em **Ajustes da composição pronta**. Se a grade não possuir larguras personalizadas por `data-cms-span`, o editor oferece uma conversão explícita para a estrutura atual. A conversão:

- mantém o conteúdo existente;
- transforma a grade em um grupo de colunas de primeira classe;
- transforma suas células em colunas de primeira classe;
- preserva, quando possível, proporção, espaçamento aproximado e ordem móvel;
- passa a expor as colunas na árvore Estrutura;
- nunca ocorre automaticamente.

Grades antigas que usam `data-cms-span` não são convertidas automaticamente, porque o modelo atual ainda não representa com fidelidade essas larguras individuais.

Próximas extensões devem partir desta árvore, e não criar novos comportamentos isolados por template.

Prioridades seguintes:

1. implementar largura individual de coluna no modelo atual, com controles independentes por viewport, para permitir a conversão segura das últimas grades antigas com `data-cms-span`;
2. continuar reduzindo controles duplicados dos templates antigos, mantendo-os apenas quando ainda forem necessários para preservar o desenho existente;
3. tornar a biblioteca de seções prontas baseada internamente nos mesmos contêineres e componentes usados pela edição estrutural;
4. ampliar a manipulação por teclado e acessibilidade da árvore e do drag-and-drop;
5. manter qualquer nova propriedade responsiva isolada por viewport ou com comportamento automático explícito.
