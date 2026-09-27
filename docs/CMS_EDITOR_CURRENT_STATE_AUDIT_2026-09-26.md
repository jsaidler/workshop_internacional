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
| Breadcrumb do inspetor | caminho seção → contêiner/coluna → componente existe | CORRETO | `cms-structure-navigation.js` | manter; seleção passa por autoridade canônica no J4A |
| Nome interno de nós | `data-cms-editor-label` editável no inspetor | CORRETO | `cms-structure-labels.js` | manter |
| Inserção interna de componentes | paleta cria componentes antes/depois/dentro e agora preserva o clique nativo do navegador | CORRETO após J1 | PR #118; `cms-inline-reliability.js` não cancela `pointerdown` nem sintetiza `click()` | manter regressões Playwright + runtime |
| Regressão do runtime real do editor | CI verifica a composição efetivamente carregada pelo editor e contratos críticos de interação/mídia | CORRETO após J1 | PR #118; `tools/test-editor-runtime-integration.php` | ampliar quando novas camadas críticas entrarem |
| Biblioteca “Adicionar seção” | os dois gatilhos usam `pro-components-dialog`; a consolidação limpa defensivamente handlers de propriedade antes de assumir o clique | AUTORIDADE ÚNICA ATIVA APÓS J3A | PR #121; merge `6cc07779469af72ceb0c6736677a63ea11895431`; deploy concluído | manter regressão |
| Biblioteca única “Seções prontas + Blocos salvos” | `cms-pro-editor.js` carrega blocos por `admin/api/blocks.php`, insere cópia independente e permite salvar a seção atual como bloco | CORRETO APÓS J3A | regressão Playwright trava os dois gatilhos, os nomes das abas e a ausência do diálogo legado | manter |
| Biblioteca hardcoded histórica de `cms-editor-v3.js` | `templates`, `openSections()`, referências ao diálogo legado e bindings antigos foram removidos | REMOVIDO E IMPLANTADO NO J3B | PR #122; merge `0014b7d0d39cf612d9230c7b30a20f8c0bb46928`; deploy concluído | manter `cms-pro-editor.js` como fonte das seções prontas |
| Coerência do inspetor | seção organizada em Identidade, Layout, Audiência e Disponibilidade; página organizada em Identidade, Navegação/aparência, SEO e Audiência; acesso da página com autosave e estados explícitos | CORRETO APÓS J2 | PR #120; merge `a9af8ae44b585d7bb24ae39d59441896f8fd4596`; deploy concluído | manter regressão |
| Seleção estrutural entre módulos | sidebar e breadcrumb dependiam de caminhos indiretos diferentes para chegar ao inspetor estrutural | EM CONSOLIDAÇÃO NO J4A | `cms-structure-sidebar.js` usava a árvore duplicada do inspetor; `cms-structure-navigation.js` disparava clique sintético diretamente | criar uma única fronteira `CmsEditorStructure` |
| DnD estrutural | `cms-direct-structure-dnd.js` e a árvore interna de `cms-component-editor.js` implementam regras de movimentação concorrentes | DUPLICADO | ambos possuem `canDrop`/drop próprios | J4B: remover DnD/árvore interna depois do J4A |
| Movimento de seções | núcleo antigo e `cms-section-coherence.js` ainda possuem autoridades sobrepostas | DUPLICADO | coherence intercepta `#s-up/#s-down` com `stopImmediatePropagation()` para superar `moveSection` legado | bloco posterior do J4 |
| Hover estrutural | `cms-hover-selection.js` só desenha alvo/etiqueta; não altera seleção | CORRETO / SEPARADO | listeners de movimento e overlay editor-only | manter separado |
| Promoção de nós legados | `cms-legacy-node-promotion.js` adota nós sem componente em `pointerdown`/`focusin`, sem cancelar o evento | CORRETO / SEPARADO | mutação de normalização, não autoridade de seleção | manter; cobrir ordenação com seleção |

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

Durante a validação do J2, o Playwright detectou um laço de mutação no próprio organizador do inspetor. O código foi corrigido para só mover blocos quando a posição realmente muda. Regra derivada: um reorganizador observado por `MutationObserver` deve ser idempotente e não pode produzir mutações sem mudança efetiva de estado.

## J3A concluído e implantado — autoridade única da biblioteca de seções

O PR #121 eliminou a concorrência ativa antes de apagar o código morto. O merge de produção é `6cc07779469af72ceb0c6736677a63ea11895431` e o deploy concluiu validação, build, upload e publicação.

Os dois gatilhos **Adicionar seção** pertencem a `cms-editor-consolidation.js`; `section-dialog` e `section-library` deixaram de ser enviados no editor; a biblioteca ativa é `pro-components-dialog`, com **Seções prontas** e **Blocos salvos**; blocos continuam usando `admin/api/blocks.php`.

## J3B concluído e implantado — remoção do dead code da biblioteca histórica

O PR #122 removeu do núcleo `cms-editor-v3.js` as referências `sectionDialog`/`sectionLibrary`, o array hardcoded `templates`, `openSections()` e os bindings antigos dos gatilhos. O merge de produção é `0014b7d0d39cf612d9230c7b30a20f8c0bb46928`; o workflow de deploy terminou com sucesso em validação, build, upload e publicação.

A regressão de runtime agora falha se a biblioteca histórica voltar ao núcleo. Não houve reimplementação de `cms-pro-editor.js`, `cms-editor-consolidation.js` ou `admin/api/blocks.php`.

## J4 — mapa factual das autoridades de interação

A inspeção do runtime atual mostra cinco responsabilidades distintas que estavam parcialmente confundidas:

1. **Seleção estrutural e inspetor estrutural** — `cms-component-editor.js` é quem efetivamente transforma um contêiner, coluna ou componente em `.cms-structure-selected` e renderiza suas propriedades.
2. **Navegação estrutural** — `cms-structure-sidebar.js` representa a árvore global; `cms-structure-navigation.js` acrescenta busca e breadcrumb.
3. **Movimentação estrutural** — `cms-direct-structure-dnd.js` implementa DnD no canvas e na árvore global, mas `cms-component-editor.js` ainda mantém uma segunda árvore interna com DnD próprio.
4. **Hover** — `cms-hover-selection.js` é apenas visualização e deve permanecer separado da mutação de seleção.
5. **Adoção de HTML legado** — `cms-legacy-node-promotion.js` promove nós antigos para componentes ao primeiro contato, sem cancelar o evento; é normalização, não seleção.

Foi encontrada uma dependência concreta que impede remover imediatamente a árvore interna do inspetor: `cms-structure-sidebar.js` selecionava um nó procurando `.cms-structure-tree [data-tree-select]` dentro do inspetor e clicando nesse botão. O breadcrumb, por outro caminho, disparava diretamente um `MouseEvent('click')` no nó do iframe. Assim, duas interfaces de navegação dependiam de mecanismos privados diferentes.

A sequência segura foi dividida em sub-blocos.

### J4A — fronteira canônica de seleção estrutural

O J4A introduz `editor/cms-structure-selection.js` como uma fronteira pequena entre interfaces de navegação e o mecanismo atual de seleção. A API pública editor-only é:

```text
window.CmsEditorStructure.select(node)
window.CmsEditorStructure.selectSection(section)
```

Neste estágio ela encaminha a seleção pelo evento do próprio nó, que continua sendo recebido pela autoridade estrutural já existente em `cms-component-editor.js`. A vantagem arquitetural é que sidebar e breadcrumb deixam de conhecer a árvore interna do inspetor e deixam de sintetizar seus próprios caminhos de seleção. A implementação interna dessa fronteira poderá mudar depois sem alterar os consumidores.

Mudanças do J4A:

- `cms-structure-sidebar.js` deixa de procurar/clicar `.cms-structure-tree [data-tree-select]` e usa `CmsEditorStructure.select(target)`;
- seleção de seção pela sidebar usa `CmsEditorStructure.selectSection(section)`;
- breadcrumb usa a mesma fronteira para seção e nó estrutural;
- `cms-structure-selection.js` é carregado logo após `cms-component-editor.js` no runtime;
- `test-editor-runtime-integration.php` trava a ordem de carga, a exportação da API e proíbe a antiga dependência da sidebar na árvore do inspetor;
- regressão Playwright valida seleção de nó, seleção de seção e rejeição de alvos fora da estrutura CMS.

### J4B — retirar a árvore/DnD duplicados do inspetor

Só depois de J4A integrado e implantado:

- remover de `cms-component-editor.js` `treeMarkup()`, `bindTree()`, `canDrop()`/`drop()` usados exclusivamente pela árvore interna;
- manter a árvore global de `cms-structure-sidebar.js` como navegação;
- manter `cms-direct-structure-dnd.js` como única autoridade de DnD estrutural;
- preservar ações explícitas do inspetor — mover, duplicar, remover e inserir — quando não forem DnD concorrente.

### J4C — autoridade única para movimento de seções

Depois da estrutura interna consolidada:

- retirar do núcleo antigo a autoridade concorrente de `moveSection`/bindings de `#s-up/#s-down`;
- deixar `cms-section-coherence.js` governar movimento entre irmãos reais;
- remover a necessidade de `stopImmediatePropagation()` nesse fluxo.

## Ordem dos próximos blocos

### J4B — árvore/DnD estrutural duplicados

Executar somente após J4A passar CI, merge e deploy.

### J4C — movimento de seções

Executar após J4B estabilizado.

### J5 — limpeza histórica

Remover `course_page_sections`, helpers `student_page_*` históricos e compatibilidade `enrolled` somente depois de confirmar que não existe instalação/dado que ainda precise de migração.

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
