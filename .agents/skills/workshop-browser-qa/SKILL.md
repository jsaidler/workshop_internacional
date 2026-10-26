---
name: workshop-browser-qa
description: "Valide visual e funcionalmente mudanças nas interfaces públicas, administrativas, do editor e da área do aluno do Workshop CMS com navegador real, Playwright, fixtures locais e inspeção de screenshots."
---

# QA de produto no navegador

Acione para qualquer mudança de apresentação, navegação, formulário, editor visual, fluxo administrativo ou jornada de aluno. Leia as regras canônicas de UI, CSS, responsividade, acessibilidade e edição indicadas no `AGENTS.md`.

1. Descubra os testes Playwright relevantes em `tools/browser-tests/`, fixtures em `tools/browser-fixture/`, scripts de inicialização e requisitos de autenticação sintética. Reuse o que existe; não construa uma segunda aplicação fictícia.
2. Diagnostique e prepare navegador, dependências, portas, fixture, conta de teste e permissões de artefatos em diretório temporário gravável. Se o MCP de browser não funcionar, tente Playwright CLI/headless ou outra alternativa instalada com equivalência demonstrável.
3. Exercite jornada e estados **reais** impactados (vazio, preenchido, sucesso, erro, carregando, bloqueado, confirmação destrutiva). Inclua teclado, Tab/Enter/Escape, foco visível, mensagens acessíveis e proteção de dados.
4. Inspecione screenshots renderizados, não apenas a existência de arquivos; compare a mesma viewport com baseline aprovado quando houver. Como mínimo para mudança responsiva, teste 390×844 e 1440×1100, sem presumir que bastam para todas as páginas. Repare regressões e reinspecione.
5. Valide comportamento com Playwright e DOM real: layout, overflow, inputs, elementos clicáveis, scroll, ARIA e acessibilidade, junto com testes CSS/ownership quando afetados. Screenshot não substitui interação; testes verdes não substituem inspeção visual.
6. Preserve imagens diagnósticas como artefatos de teste/CI ou link do PR, sem comitar imagens efêmeras na aplicação. Mencione o que realmente foi visto e o que não pôde ser verificado.
7. Não marque UI/UX como concluída quando navegador, autenticação, screenshot ou fixture tiverem falhado. Investigue a causa, corrija o ambiente quando seguro e declare o bloqueio residual se insolúvel. Nunca transfira ao usuário a inspeção de defeitos que compete à implementação.

Não use produção para testes destrutivos nem dados identificáveis de alunos. Não altere referências visuais existentes sem solicitação de redesign.
