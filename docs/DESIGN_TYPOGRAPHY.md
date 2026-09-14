# Tipografia do sistema de Design

Este documento define a regra canônica para tipografia editável no CMS.

## Fonte de verdade

Os campos de `Design → Tipografia` armazenam **pilhas CSS reais**. Eles não armazenam nomes de custom properties como `var(--title)`, `var(--mono)` ou `var(--sans)`.

Os valores aprovados atualmente são:

- corpo: `"IBM Plex Sans", Arial, sans-serif`;
- títulos: `"Saira Extra Condensed", "Arial Narrow", sans-serif`;
- técnica/mono: `"IBM Plex Mono", Consolas, monospace`.

Esses valores são defaults de inicialização e fallbacks; depois que o Design é salvo, o valor persistido no CMS é a fonte de verdade editorial para aquele locale.

## Disponibilidade das fontes

Uma fonte escolhida pelo sistema não pode depender de estar instalada no computador do visitante.

- `Saira Extra Condensed` é distribuída com os assets locais do projeto em `assets/fonts/saira-extra-condensed/`.
- `IBM Plex Sans` e `IBM Plex Mono` são carregadas como web fonts pela folha canônica retornada por `cms_design_font_import_css()`.
- As pilhas mantêm fallbacks de sistema para falha de rede ou indisponibilidade do provedor.
- O CSP público já autoriza `fonts.googleapis.com` em `style-src` e `fonts.gstatic.com` em `font-src`.

Se outra fonte passar a fazer parte do visual aprovado, sua disponibilidade pública precisa ser resolvida no mesmo trabalho — self-hosted ou web font explícita. Não é aceitável depender silenciosamente de uma instalação local.

## Aplicação dos controles

O valor salvo pelo painel precisa controlar a propriedade realmente consumida pelo template, tanto no site público quanto na prévia ao vivo.

O CSS gerado publica simultaneamente os tokens editoriais atuais e os aliases legados usados pelo template:

- corpo: `--cms-body-font`, `--body` e `--sans`;
- títulos: `--cms-display-font` e `--title`;
- mono: `--cms-mono-font` e `--mono`.

A prévia de `Design` gera os mesmos tokens dentro da camada `cms-system`. `Design → CSS adicional` continua fora dessa camada e mantém precedência editorial final.

## Compatibilidade com estado legado

Instalações anteriores podiam ter salvo `var(--sans)`, `var(--title)` ou `var(--mono)` nos campos de fonte. Esses valores eram implementação interna, não escolhas tipográficas portáveis.

- `cms_design_settings()` normaliza esses valores em leitura para impedir ciclos de custom properties e para apresentar valores úteis no formulário.
- `cms_settings_save()` normaliza antes de persistir.
- `migrations/028_normalize_design_font_values.php` reescreve os registros legados sem alterar as demais propriedades de Design.

## Regra para novos controles visuais

Quando o CMS expuser uma propriedade visual ao usuário, o valor mostrado no formulário, o valor salvo, a prévia e o site público devem representar a mesma configuração. Não criar um controle que apenas exponha nomes de variáveis internas enquanto o valor efetivo permanece fixo em outro stylesheet ou componente.
