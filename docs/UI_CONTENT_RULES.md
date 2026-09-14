# Regras de conteúdo da interface administrativa

A interface do CMS deve apresentar somente conteúdo necessário para o usuário operar e tomar decisões editoriais.

## Não transformar instrução em conteúdo

Explicações destinadas ao desenvolvimento, arquitetura, implementação, debugging, CSS interno, nomes de variáveis, fallbacks técnicos, carregamento de assets ou justificativas de código não devem aparecer na interface como texto de ajuda apenas porque foram usadas durante a implementação.

Antes de adicionar qualquer texto auxiliar a uma tela administrativa, responder:

1. o usuário precisa desta informação para executar a tarefa naquela tela?
2. a informação descreve uma decisão editorial real ou apenas como o sistema foi construído?
3. removê-la tornaria a operação ambígua ou insegura?

Se a resposta a 1 e 3 for não, o texto pertence à documentação interna, não à interface.

## Controles

Um controle deve representar diretamente a decisão disponível. Quando uma escolha puder ser validada pelo sistema, preferir seletor, opção ou controle estruturado a campo de texto livre.

Detalhes internos podem viajar em atributos, schema ou código para preview/renderização, mas não devem ser exibidos como conteúdo editorial.
