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
- A ausência temporária de uma imagem não deve produzir texto técnico ou placeholder público; o elemento é simplesmente omitido até existir mídia editorial válida.
- Cada frase pública precisa cumprir uma função para o interessado: explicar o que é, mostrar valor, responder uma dúvida real, reduzir risco relevante ou conduzir à ação.
- Quando um fato operacional é necessário para a decisão, comunicar somente o fato na linguagem do participante, sem expor a justificativa interna.

## Conteúdo editorial baseado em fonte

Quando uma página é declarada como derivada de e-mails, documentos, pesquisas ou outro corpus definido, esse corpus é a fonte editorial. O sistema pode organizar e hierarquizar o material, mas não substituí-lo por conteúdo genérico nem corrigir silenciosamente registros divergentes. Conflitos relevantes entre fontes precisam permanecer explícitos até decisão editorial do autor.

Uma ilustração criada para esse conteúdo também precisa ser derivada das relações efetivamente descritas na fonte. Não basta usar o mesmo assunto. Um infográfico sobre “fotografia analógica”, “revelação” ou “reciprocidade” em termos genéricos não satisfaz uma página cuja fonte define relações, valores, etapas ou contrastes específicos.

## Estilo das páginas do CMS

Páginas normais do CMS não carregam stylesheet próprio nem estilos inline para criar uma identidade paralela. O visual vem do sistema global do site e das configurações de Design. Uma exceção deliberada é feita em `Design → CSS adicional`, não dentro do conteúdo da página.

Também é proibido deslocar para um arquivo global um conjunto de seletores criado exclusivamente para uma página e então tratá-lo como “componente compartilhado”. Um componente só é global quando já pertence ao vocabulário geral do CMS/site ou quando existe uma necessidade sistêmica comprovada para várias superfícies, independente da página que motivou a mudança.

Ilustrações editoriais são conteúdo de mídia. Quando o projeto pede infográficos, usar arquivos de infográfico apropriados; não substituir por SVG/HTML improvisado, por fotografia simulada ou por uma peça genérica que apenas compartilhe o tema do texto.

### Materiais longos e didáticos

Material extenso não deve ser apresentado como uma coluna contínua de texto semelhante a e-mail. Quando existir divisão real por capítulos, encontros ou aulas, essa estrutura precisa ser legível também visualmente.

- separar capa, índice, aulas e unidades editoriais;
- cada aula deve possuir divisor visual inequívoco e participar da mesma regra de liberação do conteúdo que introduz;
- cada unidade extensa deve se comportar visualmente como um bloco editorial autônomo, com respiro e limite claros em relação à seguinte;
- não criar texto para preencher uma aula sem fonte editorial real;
- montar a hierarquia com componentes já existentes no sistema visual do site; não criar uma família CSS específica do material;
- se uma exceção visual for realmente necessária, ela pertence a `Design → CSS adicional` e deve continuar sob controle editorial;
- a hierarquia visual deve usar a mesma tipografia, paleta, linhas, escala e lógica de composição do restante do site.

## UI/UX administrativa unificada e escalável

Toda nova superfície administrativa deve partir dos componentes compartilhados do admin e permanecer utilizável com volume real de dados.

- Não criar uma identidade visual isolada para uma única tela quando o padrão pode ser compartilhado.
- Listas potencialmente grandes precisam prever busca, filtros e paginação ou carregamento progressivo.
- Tabelas e listas devem manter cabeçalhos, estados, ações e comportamento responsivo consistentes.
- A navegação deve separar tarefas e evitar uma página infinita com todas as operações e todos os registros carregados simultaneamente.
- Controles, estados vazios, feedback, ações primárias/secundárias e formulários devem reutilizar a mesma linguagem da administração.
- Se uma necessidade nova revelar falta de um componente, criar o componente na camada compartilhada somente quando a necessidade for sistêmica e reutilizável; não mascarar uma exceção de página como componente global.

## Controles

Um controle deve representar diretamente a decisão disponível. Quando uma escolha puder ser validada pelo sistema, preferir seletor, opção ou controle estruturado a campo de texto livre.

Detalhes internos podem viajar em atributos, schema ou código para preview/renderização, mas não devem ser exibidos como conteúdo editorial.
