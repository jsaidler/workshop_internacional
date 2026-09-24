# QA visual — ritmo vertical do material de estudo — 24/09/2026

## Evidência

Após a correção do eixo horizontal de leitura, a captura instalada mostrou um novo problema sistêmico: a página ficou verticalmente comprimida. O texto passou a ocupar uma largura adequada, mas unidades, subtítulos, notas técnicas, grades, procedimentos e figuras ficaram próximos demais entre si.

O sintoma não é falta de entrelinha. O corpo em 18 px / 1,58 está adequado como ponto de partida. O problema é a ausência de diferenças suficientes entre os níveis de espaçamento que estruturam o documento.

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

A causa está no contrato anterior aplicado literalmente ao box model: cada `.study-unit` recebeu aproximadamente 78–104 px tanto no início quanto no fim. Em um desktop de 1440 px, `7vw` resulta em cerca de 100,8 px. Portanto, uma única mudança conceitual passou a receber aproximadamente 100,8 px antes do divisor e mais 100,8 px depois dele. O intervalo renderizado superava 200 px, embora existisse apenas uma transição editorial.

A imagem também permite separar esse defeito dos demais níveis de ritmo. Dentro de **Registro dos testes**, a relação rótulo → frase introdutória → lista continua legível. O excesso está na fronteira entre duas unidades, não na entrelinha, no corpo tipográfico nem na largura de leitura.

## Decisão corrigida

Espaçamento passa a ser tratado como propriedade da **relação entre elementos**, não como soma automática de margens e paddings isolados.

Para duas unidades consecutivas, o objetivo desktop é aproximadamente **108–136 px entre o último conteúdo da unidade anterior e o primeiro rótulo/conteúdo da unidade seguinte, incluindo o divisor**. A abertura da nova unidade pode receber ligeiramente mais espaço que o encerramento da anterior, mas os dois lados não carregam mais uma pausa macro completa.

Calibração global adotada:

- desktop — início da unidade: `clamp(58px, 5vw, 72px)`;
- desktop — fim da unidade: `clamp(50px, 4.2vw, 62px)`;
- tablet — início: cerca de 58 px; fim: cerca de 54 px;
- celular — início: cerca de 48 px; fim: cerca de 46 px.

Os níveis intermediários introduzidos na primeira correção permanecem. Não há motivo visual para voltar a comprimir notas, grades, procedimentos, figuras ou parágrafos só porque a fronteira macro estava superdimensionada.

## O que não muda

- corpo de aproximadamente 18 px em desktop;
- entrelinha de aproximadamente 1,58;
- largura de leitura de aproximadamente 880 px;
- rótulo e texto no mesmo eixo;
- margens intermediárias já calibradas para notas, grades, procedimentos e figuras;
- texto técnico das fontes;
- estrutura de aulas e unidades;
- sistema visual global do site.

## Regressão

O browser test deve medir o ritmo renderizado, não apenas seletores CSS. Em desktop, tablet e celular, validar no mínimo:

- padding inicial e final da unidade com limites inferiores **e superiores**;
- distância real entre o último conteúdo de uma unidade e o divisor da próxima;
- distância real entre o divisor e o primeiro rótulo/conteúdo da unidade seguinte;
- intervalo total da fronteira entre unidades, impedindo tanto a compressão quanto o vazio duplicado;
- distância rótulo → conteúdo;
- intervalo entre parágrafos;
- margem de nota técnica;
- distância antes de grade/procedimento;
- margem de figura;
- ausência de overflow horizontal.

A regressão anterior verificava principalmente mínimos e, por isso, conseguia impedir o retorno ao estado comprimido mas não detectava excesso. O novo contrato exige faixa: respiro suficiente sem permitir que uma única transição acumule duas pausas macro.

A página longa não deve ser encurtada artificialmente às custas de legibilidade. O objetivo é leitura sustentada, não densidade máxima de interface; pela mesma razão, altura não deve ser criada artificialmente pela duplicação de espaçamento estrutural.
