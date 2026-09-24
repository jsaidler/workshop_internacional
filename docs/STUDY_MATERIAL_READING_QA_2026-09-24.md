# QA de leitura — material de estudo — 24/09/2026

## Evidência analisada

A segunda captura integral da página instalada confirmou que a hierarquia geral já estava mais organizada, mas a leitura continuava ruim em desktop.

## Diagnóstico

O defeito não era a extensão do conteúdo. O documento continuava usando uma lógica espacial inadequada para estudo prolongado:

- o texto principal permanecia estreito em relação ao viewport;
- a margem editorial lateral com rótulos criava um segundo eixo visual permanente;
- o leitor precisava reposicionar o olho horizontalmente entre rótulo, título e corpo;
- títulos recorrentes ainda carregavam escala próxima demais de uma página promocional;
- entrelinha e intervalo entre parágrafos, combinados, aumentavam a sensação de fragmentação;
- grande parte da largura disponível era usada para composição, não para melhorar efetivamente a leitura.

## Decisão

O componente global `study-material` passa a adotar um único eixo principal de leitura.

- corpo central em desktop: aproximadamente 820–900 px;
- rótulo da unidade acima do corpo, no mesmo alinhamento;
- `statement-grid` dentro de material de estudo deixa de ser composição lateral e passa a empilhar título e corpo;
- títulos recorrentes são reduzidos; divisores de aula continuam sendo o maior nível interno;
- corpo em aproximadamente 18 px / entrelinha 1,58 no desktop;
- distância entre parágrafos é reduzida para manter continuidade argumentativa;
- notas técnicas continuam distintas, porém maiores e mais legíveis;
- grades, listas comparativas e figuras podem usar uma largura maior que a coluna de leitura;
- tablet e celular preservam o mesmo eixo, apenas ajustando largura e escala.

## O que não muda

- nenhuma frase técnica da fonte é reescrita;
- não há CSS específico do slug do caderno;
- o componente continua global e reutilizável;
- não se reduz o corpo para encurtar artificialmente a página;
- não se remove conteúdo para reduzir scroll;
- os placeholders de infográfico continuam no ponto editorial já definido.

## Critérios objetivos

A regressão de navegador deve confirmar em desktop:

- corpo de leitura entre 840 e 885 px no fixture de 1440 px;
- rótulo, título/corpo e nota técnica no mesmo eixo horizontal;
- ausência de `position: sticky` no rótulo padrão da unidade;
- `statement-grid` em fluxo vertical;
- corpo de pelo menos 18 px;
- entrelinha entre aproximadamente 27 e 29,5 px;
- título recorrente abaixo de 45 px;
- cards comparativos permanecendo mais largos que o corpo quando houver espaço;
- ausência de overflow horizontal.

A mudança é de sistema global de estudo e não de uma página específica.
