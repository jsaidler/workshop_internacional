# Material de estudo — contrato editorial e de interface

Este documento define a regra canônica para materiais didáticos publicados no CMS.

## Natureza do objeto

Material de estudo não é landing page, página promocional nem sequência de chamadas visuais. A identidade gráfica continua sendo a do site, mas a composição prioriza leitura, consulta, retomada de conceitos, progressão didática e relação entre texto e imagem.

O sistema global `study-material` existe para qualquer página didática do CMS. Ele usa os mesmos tokens de cor, tipografia, espaçamento e mídia do site, mas organiza conteúdo em capa, índice, capítulos/aulas e unidades de leitura com hierarquia previsível, coluna de leitura controlada, notas técnicas, listas, dados e imagens em contexto.

Nenhum seletor desse sistema pode depender do slug, do nome do workshop ou de uma página específica.

## Contrato visual e de UX

A captura de 24/09/2026 tornou explícito um problema que não pode regressar: em desktop, o material ocupava uma coluna pequena demais dentro de uma tela larga e transformava uma publicação extensa em uma sequência visualmente monótona de blocos semelhantes. A correção não consiste em aumentar tudo indiscriminadamente; consiste em separar **canvas editorial**, **medida de leitura** e **componentes de referência**.

### Canvas e medida de leitura

- O material usa um canvas editorial mais amplo que a coluna de texto corrido. Capa, índice, divisores de aula, tabelas/cartões e figuras podem ocupar essa largura maior.
- Texto corrido mantém medida de leitura própria, limitada. Em desktop, não deve ficar espremido no centro da tela nem se estender a linhas excessivamente longas.
- Unidades de estudo podem usar uma margem editorial para rótulos e uma segunda coluna para conteúdo complementar, mas o corpo principal continua sendo o eixo de leitura.
- Em tablet e celular, a composição volta a um fluxo único sem depender de uma configuração de desktop para funcionar.

### Hierarquia

- A capa deve ter presença suficiente para identificar o documento sem assumir a escala dramática de uma hero comercial.
- O índice deve funcionar como navegação real: cada aula é um alvo claro, clicável, com foco de teclado legível e área de interação confortável.
- O divisor de aula é o marco de maior hierarquia interna do documento. Deve interromper inequivocamente o fluxo anterior sem parecer uma nova landing page.
- A unidade editorial é o nível recorrente de leitura. Seu título, rótulo lateral, texto, dados, notas e imagens precisam formar um conjunto reconhecível antes de começar a unidade seguinte.
- Títulos usam a família de display do site; corpo usa a família editorial configurada; fórmulas, valores e notas técnicas usam a família mono. O material não cria tipografia paralela.

### Ritmo e distinção semântica

- Texto corrido, nota técnica, grade de valores, procedimento e figura não podem parecer o mesmo tipo de caixa.
- Nota técnica é uma interrupção de referência, não um card promocional: recebe tratamento contido, boa legibilidade e distinção por linha/contraste.
- Grades de dados usam números tabulares e células compactas; a informação deve ser comparável sem produzir cards gigantes.
- Procedimentos mantêm sequência e separação entre passos sem aumentar artificialmente a altura da página.
- Figuras recebem largura maior que o corpo quando isso melhora leitura da relação visual, mas permanecem no fluxo da unidade a que pertencem.
- A ausência de uma figura privada continua sem produzir placeholder para o aluno.

### Legibilidade

- O corpo do material deve permanecer em tamanho confortável para leitura prolongada; desktop não pode reduzir o texto para compensar a extensão da página.
- Entrelinha e comprimento de linha têm precedência sobre a tentativa de “caber mais conteúdo”.
- Parágrafos curtos da fonte continuam curtos. O sistema não os cola artificialmente nem cria espaços tão grandes que cada frase pareça uma chamada publicitária.
- Em modo escuro, contraste, linhas e superfícies usam os tokens do sistema. Não introduzir cinzas ou cores fixas específicos do caderno.
- Âncoras de aula/unidade consideram o header fixo ao definir a posição de rolagem.

### Responsividade e acessibilidade

- Desktop, tablet e celular fazem parte do mesmo componente. Nenhuma melhoria desktop pode depender de largura fixa que gere overflow em tela menor.
- Até o breakpoint de tablet, margens editoriais e grids complexos colapsam para fluxo único.
- Grades de referência tornam-se uma coluna quando necessário para preservar leitura.
- Navegação do índice precisa funcionar por teclado e conservar o `:focus-visible` global.
- Movimentos decorativos são removidos quando `prefers-reduced-motion: reduce` estiver ativo.
- O CI valida medidas reais em Chromium, não apenas a presença textual dos seletores CSS.

## Autoridade da fonte

Para o material `caderno-positivo-direto`, a fonte editorial são os e-mails efetivamente enviados aos alunos:

- `Receitas, materiais e algumas referências para trabalhar com filme de raio-X`;
- `Segundo Encontro: Processos químicos para positivos`.

O corpo técnico da fonte é imutável. A edição pode:

1. retirar saudações, datas, pedidos administrativos, chamadas para rede social e recados circunstanciais;
2. mover blocos de texto para outra posição da sequência didática;
3. criar somente paratexto estrutural mínimo, como `Aula 01`, índice e identificação de um infográfico;
4. converter uma enumeração em lista, tabela ou ficha sem reescrever seus itens.

A edição não pode:

- parafrasear;
- resumir;
- fundir dois parágrafos numa nova redação;
- completar uma explicação com conhecimento externo;
- transformar uma frase do corpo em slogan/manchete e retirá-la da posição argumentativa original;
- corrigir silenciosamente divergências entre os dois registros.

Quando a ordem muda, a transição deve ser resolvida pela estrutura gráfica, não por frases novas.

## Critério editorial de qualidade

O escrutínio editorial deste material acontece sem reescrever a pesquisa. Qualidade editorial, neste caso, significa tornar explícita a estrutura que já existe na fonte.

- Uma ideia principal não deve competir visualmente com um exemplo, uma fórmula ou uma observação operacional.
- Relações comparáveis podem ser apresentadas em grade ou ficha desde que as palavras e valores continuem sendo os da fonte.
- A sucessão de parágrafos pode ser agrupada visualmente, mas a ordem argumentativa de cada bloco precisa permanecer reconhecível.
- Frases de transição que pertencem à fonte permanecem no corpo e não devem ser promovidas a slogan para “melhorar” a composição.
- Aulas e unidades são a navegação editorial; o layout não deve inventar capítulos conceituais novos para preencher espaço ou criar ritmo.
- A melhoria visual nunca autoriza abreviar valores, converter formulações para uma redação mais elegante nem eliminar redundâncias que fazem parte do registro técnico.

## Filme de raio-X e Fuji Super HR-U

O assunto é filme de raio-X. O Fuji Super HR-U é o filme utilizado na pesquisa e nos exemplos registrados nas fontes; ele não deve virar o título conceitual do assunto nem autorizar universalização para todos os filmes radiográficos.

Quando uma afirmação da fonte é formulada especificamente a partir do Fuji, ela permanece assim. A organização editorial enquadra o Fuji como exemplo/material utilizado, sem alterar a frase original para transformá-la em regra geral.

## Infográficos

Infográficos são ilustrações editoriais que reforçam relações já presentes no texto. Não criam um processo genérico, não substituem o texto e não acrescentam teoria.

Cada imagem deve nascer do trecho ao qual está associada. Enquanto não houver arquivo, o slot permanece invisível para alunos e aparece somente para administrador, no contexto da página, com sua chave e descrição.

## Critério de regressão

Os testes do material devem verificar simultaneamente:

- presença literal de trechos distintivos das duas fontes;
- ausência de frases editoriais inventadas já identificadas;
- presença dos slots privados;
- segmentação por aula;
- uso do sistema global `study-material`;
- ausência de CSS, SVG ou estilo inline exclusivo da página;
- medida de leitura e tamanho de corpo adequados em desktop;
- colapso correto do grid editorial em tablet/celular;
- ausência de overflow horizontal;
- índice navegável e visualmente distinto;
- permanência dos componentes dentro da paleta e tipografia globais do site.
