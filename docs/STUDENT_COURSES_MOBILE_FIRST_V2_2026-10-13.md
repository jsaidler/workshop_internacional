# Cursos — reconstrução mobile-first da área do aluno

Estado: implementado na branch `feat/student-mobile-first-rebuild-2026-10-13`, ainda não integrado em produção.

## Intenção e navegação

Cursos é o destino global para estudo e conversas acadêmicas; Agenda é destino independente. A unidade de navegação de Cursos é a turma autorizada, identificada por `cohort_uuid`. O aluno com uma matrícula abre diretamente o workspace dessa turma; o aluno com mais de uma escolhe explicitamente o contexto antes de abrir material. A troca de curso volta à lista, sem inferir seleção.

No telefone (320 e 390 px), a página oferece: título do curso e turma, ligação contextual para Material e Dúvidas, alertas pedagógicos somente quando há fatos que exigem atenção, páginas de material em sua ordem CMS, estado real de cada página e divulgação progressiva das próximas liberações. No desktop, a mesma organização funciona com sidebar global.

## Autoridades de domínio preservadas

- `student_enrollment_list`, `student_enrollment_dashboard_context`: matrículas e seleção exclusivamente autorizadas.
- `student_enrollment_pages_for_enrollment`: páginas acadêmicas no escopo correto, sem revelar páginas de outras turmas.
- `course_material_page_release_summary` e `cms_access_lesson_release_state`: disponibilidade por turma; `available`, `partial`, `scheduled`, `blocked`. A interface informa o estado, mas o acesso continua regulado pelo CMS ao abrir a página.
- `student_feedback_course_attention` e `student_feedback_state`: retornos e dúvidas contextualizados.
- Links de material e dúvidas preservam `cohort=<uuid>`. Não há contadores de progresso inventados, presença presumida, ou mudança da regra de liberação.

## Implementação

- Rota `aluno/cursos.php`: coleta e valida a projeção autorizada.
- `app/student_courses_view.php`: renderer visual compartilhável que não consulta o banco nem decide permissões.
- `assets/student-courses.css`: autoridade CSS própria, carregada pelo shell somente em `/aluno/cursos.php`.
- `tools/browser-fixture/student-courses.php`: mesmo renderer com dados controlados.
- `tools/browser-tests/student-courses-operational.spec.cjs`: estados de matrícula vazia/única/múltipla, materiais disponíveis/parciais/agendados, retorno pedagógico e responsividade em 320/390/1440, temas, overflow, destinos e scoped links.

A rota de edição de dúvidas, o leitor CMS e os processos permanecem existentes e devem ser testados no percurso autenticado, não duplicados nesta view.

## Gates antes do merge

Não integrar até: validação e auditoria visual do SHA final em verde; inspeção de capturas 320/390/1440; navegação entre lista, curso, material e dúvidas; verificação real com aluno de uma matrícula e de várias; manter o PR em draft enquanto Caderno e Laboratório ainda não forem migrados para a v2.

## Auditoria transversal de percurso — 13/10/2026

No PR #243, o teste de navegação entre Início, seleção de turma, material CMS, lista de Dúvidas, conversa individual e retorno foi ampliado em 320, 390 e 1440 px. O primeiro `validate` (`dd6ade4`) detectou um falso percurso: a URL `/aluno/duvidas.php?cohort=...` recebia a fixture de telas secundárias, mas ela exibia Perfil porque escolhia a superfície por `screen` ausente da URL canônica. O teste antigo verificava a URL e a barra, sem garantir que a tarefa acadêmica existia. A fixture agora escolhe lista ou conversa pelo pathname/ID, preserva os links explícitos e a suíte verifica o conteúdo, Back/Forward e links diretos, além de produzir capturas de ambos os estados.

Esta correção melhora a **fidelidade da auditoria de navegação**, mas a fixture de Dúvidas continua sintética e não executa os serviços reais de autorização, postagem nem persistência. O renderer PHP real permanece em `aluno/duvidas.php`; os testes de negócio existentes seguem obrigatórios. A homologação de uma sessão autenticada, com turmas distintas, rascunhos e autorização real, continua pendente. Nenhuma aprovação é presumida antes da verificação dos workflows e das capturas do novo SHA.

### Paridade PHP das telas internas — auditoria em execução

Capturas anteriores de Dúvidas provinham de `student-secondary-screens-audit.html`, com cabeçalho horizontal legado no desktop em vez da sidebar atual. O conteúdo visual de `aluno/duvidas.php` foi extraído para `app/student_questions_view.php`, sem alterar as consultas, autorização, CSRF, POST nem o ciclo de persistência do endpoint. A rota real e `tools/browser-fixture/student-questions-operational.php` consomem o mesmo corpo PHP. A fixture utiliza também o `student_shell_start/end` canônico com projeções controladas; a auditoria básica de lista, formulário e conversa passa a fotografar essas composições. Testes de código que verificavam o HTML inline foram atualizados para examinar rota mais view, mantendo suas verificações.

O runner visual com fixtures **não autentica alunos nem testa permissões e operações de escrita reais**. CI e screenshots no SHA definitivo permanecem gates antes de aprovar este recorte. A matriz completa de estados continua em aberto.

### Continuidade interna de Dúvidas: formas e estados adicionais

Os testes Playwright de Dúvidas anteriormente capturavam o HTML duplicado de `student-secondary-screens-audit.html` para os estados de criação, erro, resposta, listagem longa e registro relacionado. A matriz foi migrada para o renderer PHP compartilhado `student_questions_view.php` via `student-questions-operational.php`, com controles, contexto e shell reais. Dados de cenário continuam sintéticos: a auditoria não executa POST autenticado, nem autorizações efetivas, que permanecem sob os testes de domínio. O gate passa a exigir foco, validação, conteúdo de rascunho, estados de visibilidade, rolagem no fim da lista, registro vinculado e ausência de overflow visual nas capturas, sem aprovar etapas não examinadas.


### Percurso Material → Anotação → Dúvida → Material (16/10/2026)

A leitura de uma dúvida originada em anotação preservava o trecho citado, mas a navegação contextual de Material apontava à **primeira página do curso**, mesmo quando a origem estava em página posterior. Isso não satisfaz o contrato de retorno explícito ao objeto de origem nem garante um destino lógico em deep link.

**Decisão:** junto da origem citada, apresentar um link específico para a página de material de onde veio a anotação; na criação da dúvida a partir da anotação, oferecer retorno equivalente. Para anotação de página inteira sem citação exata, mostrar a página de origem sem inventar um trecho. O retorno inclui `cohort=<cohort_uuid>`; a navegação geral da turma e a barra global de cinco destinos permanecem intactas.

**Autoridade e acesso:** `student_question_source_page()` associa a página registrada na anotação **somente** às páginas do material da matrícula selecionada. O controlador `aluno/duvidas.php` exige `student_enrollment_material_context()` com o mesmo `cohort_uuid` selecionado e `cms_access_page_allowed()` para o aluno atual antes de fornecer a URL `cms_page_url()`. Origem de outro curso, página retirada/não publicada ou acesso perdido não produzem link. Não divulgar IDs da anotação de outro participante, nem prometer navegação até um trecho privado por hash. Preservar mensagens, POST/PRG, visibilidade da conversa e CSRF.

**Aceite:** teste PHP de origem legítima/inválida, fixture com o renderer operacional e exemplos de trecho, nota integral, formulário de criação e origem incompatível, captures mobile 320/390 e desktop 1440, visibilidade/navegação global e regressões de Dúvidas, Material e Cursos. CI e inspeção no SHA final; a fixture não comprova acesso à página do CMS na hospedagem.

### Anotação pessoal ↔ dúvida de turma em materiais compartilhados (17/10/2026)

O aluno pode estudar a mesma página CMS em matrículas diferentes; suas anotações continuam pessoais à página, sem migração nem duplicação por turma. Uma dúvida originada nessa anotação é um objeto independente, ligado a uma **turma de origem** e à visibilidade `private`, `cohort` ou `course`. A associação é singular: a mesma anotação não cria outra dúvida ao mudar de turma.

Ao exibir **Ver dúvida**, responder a uma publicação AJAX ou redirecionar de `duvidas.php?annotation=...`, resolver o destino pelo mesmo contrato do domínio das conversas:
- a turma indicada na página de Material prevalece **apenas se** a pergunta puder realmente ser aberta naquele contexto;
- caso contrário, utilizar a turma de origem quando ainda existir matrícula ativa e acesso;
- uma dúvida `course` pode usar outra matrícula ativa do **mesmo curso**, nunca de curso diferente;
- `private` e `cohort` não ganham visibilidade em outra turma apenas por compartilharem a página CMS;
- quando nenhuma matrícula ativa autorizar a navegação, não criar URL quebrada ou redirecionamento silencioso. O vínculo permanece no banco e não oferece segunda publicação.

A URL canônica da dúvida com `cohort` e `id` é responsabilidade única de `student_question_annotation_url()`, que consulta as matrículas ativas e aplica `student_question_in_cohort_context()`. O JavaScript do Material consome o destino autorizado fornecido pelo servidor; não o reconstrói com a turma corrente para uma dúvida já existente. Permanecem intocados o renderer editorial CMS, a política de acesso às seções, o conteúdo privado da anotação, a identidade do aluno, POST/CSRF e o modelo de visibilidade.

**Aceite:** regressão SQLite de turmas e cursos compartilhados, desativação/arquivamento e ausência de link indevido; testes do HTML e da resposta AJAX; mesmo fluxo em 320/390/1440, captura visual do link e estado sem acesso, três workflows no SHA final e inspeção crítica. Fixture e CI não demonstram sessão autenticada na HostGator.

### Teste HTTP autenticado de Material → Anotação → Dúvida → Material (17/10/2026)

Os testes anteriores cobriam os serviços de domínio SQLite e os renderizadores PHP, mas o navegador usava projeções sem sessão real ou persistência entre requisições. A suíte tools/browser-tests/student-authenticated-material-question.spec.cjs prepara uma instalação **temporária, migrada e isolada**, com configuração e banco SQLite em arquivo exclusivos, e acessa por HTTP os controladores reais: index.php, aluno/login.php, aluno/material-anotacao.php e aluno/duvidas.php. O script de seed CLI tools/browser-fixture/student-authenticated-material-question-seed.php não é exposto pelo servidor e não integra o pacote de distribuição.

O percurso exercita: acesso anônimo redirecionado ao login; autenticação com senha e CSRF da aplicação; leitura da página CMS na segunda turma; criação de anotação e dúvida privada em operação única AJAX; associação persistida no SQLite; acesso à conversa e retorno à página de origem; mesma anotação noutra turma com contexto correto de dúvida; isolamento diante de outro aluno do mesmo curso; revogação da matrícula de origem sem perder a anotação nem criar link ou segundo formulário de publicação; inspeção dos registros persistidos e das capturas nos tamanhos 320/390/1440.

Os dados e credenciais são exclusivamente sintéticos. Esta verificação local não instala arquivos na HostGator nem comprova funcionamento na hospedagem, que permanece dependente de instalação manual, verificação de versão e teste de sessão real.

### Coerência AJAX da anotação ao trocar o trecho por página inteira (17/10/2026)

**Defeito:** em **Tornar geral**, o backend mantém `student_questions.source_annotation_id`, mas a resposta de sucesso antiga incluía `question: null` e o cliente reconstruía o artigo sem vínculo (`renderArticle(note)`). Até recarregar a página, o editor passava a oferecer indevidamente `Transformar em dúvida`, embora o banco já tivesse uma dúvida associada.

**Contrato:** toda mutação que retorna uma anotação existente deve informar também a pergunta singular vinculada, se existir. `aluno/material-anotacao.php` obtém a associação do banco, não da requisição do cliente; envia `question` com identificação mínima e `question_url` calculado exclusivamente por `student_question_annotation_url()`. A URL pode ser vazia se a turma original não for mais acessível. `assets/student-inline-annotations.js` usa ambos os campos para reconstruir o editor ao desvincular o trecho. Com URL autorizada, mantém **Ver dúvida**; sem URL, mantém **Dúvida vinculada — acesso indisponível**; em nenhum dos casos volta a oferecer **Transformar em dúvida**. Não desfazer o vínculo nem criar segundo registro; a anotação passa a ser da página inteira e o CMS permanece sua fonte editorial.

**Aceite:** regressão do percurso HTTP autenticado com duas anotações de seleção vinculadas a dúvidas `private` originadas na turma B, abertas pelo mesmo aluno na turma A do mesmo curso. Uma é tornada geral com B ativa e conserva o link B imediatamente e após reload; a outra é tornada geral após revogar B e exibe indisponibilidade imediatamente e após reload. Testar payload AJAX e contagem/vínculo persistidos no SQLite, capturas 390×844 e desktop, CI triplo no SHA final e inspeção humana. Essa regressão local não autentica sessão na HostGator.

### Continuidade após edição do Material CMS e reassociação de anotação (17/10/2026)

**Defeito de projeção:** a reassociação AJAX de uma anotação de trecho atualiza os dados em SQLite, o destaque e a citação interna do editor, porém mantinha o título visível do artigo com a frase anterior. Somente após reload o título passava a corresponder à nova seleção. Uma ação de reassociação concluída deve atualizar no mesmo frame os metadados do editor a partir da anotação autoritativa retornada pelo servidor, não reaproveitar texto antigo.

**Contrato:** quando um trecho muda ou desaparece do CMS publicado, a anotação e seu eventual vínculo com a dúvida são preservados. A localização automática pode indicar trecho alterado ou removido, sem inventar correspondência. Ao reassociar explicitamente a outro trecho autorizado da mesma página, o serviço persiste os novos dados de âncora e o editor atualiza título, citação, seção e destaque, conservando corpo, identidade e vínculo preexistente da dúvida. A posição de leitura deve ser restaurada e não se cria entrada de histórico para o microestado. Não modificar CMS editorial, permissão de seção ou visibilidade de Dúvidas para contornar um estado visual.

**Aceite:** regressão autenticada em cópia isolada com publicação de revisão real do CMS, anotação de seleção já vinculada a dúvida, confirmação de estado alterado, reassociação pelo seletor de trecho na página, título/citação/destaque atualizados imediatamente e após reload, corpo e vínculo únicos no SQLite, link autorizado preservado; teste de viewport 390 e 1440, capturas inspecionadas no HEAD final e três workflows verdes. A instalação HostGator informada pelo usuário não é medida automaticamente pelo CI.

### Ambiguidade de trechos repetidos após publicação CMS (17/10/2026)

**Risco comprovado no localizador cliente:** `locate()` tratava `block_key + start_offset + quote_exact` como prova definitiva, mesmo quando uma publicação CMS substituía o parágrafo nessa posição por outro com a mesma frase curta. Também priorizava automaticamente o `block_key` anterior e somava pontuação fraca por seção, podendo destacar a ocorrência errada quando a frase se repetia.

**Autoridade e decisão:** o texto editorial do CMS pode mudar, mas a anotação pessoal (corpo, identidade, trecho original, associação com dúvida) não muda automaticamente. Uma âncora no bloco e deslocamento originais só é considerada **confirmada** quando o fingerprint do texto do bloco coincide com `source_block_hash`, a citação confere na posição original **e não há outra ocorrência igualmente fundamentada** no documento atual. Mesmo uma cópia idêntica do bloco original pode ocupar o ordinal antigo após a edição do CMS; nesse caso há ambiguidade e nenhuma marcação automática. Se o fingerprint não estiver disponível, não for equivalente ou o trecho tiver se movido, localizar todas as ocorrências na página entregue ao aluno (já filtrada por permissões no servidor) e compará-las pelo contexto textual `quote_prefix`/`quote_suffix` efetivamente salvo. Permitir realce automático somente diante de uma **correspondência textual suficientemente forte e única** (fingerprint integral do bloco com deslocamento original ou vizinhança exata de ao menos 12 caracteres úteis, não uma palavra curta comum); desempate pelo ordinal antigo, seção ou simples presença da citação não é uma prova. Se houver várias candidatas igualmente plausíveis, ou somente a frase isolada sem contexto confiável, exibir `Trecho não identificado com segurança — reassocie para restaurar o destaque.`, sem marcação automática. Quando não houver citação na página, manter os estados anteriores `Trecho alterado`/`Trecho original removido`.

**Limite epistemológico:** dois blocos editorialmente idênticos são indistinguíveis pela informação que a âncora atual registra. Não presumir identidade sem dados extras ou pedir ao servidor que salve automaticamente novo `block_key` por heurística. A ação **Reassociar** continua explicitamente conduzida pelo aluno e persistida pelo controller autenticado. `source_block_hash` histórico ausente ou inconsistente jamais autoriza atribuir destaque pela posição antiga sozinho; a degradação segura é ambiguidade, não perda da anotação.

**Aceite:** regressão HTTP real com login, SQLite e publicação de duas revisões CMS: (i) novo parágrafo ocupa o ordinal antigo, ainda contém a citação mas perdeu o contexto, enquanto o parágrafo original é transferido a outro bloco — destacar apenas o texto com contexto correto; (ii) dois blocos possuem citação e contexto iguais — não escolher nenhum, informar ambiguidade. Confirmar corpus filtrado, fonte persistida no banco imutável, acesso à dúvida preservado, ações de reassociação disponíveis, capturas 390/1440 e CI triplo no mesmo SHA. Fixtures adicionais podem testar ausência de fingerprint/contexto. HostGator instalada anteriormente não constitui execução dessas regressões.


### Trechos de anotação sobrepostos e seleção com formatação interna (17/10/2026)

**Diagnóstico:** o realce cliente utilizava `Range.extractContents()` em intervalos completos do bloco e iterava notas ordenadas apenas pelo início. Quando os intervalos se cruzavam, um `<mark>` podia conter ou cortar outro `<mark>`, sem resolver a escolha de qual anotação abrir. Quando a seleção atravessava `<strong>`, `<em>` ou `<a>`, o recorte podia desmontar e reconstruir os nós editoriais. O clique em destaque dentro de um link também precisava impedir que a navegação do link competisse com a abertura da anotação.

**Contrato:** o HTML editorial autorizado do CMS continua íntegro em sua estrutura semântica: a marcação da nota não pode mover, duplicar ou remover links, ênfases, atributos e seus destinos. Dividir realces apenas nos nós de texto; dentro de cada bloco, particionar os intervalos em segmentos **não sobrepostos**. Cada segmento expõe todas as identidades de notas que o abrangem, sem aninhar `<mark>`. Um destaque de uma única anotação abre seu editor; quando duas ou mais anotações ocupam o mesmo fragmento, o leitor abre uma escolha explícita das anotações desse fragmento, com opções acessíveis e retorno à listagem. Clique/teclado sobre destaque dentro de `<a>` abre a anotação e não aciona a navegação do link; texto de link fora do destaque mantém a navegação normal. O foco e a posição de leitura não devem saltar indevidamente, e o painel segue como estado transitório, sem URL/histórico inventado.

**Persistência e edição:** o algoritmo de localização canônico, com verificação de identidade/contexto e fallback de ambiguidade, permanece independente da fragmentação visual. A criação e a edição das notas continuam nos controladores PHP autorizados, com `CSRF`; não há migração de banco nem mudança do texto da anotação ou da questão vinculada. Após criar ou reassociar uma nota, realçar novamente sem perda de caracteres nem aninhamento acumulado; ao recarregar, reproduzir o mesmo estado.

**Aceite:** navegador operacional com duas anotações parcialmente sobrepostas, marcação atravessando `strong`, `em` e link, ação do segmento exclusivo, escolha do segmento comum via teclado e ponteiro, preservação de texto/elementos/href e destino do link não selecionado, repetição de `renderHighlights` após mutação e ausência de marcas aninhadas; teste HTTP autenticado de criação real de duas notas parcialmente sobrepostas com revisita à página e inspeção de SQLite. Capturas 320/390/1440 e três workflows no mesmo SHA, com revisão efetiva do material visual. A instalação em HostGator segue sendo ação independente do usuário.

### Seleção por arrasto dentro de destaque já existente (17/10/2026)

**Falha investigada:** Chromium produz `click` no `<mark>` ao término de um arrasto que seleciona texto dentro dele. Um handler que sempre abre a anotação no `click` disputa a seleção que o aluno pretendia anotar. Os testes anteriores construíam o `Range` por JavaScript e não reproduziam esse gesto real.

**Contrato:** selecionar texto sobre um destaque, inclusive para criar outra anotação contida ou parcialmente sobreposta, deve manter o painel fechado e oferecer **Anotar seleção**. Um clique intencional sem seleção continua abrindo a nota, bem como Enter/Espaço sobre o destaque focado. Se o destaque estiver dentro de um link editorial, impedir a navegação acidental durante o gesto de seleção, sem alterar o link, seu texto ou sua navegação fora do realce. Não desativar a seleção via CSS, nem sacrificar o acesso às anotações existentes.

**Aceite:** reproduzir em Playwright com `page.mouse` real sobre `<mark>` previamente renderizado, comprovar que houve texto selecionado e não houve abertura do painel, abrir o compositor de nova nota, cobrir clique de ativação e teclado e exigir regressões autenticadas e inspeção visual quando houver alteração. A reprodução isolada do evento DOM não substitui os testes da aplicação.
