# Direção do editor de conteúdo

O editor passa a tratar seções como contêineres editoriais, não como templates fechados.

A base implementada nesta etapa estabelece:

- componentes inseríveis dentro de seções;
- contêiner vertical;
- grupos de 2, 3 ou 4 colunas;
- colunas como alvos de inserção;
- árvore estrutural para seleção e drag-and-drop;
- configurações independentes para desktop, tablet e celular nos grupos de colunas;
- compatibilidade com seções legadas sem migração destrutiva.

Próximas extensões devem partir desta árvore, e não criar novos comportamentos isolados por template. Exemplos futuros: largura individual de coluna, alinhamento interno, background por contêiner, reordenação por breakpoint e mais componentes de conteúdo.
