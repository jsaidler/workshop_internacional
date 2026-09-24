# Material de estudo — contrato editorial e de interface

Este documento define a regra canônica para materiais didáticos publicados no CMS.

## Natureza do objeto

Material de estudo não é landing page, página promocional nem sequência de chamadas visuais. A identidade gráfica continua sendo a do site, mas a composição prioriza leitura, consulta, retomada de conceitos, progressão didática e relação entre texto e imagem.

O sistema global `study-material` existe para qualquer página didática do CMS. Ele usa os mesmos tokens de cor, tipografia, espaçamento e mídia do site, mas organiza conteúdo em capa, índice, capítulos/aulas e unidades de leitura com hierarquia previsível, coluna de leitura controlada, notas técnicas, listas, dados e imagens em contexto.

Nenhum seletor desse sistema pode depender do slug, do nome do workshop ou de uma página específica.

## Contrato visual e de UX

As capturas integrais de 24/09/2026 mostraram cinco problemas sucessivos. Primeiro, o material ocupava uma coluna pequena demais dentro de uma tela larga. Depois, ao tentar aproveitar mais o canvas, foi criada uma margem editorial lateral permanente com rótulos e blocos em dois eixos. Em seguida, ao corrigir o eixo de leitura, o ritmo vertical foi comprimido demais: unidades, subtítulos, notas, grades e figuras ficaram visualmente próximos demais, produzindo uma massa contínua. A validação seguinte mostrou o problema inverso em uma relação específica: duas unidades consecutivas estavam somando o respiro macro completo dos dois lados do mesmo divisor, criando um vazio de mais de 200 px em desktop. Depois de corrigida essa fronteira macro, a inspeção ampliada mostrou que o nível intermediário permanecia comprimido: títulos e subtítulos ainda encostavam no conteúdo, e grades, procedimentos e outras caixas tinham entrada razoável mas pouca ou nenhuma pausa quando o texto retomava depois delas.

A decisão canônica passa a ser: **material de estudo usa um eixo principal contínuo de leitura com respiro vertical hierárquico, relacional e bilateral nos componentes intermediários**. A largura adicional do desktop fica reservada para figuras, tabelas, grades comparativas e outros elementos que realmente se beneficiam dela; o espaço vertical é usado para marcar mudanças reais de nível sem transformar cada parágrafo em bloco autônomo. Uma única transição conceitual recebe um único intervalo macro; componentes internos, por outro lado, precisam ter entrada e saída perceptíveis quando existem conteúdos dos dois lados.

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
- Rótulo de unidade e título estrutural precisam separar claramente identificação, assunto e corpo. O rótulo não pode parecer colado à primeira frase ou ao primeiro título.
- `h2` e `h3` internos precisam ter respiro assimétrico: maior distância antes quando abrem uma mudança de assunto e distância menor, mas ainda inequívoca, depois, antes do texto que introduzem.
- Nota técnica é uma interrupção de referência, não um card promocional: recebe tratamento contido, boa legibilidade e distância suficiente do texto anterior e seguinte.
- Grades de dados usam números tabulares e células compactas; a informação deve ser comparável sem produzir cards gigantes, mas a grade deve ter distância clara tanto do argumento que a introduz quanto do texto que retoma depois dela.
- Procedimentos mantêm sequência e separação entre passos sem aumentar artificialmente a altura interna de cada passo; o conjunto, porém, precisa respirar antes e depois quando houver conteúdo adjacente.
- Figuras recebem largura maior que o corpo quando isso melhora a leitura da relação visual e usam margem vertical suficiente para serem percebidas como apoio editorial, não como caixa colada ao texto.
- O espaçamento pertence à relação entre elementos, não a cada componente isoladamente. Margens e paddings adjacentes não devem somar duas pausas completas para representar uma única mudança de assunto.
- A regra bilateral do nível meso não deve contaminar o nível macro: quando uma nota, grade, procedimento, figura, definição ou separador encerra a unidade, sua margem inferior intermediária deve ceder ao padding estrutural da unidade em vez de somar uma segunda pausa.
- A ausência de uma figura privada continua sem produzir placeholder para o aluno.

### Escala de respiro vertical

O componente usa três níveis de espaçamento vertical. Eles não são números absolutos para todas as telas, mas uma relação hierárquica que deve permanecer perceptível:

1. **continuidade de raciocínio** — parágrafos e itens relacionados usam o menor intervalo;
2. **mudança interna de assunto** — subtítulos, notas técnicas, fórmulas, grades, procedimentos e figuras usam um intervalo intermediário claramente maior e, quando há conteúdo dos dois lados, perceptível na entrada e na saída;
3. **mudança de unidade ou aula** — unidades e divisores usam o maior intervalo, criando uma pausa perceptível antes do próximo conjunto conceitual.

Em desktop, a fronteira entre duas unidades consecutivas deve produzir aproximadamente 108–136 px entre o último conteúdo da unidade anterior e o primeiro rótulo/conteúdo da unidade seguinte, incluindo o divisor. Para chegar a essa faixa sem duplicar a pausa, cada borda de unidade trabalha aproximadamente entre 50 e 72 px, com ligeira ênfase na abertura da unidade seguinte.

No nível intermediário, os alvos desktop são aproximadamente:

- rótulo de unidade → primeiro conteúdo: 28–32 px;
- `h2` estrutural → corpo: 34–40 px;
- mudança interna com `h3`: cerca de 50–58 px antes e 20–24 px depois;
- nota técnica: aproximadamente 46–50 px dos dois lados;
- grade comparativa e procedimento: aproximadamente 50–54 px dos dois lados;
- figura: aproximadamente 58–62 px dos dois lados;
- título do índice → grade de aulas: aproximadamente 34–38 px.

Parágrafos relacionados permanecem próximos de 0,9–1 em. Em tablet e celular os valores diminuem proporcionalmente, mas a ordenação entre micro, meso e macro deve continuar óbvia.

O divisor de unidade não deve ficar isolado no centro de dois grandes vazios. Tampouco uma caixa pode terminar e entregar imediatamente o próximo parágrafo sem uma pausa intermediária. O sistema precisa sustentar simultaneamente esses dois limites.

Não reduzir o respiro de nota, figura ou grade apenas para encurtar a página. Uma publicação longa pode ser longa; o critério é se a leitura mantém orientação e ritmo. Da mesma forma, não aumentar indiscriminadamente o padding de cada unidade: respiro editorial é calibrado pela distância renderizada entre conteúdos adjacentes.

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
- fronteiras entre unidades com limites inferior e superior de respiro, para impedir tanto compressão quanto soma excessiva de paddings;
- validação da distância renderizada entre o último conteúdo de uma unidade, o divisor e o primeiro conteúdo da unidade seguinte — não apenas valores isolados de CSS;
- validação renderizada do rótulo para o primeiro conteúdo e do título/subtítulo para o corpo que ele introduz;
- validação de entrada e saída para nota técnica, grade, procedimento e figura;
- ausência de margem meso inferior redundante quando um desses componentes encerra a unidade;
- redução proporcional do ritmo macro e meso em telas menores;
- colapso correto de grades em tablet/celular;
- ausência de overflow horizontal;
- índice navegável e visualmente distinto;
- permanência dos componentes dentro da paleta e tipografia globais do site.
