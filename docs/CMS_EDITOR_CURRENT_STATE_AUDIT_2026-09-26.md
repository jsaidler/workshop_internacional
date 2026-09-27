# CMS/editor — auditoria factual do estado atual — 26/09/2026

## Objetivo

Este documento substitui, para fins de estado atual, a fotografia de implementação registrada em `CMS_STUDENT_ACCESS_CONTRACT_2026-09-26.md` antes dos PRs #103–#115. O contrato funcional daquele documento continua válido; esta auditoria registra o que está realmente implementado na branch de produção `wip/form-response-refinement-2026-07-16`.

A regra operacional permanece: correções e melhorias seguem em blocos pequenos, cada um com branch, regressão, PR, merge e deploy antes do próximo.

## Matriz de estado

| Requisito | Estado atual | Classificação | Evidência / observação | Próxima ação |
| --- | --- | --- | --- | --- |
| Conta global por pessoa | `student_users` é identidade global e `course_enrollments` é vínculo por turma/atividade | CORRETO | PR #103; serviço canônico em `app/student_enrollments.php` | manter |
| Usuário autenticado inscrito em novo curso | submissão de matrícula pode ser vinculada ao `student_id` da sessão e a reconciliação usa esse vínculo | CORRETO | PR #103 | manter regressão multi-curso |
| Múltiplas matrículas independentes | `course_enrollments` preserva uma matrícula por conta/turma e reativa sem duplicar | CORRETO | PR #103 | manter |
| Hierarquia real de páginas | `parent_page_id`, validação de ciclo/idioma/atividade, árvore e ordenação entre irmãos | CORRETO | PRs #104 e #112; `app/cms_page_structure.php`, `admin/pages.php` | manter |
| Hierarquia não altera URL | mudar página superior não altera slug/URL | CORRETO | `cms_page_set_parent()` | manter |
| Acesso da página | público, autenticado, atividade e turma específica | CORRETO | PR #105; `app/cms_access.php`, `editor/cms-access-controls.js` | manter integração pelo inspetor coerente |
| Audiência da seção | público, autenticado, atividade e turma | CORRETO | PR #106; atributos no próprio HTML CMS | manter |
| Disponibilidade da seção | imediata, agendada e controlada por aula | CORRETO | PR #106; validação de datas/aula | manter |
| Autorização server-side | página e seção são filtradas no servidor; conteúdo sem autorização não é enviado ao público | CORRETO | `cms_access_page_allowed()` e `cms_access_filter_html()` | manter |
| Liberação por aula/turma | `cohort_lesson_releases.released_at` é autoridade | CORRETO | PR #106 e serviço em `app/cms_access.php` | manter |
| Material dividido por aulas | seções do `caderno-positivo-direto` são vinculadas a Aula 1, 2 e 3; capa/índice permanecem imediatos | CORRETO | PR #107 | manter regressão da migração |
| Lista “Seções” do editor | reflete todas as seções CMS, inclusive sob wrappers | CORRETO após correção | PR #109 | manter |
| Hover revela alvo de seleção | moldura/etiqueta mostram o elemento que o clique deve selecionar | CORRETO após correção | PR #111 | manter no runtime integrado |
| Placeholder privado visível só ao admin | slot vazio não produz markup para aluno; editor mostra placeholder | CORRETO | PRs #101/#110/#115 | manter |
| Placeholder de infográfico como alvo de imagem | placeholder é selecionável e permite escolher/trocar/remover imagem privada sem virar componente público | CORRETO após correção | PR #115 | manter |
| “Páginas protegidas” como autoridade editorial paralela | rota editorial antiga é redirecionada/retirada e ações antigas retornam 410 | CORRETO no caminho ativo | `admin/student-area.php` | manter legado apenas para compatibilidade de dados |
| `course_page_sections` como autoridade | não participa do runtime canônico, mas helpers/tabela históricos permanecem no código | PARCIAL / DÍVIDA TÉCNICA | `app/student_accounts.php` ainda contém helpers históricos | remover apenas quando migração/compatibilidade estiver comprovadamente dispensável |
| `enrolled` como autoridade | caminhos canônicos usam `activity`; leitura de `enrolled` permanece por compatibilidade | PARCIAL / COMPATIBILIDADE | `app/cms_access.php`, `app/student_enrollments.php`, helpers antigos | não usar em código novo; limpar depois |
| Árvore estrutural de componentes | componentes, contêineres e colunas são navegáveis | CORRETO | `cms-structure-sidebar.js` | manter |
| Busca da estrutura | busca existe na visão Estrutura | CORRETO | `cms-structure-navigation.js` | manter |
| Breadcrumb do inspetor | caminho seção → contêiner/coluna → componente existe | CORRETO | `cms-structure-navigation.js` | melhorar acabamento, não reimplementar |
| Nome interno de nós | `data-cms-editor-label` editável no inspetor | CORRETO | `cms-structure-labels.js` | manter |
| Inserção interna de componentes | paleta cria componentes antes/depois/dentro e agora preserva o clique nativo do navegador | CORRETO após J1 | PR #118; `cms-inline-reliability.js` não cancela `pointerdown` nem sintetiza `click()` | manter regressões Playwright + runtime |
| Regressão do runtime real do editor | CI verifica a composição efetivamente carregada pelo editor e contratos críticos de interação/mídia | CORRETO após J1 | PR #118; `tools/test-editor-runtime-integration.php` | ampliar quando novas camadas críticas entrarem |
| Biblioteca “Adicionar seção” | os dois gatilhos usam `pro-components-dialog`; a consolidação limpa defensivamente handlers de propriedade antes de assumir o clique | AUTORIDADE ÚNICA ATIVA APÓS J3A | PR #121; merge `6cc07779469af72ceb0c6736677a63ea11895431`; deploy concluído | manter regressão |
| Biblioteca única “Seções prontas + Blocos salvos” | `cms-pro-editor.js` carrega blocos por `admin/api/blocks.php`, insere cópia independente e permite salvar a seção atual como bloco | CORRETO APÓS J3A | regressão Playwright trava os dois gatilhos, os nomes das abas e a ausência do diálogo legado | manter |
| Biblioteca hardcoded histórica de `cms-editor-v3.js` | `templates`, `openSections()`, referências ao diálogo legado e bindings antigos foram removidos | REMOVIDO NO J3B | `cms-editor-v3.js` deixa de conhecer a biblioteca histórica; a regressão de runtime proíbe sua reintrodução | manter `cms-pro-editor.js` como fonte das seções prontas |
| Coerência do inspetor | seção organizada em Identidade, Layout, Audiência e Disponibilidade; página organizada em Identidade, Navegação/aparência, SEO e Audiência; acesso da página com autosave e estados explícitos | CORRETO APÓS J2 | PR #120; merge `a9af8ae44b585d7bb24ae39d59441896f8fd4596`; deploy de produção concluído | manter regressão |
| Autoridade de interação do editor | seleção, estrutura, DnD, hover, promoção legada e inserção continuam distribuídos por módulos sobrepostos | FRÁGIL, MAS COM REGRESSÃO MELHOR | PR #118 passou a travar parte do stack real; ainda há sobreposição | reduzir gradualmente, uma responsabilidade por PR |

## Correção da auditoria: biblioteca de seções

A primeira versão desta auditoria classificou a biblioteca única como ausente porque examinou `openSections()` em `cms-editor-v3.js` de forma isolada. Essa classificação estava errada.

O runtime efetivo também carrega `cms-pro-editor.js` e `cms-editor-consolidation.js`. A camada de consolidação governa os dois gatilhos “Adicionar seção”, abre `pro-components-dialog`, apresenta as abas **Seções prontas** e **Blocos salvos** e esconde o gatilho concorrente do editor Pro. A aba de blocos usa `admin/api/blocks.php` e o mesmo módulo permite salvar a seção selecionada como bloco reutilizável.

O J3 não criou uma nova biblioteca. Ele retirou o caminho histórico em duas etapas para não misturar troca de autoridade com limpeza de código.

## J1 concluído — confiabilidade de interação do editor

O PR #118 removeu do runtime o padrão que cancelava `pointerdown` com `preventDefault()`/`stopImmediatePropagation()` e executava `button.click()` artificialmente. A paleta continua acima das demais camadas, mas os botões voltam a usar a ativação nativa do navegador.

O mesmo PR adicionou `tools/test-editor-runtime-integration.php` ao CI. Essa regressão verifica a ordem das camadas críticas do editor, proíbe a reintrodução do clique sintético/cancelador e confirma que mídia privada e hover continuam ligados ao runtime efetivo.

## J2 concluído e implantado — coerência do inspetor

O PR #120 consolidou a apresentação do inspetor sem criar nova autoridade de dados. O merge de produção é `a9af8ae44b585d7bb24ae39d59441896f8fd4596` e o workflow de deploy concluiu validação, build, upload e publicação da branch de produção para o atualizador administrativo.

Na seleção de seção, a interface apresenta quatro grupos editoriais: **Identidade**, **Layout**, **Audiência** e **Disponibilidade**. Os controles já existentes são movidos no DOM, preservando seus listeners e os atributos canônicos da seção. O acesso continua usando `data-cms-access`, `data-cms-cohort-id`, `data-cms-availability`, `data-cms-visible-from`, `data-cms-visible-until` e `data-cms-lesson-id`.

Nas configurações da página, os controles são organizados em **Identidade**, **Navegação e aparência**, **SEO e compartilhamento** e **Audiência**. O botão isolado **Salvar acesso da página** foi removido: alteração de audiência/turma usa a mesma API canônica já existente, com autosave curto e estados visíveis de salvando, salvo e erro.

A camada `cms-inspector-coherence.js` é carregada pelo runtime real depois da coerência de seções. Há regressão estática do runtime e regressão Playwright para confirmar os grupos e que mover controles no DOM não elimina os listeners já ligados por outras camadas.

Durante a validação do J2, o Playwright detectou um laço de mutação no próprio organizador do inspetor: o bloco de ações era reapensado ao painel em toda passagem do `MutationObserver`, mesmo quando já era o último filho, impedindo a página de concluir o evento `load`. O código foi corrigido para só mover esse bloco quando sua posição realmente precisa mudar. Regra derivada: um reorganizador observado por `MutationObserver` deve ser idempotente e não pode produzir mutações sem mudança efetiva de estado.

## J3A concluído e implantado — autoridade única da biblioteca de seções

O PR #121 eliminou a concorrência ativa antes de apagar o código morto. O merge de produção é `6cc07779469af72ceb0c6736677a63ea11895431` e o deploy concluiu validação, build, upload e publicação.

O contrato implantado é:

- os dois gatilhos **Adicionar seção** pertencem a `cms-editor-consolidation.js`;
- ao assumir cada gatilho, a consolidação limpa defensivamente qualquer `onclick` de propriedade antes de instalar seu listener;
- o fluxo não depende mais de `stopImmediatePropagation()` para vencer outro handler;
- `section-dialog` e `section-library` não são enviados em `editor/index.html`;
- a biblioteca ativa é `pro-components-dialog`, com as abas **Seções prontas** e **Blocos salvos**;
- os blocos salvos continuam usando `admin/api/blocks.php`;
- Playwright comprova que handlers legados artificiais são neutralizados e que os dois gatilhos abrem somente o diálogo moderno.

## J3B — remoção do dead code da biblioteca histórica

Com a autoridade ativa já protegida e implantada pelo J3A, este bloco remove do núcleo `cms-editor-v3.js` tudo o que restava da biblioteca anterior:

- referências `sectionDialog` e `sectionLibrary`;
- o array hardcoded `templates`;
- a função `openSections()`;
- os bindings `#add-section` e `#add-section-side` para `openSections`.

Nenhuma lógica de `cms-pro-editor.js`, `cms-editor-consolidation.js` ou `admin/api/blocks.php` é reimplementada. `test-editor-runtime-integration.php` passa a falhar se qualquer uma das referências históricas voltar ao núcleo do editor. A regressão Playwright criada no J3A continua cobrindo o comportamento funcional da biblioteca consolidada.

## Ordem revisada dos próximos blocos

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
