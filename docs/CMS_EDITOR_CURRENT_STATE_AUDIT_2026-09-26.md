# CMS/editor — auditoria factual do estado atual — 26/09/2026

## Objetivo

Este documento substitui, para fins de estado atual, a fotografia de implementação registrada em `CMS_STUDENT_ACCESS_CONTRACT_2026-09-26.md` antes dos PRs #103–#115. O contrato funcional daquele documento continua válido; esta auditoria registra o que está realmente implementado na branch de produção `wip/form-response-refinement-2026-07-16` no commit `7fdf82df2bf141f12899830fddde10228342761d`.

A regra operacional permanece: correções e melhorias seguem em blocos pequenos, cada um com branch, regressão, PR, merge e deploy antes do próximo.

## Matriz de estado

| Requisito | Estado atual | Classificação | Evidência / observação | Próxima ação |
| --- | --- | --- | --- | --- |
| Conta global por pessoa | `student_users` é identidade global e `course_enrollments` é vínculo por turma/atividade | CORRETO | PR #103; serviço canônico em `app/student_enrollments.php` | manter |
| Usuário autenticado inscrito em novo curso | submissão de matrícula pode ser vinculada ao `student_id` da sessão e a reconciliação usa esse vínculo | CORRETO | PR #103 | manter regressão multi-curso |
| Múltiplas matrículas independentes | `course_enrollments` preserva uma matrícula por conta/turma e reativa sem duplicar | CORRETO | PR #103 | manter |
| Hierarquia real de páginas | `parent_page_id`, validação de ciclo/idioma/atividade, árvore e ordenação entre irmãos | CORRETO | PRs #104 e #112; `app/cms_page_structure.php`, `admin/pages.php` | manter |
| Hierarquia não altera URL | mudar página superior não altera slug/URL | CORRETO | `cms_page_set_parent()` | manter |
| Acesso da página | público, autenticado, atividade e turma específica | CORRETO | PR #105; `app/cms_access.php`, `editor/cms-access-controls.js` | melhorar apenas integração visual do inspetor |
| Audiência da seção | público, autenticado, atividade e turma | CORRETO | PR #106; atributos no próprio HTML CMS | manter |
| Disponibilidade da seção | imediata, agendada e controlada por aula | CORRETO | PR #106; validação de datas/aula | manter |
| Autorização server-side | página e seção são filtradas no servidor; conteúdo sem autorização não é enviado ao público | CORRETO | `cms_access_page_allowed()` e `cms_access_filter_html()` | manter |
| Liberação por aula/turma | `cohort_lesson_releases.released_at` é autoridade | CORRETO | PR #106 e serviço em `app/cms_access.php` | manter |
| Material dividido por aulas | seções do `caderno-positivo-direto` são vinculadas a Aula 1, 2 e 3; capa/índice permanecem imediatos | CORRETO | PR #107 | manter regressão da migração |
| Lista “Seções” do editor | reflete todas as seções CMS, inclusive sob wrappers | CORRETO após correção | PR #109 | manter |
| Hover revela alvo de seleção | moldura/etiqueta mostram o elemento que o clique deve selecionar | CORRETO após correção | PR #111 | incluir no runtime integrado |
| Placeholder privado visível só ao admin | slot vazio não produz markup para aluno; editor mostra placeholder | CORRETO | PRs #101/#110/#115 | manter |
| Placeholder de infográfico como alvo de imagem | placeholder é selecionável e permite escolher/trocar/remover imagem privada sem virar componente público | CORRETO após correção | PR #115 | manter |
| “Páginas protegidas” como autoridade editorial paralela | rota editorial antiga é redirecionada/retirada e ações antigas retornam 410 | CORRETO no caminho ativo | `admin/student-area.php` | manter legado apenas para compatibilidade de dados |
| `course_page_sections` como autoridade | não participa do runtime canônico, mas helpers/tabela históricos permanecem no código | PARCIAL / DÍVIDA TÉCNICA | `app/student_accounts.php` ainda contém helpers históricos | remover apenas quando migração/compatibilidade estiver comprovadamente dispensável |
| `enrolled` como autoridade | caminhos canônicos usam `activity`; leitura de `enrolled` permanece por compatibilidade | PARCIAL / COMPATIBILIDADE | `app/cms_access.php`, `app/student_enrollments.php`, helpers antigos | não usar em código novo; limpar depois |
| Árvore estrutural de componentes | componentes, contêineres e colunas são navegáveis | CORRETO | `cms-structure-sidebar.js` | manter |
| Busca da estrutura | busca existe na visão Estrutura | CORRETO | `cms-structure-navigation.js` | manter |
| Breadcrumb do inspetor | caminho seção → contêiner/coluna → componente existe | CORRETO | `cms-structure-navigation.js` | melhorar acabamento, não reimplementar |
| Nome interno de nós | `data-cms-editor-label` editável no inspetor | CORRETO | `cms-structure-labels.js` | manter |
| Inserção interna de componentes | paleta existe e cria componentes antes/depois/dentro | FUNCIONALIDADE PRESENTE, INTERAÇÃO AINDA INSEGURA | o runtime atual ainda intercepta `pointerdown` com `stopImmediatePropagation()` e sintetiza `button.click()` em `cms-inline-reliability.js`; esse padrão pode competir com os demais handlers do editor | **corrigir primeiro** |
| Regressão do runtime real do editor | há muitos testes por fixture, mas não existe atualmente uma trava simples que verifique o stack efetivamente carregado em `editor/index.html` e os contratos das camadas críticas | AUSENTE | a tentativa existiu no PR #116, fechado sem merge | **adicionar junto da correção de interação** |
| Biblioteca “Adicionar seção” | mostra apenas templates hardcoded | PARCIAL / INCORRETO EM RELAÇÃO AO CONTRATO | `admin/api/blocks.php` existe, mas `openSections()` em `cms-editor-v3.js` não carrega Blocos salvos | implementar depois da correção de interação |
| Biblioteca única “Seções prontas + Blocos salvos” | prevista em `CMS_CONTENT_STRUCTURE.md`, ainda não materializada na UI atual | AUSENTE | API de blocos existe; UI não a consome | bloco próprio |
| Coerência do inspetor | propriedades funcionais vêm de módulos diferentes e alguns controles são injetados depois do inspetor base | PARCIAL / UX FRAGMENTADA | página, seção, acesso, layout e mídia são compostos por várias camadas JS | consolidar apresentação por etapas, sem reescrever backend |
| Autoridade de interação do editor | seleção, estrutura, DnD, hover, promoção legada e inserção são distribuídos por vários módulos sobrepostos | FRÁGIL | os PRs #109, #114 e #115 corrigiram falhas de integração que passavam em fixtures isoladas | reduzir sobreposição gradualmente, com teste integrado antes de cada remoção |

## Achado crítico da auditoria

A correção da paleta inline não está fechada no runtime atual.

`editor/cms-inline-reliability.js` ainda captura `pointerdown` em botões da paleta, chama `preventDefault()`, `stopImmediatePropagation()` e em seguida executa `button.click()` artificialmente. Esse desenho é exatamente o tipo de competição de eventos que pode produzir o sintoma observado no editor real: a paleta aparece, mas a ação não chega de forma confiável ao handler que cria o componente.

A próxima alteração funcional deve corrigir esse ponto antes de acrescentar novas capacidades ao editor.

Contrato para a correção:

1. a paleta deve ficar acima das demais camadas editoriais;
2. o botão deve receber o clique nativo do navegador;
3. a camada de seleção/drag do editor não deve capturar eventos originados na paleta;
4. não sintetizar `click()` a partir de `pointerdown`;
5. não usar `stopImmediatePropagation()` de modo que mate o próprio handler do comando;
6. mouse, touch/pointer e teclado precisam continuar válidos;
7. o CI deve verificar o stack efetivamente carregado pelo editor de produção, não apenas uma fixture reduzida.

## Ordem revisada dos próximos blocos

### J1 — confiabilidade de interação do editor

- corrigir `cms-inline-reliability.js` para preservar clique nativo;
- adicionar regressão do runtime de produção;
- manter as regressões Playwright de inserção já existentes.

### J2 — biblioteca canônica de seções

- `Adicionar seção` passa a apresentar **Seções prontas** e **Blocos salvos** na mesma biblioteca;
- consumir `admin/api/blocks.php` em vez de manter uma API sem superfície correspondente;
- estados vazio/carregando/erro e ações com hierarquia visual adequada.

### J3 — coerência do inspetor

- agrupar identidade, layout, acesso e disponibilidade com hierarquia consistente;
- eliminar botões de “salvar” isolados quando a operação puder participar do fluxo normal de salvar página;
- estados alterado/salvo/erro visíveis e previsíveis.

### J4 — redução de autoridades concorrentes no front-end

- mapear os handlers sobrepostos de seleção, estrutura, DnD, hover e promoção legada;
- escolher uma autoridade por responsabilidade;
- remover apenas uma sobreposição por PR, sempre com regressão do runtime real.

### J5 — limpeza histórica

- remover `course_page_sections`, helpers `student_page_*` históricos e compatibilidade `enrolled` somente depois de confirmar que não existe instalação/dado que ainda precise de migração.

## Critério de pronto para o editor

Uma função do CMS não é considerada concluída apenas porque existe backend ou um controle visual. Para cada fluxo editorial, o critério de pronto inclui:

- ação visível e compreensível;
- alvo de seleção previsível;
- ação executável por mouse/pointer e teclado quando aplicável;
- estado de sucesso/erro perceptível;
- persistência após salvar/recarregar;
- comportamento correto no preview e no público;
- ausência de UI editorial no HTML canônico;
- regressão que cubra o runtime em que o usuário realmente trabalha.
