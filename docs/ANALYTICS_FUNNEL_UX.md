# Funil de conversão e UX de métricas

## Objetivo

O painel de métricas deve responder duas perguntas comerciais distintas sem virar uma ferramenta de analytics genérica:

1. quantas sessões do site chegam a uma resposta enviada;
2. quantas respostas recebidas acabam marcadas como convertidas no admin.

## Funil atual

1. **Sessão** — uma sessão de navegação com ao menos uma página pública carregada.
2. **Resposta enviada** — submissão aceita pelo servidor.
3. **Convertido** — resposta marcada como `converted` no admin.

Os percentuais precisam sempre deixar explícito o denominador. `Acesso → resposta` mede o desempenho do site; `Resposta → convertido` mede o fechamento comercial.

## Atribuição

A origem é atribuída pelo primeiro pageview da sessão. Prioridade:

1. `utm_source`;
2. domínio externo de referência;
3. `Direto`.

`utm_medium` e `utm_campaign` permanecem associados à origem. O dashboard compara sessões, respostas e taxa de conversão por origem.

## Privacidade

A coleta é first-party. A tabela de analytics não recebe IP bruto, e-mail, nome, telefone ou conteúdo de campos. Sessões são representadas por hash HMAC do identificador de sessão já existente. Falhas de analytics nunca podem bloquear navegação ou envio do formulário.

## Interface

A página `Métricas` prioriza, nesta ordem:

1. **Quanto tráfego chegou?** — sessões e visualizações.
2. **Quanto desse tráfego respondeu?** — funil Sessão → Resposta → Convertido.
3. **De onde vêm as sessões que respondem?** — origem/campanha, página, dispositivo e idioma.

O painel inicial do admin deve mostrar apenas um resumo suficiente para tomada de decisão e encaminhar para `Métricas` quando for necessário aprofundar.

## Próxima etapa prevista

A infraestrutura já prevê eventos de `cta_click` e `form_start`. Eles só devem entrar no funil visual depois que a coleta no cliente estiver ligada e validada, para evitar exibir etapas permanentemente zeradas ou percentuais sem base real.
