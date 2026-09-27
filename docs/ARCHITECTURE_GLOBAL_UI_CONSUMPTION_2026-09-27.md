# Autoridade estrutural de UI — consumir antes de criar

## Regra canônica

Toda nova interface deve **consumir primeiro** o sistema global existente.

A ordem obrigatória é:

1. **Procurar** a regra, componente, comportamento ou token global já existente.
2. **Consumir** essa autoridade sem criar uma variante local.
3. Apenas quando não existir uma regra válida, registrar a lacuna.
4. Se a lacuna for reutilizável, **criá-la no sistema global**, coerente com as autoridades já existentes.
5. A superfície específica passa então a **consumir** essa nova regra global.
6. Código específico de uma área só é aceitável quando a responsabilidade é estrutural e exclusiva daquela área.

## O que não pode nascer localmente

Não devem existir implementações específicas por superfície para responsabilidades reutilizáveis como:

- tema e persistência de tema;
- tipografia base e tokens visuais;
- botões e seus estados;
- inputs, selects e textareas;
- choices/radio/checkbox;
- validação de formulário e mensagens de erro;
- notices/alerts;
- foco, hover, disabled e acessibilidade básica;
- espaçamento-base de controles.

Nomes como `.student-button`, `.student-field` ou um motor de validação exclusivo de `student-area.js` são sinais de que uma primitiva reutilizável foi criada no lugar errado.

## O que pode permanecer específico

Uma superfície pode possuir regras próprias quando elas descrevem a composição exclusiva daquela aplicação, por exemplo:

- stepper do workflow de testes;
- organização do workspace da turma;
- composição dos cards de curso;
- layout da ficha de exposição/revelação;
- navegação móvel específica da Área do aluno;
- composição de conversa e revisão de teste.

Mesmo nesses casos, os elementos básicos usados por essas estruturas continuam vindo do sistema global.

## Aplicação imediata à Área do aluno

O PR #136 corrigiu diversos sintomas de UI/UX, mas introduziu/promoveu primitivas locais que violam esta regra. A correção arquitetural subsequente deve:

- substituir controles locais por componentes globais já existentes quando houver autoridade válida;
- promover para a camada global somente as lacunas reais;
- eliminar duplicações locais de tema, botão, campo, choice e validação;
- manter em `student-area.css` apenas composição e layout exclusivos da Área do aluno;
- manter JS local apenas se houver comportamento que não pertença ao sistema global.

## Gate para mudanças futuras

Antes de aceitar qualquer novo CSS/JS específico de uma superfície, a revisão deve responder:

1. Já existe autoridade global para isso?
2. Se existe, por que não está sendo consumida?
3. Se não existe, a necessidade é reutilizável?
4. Se é reutilizável, por que a implementação não está entrando na camada global?

A ausência de resposta suficiente bloqueia a criação da variante local.
