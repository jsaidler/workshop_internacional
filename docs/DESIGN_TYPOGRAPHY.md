# Tipografia do sistema de Design

Este documento define a regra canônica para tipografia editável no CMS.

## Fonte de verdade

Os controles de `Design → Tipografia` armazenam **nomes de famílias disponíveis no Google Fonts**. O usuário escolhe uma família em um seletor; não digita pilhas CSS, custom properties nem detalhes de implementação.

Defaults atuais:

- corpo: `IBM Plex Sans`;
- títulos: `Saira Extra Condensed`;
- técnica/mono: `IBM Plex Mono`.

Depois que o Design é salvo, a família persistida no CMS é a fonte de verdade editorial para aquele locale.

## Catálogo

O catálogo é controlado pelo CMS e separado por função tipográfica:

- corpo: famílias adequadas a texto corrido;
- títulos: famílias de display/condensadas ou de maior presença;
- técnica/mono: famílias monoespaçadas.

Todas as opções oferecidas pelo painel precisam existir no Google Fonts. Não aceitar texto livre nessa superfície.

## Carregamento

A página pública carrega somente as famílias atualmente selecionadas, através do Google Fonts, com os pesos declarados no catálogo do CMS. A prévia ao vivo faz o mesmo quando o usuário troca uma opção.

O valor editorial salvo é apenas o nome da família. O sistema gera internamente a pilha CSS com fallback e os tokens consumidos pelo template. Esses detalhes não aparecem como conteúdo da interface administrativa.

O CSP público autoriza `fonts.googleapis.com` em `style-src` e `fonts.gstatic.com` em `font-src`.

## Aplicação dos controles

O valor salvo pelo painel precisa controlar a propriedade realmente consumida pelo template, tanto no site público quanto na prévia ao vivo.

O CSS gerado publica simultaneamente os tokens editoriais atuais e os aliases legados usados pelo template:

- corpo: `--cms-body-font`, `--body` e `--sans`;
- títulos: `--cms-display-font` e `--title`;
- mono: `--cms-mono-font` e `--mono`.

`Design → CSS adicional` continua fora da camada `cms-system` e mantém precedência editorial final.

## Compatibilidade com estado legado

Instalações anteriores podiam ter salvo `var(--sans)`, `var(--title)`, `var(--mono)` ou pilhas CSS completas nos campos de fonte. Esses valores eram detalhes de implementação e não devem permanecer como conteúdo editorial.

- `cms_design_settings()` normaliza valores antigos para uma família válida do catálogo;
- `cms_settings_save()` valida novamente antes de persistir;
- `migrations/029_normalize_design_font_values.php` converte referências internas antigas para pilhas portáveis;
- `migrations/030_google_font_family_values.php` converte essas pilhas para nomes de famílias do Google Fonts.

## Regra de interface

Textos que explicam arquitetura, implementação, fallback, carregamento de fontes ou como o código funciona são documentação interna e não conteúdo da interface do CMS. A interface deve apresentar apenas o que o usuário precisa para tomar a decisão editorial.

Quando o CMS expuser uma propriedade visual, o valor mostrado, o valor salvo, a prévia e o site público devem representar a mesma configuração. Não criar um controle que exponha variáveis internas, sintaxe CSS ou instruções destinadas ao desenvolvedor.
