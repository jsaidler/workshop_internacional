# Área do aluno — inventário obrigatório de telas — revisão de 05/10/2026

Este inventário operacionaliza a regra de que nenhuma tela da área do aluno pode ser presumida correta. Toda tranche deve renderizar e inspecionar as superfícies visuais abaixo em desktop e mobile.

A revisão de 05/10/2026 incorpora o contrato não linear do Caderno. Modos visuais separados de “ao vivo”, “já realizado”, “retrospectivo” ou “próximas etapas” deixam de ser superfícies de produto: o Caderno não precisa saber quando a operação física aconteceu.

## Superfícies visuais

| Rota / domínio | Estados mínimos no audit |
|---|---|
| `aluno/login.php` | entrada e ativação inicial |
| `aluno/index.php` | Caderno em andamento; retorno do professor pendente; uma matrícula sem pendência no Caderno e com material utilizável; múltiplas matrículas |
| `aluno/agenda.php` | Meus encontros: vazio, matrícula regular, ouvinte confirmado e links protegidos; Próximas turmas: reserva fechada, espera, lotação, cancelamento e sem encontros; telefone estreito, telefone e desktop, antes e depois da rolagem. Cobertura suplementar de 15 estados em `tools/browser-tests/student-agenda.spec.cjs`, com renderizador PHP operacional reutilizado na fixture. |
| `aluno/cursos.php` | múltiplas matrículas; curso/turma com material disponível, parcial e agendado; curso com acompanhamento pedagógico pendente; curso sem material |
| material do curso (CMS autenticado) | leitura parcial com seções de aulas diferentes filtradas no servidor; contexto curso/turma; anterior/próximo preservando `cohort`; painel de anotações; criação de dúvida a partir de anotação no mesmo painel; telefone estreito e desktop |
| `aluno/duvidas.php` | lista; lista com conversa de avaliação contextual; nova dúvida; conversa |
| `aluno/caderno.php` | lista; novo registro; registro derivado com origem e intenção de pesquisa |
| `aluno/teste.php` | registro vazio; exposição isolada; processamento sem roteiro; roteiro associado com zero checks; conjunto arbitrário de checks; resultado independente; avaliação aguardando retorno, revisão solicitada e avaliação concluída |
| `aluno/registro-roteiro.php` | associação inicial e alteração de roteiro a partir do próprio registro, sem iniciar processamento, impor ordem ou movimentar estoque |
| `aluno/teste-etapa.php` | edição de etapa livre sem afetar outras etapas ou estoque |
| `aluno/teste-compartilhado.php` | registro compartilhado |
| `aluno/excluir-teste.php` | confirmação destrutiva |
| `aluno/comparar-processos.php` | um registro já escolhido + escolha do segundo; comparação analítica com diferenças registradas, resultados e continuidade |
| `aluno/processamentos.php` | biblioteca e editor de roteiros reutilizáveis |
| `aluno/processar.php` | consulta de qualquer etapa; checks reversíveis; timer idle/running/paused/elapsed e reutilização; agitação periódica e contínua; edição de dados; estoque como ação separada |
| `aluno/inventario.php` | estoque, vazio e movimentação |
| `aluno/inventario-item.php` | edição de item |
| `aluno/preparos.php` | lista e editor de predefinição |
| `aluno/calibracao.php` | lista e editor de referência |
| `aluno/ferramentas.php` | bancada e toolbox |
| `aluno/perfil.php` | conta/perfil |
| `aluno/senha.php` | ativação e alteração de senha |

## Arquivos em `aluno/` que não constituem tela

Estes arquivos não recebem screenshot próprio porque são redirecionadores, ações HTTP ou streams; o destino visual correspondente continua coberto acima.

- `aluno/exposicao.php` — redireciona para a ferramenta correspondente;
- `aluno/logout.php` — ação de encerramento de sessão;
- `aluno/material-anotacao.php` — endpoint de gravação/edição de anotação;
- `aluno/material.php` — redirecionamento legado para material canônico;
- `aluno/media.php` — entrega de mídia;
- `aluno/preparo-solucoes.php` — redirecionamento para receitas/preparo;
- `aluno/reciprocidade.php` — redirecionamento para ferramenta;
- `aluno/temporizador.php` — redirecionamento legado para Processamentos;
- `aluno/teste-media.php` — entrega de mídia do Caderno;
- `aluno/testes.php` — redirecionamento legado para Caderno;
- `aluno/visibilidade-teste.php` — ação de alteração de visibilidade;
- `aluno/processamento-realizado.php` — compatibilidade: converge para o Processamento do próprio registro, sem modo retrospectivo separado;
- `aluno/processamentos-trocar.php` — compatibilidade: converge para `registro-roteiro.php`, sem conceito de “próximas etapas”.

## Gate visual atual

`tools/browser-tests/student-complete-area-audit.spec.cjs` é a lista executável de cobertura visual total e declara **46 superfícies por viewport**. Os cinco estados que representavam distinções temporais artificiais — `process-intent`, `recording-start`, `recording-associated`, `recording-partial` e `recording-complete` — foram retirados do produto visual porque contradizem o contrato atual do Caderno.

A cobertura específica do Caderno fica em `tools/browser-tests/student-caderno-product-audit.spec.cjs`, com **dez estados por viewport**: biblioteca; registro vazio; Dados opcionais aberto; etapa livre aberta; roteiro associado com zero checks; roteiro associado com uma etapa em edição; conjunto parcial de checks; seletor contextual de roteiro; resultado com contexto recolhido; resultado com contexto aberto. O audit verifica também a ausência de linguagem de workflow, a disponibilidade de todas as etapas, checks reversíveis, edição direta da cópia do roteiro, acesso ao timer, navegação interna em uma única linha no telefone e área de toque mínima das ações por etapa.

Fixtures dessa cobertura precisam reproduzir o DOM relevante da tela real. Uma fixture que omita disclosure, campo, ação condicional ou estado aberto não pode servir como prova visual daquela superfície.

A cobertura de anotações do material inclui o estado com **Transformar em dúvida** aberto, campos de título/visibilidade visíveis e captura do próprio painel fixo — não screenshot `fullPage` de um elemento `position: fixed`, que distorce a inspeção. O estado é auditado em telefone e desktop.

`tools/browser-tests/student-process-execution-state-audit.spec.cjs` cobre o timer como ferramenta independente: ele pode iniciar, pausar, reiniciar e ser reutilizado depois de chegar a zero. O fim do timer pode marcar o check da própria etapa, mas não libera, bloqueia nem seleciona outra etapa.

O pacote de screenshots usado pelo material da Aula 3 deve ser regenerado a partir dessas superfícies sempre que a apresentação canônica do Caderno mudar. Na revisão não linear, o pacote foi atualizado depois de o audit visual passar.

Os testes não substituem a inspeção humana: depois da geração, o artefato inteiro deve ser aberto e observado. Qualquer problema encontrado bloqueia merge até correção e reinspeção.

### Fidelidade da evidência — 13/10/2026 (#243 em draft)

A matriz de 46 estados tinha 20 fontes PHP e 26 HTML estático. Quatro estados (`course-feedback`, `questions-feedback`, `process-library`, `profile`) passam a usar renderizadores PHP reais e fixtures com estado controlado, resultando em 24 fontes PHP e 22 HTML. O teste de inventário impede retorno desses quatro estados ao HTML legado; o Playwright não injeta CSS sobre eles, exigindo shell de cinco destinos, stylesheet canônico e geometria de rolagem. Os outros estados ainda exigem confronto com renderização operacional; a matriz **não é atestado de homologação integral**.

### Autenticação na auditoria transversal — 13/10/2026 (#243 draft)

Os estados de login, ativação de conta e alteração de senha usam agora a fixture `student-auth-operational.php`, o mesmo renderer de `aluno/login.php` e `aluno/senha.php` e o shell real. Acesso anônimo não possui menu autenticado; alteração de senha logada deve preservar os cinco destinos. Novos testes cobrem erros, validação, campos e responsividade. A matriz mantém 46 superfícies, com **27 fontes PHP e 19 HTML**; as 19 fontes estáticas remanescentes ainda precisam de substituição e inspeção autenticada no ambiente real.

### Listas do Laboratório na auditoria transversal — 14/10/2026 (#243 draft)

`preparations` e `calibration-list` migram do HTML demonstrativo para `student-long-editors.php?kind=preparation&mode=list` e `?kind=calibration&mode=list`, consumindo o mesmo componente de listas, detalhes, botões e mensagens das rotas reais por `app/student_lab_catalog_views.php`. A fixture projeta dados de teste e usa o shell canônico, sem inventar uma implementação paralela da biblioteca. O inventário de 46 estados passa a 29 fontes PHP/17 HTML, condicionadas ao resultado do CI e à inspeção das capturas atuais. As 17 telas HTML restantes ainda não são prova do produto autenticado.

### Edição de etapa e confirmação de exclusão — 14/10/2026 (#243 draft)

`step-editor` e `delete-record` deixam as versões HTML de `student-secondary-screens-audit.html` na matriz principal, passando para `student-record-actions-operational.php?mode=step|delete`. As rotas reais `aluno/teste-etapa.php` e `aluno/excluir-teste.php` compartilham o markup de `app/student_record_auxiliary_views.php` com a fixture PHP; ela só projeta registro e etapa sintéticos, inclusive bloqueio/erro, e não efetua operações. Testes PHP/Playwright conferem CSRF de salvar/excluir, destino de retorno, ausência de ações quando bloqueado, avisos destrutivos, cinco destinos, scroll e larguras 320/390/1440. Contagem da matriz: 46 estados, agora **31 fontes PHP / 15 HTML estáticas**, condicionadas a CI e inspeção em capturas do HEAD. Autenticação real não foi testada.

### Comparação de dois registros com fonte operacional — 14/10/2026, PR #243 draft

`compare-select` e `compare-records` passam da demonstração `student-notebook-compare.html` à fixture PHP `student-comparison-operational.php` com o mesmo renderer de `aluno/comparar-processos.php` (`student_research_comparison_view.php`) e algoritmos descritivos da camada `student_research.php`. Estados adicionais cobrem número insuficiente, ausência real de diferença e erro preservando texto/base. Gate exige shell, cinco destinos, sem injeção corretiva de CSS, 320/390/1440, seleção via GET, evidências/resultados e formulário de continuação com CSRF. Matriz **33 PHP / 13 HTML**, total 46. A continuidade derivada ainda depende de HTML estático e a homologação autenticada está pendente.

### Três partes do registro e avaliação no renderer real — 14/10/2026, PR #243 draft

`exposure`, `process-choice`, `result`, `result-waiting`, `result-revision` e `result-reviewed` passam de HTML demonstrativo à fixture `student-record-workspace-operational.php`, que usa **a mesma apresentação de produção** `app/student_record_workspace_view.php` e o shell canônico para três partes independentes com link de fragmento. A fixture contém dados sintéticos e não autoriza/grava/envia; o controlador `aluno/teste.php` continua responsável por estado/lock, CSRF e operações. Smoke PHP e Playwright devem exigir as três partes, campos reais, galeria compartilhada, roteiro não associado, comunicação acadêmica condicional e bloqueio de edição em registro avaliado, 320/390/1440 e scroll. Ainda ficam HTML demonstrativos com roteiro associado/parcial, compartilhamento, derivação, material, biblioteca/editor/runner e outros. Matriz estimada de 46 estados: **39 PHP / 7 HTML**; condicionada a gates e inspeção visual, não homologação de dados reais.

### Dois estados de roteiro no Caderno — 14/10/2026 (PR #243 draft)

`process-plan` e `process-partial` usam agora o mesmo renderer PHP do registro (`student-record-workspace-operational.php?state=process-plan|process-partial`), substituindo suas duas entradas HTML de `student-caderno-product-audit.html` na matriz principal. A fixture projeta nove etapas com cinco marcações não consecutivas, utiliza os utilitários reais de tempo/JSON e mantém os controles canônicos de check reversível, editar, timer, troca de roteiro e inventário separado. A variante fora da matriz `process-plan-edit` verifica o editor longo da etapa. Playwright testa 320/390/1440, cinco destinos, detalhes, status e ausência de progressão fictícia; smoke PHP exige os mesmos contratos no renderer de produção. A matriz de 46 estados tem **41 origens PHP/5 HTML** após aprovação. A inspeção autenticada da hospedagem continua pendente.

### Registro compartilhado do Caderno — 14/10/2026, PR #243 draft

`shared-record` deixa o HTML legado e passa a `student-shared-record-operational.php?mode=filled`, que usa exatamente o renderer da rota autorizada `aluno/teste-compartilhado.php`, compartilhado por `app/student_shared_record_view.php`. A rota verifica identidade, turma e direito de acesso antes de ler mídias/mensagens; o renderer não permite edição, postagem nem destruição. Estados sintéticos: ficha preenchida com processamento/conversa, vazios de mídia/etapa/mensagem e escape de conteúdo suspeito; Playwright gera início, meio e fim de scroll em 320/390/1440 px. Gate PHP exige autorização no controlador e markup apenas de leitura. **Matriz de 46 estados: 42 origens PHP, 4 HTML demonstrativos**, ainda sem homologação real de permissão e mídia.

### Continuidade da pesquisa no renderer operacional da lista — 14/10/2026, PR #243 draft

`research-derived` deixa `student-notebook-compare.html?screen=derived` e usa `student-notebook.php?state=derived`, que renderiza **`app/student_notebook_view.php`**, o mesmo componente da lista real de `aluno/caderno.php`. A fixture inclui registro derivado e origem do mesmo aluno com dados sintéticos, não gera ligações novas no banco. A origem e intenção vêm dos campos/serviços canônicos; o menu contextual `Mais ações` entrega a comparação exata entre os IDs autorizados, sem seleção permanente. Cenários adicionais de origem ausente e intenção vazia testam ausência de afirmação inventada. A matriz passa a 43 PHP / 3 HTML, total 46, sujeito a gates e inspeção de screenshots. Não comprova autorização real de registros.

### Editor integral de Processamentos — fonte operacional (15/10/2026, PR #243 draft)

`process-editor` da matriz de 46 telas passa a `tools/browser-fixture/student-process-editor.php?mode=edit`, com `app/student_process_template_editor_view.php` e `app/student_process_template_step_editor.php` idênticos aos consumidos pelo controlador autenticado. O roteiro sintético contém nove cartões de etapa, segunda revelação com reutilização independente, etapa em edição com POST e CSRF, ações reais de mover/remover, inclusão e exclusão separadas. Variantes fora da matriz cobrem leitura, roteiro vazio, associação sem execução e operação personalizada. A auditoria gera início/meio/fim da rolagem de `.student-main` a 320/390/1440 px; confirmação qualitativa exige abrir as imagens do SHA final. A contagem projetada é **44 PHP e 2 HTML em 46 estados**, não homologação da persistência nem do acesso remoto.

### Modo laboratório — renderer da rota autenticada (15/10/2026, PR #243 draft)

A entrada `lab-runner` de 46 estados deixa o HTML `student-process-execution-state-audit.html?state=idle` e passa a `student-process-runner-operational.php?state=idle`, utilizando o mesmo `app/student_process_runner_view.php` de `aluno/processar.php`. A fixture separa explicitamente timer de etapa marcada, oferece 9 etapas e operações reversíveis e nega gravação de dados. A suíte específica cobre 320/390/1440, estados idle/running/paused/elapsed, agitação e editor, inicia/pausa/retoma/reinicia com respostas de timer sintéticas e exige que não aconteça avanço nem movimentação implícita. Imagens de início/meio/fim do painel são geradas pelo workflow, sujeitas à inspeção efetiva. Matriz projetada: **45 PHP, 1 HTML (Material)** em 46 estados.

### Material — última superfície demonstrativa substituída (15/10/2026, PR #243 em draft)

O último HTML da matriz (`student-material-context.html`) foi substituído por `student-material-cms-operational.php?page=current&cohort=cohort-a`. O novo cenário usa a rota visual compartilhada `cms_render_public_page`, a autorização CMS existente, o contexto de matrícula/curso, as relações de páginas/seções-aulas, os estados de liberação e o painel real de anotações `student_material_render_notebook`. Os dados são exclusivamente fictícios. O leitor tem shell CMS próprio, diferente de `student-shell`; sua rolagem é `.cms-student-reading` e o destino global ativo é Cursos. Bloqueio de seção, conta e turma, paginação liberada, navegação e acessibilidade do skip-link têm testes explícitos. **Matriz projetada: 46 fontes PHP e 0 fontes HTML**. A mudança elimina demonstração HTML da matriz, mas não equivale à homologação autenticada da hospedagem; imagens e workflows do HEAD final precisam ser auditados antes da aceitação.
