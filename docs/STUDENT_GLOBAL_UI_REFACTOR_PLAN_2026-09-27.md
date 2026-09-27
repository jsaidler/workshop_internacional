# Refatoração estrutural da Área do aluno para consumo do sistema global

Este plano aplica `ARCHITECTURE_GLOBAL_UI_CONSUMPTION_2026-09-27.md` ao estado posterior ao PR #136.

## Inventário inicial

### Autoridades globais já existentes e que devem ser consumidas

- `template/page.css`
  - tokens de tema (`--bg`, `--surface`, `--text`, `--line`, `--focus` etc.);
  - tipografia base;
  - `.button`, `.button-primary`, `.button-secondary`;
  - `.theme-switch`;
  - foco global.
- `template/page.js`
  - persistência e aplicação de `workshop-theme`.
- sistema público de formulários
  - já há linguagem canônica de campo, choice e estados de formulário, embora parte dela esteja hoje excessivamente acoplada a `.cms-form`.

### Lacunas globais reais

- não há uma primitiva neutra reutilizável para field/textarea/select que possa ser consumida fora de `.cms-form`;
- não há uma primitiva neutra reutilizável para choice segmentado;
- não há um motor global de validação inline acessível; o comportamento criado no PR #136 ficou preso em `student-area.js`.

Essas lacunas devem ser preenchidas globalmente, não dentro da Área do aluno.

## Execução

1. Extrair/promover field, choice, feedback e validação para uma autoridade global neutra.
2. Fazer formulários da Área do aluno consumir essa autoridade.
3. Fazer botões da Área do aluno consumir `.button` e suas variantes globais.
4. Remover do CSS local toda regra que define aparência/estado de primitivas compartilháveis.
5. Remover a validação de `student-area.js`; se o arquivo ficar sem responsabilidade exclusiva, eliminá-lo.
6. Preservar em `student-area.css` apenas layout/composição do shell, navegação, workflow, cards, listas, revisão, conversa e responsividade específica.
7. Adicionar regressão arquitetural que falhe caso primitivas globais voltem a nascer com prefixo `student-`.

## Critério de pronto

A Área do aluno deve continuar visualmente e funcionalmente correta, mas sua UI básica precisa ser derivada das mesmas autoridades reutilizáveis usadas pelo restante do sistema. Uma mudança futura no botão/campo/choice/validação global deve alcançar a Área do aluno sem exigir uma segunda implementação local.
