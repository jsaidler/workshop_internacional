# Regra estrutural de consumo da UI global — 27/09/2026

## Regra canônica

Toda nova interface deve partir do sistema já existente.

A ordem obrigatória é:

1. **procurar** a autoridade global já existente para a necessidade;
2. **consumir** essa autoridade sem criar uma versão local;
3. somente quando não houver regra válida, **identificar a lacuna**;
4. se a necessidade for reutilizável, **criar ou ampliar a regra global**, mantendo coerência com os componentes, tokens e comportamento existentes;
5. a superfície específica então **consome** a nova regra global.

Portanto, a decisão padrão nunca é "corrigir/criar um componente para esta tela". A decisão padrão é **consumir o componente global existente**.

## Autoridades

`template/page.css` continua sendo a autoridade dos tokens e das primitivas visuais que já existiam no sistema, incluindo tipografia, paleta, `.button`, `.button-primary`, `.button-secondary` e `.theme-switch`.

`assets/ui-core.css` contém somente as lacunas reutilizáveis que não possuíam antes uma autoridade neutra: campos, select normalizado, choices, checkbox, mensagens/alertas e modificadores genéricos de botão. Não é uma folha da Área do aluno.

`assets/ui-core.js` é a autoridade única para os comportamentos reutilizáveis que estavam duplicados ou haviam nascido localmente: preferência de tema e validação progressiva acessível. `template/page.js`, `assets/public.js` e a Área do aluno devem consumir essa autoridade em vez de manter implementações paralelas.

CSS/JS de uma superfície específica só pode conter comportamento ou composição que exista por causa daquela superfície. Exemplos válidos na Área do aluno:

- composição do workspace;
- stepper do teste;
- disposição do workflow de exposição/revelação;
- navegação móvel específica da Área do aluno;
- composição de cartões de teste, mídia e conversa;
- adaptação estrutural do input de captura dentro do workflow.

Não são responsabilidades locais:

- tema;
- botão primário/secundário;
- input, textarea e select;
- grupo de escolhas;
- checkbox;
- estados hover/focus/disabled/error;
- validação progressiva;
- mensagens de erro associadas a campo;
- notice/alert genérico;
- tipografia ou paleta.

## Correção executada na Área do aluno

A revisão premium anterior melhorou a aparência, mas introduziu primitivas locais como `.student-button`, `.student-field`, `.student-choice-*` e um motor de validação em `assets/student-area.js`. Essa decisão violava a ordem canônica acima.

A correção estrutural executada é:

- `.button`, `.button-primary`, `.button-secondary` e `.theme-switch` existentes passam a ser consumidos diretamente;
- campos, choices/checks, alerts e validação progressiva passam a existir como primitivas neutras em `ui-core` e são consumidos pela Área do aluno;
- `assets/student-area.css` fica restrito à composição/layout do aplicativo e a modificadores estruturais;
- `assets/student-area.js` é eliminado porque não restou comportamento exclusivo da Área do aluno;
- o shell da Área do aluno carrega `ui-core` e deixa de carregar uma implementação local de interação;
- `template/page.js` e `assets/public.js` deixam de possuir implementação própria de tema e passam a delegá-la a `ui-core`.

## Convenção de marcação

Primitivas globais reutilizáveis usam nomes neutros:

- `.form-field`, `.form-field-help`, `.form-field-error`;
- `.choice-field`, `.choice-row`, `.choice-option`;
- `.check-field`;
- `.ui-alert`, `.ui-alert-error`, `.ui-alert-notice`;
- `.button`, `.button-primary`, `.button-secondary`, `.button-compact`, `.button-danger`;
- `data-ui-validate` e `data-ui-match`.

Prefixos de superfície, como `student-*`, permanecem apenas onde a responsabilidade é realmente da superfície.

## Regra de regressão

A suíte deve falhar se a Área do aluno voltar a declarar primitivas globais com prefixo local ou reimplementar validação/tema.

Uma necessidade nova só pode gerar CSS/JS local quando o motivo de ela existir for específico daquela aplicação, e não apenas porque a tela atual precisa dela.
