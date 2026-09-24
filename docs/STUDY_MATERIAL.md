# Material de estudo — contrato editorial e de interface

Este documento define a regra canônica para materiais didáticos publicados no CMS.

## Natureza do objeto

Material de estudo não é landing page, página promocional nem sequência de chamadas visuais. A identidade gráfica continua sendo a do site, mas a composição prioriza leitura, consulta, retomada de conceitos, progressão didática e relação entre texto e imagem.

O sistema global `study-material` existe para qualquer página didática do CMS. Ele usa os mesmos tokens de cor, tipografia, espaçamento e mídia do site, mas organiza conteúdo em capa, índice, capítulos/aulas e unidades de leitura com hierarquia previsível, coluna de leitura controlada, notas técnicas, listas, dados e imagens em contexto.

Nenhum seletor desse sistema pode depender do slug, do nome do workshop ou de uma página específica.

## Contrato visual e de UX

As capturas integrais de 24/09/2026 mostraram três problemas sucessivos. Primeiro, o material ocupava uma coluna pequena demais dentro de uma tela larga. Depois, ao tentar aproveitar mais o canvas, foi criada uma margem editorial lateral permanente com rótulos e blocos em dois eixos. Por fim, ao corrigir o eixo de leitura, o ritmo vertical foi comprimido demais: unidades, subtítulos, notas, grades e figuras ficaram visualmente próximos demais, produzindo uma massa contínua.

A decisão canônica passa a ser: **material de estudo usa um eixo principal contínuo de leitura com respiro vertical hierárquico**. A largura adicional do desktop fica reservada para figuras, tabelas, grades comparativas e outros elementos que realmente se beneficiam dela; o espaço vertical é usado para marcar mudanças reais de nível sem transformar cada parágrafo em bloco autônomo.

### Canvas e medida de leitura

- O material usa um canvas editorial amplo para capa, índice, divisores de aula, tabelas, grades e figuras.
- O texto corrido usa uma coluna central estável de aproximadamente 820–900 px em desktop, dependendo da largura disponível.
- Rótulos de unidade aparecem acima do conteúdo, alinhados ao mesmo eixo de leitura. Não há margem editorial lateral sticky como padrão do componente.
- Um título estrutural curto pode anteceder o corpo, mas não deve retirar do fluxo uma frase argumentativa da fonte para transformá-la em manchete.
- Elementos largos podem ultrapassar a coluna de texto quando a comparação visual justificar isso, retornando depois ao mesmo eixo de leitura.
- Em tablet e celular, a composição permanece em fluxo único, sem depender de estruturas laterais de desktop.

### Hierarquia

- A capa identifica o documento sem assumir a escala dramática de uma hero comercial.
- O índice funciona como navegação real: cada aula é um alvo claro, clicável, com foco de teclado legível e área de interação confortável.
- O divisor de aula é o maior marco interno do documento e interrompe inequivocamente o capítulo anterior.
- Unidades recorrentes usam hierarquia mais contida que uma landing page. Títulos não devem dominar uma área maior que o conteúdo que introduzem.
- Subtítulos internos servem para orientar consulta e retomada; não para converter cada passagem em chamada visual.
- Títulos usam a família de display do site; corpo usa a família editorial configurada; fórmulas, valores e notas técnicas usam a família mono. O material não cria tipografia paralela.

### Ritmo e distinção semântica

- Texto corrido, nota técnica, grade de valores, procedimento e figura não podem parecer o mesmo tipo de caixa.
- Parágrafos relacionados permanecem visualmente próximos, mas não comprimidos. O intervalo entre parágrafos precisa permitir que o olho reconheça a continuidade sem fundir blocos consecutivos.
- A entrelinha deve favorecer leitura prolongada, sem inflar artificialmente a página. Em desktop, o alvo permanece aproximadamente 1,55–1,62 para o corpo.
- Nota técnica é uma interrupção de referência, não um card promocional: recebe tratamento contido, boa legibilidade e distância suficiente do texto anterior e seguinte.
- Grades de dados usam números tabulares e células compactas; a informação deve ser comparável sem produzir cards gigantes, mas a grade deve ter distância clara do argumento que a introduz.
- Procedimentos mantêm sequência e separação entre passos sem aumentar artificialmente a altura interna de cada passo; o conjunto, porém, precisa respirar em relação ao texto corrido.
- Figuras recebem largura maior que o corpo quando isso melhora a leitura da relação visual e usam margem vertical suficiente para serem percebidas como apoio editorial, não como caixa colada ao texto.
- A ausência de uma figura privada continua sem produzir placeholder para o aluno.

### Escala de respiro vertical

O componente usa três níveis de espaçamento vertical. Eles não são números absolutos para todas as telas, mas uma relação hierárquica que deve permanecer perceptível:

1. **continuidade de raciocínio** — parágrafos e itens relacionados usam o menor intervalo;
2. **mudança interna de assunto** — subtítulos, notas técnicas, fórmulas, grades, procedimentos e figuras usam um intervalo intermediário claramente maior;
3. **mudança de unidade ou aula** — unidades e divisores usam o maior intervalo, criando uma pausa perceptível antes do próximo conjunto conceitual.

No desktop, uma unidade de estudo deve ter aproximadamente 80–104 px de respiro vertical total por lado quando houver espaço. Componentes intermediários normalmente entram na faixa aproximada de 36–56 px de distância do conteúdo adjacente. Em telas menores esses valores diminuem, mas a proporção entre os três níveis deve ser preservada.

Não reduzir o respiro de unidade, nota, figura ou grade apenas para encurtar a página. Uma publicação longa pode ser longa; o critério é se a leitura mantém orientação e ritmo.

### Legibilidade

- O corpo do material deve permanecer em tamanho confortável para leitura prolongada; desktop não pode reduzir o texto para compensar a extensão da página.
- A largura maior do corpo deve reduzir a sensação de coluna de celular no desktop sem produzir linhas excessivamente longas.
- O corpo de referência é de aproximadamente 18 px em desktop, com redução moderada apenas em telas menores.
- Parágrafos curtos da fonte continuam curtos. O sistema não os cola artificialmente nem cria espaços tão grandes que cada frase pareça uma chamada publicitária.
- Em modo escuro, contraste, linhas e superfícies usam os tokens do sistema. Não introduzir cinzas ou cores fixas específicos do caderno.
- Âncoras de aula/unidade consideram o header fixo ao definir a posição de rolagem.

### Responsividade e acessibilidade

- Desktop, tablet e celular fazem parte do mesmo componente. Nenhuma melhoria desktop pode depender de largura fixa que gere overflow em tela menor.
- Em tablet e celular, todas as unidades permanecem no mesmo eixo de leitura.
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
- corpo de leitura central com largura efetiva adequada em desktop;
- ausência de coluna lateral sticky no fluxo padrão de estudo;
- títulos recorrentes em escala inferior aos divisores de aula;
- entrelinha adequada para leitura contínua;
- três níveis de respiro vertical perceptíveis entre parágrafo, componente intermediário e unidade;
- unidade com respiro vertical suficiente em desktop e redução proporcional em telas menores;
- colapso correto de grades em tablet/celular;
- ausência de overflow horizontal;
- índice navegável e visualmente distinto;
- permanência dos componentes dentro da paleta e tipografia globais do site.
