# Contrato de responsividade do editor

## Princípio

Uma configuração de desktop não pode desligar silenciosamente a responsividade de um componente.

O editor trabalha com três contextos de layout:

- **Desktop**: configuração-base da seção.
- **Tablet**: comportamento próprio entre 721 e 1024 px.
- **Celular**: comportamento próprio até 720 px.

Nos controles de tablet e celular, **Automático** significa adaptação para aquele tamanho de tela. Não significa simplesmente copiar o valor de desktop.

## Regra automática

Para grades de conteúdo administradas pelo editor, tablet e celular usam uma coluna quando não há uma escolha explícita para aquele dispositivo. Isso continua verdadeiro mesmo quando o desktop usa:

- uma proporção 30/70, 40/60, 50/50, 60/40 ou 70/30;
- duas, três ou quatro colunas;
- um componente legado com sua própria grade interna.

Assim, escolher uma proporção de desktop nunca transforma essa escolha em uma exceção oculta que impede o breakpoint de funcionar.

## Exceções explícitas por dispositivo

Quando a seção comporta uma grade, o painel **Responsividade** pode definir separadamente para tablet e celular:

- quantidade de colunas;
- proporção das duas colunas, quando o dispositivo foi explicitamente configurado com duas colunas;
- espaço entre colunas;
- alinhamento vertical;
- ordem dos itens;
- alinhamento do texto;
- espaçamento vertical;
- visibilidade.

Os controles de proporção só aparecem em componentes nos quais uma proporção entre duas colunas tem significado. Controles que não têm efeito sobre determinado componente não devem ser exibidos como se fossem funcionais.

## Elementos individuais

Textos e imagens podem ter largura, alinhamento e visibilidade específicos para tablet e celular. O valor **Automático** usa o comportamento natural do elemento naquele dispositivo.

## Compatibilidade

`data-layout-*` continua representando o layout-base/desktop para não invalidar páginas existentes. As regras por dispositivo usam `data-cms-tablet-*` e `data-cms-mobile-*`.

O antigo `data-layout-mobile="reverse"` é aceito como compatibilidade. Ao editar a ordem do celular pelo painel responsivo, o editor passa a usar `data-cms-mobile-order` e elimina o override legado daquele elemento.

## Invariantes

1. Uma escolha de desktop não pode aumentar a especificidade de CSS a ponto de bloquear o comportamento automático de tablet ou celular.
2. Um override de tablet não pode vazar para celular ou desktop.
3. Um override de celular não pode vazar para tablet ou desktop.
4. Controles de layout só devem ser apresentados quando o componente oferece aquela capacidade.
5. As regras responsivas do sistema não usam `!important`; o CSS adicional do usuário continua podendo sobrescrever o sistema.
6. O comportamento é testado em Chromium para desktop, tablet e celular, incluindo o caso que originou esta correção: proporção de desktop explicitamente selecionada.
