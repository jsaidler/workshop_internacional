# QA visual — ritmo vertical do material de estudo — 24/09/2026

## Evidência

Após a correção do eixo horizontal de leitura, a captura instalada mostrou um problema sistêmico de ritmo vertical. O texto passou a ocupar uma largura adequada, mas unidades, subtítulos, notas técnicas, grades, procedimentos e figuras ficaram próximos demais entre si.

O sintoma não é falta de entrelinha. O corpo em 18 px / 1,58 está adequado como ponto de partida. O problema é a diferença insuficiente entre os níveis de espaçamento que estruturam o documento.

## Diagnóstico inicial

A versão anterior usava aproximadamente:

- 48–68 px de padding vertical por unidade em desktop;
- 14 px entre rótulo de unidade e conteúdo;
- 24 px ao redor de notas técnicas;
- 28 px antes de grades e procedimentos;
- 34 px ao redor de figuras;
- 0,72 em entre parágrafos.

Em uma publicação longa e escura, esses valores produziam uma massa contínua. O leitor conseguia seguir horizontalmente, mas tinha pouca pausa visual para reconhecer quando terminava um argumento, começava um elemento de referência ou mudava a unidade conceitual.

## Primeira decisão

O sistema global `study-material` passou a usar três níveis perceptíveis de respiro:

1. **continuidade** — parágrafos e itens relacionados;
2. **mudança interna** — subtítulos, notas, fórmulas, grades, procedimentos e figuras;
3. **mudança de unidade/aula** — maior pausa do sistema.

A primeira correção aumentou o respiro de unidades e componentes intermediários sem alterar a largura de leitura, o corpo tipográfico nem o conteúdo técnico.

### Primeira calibração de desktop

- unidade: aproximadamente 78–104 px de padding vertical em cada lado;
- rótulo → conteúdo: cerca de 22 px;
- parágrafos: aproximadamente 0,95 em;
- notas técnicas: cerca de 40 px de margem vertical;
- grades: cerca de 46 px antes do componente;
- procedimentos: cerca de 44 px antes do conjunto;
- figuras: cerca de 56 px acima e abaixo;
- separadores internos: cerca de 52 px.

### Tablet e celular

Os valores diminuíram proporcionalmente, mas sem voltar ao compactamento anterior. O eixo continuou único e a relação entre continuidade, mudança interna e mudança de unidade permaneceu visível.

## Segunda validação: a pausa macro estava sendo duplicada

A captura instalada após essa primeira correção revelou um problema diferente. Entre duas unidades consecutivas — visível com clareza na passagem de **Como ler os resultados** para **Registro dos testes** — o divisor ficou isolado entre dois vazios grandes.

A causa estava no contrato anterior aplicado literalmente ao box model: cada `.study-unit` recebeu aproximadamente 78–104 px tanto no início quanto no fim. Em um desktop de 1440 px, `7vw` resulta em cerca de 100,8 px. Portanto, uma única mudança conceitual passou a receber aproximadamente 100,8 px antes do divisor e mais 100,8 px depois dele. O intervalo renderizado superava 200 px, embora existisse apenas uma transição editorial.

A segunda correção tratou espaçamento como propriedade da **relação entre elementos**, não como soma automática de margens e paddings isolados.

Para duas unidades consecutivas, o objetivo desktop passou a ser aproximadamente **108–136 px entre o último conteúdo da unidade anterior e o primeiro rótulo/conteúdo da unidade seguinte, incluindo o divisor**. A abertura da nova unidade pode receber ligeiramente mais espaço que o encerramento da anterior, mas os dois lados não carregam uma pausa macro completa.

Calibração macro adotada:

- desktop — início da unidade: `clamp(58px, 5vw, 72px)`;
- desktop — fim da unidade: `clamp(50px, 4.2vw, 62px)`;
- tablet — início: cerca de 58 px; fim: cerca de 54 px;
- celular — início: cerca de 48 px; fim: cerca de 46 px.

## Terceira validação: o nível intermediário continuava comprimido

A inspeção seguinte, em captura integral e em recorte ampliado, mostrou que a correção macro não resolvia o problema de leitura dentro das unidades. A primeira leitura havia considerado o conjunto **Registro dos testes** aceitável, mas o recorte em escala normal tornou evidente que ainda havia conteúdo visualmente grudado, sobretudo **títulos/subtítulos e caixas/componentes de referência**.

O defeito estava concentrado em relações de nível intermediário:

- rótulo de unidade muito próximo do primeiro conteúdo;
- `h2` e `h3` com pouca distância do texto que introduzem;
- subtítulo interno com pouca pausa em relação ao argumento anterior;
- `format-grid` e `process-list` com margem apenas de entrada, sem respiro equivalente quando o texto retomava depois deles;
- nota técnica, grade, procedimento e figura sem uma regra explícita de entrada **e saída**;
- índice com título e grade visualmente próximos demais.

Isso explica por que a página podia simultaneamente apresentar um vazio macro exagerado em uma fronteira e, poucos centímetros abaixo, uma caixa parecer colada ao texto seguinte. São níveis diferentes de espaçamento e precisam de contratos independentes.

## Decisão final desta rodada

O nível meso passa a ter relações explícitas dos dois lados do componente. Em desktop:

- rótulo de unidade → primeiro conteúdo: aproximadamente **30 px**;
- `h2` estrutural → texto que ele introduz: aproximadamente **36 px**;
- subtítulo interno `h3`: aproximadamente **56 px antes** e **22 px depois**;
- nota técnica: aproximadamente **48 px antes e depois**;
- grade comparativa: aproximadamente **52 px antes e depois**;
- procedimento: aproximadamente **52 px antes e depois**;
- figura: aproximadamente **60 px antes e depois**;
- título do índice → grade de aulas: aproximadamente **36 px**.

Em tablet, esses valores descem aproximadamente para 28 / 34 / 50–20 / 44 / 48 / 48 / 52 px. Em celular, para aproximadamente 24 / 30 / 44–18 / 36 / 40 / 40 / 44 px.

A regra de saída não pode aumentar novamente a pausa macro. Quando nota, grade, procedimento, figura, definição ou separador são o último elemento da unidade, a margem inferior intermediária é anulada e o padding da própria unidade continua sendo a única pausa estrutural até a próxima seção.

## O que não muda

- corpo de aproximadamente 18 px em desktop;
- entrelinha de aproximadamente 1,58;
- largura de leitura de aproximadamente 880 px;
- rótulo e texto no mesmo eixo;
- intervalo macro corrigido entre unidades consecutivas;
- texto técnico das fontes;
- estrutura de aulas e unidades;
- sistema visual global do site.

## Regressão

O browser test deve medir o ritmo renderizado, não apenas seletores CSS. Em desktop, tablet e celular, validar no mínimo:

- padding inicial e final da unidade com limites inferiores **e superiores**;
- distância real entre o último conteúdo de uma unidade e o divisor da próxima;
- distância real entre o divisor e o primeiro rótulo/conteúdo da unidade seguinte;
- intervalo total da fronteira entre unidades, impedindo tanto a compressão quanto o vazio duplicado;
- distância rótulo → primeiro conteúdo;
- distância `h2` → corpo;
- distância argumento anterior → `h3` e `h3` → corpo seguinte;
- intervalo entre parágrafos relacionados;
- entrada e saída de nota técnica;
- entrada e saída de grade comparativa;
- entrada e saída de procedimento;
- entrada e saída de figura;
- título do índice → grade;
- ausência de margem meso inferior redundante quando o componente encerra a unidade;
- ausência de overflow horizontal.

A regressão não pode testar apenas mínimos. Cada relação importante recebe uma faixa inferior e superior para impedir tanto a volta ao estado comprimido quanto a criação de vazios artificiais.

A página longa não deve ser encurtada às custas de legibilidade. O objetivo é leitura sustentada, não densidade máxima de interface; pela mesma razão, altura não deve ser criada artificialmente pela duplicação de espaçamento estrutural.
