# Funil de conversão e UX de métricas

## Objetivo

O painel de métricas deve responder onde o visitante abandona o caminho entre acessar o site e tornar-se uma inscrição convertida, sem transformar o admin em uma ferramenta de analytics genérica.

## Funil canônico

1. **Sessão** — uma sessão de navegação com ao menos uma página pública carregada.
2. **CTA** — sessão que acionou um botão/link de chamada para inscrição ou interesse.
3. **Formulário iniciado** — sessão que interagiu pela primeira vez com um campo real do formulário.
4. **Resposta enviada** — submissão aceita pelo servidor.
5. **Convertido** — resposta marcada como `converted` no admin.

O funil deve distinguir volume de eventos de quantidade de sessões. Cliques repetidos não podem inflar a etapa CTA.

## Atribuição

A origem é atribuída pelo primeiro pageview da sessão. Prioridade:

1. `utm_source`;
2. domínio externo de referência;
3. `Direto`.

`utm_medium` e `utm_campaign` permanecem associados à origem. O dashboard mostra sessões, respostas e conversão por origem.

## CTAs

O cliente registra apenas links de ação relevantes (`a.button`, CTA de cabeçalho e barra persistente), ignorando âncoras locais. O evento guarda:

- destino;
- texto curto do CTA;
- posição (barra persistente, cabeçalho, hero ou nome da seção quando disponível).

Isso permite avaliar se a barra persistente e os CTAs distribuídos pela página realmente participam da conversão.

## Privacidade

A coleta é first-party. A tabela de analytics não recebe IP bruto, e-mail, nome, telefone ou conteúdo de campos. Sessões são representadas por hash HMAC do identificador de sessão já existente. Eventos de interação são best-effort: qualquer falha na coleta deve ser invisível para o visitante e jamais bloquear navegação ou envio do formulário.

## Interface

A página `Métricas` prioriza três perguntas, nesta ordem:

1. **Quanto tráfego chegou?** — sessões e visualizações.
2. **Onde o funil perde pessoas?** — Sessão → CTA → Formulário → Resposta → Convertido.
3. **De onde vêm as sessões que respondem?** — origem/campanha, página, dispositivo e idioma.

Evitar KPIs sem relação clara com decisão. Percentuais devem sempre explicitar o denominador.
