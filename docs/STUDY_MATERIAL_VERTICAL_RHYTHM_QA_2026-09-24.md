# QA visual — ritmo vertical do material de estudo — 24/09/2026

## Evidência

Após a correção do eixo horizontal de leitura, a captura instalada mostrou um novo problema sistêmico: a página ficou verticalmente comprimida. O texto passou a ocupar uma largura adequada, mas unidades, subtítulos, notas técnicas, grades, procedimentos e figuras ficaram próximos demais entre si.

O sintoma não é falta de entrelinha. O corpo em 18 px / 1,58 está adequado como ponto de partida. O problema é a ausência de diferenças suficientes entre os níveis de espaçamento que estruturam o documento.

## Diagnóstico

A versão anterior usava aproximadamente:

- 48–68 px de padding vertical por unidade em desktop;
- 14 px entre rótulo de unidade e conteúdo;
- 24 px ao redor de notas técnicas;
- 28 px antes de grades e procedimentos;
- 34 px ao redor de figuras;
- 0,72 em entre parágrafos.

Em uma publicação longa e escura, esses valores produziam uma massa contínua. O leitor conseguia seguir horizontalmente, mas tinha pouca pausa visual para reconhecer quando terminava um argumento, começava um elemento de referência ou mudava a unidade conceitual.

## Decisão

O sistema global `study-material` passa a usar três níveis perceptíveis de respiro:

1. **continuidade** — parágrafos e itens relacionados;
2. **mudança interna** — subtítulos, notas, fórmulas, grades, procedimentos e figuras;
3. **mudança de unidade/aula** — maior pausa do sistema.

A correção aumenta o respiro de unidades e componentes intermediários sem alterar a largura de leitura, o corpo tipográfico nem o conteúdo técnico.

### Desktop

- unidade: aproximadamente 78–104 px de padding vertical;
- rótulo → conteúdo: cerca de 22 px;
- parágrafos: aproximadamente 0,95 em;
- notas técnicas: cerca de 40 px de margem vertical;
- grades: cerca de 46 px antes do componente;
- procedimentos: cerca de 44 px antes do conjunto;
- figuras: cerca de 56 px acima e abaixo;
- separadores internos: cerca de 52 px.

### Tablet e celular

Os valores diminuem proporcionalmente, mas não voltam ao compactamento anterior. O eixo continua único e a relação entre continuidade, mudança interna e mudança de unidade permanece visível.

## O que não muda

- corpo de aproximadamente 18 px em desktop;
- entrelinha de aproximadamente 1,58;
- largura de leitura de aproximadamente 880 px;
- texto técnico das fontes;
- estrutura de aulas e unidades;
- sistema visual global do site.

## Regressão

O browser test deve medir o ritmo renderizado, não apenas seletores CSS. Em desktop, tablet e celular, validar no mínimo:

- padding vertical da unidade;
- distância rótulo → conteúdo;
- intervalo entre parágrafos;
- margem de nota técnica;
- distância antes de grade/procedimento;
- margem de figura;
- ausência de overflow horizontal.

A página longa não deve ser encurtada artificialmente às custas de legibilidade. O objetivo é leitura sustentada, não densidade máxima de interface.
