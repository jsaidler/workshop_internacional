# Material de estudo — contrato editorial

Este documento define a regra canônica para materiais didáticos publicados no CMS.

## Natureza do objeto

Material de estudo não é landing page, página promocional nem sequência de chamadas visuais. A identidade gráfica continua sendo a do site, mas a composição prioriza leitura, consulta, retomada de conceitos, progressão didática e relação entre texto e imagem.

O sistema global `study-material` existe para qualquer página didática do CMS. Ele usa os mesmos tokens de cor, tipografia, espaçamento e mídia do site, mas organiza conteúdo em capa compacta, índice, capítulos/aulas e unidades de leitura com coluna estável, títulos previsíveis, notas técnicas, listas, dados e imagens em contexto.

Nenhum seletor desse sistema pode depender do slug, do nome do workshop ou de uma página específica.

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
- ausência de CSS, SVG ou estilo inline exclusivo da página.
