# QA visual/editorial — material de estudo — 24/09/2026

## Evidência

Revisão feita sobre a captura integral do material já atualizado e sobre a captura em escala de leitura do final da Aula 02 / Aula 03.

A primeira rodada corrigiu o problema estrutural principal: o material deixou de funcionar como uma coluna estreita perdida num desktop largo e passou a distinguir canvas editorial, medida de leitura, capítulos, unidades e componentes de referência.

A segunda captura mostrou um problema diferente, agora de **hierarquia editorial aplicada ao conteúdo real**.

## Achado crítico

A frase da fonte:

> Para os próximos testes, considero mais útil estabelecer primeiro uma condição de referência:

estava sendo usada como `h3` dentro da coluna estreita de título de um `statement-grid`.

O resultado renderizado transformou uma frase argumentativa em uma torre tipográfica de muitas linhas. Isso produz três defeitos ao mesmo tempo:

1. a frase deixa de ser lida como parte do raciocínio e passa a parecer uma manchete;
2. a coluna estreita força quebras excessivas e cria peso visual desproporcional;
3. o tratamento contradiz o contrato editorial: melhorar a composição não autoriza promover uma frase da fonte a slogan.

## Decisão

Frases completas da fonte que introduzem uma lista, hipótese, condição ou referência permanecem no eixo de leitura. Quando o bloco precisar de identificação lateral, o título pode ser apenas **paratexto estrutural curto**.

Neste caso:

- `Condição de referência` passa a ser o título estrutural curto;
- a frase original volta para o corpo, sem alteração de palavras;
- os valores e os dois parágrafos seguintes permanecem na mesma ordem e com a mesma redação.

Essa regra não é específica deste caderno. Em qualquer `study-material`, uma coluna de título estreita deve receber nomes de seção ou rótulos curtos, não frases argumentativas extensas.

## O que a captura também confirmou

- O corpo está em medida adequada para leitura prolongada; não deve ser encolhido para reduzir a altura total da página.
- O comprimento do documento é majoritariamente consequência da quantidade real de conteúdo. Não deve ser tratado como defeito por meio de compressão indiscriminada, redução tipográfica ou eliminação de respiro.
- Os divisores de aula agora interrompem corretamente o fluxo sem parecer hero de landing page.
- A grade de materiais é densa, mas continua legível na captura fornecida; não há evidência suficiente para substituí-la por outro componente apenas para reduzir quebras de linha.
- O registro de testes funciona melhor como lista de referência do que como cards e deve permanecer assim.

## Critério de regressão

O CI deve impedir que a frase `Para os próximos testes, considero mais útil estabelecer primeiro uma condição de referência:` volte a ser um heading. A frase precisa continuar presente literalmente no documento, mas no corpo de leitura, enquanto `Condição de referência` identifica o bloco estruturalmente.
