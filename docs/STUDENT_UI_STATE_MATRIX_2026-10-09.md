# Matriz verificável de telas, estados e dispositivos — Área do aluno

Data: 09/10/2026  
Status: **auditoria em andamento; não aprovada integralmente.**  
Escopo: área autenticada do aluno, componentes compartilhados e fluxos que abrem superfícies transitórias.  
Autoridades: `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md`, `docs/STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md`, `docs/STUDENT_REAL_VISUAL_AUDIT_2026-10-02.md` e `docs/PROJECT_STATE.md`.

## Por que esta matriz existe

A tela de edição da anotação existente foi omitida pela revisão do PR #215. O pipeline verde confirmou apenas estados escolhidos pelo autor dos testes, não a qualidade do produto. **Nenhum workflow verde é aceito como certificado de inspeção humana.**

Uma tela é o produto cartesiano de contexto de dados, tarefa, estado operacional e dispositivo, não uma URL. A matriz de 46 screenshots desktop + 46 mobile é apenas o inventário de superfícies básicas; não constitui aprovação integral de seus estados.

## Critério de aprovação por superfície

Para cada estado aplicável registrar **ambas** as evidências:
1. Funcionamento — interação reproduzida no navegador com entrada válida, inválida e as transições relevantes; comportamento server-side e persistência conferidos quando houver gravação.
2. Inspeção — captura de renderização real com DOM e classes fiéis à aplicação, efetivamente observada em telefone e desktop, verificando leitura, contraste, hierarquia, foco, rolagem até o último controle, barra global, navegação, conteúdo extenso, sobreposições e feedback.

Os estados mínimos, quando aplicáveis, são: **vazio**, **listagem com poucos e muitos registros**, **visualização**, **criação**, **edição**, **desdobramento/accordion/modal**, **confirmação**, **cancelamento**, **erro de validação e erro de serviço**, **salvamento**, **exclusão/desvinculação**, **retorno/histórico**, **permissão/indisponibilidade** e **conteúdo extenso**. Cada estado deve existir em **telefone (390×844)** e **desktop (1440×1100)**. Testes de largura intermediária/teclado físico são suplementares. Estados inaplicáveis devem ser justificados, não ignorados.

## Inventário obrigatório (46 superfícies básicas)

| Família | Superfícies indexadas | Estados que precisam ser cobertos explicitamente |
|---|---|---|
| Acesso e conta | `login`, `activation-password`, `profile`, `password-change` | entrada, formulário incompleto, erro, credencial inválida, sucesso, retorno, sessão expirada |
| Início | `home`, `home-feedback`, `home-study`, `home-multiple` | sem matrícula, 1 ou várias, tarefa pendente, feedback e desvio contextual |
| Cursos | `course-list`, `course-detail`, `course-feedback`, `course-empty` | matriz de matrículas, aulas liberadas/parciais, vazios, retornar, seleção do curso |
| Material | `material` + painel de anotações da página | texto extenso, seleção, vínculo, marca deslocada/alterada/removida, notas gerais e antigas, lista, edição, salvamento, erro, exclusão, anotação→dúvida |
| Dúvidas | `questions-list`, `questions-feedback`, `question-new`, `question-thread` | lista vazia/cheia, privacidade aluno/professor, turma/curso, título/corpo inválidos, nova, conversa, resposta, acesso não autorizado |
| Caderno | `notebook`, `new-record`, `shared-record`, `delete-record` | vazio, várias fichas, criação, cancelamento, exclusão/confirmar, registro compartilhado/privado, retroatividade, voltar |
| Exposição e processamento | `exposure`, `process-choice`, `process-plan`, `process-partial`, `step-editor` | sem plano, selecionar/editar/trocar, etapas parciais, marcação/desmarcação, inputs inválidos, rollback, retorno |
| Resultado e avaliação | `result`, `result-waiting`, `result-revision`, `result-reviewed` | sem resultado, enviado, revisão solicitada, revisão submetida, recebido, dados/fotos/observações |
| Comparação e linhagem | `compare-select`, `compare-records`, `research-derived` | seleção, comparação, diferenças, variação derivada, ações condicionais, retorno |
| Processamentos e laboratório | `process-library`, `process-editor`, `lab-runner` | biblioteca, criação, 9 etapas, edição, reordenação, remoção, andamento, timer, retomada, cancelamento |
| Inventário | `inventory`, `inventory-empty`, `inventory-movement`, `inventory-item-editor` | vazio/cheio, item novo/editar, movimento +/−, quantidade insuficiente, confirmação, erro |
| Preparos e referências | `preparations`, `preparation-editor`, `calibration-list`, `calibration-editor` | listas vazias/cheias, criar, editar, excluir, validar campos, retorno |
| Ferramentas | `tools`, `toolbox` | cronômetro parado/ativo/pausado/zerado, reciprocidade, exposição equivalente, painéis abertos/fechados |

O inventário de rotas canônicas está em `tools/browser-tests/student-complete-area-audit.spec.cjs`. Uma fixture que omita controles presentes na interface real é **não conforme**, independentemente de o teste passar.

## Evidência efetivamente disponível em 09/10

- As 46 superfícies acima foram renderizadas em desktop e telefone na auditoria da revisão anterior: **evidência de composição básica apenas**. Os mosaicos foram examinados; estados adicionais não cobertos permanecem pendentes.
- Para **Material/anotações**, PR #216 adicionou captura de lista, editor de nota persistida, transformação em dúvida e criação de nota geral, nos dois dispositivos, além dos estados de anotação nova já existentes. O acesso à anotação geral estava impossível de alcançar no fim de uma lista longa no desktop; o controle foi deslocado para o início.
- Regressões existentes cobrem uma série de processos e transições, mas **não são equivalentes à inspeção visual de cada um dos estados acima**.
- Nenhuma superfície sem avaliação de seus estados aplicáveis pode ser declarada 'aprovada integralmente'. O PR #216 **não** representa por si só a conclusão da revisão de todas as telas.

## Imutabilidade da auditoria visual (09/10/2026)

Foi identificada uma falha transversal no workflow `student-visual-audit`: a geração do pacote de capturas da Aula 3 terminava em um commit automático na **branch de qualquer PR**, alterando a revisão durante a validação. A etapa de commit foi retirada, e o workflow passou a operar com `contents: read`. A saída visual fica disponível como artefato, sem modificar o código-fonte examinado. A atualização persistente de imagens da Aula 3 exige operação explícita, separada e revisada, no seu próprio escopo. Um PR não pode mudar de SHA por efeito colateral de testes.

## Auditoria por domínio — Dúvidas e formulários (09/10/2026)

**Achado D-01 — fixture incompleta:** `student-secondary-screens-audit.html?screen=question-new` só apresentava `private` e `cohort`; o formulário real `aluno/duvidas.php` também permite `course` (todas as turmas do mesmo curso). A fixture tinha ainda controles sem nomes e campos sem os atributos `required` reais. Corrigidos o markup da fixture, a verificação de paridade PHP e os testes de visibilidade em ambas as viewports. Teste e renderização: `tools/browser-tests/student-complete-area-audit.spec.cjs`, capturas em `student-visual-audit/questions/`.

**Achado D-02 — foco em campo invisível:** o controlador compartilhado `assets/student-workbench.js` aplicava `.focus()` no primeiro `input`, inclusive campos `type=hidden` de formulários que o servidor já preenche automaticamente. A abertura podia manter foco no acionador. O seletor agora busca o primeiro controle visível e habilitado; o teste usa o JS real e verifica foco no assunto após reabrir o painel. Esta correção transversal também alcança painéis de inventário/processos que consomem o mesmo controlador.

**Verificações introduzidas:** estado aberto e cancelado, `aria-expanded`, título/corpo obrigatórios, teto de 180 caracteres, exclusividade do grupo de três rádios, navegação por foco, renderização de cada visibilidade e **listagem simultânea de dúvida privada, da turma e do curso** em telefone 390×844 e desktop 1440×1100.

**Ainda não certificados:** sucesso real de POST, erro de servidor, lista vazia/longa de conversas, professor/aluno com matrículas diferentes, deep link e resposta, reconciliação da conversa depois de exclusão e a ausência de oclusão visual causada pela navegação inferior durante o scroll. Os testes de autorização por curso introduzidos no PR #215 continuam necessários, mas não equivalem a essa inspeção.

## Dúvidas e respostas — preservação de rascunho em erro (09/10/2026)

**Achado D-03 — falha destrutiva de formulário:** o retorno de um POST inválido ou de erro no servidor exibia a mensagem no topo, mas mantinha o painel de criação recolhido e perdia o assunto, registro, título, corpo e visibilidade escolhidos. Na resposta, a falha poderia retornar à listagem porque a reabertura da conversa só consultava `GET.id`, não `POST.question_id`; o texto da resposta também podia se perder. O estado correto mantém a tarefa aberta, os dados digitados e o contexto da conversa, sinalizando a falha com `role=alert`. Os valores são escapados no HTML e a visibilidade continua limitada a `private/cohort/course`.

Exigir inspeção e regressão de: falha de criação, falha de resposta, recuperação do estado, foco e rolagem no telefone, com material de origem e sem material de origem. A fixture sozinha não constitui prova de persistência real no servidor; a apresentação e a semântica da requisição também devem constar de verificações dos templates PHP.

## Regiões de leitura e navegação mobile — auditoria estrutural (09/10/2026)

**N-01 — Oclusão da barra fixa:** a reserva de 66–92 px apenas no final da página não protegia parágrafos, cards ou formulários durante a rolagem. Uma barra `position:fixed` continuava atravessando os conteúdos no meio da leitura. Contrato atualizado em `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md`.

O shell autenticado usa `.student-main` como região rolável abaixo do cabeçalho e acima da barra inferior, e material CMS protegido usa `.cms-student-reading` envolvendo o conteúdo **e o rodapé**. Os dois rolam independentemente do documento; a faixa inferior da barra fica fisicamente fora da região de leitura. A CSS de geometria é centralizada em `student-area.css` e deixa de concorrer com paddings de `student-experience.css` e `student-rendered-fixes.css`. A ação de seleção de texto fica acima da barra no telefone.

**Continuidade funcional:** o controlador AJAX `student-local-actions.js` lê e restaura `scrollTop` da região de leitura mobile, mantendo `window.scrollY` em desktop; o sistema de anotações usa o `scrollTop` da leitura do material CMS. Não foi mudado o significado dos quatro destinos globais nem suas URLs.

**Nova evidência obrigatória:** em `student-complete-area-audit.spec.cjs`, cada superfície mobile autenticada deve ter o fim da região de leitura acima do topo da barra, sem rolagem paralela do documento. Para listas longas, capturar **início, meio e fim** do contêiner; adicionalmente, `student-local-actions.spec.cjs` verifica preservação da posição após salvar e `student-material-context.spec.cjs` registra leitura progressiva.

**Ainda pendente de aprovação:** resultado real dos testes, inspeção crítica das capturas nas 46 superfícies e regressões de Back/Forward, deep link, formulários, estados de teclado virtual e foco. Um teste verde não substitui a inspeção visual por estado.

## Gates que não podem ser flexibilizados

- [ ] Todos os estados do fluxo de anotações, incluindo notas antigas, confirmação de remoção, erro e retorno, renderizados e inspecionados em ambas as viewports.
- [ ] Todas as superfícies da matriz com estados aplicáveis enumerados e evidências de interação + inspeção vinculadas.
- [ ] Nenhuma fixture incompleta usada como prova de fidelidade à superfície real.
- [ ] Telas extensas verificadas no início, meio, fim e com teclado/rolagem do painel, não apenas screenshot de corpo inteiro.
- [ ] Problemas da inspeção resolvidos, telas alteradas reinspecionadas e regressão automatizada verde.
- [ ] Só então considerar revisão completa e autorizar atualização da hospedagem.

**Não utilizar a expressão “auditoria visual completa” enquanto restar qualquer gate sem evidência verificável.**

## Tranche D-04 — registros vinculados em dúvidas e estados de listagem (10/10/2026)

**Achado D-04 — link existente, porém inacessível:** a conversa de dúvida renderizava indiscriminadamente `/aluno/teste.php?id=...`, rota exclusiva do proprietário da ficha. Aluno de outra turma do mesmo curso podia receber esse link para uma ficha compartilhada ou privada; o resultado era navegação para uma tela inacessível. A visibilidade da **dúvida** não concede, por si só, acesso ao **registro** relacionado.

Correção em revisão na branch `audit/student-question-linked-record-2026-10-10`:
- consultar `student_test_accessible_to_student()` antes de apresentar a ação;
- proprietário recebe `/aluno/teste.php`; colega autorizado recebe `/aluno/teste-compartilhado.php`; sem autorização, não apresentar ação;
- na criação, não vincular ficha própria pertencente a outra turma à pergunta atual;
- regressão com banco SQLite para autor, mesma turma, outra turma do mesmo curso, curso diferente, matrícula inativa, ficha privada, ficha compartilhada e registro inexistente;
- fixtures expandidas: criação partindo da lista vazia, listagem extensa (início/meio/fim), seção condicional de conversas de avaliação e conversa com/sem vínculo visível, em telefone e desktop.

**Status de evidências:** implementado na branch de revisão; regressões e inspeção das novas capturas dependem dos respectivos resultados CI e da leitura humana do artefato. Não atribuir aprovação antecipada. O banco real da hospedagem e a atualização instalada não foram examinados.

**Ainda não certificados:** execução autenticada completa com usuários reais de turmas diferentes, retorno da ficha ao contexto de dúvida, lista real com alto volume no servidor, postagem persistente de perguntas e respostas, restrições/erro de serviço em cada dispositivo e todas as demais famílias de telas. A auditoria integral permanece aberta.

### Achado D-05 — contaminação transversal das capturas-base

A inspeção **efetiva** do artefato `student-visual-audit` do PR #222 encontrou a mensagem `Não foi possível enviar a resposta. O texto foi mantido.` na tela **Referências de calibração** e em outras superfícies não relacionadas. A causa foi o predicado de `applyQuestionFailureState()`, no fixture compartilhado `student-secondary-screens-audit.html`: a condição não retornava para telas diferentes de `question-new` e `question-thread`, inserindo um `role=alert` espúrio e podendo disparar erro JavaScript fora do contexto. Capturas antigas afetadas **não são evidência válida de inspeção fiel**.

Em correção nesta tranche: a fixture só aplica o erro quando a combinação tela+estado é explicitamente `question-new/failed-create` ou `question-thread/failed-reply`. A regressão de 46 superfícies verifica erros de JavaScript e ausência de alerta indevido em telas vizinhas; o workflow deve renderizar novamente todo o conjunto, e as novas capturas exigem inspeção efetiva. Este registro não atesta que tal reinspeção já ocorreu.

## Tranche N-01 — legibilidade da navegação global mobile (10/10/2026)

**Achado N-01:** o estilo único das legendas dos quatro destinos persistentes em `assets/student-area.css` fixava `font:500 8.5px/1`, tornando os rótulos excessivamente pequenos nas capturas do telefone. A verificação antiga cobria posição/visibilidade dos controles, mas não estabelecia um limiar de legibilidade das legendas nem garantia de não truncamento a 320 px.

**Correção na branch `audit/student-mobile-navigation-readability-2026-10-10`:** legendas ampliadas para 11 px (entrelinha 1,25), quatro destinos preservados, CSS com autoria única e testes em 320/390 px nos shells comum e Material. O recorte da barra inferior foi validado por CI e inspeção das quatro capturas; desktop e estados funcionais adicionais permanecem nas linhas abertas desta matriz.

**N-01 — corrigido e validado (10/10):** o primeiro CI reprovou por conflito entre `student-rendered-fixes.css` (8,5 px) e `student-area.css` e truncamento de “Laboratório” a 320 px. Foram removidas as três regras concorrentes, consolidando a navegação em `student-area.css`, com `font:600 11px/1.25 var(--body,Arial,sans-serif)` e capitalização normal. No commit `a72b2f8`, `validate`, `student-visual-audit` e `deploy` passaram. As quatro capturas reais foram abertas e inspecionadas (`student-shell` e `material-reader` em 320 e 390 px): quatro destinos legíveis, completos e sem sobreposição visível. Aprovação restrita ao recorte N-01; demais estados da matriz e instalação em hospedagem permanecem pendentes. PR #224 aguardava integração neste registro.

## Tranche D-06 — erro contextual em dúvidas e respostas (10/10/2026)

A inspeção efetiva dos artefatos `questions/phone-create-error-draft.png` e `phone-reply-error-draft.png` da versão integrada #224 identificou uma quebra de contexto: o alerta de erro surgia antes de todo o cabeçalho do curso, enquanto o formulário preservado permanecia abaixo da dobra. O rascunho era mantido, mas a falha não aparecia junto à ação de correção.

**Correção em revisão:** `aluno/duvidas.php` apresenta o erro de criação imediatamente antes do formulário expandido e o erro de resposta imediatamente antes do formulário da conversa; ambos são alertas `role=alert` com foco inicial não tabulável. Erros que não pertencem a um formulário disponível (por exemplo, acesso negado) permanecem no contexto geral. A fixture reproduz o posicionamento e o foco. A ação AJAX de resposta deve preservar o rascunho se o servidor responder HTTP 200 com `ui-alert-error`; `student-local-actions.js` marca seu feedback com `role=alert` e `aria-live`.

**Status:** branch de auditoria em validação; CI, capturas e inspeção da correção ainda pendentes. O teste com resposta HTTP simulada não atesta persistência real na hospedagem nem a execução HTTP autenticada ponta a ponta.

**Primeiro CI D-06:** reprovou duas asserções de paridade porque a fixture `question-thread` não possuía o wrapper `data-student-local-key="question-thread"`, presente no PHP real. O wrapper foi incluído na fixture e adicionado ao gate PHP de paridade. Correção aguarda novos testes e inspeção; a asserção não foi afrouxada.

**Diagnóstico D-06 posterior (CI de `68bd837`):** regressão de resposta AJAX expôs defeito funcional transversal: `form.action` devolvia o elemento `input[name="action"]` em vez da URL, produzindo POST para `[object HTMLInputElement]` e 404. `student-local-actions.js` passa a usar `getAttribute('action')||location.href`, `getAttribute('method')` e `setAttribute('action',...)` em editores contextuais. O teste força o campo oculto homônimo e a resposta HTML 200 com alerta. Correção em revisão, CI e inspeção pendentes.

**Segundo CI D-06 (commit `9334c7e`):** gate estático `test-student-interaction-continuity.php` ainda exigia literalmente `form.action=...` e reprovou a migração para `setAttribute('action',...)`. O gate foi atualizado para **exigir o padrão seguro**, sem retirar a verificação do destino dos editores; o simulador de rascunho passa a declarar o contexto opcional `$question=null` para evitar aviso de variável indefinida. Pendente de nova execução.

**Inspeção efetiva do artefato D-06 de `7130f7f` (10/10):** os estados de erro de criação e resposta foram vistos em telefone e desktop com conteúdo e campos associados na mesma região. A ampliação da captura mobile mostrou que o anel de foco programático do `role=alert` tocava o rótulo do primeiro campo, por falta de margem inferior (`.ui-alert` tinha apenas margem superior). A revisão acrescenta classe própria de contexto `student-question-error`, margem inferior de 20 px e `outline-offset:-3px` mantendo foco e feedback; teste de geometria exige pelo menos 12 px de separação. Reinspeção requerida no commit final.

**Gate visual D-06 verificado no commit `e0afbdd` (10/10):** workflow `student-visual-audit` concluído com sucesso. Capturas reais de `phone-create-error-draft`, `phone-reply-error-draft`, `desktop-create-error-draft` e `desktop-reply-error-draft` foram abertas e inspecionadas na resolução original: alertas adjacentes ao formulário, rascunhos preservados, contorno de foco separado dos rótulos, navegação global visível e sem oclusão da tarefa. Testes de geometria em ambas as viewports exigem ≥12 px entre alerta e formulário. No commit `5720793`, `validate`, `student-visual-audit` e `deploy` passaram, incluindo regressão da colisão `form.action` com `input[name=action]` e erro AJAX de resposta; a validação funcional completa de `e0afbdd` estava em execução no momento deste registro. **Escopo aprovado visualmente: somente os quatro estados D-06**. Permanecem pendentes POST autenticado real com persistência, desempenho/erro de servidor em hospedagem e as demais superfícies/estados da matriz. A integração em produção requer checks verdes no HEAD final do PR #225.

## Registro operacional e tranche M-01 — 10/10/2026

**Versão instalada (confirmação do usuário):** `b3d5cf6b9adb6ffb8e97b972e67c4d442f3c8fa3` (merge do PR #225), cujo manifesto `production-dist/deploy-info.json` tem `sourceSha` coincidente e foi gerado em 2026-10-10T17:19:18+00:00. Essa confirmação vem do responsável pela hospedagem; não ocorreu acesso técnico à instalação, consulta à versão efetivamente servida ou verificação de persistência em banco. O recorte D-06 passa a constar como **instalado por relato do operador**, mantendo as pendências de regressão autenticada real.

**Achado M-01 — falsa completude da captura do Material:** `student-aula3-material-audit.php` era uma fixture SQLite mínima: o fim da Aula 2 era texto sintético, e a captura usava `fullPage:true` sem o shell autenticado. A própria migration 091 altera o documento publicado de teste para apresentar **sete placeholders de infográfico**, cuja existência é explicitamente exigida nos testes. Isso valida espaço e estrutura provisória, **não** a experiência editorial final, a mídia definitiva nem o material efetivamente editado no CMS hospedado.

**Correção em revisão:** aproximação do contêiner autêntico de leitura e navegação (`cms-student-reading`, contexto, quatro destinos, painel final de notas demonstrativo); screenshots em telefone/desktop no **início, meio, fim e notas abertas**, usando scroll interno no telefone. A fixture passa a marcar inequivocamente `data-audit-data-source=migration-fixture-not-live-cms` e o trecho artificial da Aula 2. As interações CRUD das anotações permanecem em sua suíte específica. Não declarar os 46 estados, o conteúdo completo da Aula 2 ou os sete recursos ilustrativos aprovados a partir desta evidência. CI e inspeção da nova tranche ainda pendentes.

**Inspeção M-01 do artefato `bc4b595` (10/10):** o workflow visual estava verde, mas a leitura efetiva das oito imagens mostrou que `desktop/aula2-practice-middle.png` era praticamente idêntica ao início. O teste chamava `window.scrollTo` imediatamente antes de fotografar, sem verificar `scrollY`; a rolagem suave do documento não havia alcançado a posição central. No telefone, as três posições diferiam e a nota final permanecia acima da navegação. Revisão obrigatória: rolagem instantânea na região correta (documento no desktop, leitor CMS no telefone), espera por posição observada, exigência de conteúdo longo e distância mínima entre meio/fim. As imagens originais de `bc4b595` **não aprovam** o estado intermediário desktop; nova execução e reinspeção obrigatórias.

**Inspeção e reinspeção M-01 (10/10):** renderização `bc4b595` gerou oito capturas, mas foi rejeitada visualmente porque `desktop/...middle.png` praticamente duplicava `start.png`. No commit `033fe70`, o teste força rolagem imediata em `document.scrollingElement` no desktop e `cms-student-reading` no telefone, exige conteúdo suficientemente longo, verifica posição alcançada e distância meio/fim. O segundo `student-visual-audit` passou; as oito capturas do novo artefato foram **abertas e inspecionadas**. Desktop agora mostra trechos distintos no início/meio/fim; telefone preserva leitura acima da barra inferior e apresenta anotações acessíveis no fim, com modal demonstrativo aberto. **Aprovação delimitada à geometria da fixture M-01**, sem dar baixa no conteúdo real hospedado, Aula 2 inteira, imagens pendentes, CRUD autenticado de notas ou nas demais famílias da matriz. `validate` ainda estava em conclusão no momento do registro. A instalação de `b3d5cf6` segue confirmada somente pelo relato do operador.


## G-01 — seletor de galeria e envio múltiplo (10/10/2026)

O relato de uso em aparelho real detectou comportamento indevido não coberto pela matriz: “Fotografar ou anexar” forçava câmera devido a `capture="environment"` nos dois inputs de `aluno/teste.php`. O endpoint esperava um único `image`; por isso, acrescentar apenas `multiple` ao input não resolveria o envio. Branch `fix/student-gallery-multiple-upload-2026-10-10`: separar Fotografar e Escolher imagens em Exposição/Resultado, normalizar `images[]` no servidor, validar lote e capacidade total (máx. 6 arquivos; 12 MB cada; JPEG/PNG/WebP) antes da gravação e preservar `phase`. Manter fotografia singular como fluxo compatível. Regressão de navegador no telefone, teste PHP do batch e inspeção visual ainda necessários. Isso não atesta comportamento de selecionador Android em todas as versões/galerias, que precisa de teste no dispositivo após atualização.

**Inspeção real do artefato G-01 do primeiro CI (10/10):** os dois POSTs de seleção múltipla e o POST individual passaram no Playwright e `student-visual-audit` terminou com sucesso. Entretanto, as capturas de 390 e 1440 px mostraram os novos seletores apresentados como palavras pequenas sem contorno de botão: o seletor de estilo do botão base não alcançava suficientemente os controles `<label>`. **Reprovação visual do primeiro commit.** Nova regra específica da propriedade `student-area.css` para o seletor de imagens estabelece `inline-flex`, 48 px de altura, borda, tamanho mínimo de 12 px e foco visível; testes verificam geometria e capturas separadas da cena e do resultado. Não integrar sem CI e reinspeção das quatro novas imagens.

**Segundo CI G-01 (`c217524`, 10/10):** a suíte visual reprovou apenas duas asserções em 390 e 1440 px que exigiam literalmente `display:inline-flex` nos rótulos; o CSS computado apresentou `display:flex`, pois a folha global usa esta variante, igualmente adequada aos botões. O teste foi corrigido para aceitar ambas, preservando os critérios de altura mínima 48 px, borda presente e fonte mínima 12 px. Reexecutar CI e inspecionar capturas, sem enfraquecer os critérios perceptíveis.

**Reinspeção efetiva G-01 (`f38b471`, 10/10):** workflow `student-visual-audit` verde, com quatro capturas reais extraídas e abertas. No telefone a 390×844, ambas as etapas possuem dois alvos separados, delimitados por borda, com 48 px ou mais de altura, sem truncamento e acima da barra global. No desktop a 1440×1100, o layout apresenta as duas ações alinhadas e legíveis. Regressões instrumentam escolha múltipla e câmera singular via `FormData` enviado para a rota simulada. **Aprovação de UI e contrato de envio no navegador**, não atestado de abertura do picker de galeria em Android real ou gravação no banco hospedado. A instalação anterior relatada permanece `b3d5cf6`; publicação e instalação desta correção ainda pendentes.

## S-01 — revisão de arquitetura mobile, não apenas responsividade (10/10/2026)

O diagnóstico do controle de galeria mostrou que a auditoria G-01 aprovou dois botões visíveis quando deveria ter testado a melhor arquitetura de ação contextual. A autoridade de interação passa a ser `STUDENT_MOBILE_INTERACTION_ARCHITECTURE_2026-10-10.md`. **S-01 pendente de teste:** o Caderno terá uma ação visível **Adicionar imagens**, que abre `dialog` transitório adaptado a bottom sheet no mobile, com opções `Fotografar` e `Escolher da galeria`, foco e fechamento acessíveis, falha de envio contextual e preservação do seletor múltiplo. A revisão de todas as famílias ainda exige casos separados e inspeção real; não concluir os 46 itens por adoção do componente compartilhado.

**Inspeção S-01 de gaveta (10/10, commit `5d19313`):** seis capturas renderizadas no CI e inspecionadas manualmente, em `student-visual-audit/sheets/{phone,desktop}-{scene-open,result-open,error}.png`; controles e texto sem corte, escolhas separadas e evidência de erro local. Playwright confirmou ação única fechada, abertura com modal nativo, duas imagens da galeria por fase, câmera singular, Escape, Fechar, retorno do foco, erro sem fechar e seleção novamente. Classificação: **geometria e contrato de UI do componente S-01 aprovados**, não a auditoria dos 46 estados nem persistência em produção ou teste do picker Android. `validate` estava pendente quando registrado. A publicação do PR #228 e a instalação na hospedagem **não foram confirmadas**.

**Inconsistência adicional de fixture S-01 detectada ao olhar as 46 miniaturas (10/10):** os screenshots `exposure`, `process-choice`, `process-plan`, `result` provenientes de `student-caderno-product-audit.html` ainda mostravam “Fotografar ou anexar”, apesar de a rota real já oferecer o controle contextual. A captura anterior não podia ser aceita como fidelidade de produto. O fixture de composição foi separado em template e renderer PHP (`student-caderno-product-audit.php`), que chama diretamente o componente real `student_record_media_controls` para cada fase; a antiga URL HTML redireciona para o renderer preservando `?screen`. Teste de paridade acrescentado para impedir a reintrodução de rótulos legados. **Aguardar CI e inspeção das capturas efetivamente regeneradas** antes de dar baixa nesta divergência. Os demais estados da matriz não são considerados certificados.
