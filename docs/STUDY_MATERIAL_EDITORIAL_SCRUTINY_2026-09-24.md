# Escrutínio editorial e de leitura — material de estudo — 24/09/2026

## Evidência examinada

A captura integral instalada após o PR #81 foi avaliada como página longa, e não apenas por recortes de componentes. A correção anterior resolveu relações locais de espaçamento, mas o documento ainda apresentava uma experiência excessivamente fragmentada.

O problema deixou de ser simplesmente “falta de espaço”. Havia espaço, mas ele estava distribuído por um sistema visual que dava peso demais a elementos secundários. O resultado era uma sequência de texto, linha, quadro, texto, quadro, linha, título, quadro. O leitor precisava reaprender continuamente qual superfície merecia atenção.

## Diagnóstico

### 1. Excesso de superfícies enquadradas

`technical-note` era renderizada como um painel largo com fundo distinto, bordas horizontais e borda lateral. Isso fazia uma fórmula curta, uma instrução operacional, uma citação e um resumo ocuparem visualmente quase a mesma categoria de uma referência central.

Na captura, vários desses painéis parecem grandes demais para a quantidade de informação que carregam. O vazio interno e a moldura passam a chamar mais atenção que o texto.

### 2. Todas as grades pareciam ter a mesma importância

A fonte contém cinco grades não ligadas ao índice, mas elas cumprem funções muito diferentes:

- relação EV → fração de luz: comparação conceitual útil;
- reciprocidade: série de dados comparáveis;
- quatro volumes de Parodinal com a mesma indicação “água até 550 ml”: referência literal de baixa densidade sem rótulos que expliquem uma diferença adicional;
- EI 200 × EI 400: comparação direta entre duas condições;
- onde encontrar materiais: catálogo de consulta.

Renderizar tudo como `format-card` produz uma parede de cards. Isso é semanticamente incorreto e visualmente cansativo.

O caso dos quatro volumes de Parodinal é o exemplo mais claro do problema apontado pelo usuário: a fonte preserva `15 ml`, `11 ml`, `6 ml` e `5 ml`, e cada item repete `água até 550 ml`. A fonte, nesse ponto, não fornece rótulos adicionais que expliquem por que cada valor mereceria um card autônomo. Inventar esses rótulos violaria a fidelidade editorial. A solução correta é manter os valores literais e reduzir sua hierarquia gráfica.

### 3. Lista de materiais tratada como mosaico promocional

“Onde encontrar os materiais” contém informação útil, mas é lookup: nome do material de um lado, local/observação do outro. Doze cards criam área visual, não ganho cognitivo. O formato adequado é lista de referência com linhas, não mosaico.

### 4. Regras horizontais demais

Cada unidade tinha borda superior e cada `section-label` possuía outra linha horizontal. Somadas a grades, notas e listas, essas linhas dividiam a página em pequenas caixas sucessivas. O divisor de aula já cumpre a função macro. Dentro de uma aula, o fluxo deve depender sobretudo de tipografia e espaço.

### 5. A coluna estava ligeiramente larga para a densidade real do texto

O corpo de 18 px em 880 px funciona tecnicamente, mas a captura longa contém muitos parágrafos explicativos e relações químicas. Reduzir a medida para aproximadamente 840 px melhora a varredura ocular sem retornar à coluna excessivamente estreita das primeiras versões.

### 6. O ritmo macro estava correto em princípio, mas alto para um documento tão longo

Depois de remover a duplicação extrema, a fronteira de unidade ficou em torno de 108–136 px. Com mais de vinte unidades, isso ainda produz uma página artificialmente alongada. Como as linhas divisórias internas serão removidas e o nível meso será mais claro, a fronteira pode cair para aproximadamente 92–118 px em desktop sem perder orientação.

## Decisões

### O documento volta a ser texto antes de ser interface

O material passa a ser tratado como longform editorial. Painéis e matrizes são exceção. Um elemento recebe superfície própria somente quando a espacialização ajuda a comparar, consultar ou executar.

### Regra de merecimento de um quadro

Um quadro só é justificável quando pelo menos uma destas condições é verdadeira:

1. dois ou mais valores precisam ser comparados pela posição;
2. a informação será consultada como referência independente;
3. há uma sequência operacional que se beneficia de numeração;
4. a imagem ou tabela precisa de largura própria;
5. a distinção visual evita erro operacional real.

Se o conteúdo é apenas uma frase curta, uma observação, uma fórmula ou uma citação, a solução padrão é tipográfica: espaço, keyline, corpo, mono ou outra diferença mínima — não um retângulo grande.

### Tratamento das referências existentes

- Índice: permanece navegacional e pode conservar cards clicáveis.
- EV → fração de luz: `study-data-strip`, matriz leve com linhas e sem fundo de card.
- Reciprocidade: `study-data-strip`, porque posição comparativa ajuda a perceber o crescimento dos tempos.
- Diluições 15/11/6/5 ml: `study-compact-values`; preserva integralmente os quatro itens da fonte, mas sem quatro cards dominantes.
- EI 200 × EI 400: `study-comparison`, duas condições lado a lado no desktop e empilhadas no celular.
- Onde encontrar materiais: `study-resource-list`, linhas de consulta material → fornecedor/observação.

### Notas curtas

`technical-note` deixa de ser painel sombreado. Passa a ser uma interrupção tipográfica com keyline lateral, fundo transparente e altura determinada pelo próprio conteúdo.

Funções específicas podem receber subclasses sem alterar uma palavra da fonte:

- `study-equation` — reação/equação;
- `study-quote` — citação;
- `study-summary` — síntese operacional/conceitual;
- `study-instruction` — instrução curta.

### Linhas e divisores

- remover borda superior de cada `study-unit`;
- remover sublinhado de largura total do `section-label`;
- conservar o divisor de aula como marco macro;
- listas de processo e listas de referência podem usar linhas finas porque nelas a linha organiza itens, não divide artificialmente o texto.

### Medida e ritmo

Desktop:

- corpo: 18 px;
- entrelinha: aproximadamente 1,64;
- medida de leitura: aproximadamente 840 px;
- rótulo → conteúdo: aproximadamente 24 px;
- título estrutural → corpo: aproximadamente 28 px;
- mudança interna por `h3`: aproximadamente 46 px antes e 17 px depois;
- nota curta: aproximadamente 40 px de entrada/saída;
- grade/processo: aproximadamente 42 px;
- figura: aproximadamente 50 px;
- fronteira entre unidades consecutivas: aproximadamente 112–118 px no viewport desktop de referência, variando dentro dos `clamp()` do componente.

Tablet e celular reduzem os intervalos, preservando a ordem micro < meso < macro.

## O que não foi feito

- nenhum trecho técnico foi reescrito;
- nenhum dos quatro volumes do bloco de diluição foi eliminado;
- nenhum rótulo explicativo foi inventado para completar lacunas da fonte;
- nenhuma divergência entre os e-mails foi “corrigida”;
- nenhum conhecimento externo foi acrescentado;
- nenhum seletor CSS passou a depender do slug do caderno.

## Critério de regressão

Os testes de browser passam a verificar não apenas distância, mas também peso semântico:

- corpo central próximo de 840 px em desktop;
- entrelinha de longform;
- ausência de borda de unidade e sublinhado de rótulo;
- notas com fundo transparente, sem bordas horizontais e com keyline lateral;
- `study-data-strip` em matriz no desktop e coluna no celular;
- `study-compact-values` em quatro colunas no desktop e duas no celular;
- `study-comparison` em duas colunas no desktop e uma no celular;
- `study-resource-list` como lista de linhas, não card wall;
- fronteira macro com limites inferior e superior;
- ausência de overflow horizontal;
- índice ainda navegável por teclado.

O teste de integridade do caderno também passa a executar a migração 061 e exigir as classes semânticas, ao mesmo tempo em que continua validando literalmente o conteúdo das fontes.
