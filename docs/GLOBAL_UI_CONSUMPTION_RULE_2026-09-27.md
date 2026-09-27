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

`template/page.css` é a autoridade visual global compartilhada pelas superfícies públicas e privadas que usam a identidade do sistema.

`template/page.js` é a autoridade global para comportamentos reutilizáveis de interface já pertencentes à página-base, como tema e validação de formulários.

CSS/JS de uma superfície específica só pode conter comportamento ou composição que exista por causa daquela superfície. Exemplos válidos na Área do aluno:

- composição do workspace;
- stepper do teste;
- disposição do workflow de exposição/revelação;
- navegação móvel específica da Área do aluno;
- composição de cartões de teste, mídia e conversa.

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

## Consequência para a Área do aluno

A revisão premium anterior melhorou a aparência, mas introduziu primitivas locais como `.student-button`, `.student-field`, `.student-choice-*` e um motor de validação em `assets/student-area.js`. Isso viola a ordem acima.

A correção estrutural é:

- consumir `.button`, `.button-primary`, `.button-secondary` e `.theme-switch`, já globais;
- mover para a autoridade global apenas as lacunas reais que ainda não existiam como componentes neutros: campo, choice/check, mensagens e validação progressiva;
- remover da Área do aluno a implementação paralela dessas primitivas;
- manter em `assets/student-area.css` apenas composição/layout específicos;
- eliminar `assets/student-area.js` se não restar comportamento exclusivo da Área do aluno.

## Regra de regressão

A suíte deve falhar se a Área do aluno voltar a declarar primitivas globais com prefixo local ou reimplementar validação/tema.

Uma necessidade nova só pode gerar CSS/JS local quando o motivo de ela existir for específico daquela aplicação, e não apenas porque a tela atual precisa dela.
