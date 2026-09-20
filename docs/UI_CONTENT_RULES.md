# Regras de conteúdo da interface e das páginas públicas

O conteúdo exibido pelo sistema deve apresentar somente o que o público daquela superfície precisa para compreender, operar ou decidir.

## Não transformar instrução em conteúdo

Explicações destinadas ao desenvolvimento, arquitetura, implementação, debugging, CSS interno, nomes de variáveis, fallbacks técnicos, carregamento de assets ou justificativas de código não devem aparecer na interface como texto de ajuda apenas porque foram usadas durante a implementação.

Antes de adicionar qualquer texto auxiliar a uma tela administrativa, responder:

1. o usuário precisa desta informação para executar a tarefa naquela tela?
2. a informação descreve uma decisão editorial real ou apenas como o sistema foi construído?
3. removê-la tornaria a operação ambígua ou insegura?

Se a resposta a 1 e 3 for não, o texto pertence à documentação interna, não à interface.

## Páginas públicas: contexto zero

Toda página pública deve ser escrita para uma pessoa que chegou ali sem conhecer conversas internas, decisões de bastidor, raciocínio de marketing ou processo de desenvolvimento.

- O visitante não conhece o histórico da oferta, o funil, os testes de preço, a arquitetura do produto nem as razões operacionais de cada decisão.
- Instruções usadas para orientar implementação, placeholders, justificativas de método e comentários de bastidor nunca devem ser transformados em copy pública.
- Expressões como “protótipo entra aqui”, “em preparação”, “para não depender do tempo”, “porta de entrada”, “regras serão informadas” ou explicações sobre por que uma etapa foi estruturada de determinada maneira pertencem ao trabalho interno, não à página.
- Cada frase pública precisa cumprir uma função para o interessado: explicar o que é, mostrar valor, responder uma dúvida real, reduzir risco relevante ou conduzir à ação.
- Quando um fato operacional é necessário para a decisão — formato ao vivo, duração, gravação, materiais, o que a atividade inclui ou não inclui — comunicar somente o fato na linguagem do participante, sem expor a justificativa interna que levou à decisão.
- A página não deve pressupor que o visitante sabe o que foi discutido antes. Termos próprios da oferta precisam ser compreensíveis no próprio texto.
- A ausência temporária de imagem, preço, data ou outro elemento não deve ser compensada com texto de bastidor. O conteúdo público mostra apenas o que já pode ser afirmado ao interessado.

## Controles

Um controle deve representar diretamente a decisão disponível. Quando uma escolha puder ser validada pelo sistema, preferir seletor, opção ou controle estruturado a campo de texto livre.

Detalhes internos podem viajar em atributos, schema ou código para preview/renderização, mas não devem ser exibidos como conteúdo editorial.
