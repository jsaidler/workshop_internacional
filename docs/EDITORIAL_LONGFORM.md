# Sistema editorial global para conteúdo longo

Este documento define o contrato visual e editorial reutilizável para páginas longas do CMS. Ele não pertence ao caderno de positivo direto; qualquer página compatível pode utilizar os mesmos componentes.

## Princípio

Uma publicação longa não deve ser montada como uma sequência indefinida do mesmo `statement-grid`. O sistema precisa criar ritmo entre abertura, índice, capítulos, unidades de leitura, dados, processos e mídia sem introduzir stylesheet exclusivo por página.

Novos componentes são permitidos quando resolvem uma necessidade real e são implementados como componentes globais, reutilizáveis e cobertos por regressão. Um seletor colocado no CSS global, mas cujo contrato só faz sentido para uma única página, continua sendo uma exceção de página e não é aceitável.

## Componentes globais

- `editorial-cover`: abertura de publicação longa, com título de grande escala e introdução curta;
- `editorial-index`: índice editorial usando a estrutura global `format`/`format-grid`;
- `editorial-chapter`: divisor de capítulo/aula, com contraste forte e texto introdutório curto;
- `editorial-unit`: unidade de leitura. Reduz a escala dos títulos em relação às páginas promocionais, amplia a largura útil do corpo, dá ritmo vertical, compacta grades de dados e adapta listas/processos para leitura longa.

Essas classes complementam, e não substituem, os componentes existentes `section`, `statement-grid`, `statement-copy`, `format-grid`, `format-card`, `process-list`, `process-item`, `technical-note`, `media-figure` e `cms-media-placeholder`.

## Regras de composição

Uma aula/capítulo deve ser composta como:

1. divisor de capítulo;
2. unidades editoriais independentes;
3. dentro de cada unidade, escolher a representação adequada ao conteúdo: prosa, chamada curta, grade de dados, processo, nota técnica ou mídia;
4. não usar título promocional gigante para introduzir parágrafos extensos;
5. não comprimir prosa longa em uma coluna estreita apenas para manter um grid de duas colunas;
6. preservar separação visual entre unidades por ritmo, bordas e superfícies do sistema global.

## Placeholders de mídia privada

Slots privados sem arquivo continuam invisíveis para alunos. Quando um administrador autenticado inspeciona uma página protegida, o renderer mostra o slot no ponto exato da composição usando `cms-media-placeholder`, incluindo a descrição editorial e a chave do slot. Isso permite avaliar a página antes do upload sem expor instruções internas aos alunos.

## Responsividade

Os componentes editoriais são globais e responsivos. Em telas menores, grids editoriais convergem para uma coluna, índices passam a uma coluna e placeholders reduzem a altura mínima. A adaptação pertence ao componente global, não à página que o utiliza.

## Caderno de positivo direto

O caderno usa esse sistema apenas como primeira aplicação. `caderno-capa` recebe `editorial-cover`; `caderno-indice`, `editorial-index`; os divisores das três aulas, `editorial-chapter`; e as unidades de conteúdo, `editorial-unit`. Nenhuma dessas classes contém o slug, o nome do workshop ou lógica exclusiva do material.
