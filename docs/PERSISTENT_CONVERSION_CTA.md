# CTA persistente de conversão

## Objetivo

A página pública do workshop mantém um CTA fixo no rodapé durante a parte intermediária da leitura. A função é remover o custo de procurar novamente a ação de inscrição depois que o visitante já avançou além do CTA principal do hero.

O componente não substitui os CTAs editoriais da página. Ele funciona como continuidade entre o CTA inicial e o CTA final.

## Fonte de verdade

O rodapé persistente não mantém uma segunda configuração de preço ou de destino.

- O destino é obtido do CTA principal do hero.
- O componente só é criado quando existe outro CTA de botão no conteúdo apontando para o mesmo destino. Esse último CTA funciona como limite final da faixa persistente.
- O preço é lido do contexto editorial do CTA final; se não houver valor monetário ali, o sistema procura um valor monetário nos fatos do hero.
- Se não houver destino consistente ou preço reconhecível, o componente não é criado.

Essa regra evita divergências como preço atualizado na página e valor antigo no rodapé fixo.

## Comportamento

- No início da página, enquanto o CTA principal ainda está acessível, o rodapé permanece oculto.
- Depois que o CTA principal passa para cima da viewport, o rodapé entra em cena.
- Antes que o CTA final seja encoberto pelo componente fixo, o rodapé desaparece e devolve a função de conversão ao bloco editorial final.
- Depois do CTA final ele permanece oculto.
- O componente não é criado em previews do editor.
- O comportamento é o mesmo em desktop, tablet e celular; o layout interno se adapta ao espaço disponível sem depender de uma opção de desktop.

## Conteúdo

PT-BR:

- contexto: `Workshop online e ao vivo · 3 encontros`;
- ação: `Fazer inscrição`.

EN:

- contexto: `Live online workshop · 3 sessions`;
- ação: `Join the interest list`.

O valor não é hardcoded no componente: vem da própria página.

## Responsividade e acessibilidade

Em telas estreitas, o contexto secundário é retirado e permanecem preço + ação. O componente oculto usa `inert` e `aria-hidden`, portanto não cria um elemento de teclado invisível. `prefers-reduced-motion` desativa a transição.

## Arquivos canônicos

- comportamento: `assets/public.js`;
- apresentação: `assets/conversion-bar.css`;
- regressão de navegador: `tools/browser-tests/conversion-bar.spec.cjs`;
- fixture: `tools/browser-fixture/conversion-bar.html`.
