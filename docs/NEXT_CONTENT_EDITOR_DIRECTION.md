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
- compatibilidade com seções legadas sem migração destrutiva.

Próximas extensões devem partir desta árvore, e não criar novos comportamentos isolados por template.

Prioridades seguintes:

1. transformar elementos legados tocados pelo usuário em nós estruturais de primeira classe, para que possam entrar na árvore e ser movidos entre contêineres sem reconstruir a seção;
2. melhorar a inserção visual no canvas, com pontos de inserção explícitos entre blocos e dentro de colunas;
3. ampliar a biblioteca de componentes de conteúdo, começando por vídeo, lista, citação e galeria;
4. permitir largura individual de coluna quando o grupo usar mais de duas colunas, sem criar regras responsivas implícitas;
5. manter qualquer nova propriedade responsiva isolada por viewport ou com comportamento automático explícito.
