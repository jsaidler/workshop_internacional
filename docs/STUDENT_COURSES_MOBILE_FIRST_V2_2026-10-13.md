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
