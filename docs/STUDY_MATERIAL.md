# Material de estudo — contrato editorial e de interface

Este documento define a regra canônica para materiais didáticos publicados no CMS.

## Natureza do objeto

Material de estudo não é landing page, página promocional nem uma sucessão de cards. É um documento de leitura, consulta e retomada. A identidade gráfica continua sendo a do site, mas texto, hierarquia, relações técnicas e imagens têm prioridade sobre superfícies de interface.

O sistema global `study-material` vale para qualquer página didática do CMS. Nenhum seletor desse sistema pode depender do slug, do nome do workshop ou de uma seção específica.

A decisão canônica é: **o material usa um eixo principal contínuo de leitura; componentes visuais só ganham superfície própria quando a espacialização acrescenta significado**.

## Autoridade da fonte

Para `caderno-positivo-direto`, a fonte editorial são os e-mails efetivamente enviados aos alunos:

- `Receitas, materiais e algumas referências para trabalhar com filme de raio-X`;
- `Segundo Encontro: Processos químicos para positivos`.

O corpo técnico da fonte é imutável. A edição pode:

1. retirar saudações, datas, pedidos administrativos, chamadas para rede social e recados circunstanciais;
2. mover blocos para outra posição da sequência didática;
3. criar somente paratexto estrutural mínimo, como `Aula 01`, índice e identificação de um infográfico;
4. converter enumerações em lista, tabela, comparação ou ficha sem reescrever seus itens;
5. reduzir ou aumentar o peso visual de um bloco sem alterar suas palavras.

A edição não pode:

- parafrasear ou resumir;
- fundir parágrafos em nova redação;
- completar uma explicação com conhecimento externo;
- inventar rótulos para explicar dados que a fonte não explica;
- transformar uma frase do corpo em slogan e removê-la de sua posição argumentativa;
- corrigir silenciosamente divergências entre os dois registros;
- eliminar um dado técnico apenas porque sua apresentação atual é visualmente ruim.

Quando a estrutura muda, a transição é resolvida por composição e tipografia, não por texto novo.

## Canvas e medida de leitura

- O canvas pode ser amplo para capa, índice, divisores de aula, imagens e referências que realmente precisem de largura.
- O texto corrido usa aproximadamente **840 px** de medida em desktop.
- O corpo de referência permanece em aproximadamente **18 px**, com entrelinha próxima de **1,64**.
- Rótulos, títulos e corpo pertencem ao mesmo eixo central.
- Elementos largos podem ultrapassar a coluna apenas quando a comparação visual justificar isso.
- Em tablet e celular, o documento continua em um único fluxo e não depende de estruturas laterais.

A medida de leitura não deve voltar à coluna estreita das primeiras versões, mas também não deve exigir varredura ocular excessiva em parágrafos técnicos longos.

## Hierarquia

- A capa identifica o documento sem escala de hero comercial.
- O índice é navegação real e pode conservar cards clicáveis, porque a área inteira tem função de interação.
- O divisor de aula é o maior marco interno do documento e continua sendo a principal interrupção macro.
- Uma `study-unit` é uma unidade de leitura, não um card. Unidades consecutivas não precisam de moldura nem linha horizontal para existir.
- `section-label` identifica assunto, mas não ganha sublinhado de largura total por padrão.
- `h2` estrutura a unidade; `h3` abre mudança interna de assunto. Ambos devem se ligar visualmente ao texto que introduzem.
- Fórmulas, valores, citações e instruções podem mudar tipografia, mas não recebem automaticamente um painel sombreado.

## Regra de merecimento de um quadro

Um bloco só recebe superfície ou matriz própria quando pelo menos uma destas condições for verdadeira:

1. dois ou mais valores precisam ser comparados pela posição;
2. a informação será consultada como referência independente;
3. existe uma sequência operacional que se beneficia de numeração;
4. imagem ou tabela precisa de largura própria;
5. a distinção visual reduz risco real de erro de leitura ou execução.

Uma frase curta, fórmula, citação ou resumo não merece por si só um retângulo grande. A solução padrão é tipográfica: espaço, keyline, mono, mudança moderada de corpo ou combinação desses recursos.

## Papéis semânticos das referências

O componente global admite tratamentos diferentes sem reescrever a fonte:

- `study-data-strip` — relações quantitativas em que comparar posições ajuda a leitura;
- `study-compact-values` — valores literais de apoio que precisam permanecer disponíveis, mas não justificam cards dominantes;
- `study-comparison` — duas condições ou conjuntos diretamente comparáveis;
- `study-resource-list` — catálogo de consulta em linhas;
- `study-equation` — reação ou equação curta;
- `study-quote` — citação;
- `study-summary` — síntese conceitual ou operacional;
- `study-instruction` — instrução curta.

No caderno atual:

- índice: continua navegacional;
- EV → fração de luz: `study-data-strip`;
- reciprocidade: `study-data-strip`;
- volumes 15/11/6/5 ml com “água até 550 ml”: `study-compact-values`;
- EI 200 × EI 400: `study-comparison`;
- onde encontrar materiais: `study-resource-list`.

O bloco de 15/11/6/5 ml é deliberadamente rebaixado. A fonte preserva os quatro valores, mas não fornece naquele ponto um rótulo adicional que justifique quatro cards de alta hierarquia. Os valores permanecem literais; a interface não inventa significado para preencher a lacuna.

## Ritmo vertical

O componente usa três níveis:

1. **micro — continuidade**: parágrafos e itens relacionados;
2. **meso — mudança interna**: subtítulos, notas, referências, procedimentos e figuras;
3. **macro — mudança de unidade/aula**: unidades consecutivas e divisores de aula.

Há uma regra adicional que tem precedência sobre números isolados: **um título pertence visualmente ao conteúdo que ele introduz**. A distância depois do título deve ser menor que a pausa necessária para anunciar uma nova mudança de assunto. Rótulo, título e primeiro parágrafo formam um único grupo editorial.

Alvos desktop após a correção de ritmo de títulos de 24/09/2026:

- rótulo de capa → `h1`: aproximadamente 18 px;
- `h1` → subtítulo de capa: aproximadamente 20 px;
- rótulo do índice → título: aproximadamente 10 px;
- rótulo do divisor de aula → título: aproximadamente 10 px;
- `section-label` de unidade → `h2`: aproximadamente 16 px;
- `h2` → corpo que ele introduz: aproximadamente 22 px;
- corpo anterior → `h3`: aproximadamente 36 px;
- `h3` → corpo que ele introduz: aproximadamente 10 px;
- nota curta: aproximadamente 40 px;
- grade ou procedimento: aproximadamente 42 px;
- figura: aproximadamente 50 px;
- fronteira entre unidades consecutivas: aproximadamente 92–118 px, dependendo do viewport dentro dos `clamp()`.

Esses intervalos são medidos pela geometria renderizada, não inferidos a partir de uma soma de `margin`. Títulos não podem depender de colapso de margens entre `h2`/`h3`, wrappers e elementos irmãos. O bloco de título e o bloco de texto seguinte têm responsabilidades explícitas de espaçamento.

O objetivo não é maximizar espaço. É tornar perceptível a hierarquia sem criar uma página inflada. O nível meso nunca deve ser duplicado no nível macro quando o componente é o último da unidade.

## Notas técnicas

`technical-note` é uma interrupção de referência, não um painel promocional. O tratamento padrão usa:

- fundo transparente;
- keyline lateral;
- sem bordas horizontais;
- altura determinada pelo conteúdo;
- mono quando a função for fórmula, valor ou instrução técnica;
- corpo editorial quando a função for citação ou resumo.

A visualização precisa distinguir uma nota do corpo sem transformar uma frase em uma caixa vazia de grande área.

## Listas, procedimentos e materiais

- Procedimentos conservam numeração e linhas discretas entre passos.
- Ingredientes e texto explicativo permanecem no fluxo quando não existe necessidade de comparação matricial.
- Catálogos como “Onde encontrar os materiais” usam linhas material → fornecedor/observação, não mosaico de cards.
- Grades quantitativas usam números tabulares e células compactas.

## Figuras e mídia privada

Infográficos reforçam relações já presentes no texto. Não criam teoria, não substituem a fonte e não acrescentam etapas genéricas.

Quando não houver arquivo privado, o slot continua invisível para o aluno. Placeholder é recurso administrativo, não parte do documento publicado.

## Filme de raio-X e Fuji Super HR-U

O assunto é filme de raio-X. O Fuji Super HR-U é o filme utilizado na pesquisa e nos exemplos das fontes; ele não deve virar título conceitual do assunto nem autorizar universalização para todos os filmes radiográficos.

Afirmações específicas sobre o Fuji permanecem específicas.

## Responsividade e acessibilidade

- Desktop, tablet e celular pertencem ao mesmo componente.
- Nenhuma melhoria desktop pode criar overflow em tela menor.
- O vínculo título → conteúdo permanece em todos os breakpoints; apenas os intervalos diminuem de modo controlado.
- `study-data-strip` vira coluna quando necessário.
- `study-compact-values` pode usar duas colunas em celular.
- `study-comparison` vira uma coluna em celular.
- `study-resource-list` vira sequência de título + descrição em uma coluna.
- O índice mantém navegação por teclado e `:focus-visible`.
- `prefers-reduced-motion: reduce` elimina transições decorativas.

## Critério de regressão

Os testes devem verificar simultaneamente:

- fidelidade literal às duas fontes;
- ausência de texto editorial inventado já identificado;
- presença dos slots privados e seu contrato de visibilidade;
- segmentação por aula;
- ausência de CSS/estilo inline exclusivo da página;
- uso das classes globais `study-material`;
- medida central próxima de 840 px em desktop;
- corpo e entrelinha adequados para longform;
- ausência de borda superior em cada unidade e de sublinhado integral em rótulos;
- três níveis de ritmo vertical com limites inferiores e superiores;
- geometria renderizada de rótulo → título e título → conteúdo na capa, índice, divisor de aula e unidades;
- geometria renderizada de corpo → `h3` e `h3` → corpo em desktop, tablet e celular;
- notas com fundo transparente, sem bordas horizontais e com keyline lateral;
- papéis semânticos distintos para dados, valores compactos, comparação e catálogo;
- colapso correto desses papéis em tablet/celular;
- ausência de overflow horizontal;
- índice navegável por teclado.

A validação deve medir layout renderizado em Chromium. Testar apenas presença de seletores ou apenas valores mínimos não é suficiente.

## Histórico de QA

O histórico detalhado das correções de ritmo continua em `STUDY_MATERIAL_VERTICAL_RHYTHM_QA_2026-09-24.md`. O escrutínio que consolidou a passagem de “interface de caixas” para longform editorial está em `STUDY_MATERIAL_EDITORIAL_SCRUTINY_2026-09-24.md`.

## Projeção de auditoria do material autenticado — 15/10/2026 (PR #243 draft)

Material pertence ao renderer **público CMS** `cms_render_public_page`, não ao `aluno/material.php` nem a uma view editorial separada. A rota `index.php` faz controle de acesso a página e matrícula; `cms_access_filter_html` remove no servidor as seções não liberadas; `student_material_render_notebook` injeta as anotações sem editar o documento publicado. A fixture de leitura `student-material-cms-operational.php` usa exatamente esses serviços com SQLite local e dados fictícios, não o conteúdo real do CMS. O contrato de navegação autenticada corresponde a `cms-student-material`, `.cms-student-reading`, contexto do curso e cinco destinos globais; `student-shell` não pode ser exigido nesta rota. Regressões de acesso, seção bloqueada, paginação, quadro de anotações e foco do skip-link acompanham a implementação. Capturas 320/390/1440 e rolagem topo/meio/fim precisam de inspeção manual. Não alterar a fonte editorial nem suas liberação reais para satisfazer um teste de renderização.

### Sanitização única e anotações geradas pelo servidor — correção 15/10/2026

`index.php` recebe o HTML da página via `cms_page_doc($page,true)`, que já executa `cms_page_document` e `cms_sanitize_html`. Só **depois** dessa fronteira confiável são projetadas seções autorizadas, URLs de mídia privada e anotações, incluindo JSON codificado via `JSON_HEX_*` por `student_material_render_notebook`. Antes da correção, `cms_render_public_page` executava nova sanitização e removia o `<script type="application/json" data-student-annotation-data>` interno, privando o cliente dos dados de associação das notas. Agora a rota autenticada passa `preparedDocument=true` ao renderer; chamadas diretas, editoriais ou de pré-visualização conservam o padrão `false` e continuam sanitizando o corpo HTML. Não se permite passar HTML de CMS não sanitizado como documento preparado. A fixture usa a mesma ordem e os testes PHP/Playwright verificam a sobrevivência do JSON autorizado; não dispensa homologação de rede do fluxo de salvar/reassociar notas.


## Retomada da leitura no histórico e edição de notas (16/10/2026)

**Lacuna do leitor CMS autenticado:** em telas de aluno, `.cms-student-reading` é o contêiner que realmente rola. O documento e as páginas Material continuam usando links HTML canônicos, mas o navegador não garante restauro de `scrollTop` de uma região interna em todas as modalidades de Back/Forward; restaurar apenas `window.scrollY` seria ineficaz. Já existe preservação do ponto de leitura ao salvar uma seleção, sem recarregar.

**Regra de navegação:** o runtime de anotações registra a posição da região de leitura na **própria entrada do histórico**, via `history.replaceState`, preservando outros consumidores de `history.state`. A chave é a rota **com query string**, para manter página, idioma e turma em seu próprio contexto. Ação de rolar não chama `pushState`, não muda a URL e não cria entrada. Ao retornar por Back/Forward com documento novo, o leitor repõe a posição no contêiner após o layout; se o navegador usou BFCache, conserva o estado já restaurado pelo navegador. Um hash explícito tem prioridade e impede restauração automática contrária ao destino solicitado. A posição é limitada à extensão atual do conteúdo para evitar restauro além do fim após alterações editoriais.

**Notas:** o painel de anotações mantém a rolagem própria e salvar uma nota existente via AJAX não navega nem desloca o leitor. A operação não modifica CMS, permissões, acesso, conteúdo editorial ou registros de notas; os fluxos de edição e o feedback permanecem no endpoint autorizado.

**Gate:** fixture PHP compartilhada com CMS e longa leitura sintética, regressão da posição e de `history.length` com Back/Forward, recarga e preservação de `cohort`, painel e gravação AJAX simulada em 320/390/1440, capturas antes/depois e de leitura após salvar. Também manter testes legados de seleção, âncoras e rascunhos. Testes sintéticos não substituem uso autenticado na HostGator.
