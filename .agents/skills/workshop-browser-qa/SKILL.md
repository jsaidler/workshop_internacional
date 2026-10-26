---
name: workshop-browser-qa
description: "Valide de ponta a ponta a fidelidade visual e usabilidade das interfaces públicas, administrativas, editor e área do aluno do Workshop CMS. Use navegador real, dados sintéticos, comparações visuais e testes de jornada, sem confundir fixtures ou CI verde com aceitação de produto."
---

# QA visual e de experiência: contrato de entrega

**O usuário descreve a experiência que deseja, não os procedimentos de engenharia.** Uma interface não está concluída porque foi implementada, o Playwright rodou ou screenshots foram produzidos. A responsabilidade de identificar, corrigir, comparar e reinspecionar é do Codex.

Consulte primeiro `AGENTS.md`, `docs/GLOBAL_DOCUMENTATION_CONTRACT_2026-10-07.md`, `docs/UI_CONTENT_RULES.md`, `docs/CSS_SYSTEM_SANITIZATION_2026-09-28.md`, `docs/DESIGN_TYPOGRAPHY.md` e os contratos de UX específicos da superfície. Particularmente, `docs/STUDENT_PRODUCT_WIDE_UX_REVIEW_2026-10-13.md` já registra o **rechaço de CI verde como aceite visual**, fixtures com fontes e shells falsos e partes da experiência ainda não homologadas. Não reinicie um redesign nem declare aprovado algo já rejeitado.

## 1. Descubra a fonte de verdade da aparência

1. Se houver imagem/mockup/layout aprovado pelo usuário, **é a referência geométrica e estética vinculante**. Preserve identidade, conteúdo, hierarquia, tipografia, cores, alinhamentos, proporções e relação entre componentes. Não reinterpretar ou fazer redesign silencioso.
2. Se não houver referência nova, preserve o Design global e as decisões documentadas. Diferencie melhoria de UX (fluxo, priorização, navegação) de alteração da identidade visual. Não crie paleta, fonte, sistema de cartões ou CSS paralelo por conveniência.
3. Para criação de interface materialmente nova sem referência aprovada, derive uma proposta a partir da intenção e do design system existente. Peça decisão do usuário **somente** quando houver alternativa visual ou de comportamento de produto material e irredutível; não solicite aprovação a cada ajuste técnico.
4. Antes de codificar, determine a jornada completa e os critérios de aceite observáveis: quem entra, o que tenta fazer, qual o estado, o que deve acontecer, como retorna, como falha e como se recupera.

## 2. Valide a aplicação real, não uma representação conveniente

5. Descubra os testes em `tools/browser-tests/`, fixtures em `tools/browser-fixture/` e os workflows GitHub Actions relevantes. Prepare PHP/SQLite sintético, Node/Playwright Chromium, sessão de aluno/admin de teste, assets e fontes. Resolva problemas locais de navegador com alternativas equivalentes antes de alegar impedimento.
6. **Prioridade para rotas operacionais de verdade**, com autenticação e dados sintéticos persistidos em SQLite temporário: abrir, navegar, preencher, salvar, recarregar, verificar persistência/permite/bloqueia/erro e retorno. Exercite a mesma aplicação e os mesmos CSS, fontes, componentes, permissões e shell da instalação normal.
7. Fixtures e mocks podem auxiliar testes isolados e reproduções, **mas não substituem a prova de UX**. É preciso demonstrar paridade com renderer operacional e fluxo real. Uma fixture HTML antiga, CSS injetado pelo teste, `page.route()` interceptando rotas reais ou fonte fixa distinta da autoridade de Design invalida a captura como prova de aparência final, a menos que a equivalência seja demonstrada. Não omita essa limitação.
8. Para as superfícies afetadas, percorra estados vazio, preenchido, criação, edição, sucesso, erro, carregamento, indisponibilidade, cancelamento, confirmação/destrutivo e recuperação quando aplicáveis. Verifique navegação de volta e sessão; POST inválido não perde trabalho; efeitos reais não são inferidos do estado da tela.

## 3. Compare imagem com imagem

9. Capture navegador real pelo menos em 390×844 e 1440×1100; teste também 320 px quando a superfície móvel existir, mais orientação, temas claro/escuro, zoom e teclado quando relevantes. Inspecione início/meio/fim de páginas longas, modais, menus, teclado, hover e foco. Desative animações apenas para comparação de quadros estáticos; valide interação dinâmica separadamente.
10. **Abra e inspecione os screenshots efetivos** usando ferramenta de visão disponível. Arquivos criados e CI verde NÃO significam inspeção. Compare lado a lado no mesmo viewport com referência aprovada ou baseline real. Quando houver referencial estável, produza overlay/diff visual e investigue desalinhamentos; diffs automatizados são sinais, não aprovação estética automática. Normalize conteúdo e fontes apenas de modo reproduzível, sem esconder diferenças reais.
11. Corrija hierarquia, ritmo, fontes, cores, contraste, espaçamento, proporção, oclusão, overflow, alinhamento, quebra, área de toque, elementos fora da viewport, scroll owner, bordas acumuladas e estilo administrativo indevido. Avalie usabilidade com densidade e dados realistas, não só estado vazio. **Nunca aprove uma UI sabidamente diferente da referência aprovada.**
12. Reabra a tela e refaça a mesma captura após cada correção significativa. Pare quando critérios de experiência e fidelidade estiverem satisfeitos, não quando o build passar. Não faça remendos de CSS ou `!important` para mascarar falha sistêmica: corrija o proprietário canônico.

## 4. Teste a interação e o efeito real

13. Além de imagens, automatize com Playwright os objetivos do usuário: links, botões, formulários, teclado Tab/Enter/Escape, foco, feedback, estados ARIA, submissões, navegação, diálogos, rolagem e restrições de autorização. Confira estado do banco sintético após operações críticas; screenshot não comprova persistência.
14. Execute testes de arquitetura CSS e ownership pertinentes. Rodar testes globais não substitui verificar mudanças em componentes compartilhados nas outras telas afetadas. Preserve o que já foi aprovado.
15. Os workflows `admin-visual-audit.yml`, `student-visual-audit.yml` e `editor-focused-qa.yml` usam filtros de arquivos e alguns testes apenas salvam imagens: **não presuma que rodaram** para um PR ou que aprovaram o design. Verifique triggers e jobs efetivos, execute manualmente o escopo não coberto e confira os screenshots produzidos no **mesmo SHA** da implementação.

## 5. Evidência, revisão e aceite

16. Inclua no PR em rascunho a matriz dos fluxos e estados impactados, provas de jornada operacional, screenshots finais acessíveis, comparação com referência (ou baseline conservado), testes exatos, resultados, limitações e identificação da SHA. Anexe artefatos ao CI/PR; não entregue seis relatórios Markdown para transporte manual.
17. A autoinspeção pelo Codex é **gate anterior** à revisão de produto do usuário, não substituto da decisão final dele sobre mudanças de identidade. O usuário deve ver uma entrega coerente já revisada, não um rascunho sabidamente defeituoso.
18. Sem navegador real, capturas efetivamente analisadas, verificação de comportamento e paridade com renderer oficial, classifique a entrega como **não validada visualmente** e mantenha o PR em rascunho. Explique bloqueio verificável e a providência tentada; não transfira testes técnicos ao usuário.

**Regra de ouro:** aceitação de UI/UX depende de comportamento correto + renderização real + fidelidade à referência + revisão crítica dos estados, não de contagem de testes verdes ou de screenshots gerados.
