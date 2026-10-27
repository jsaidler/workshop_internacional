# Arquitetura canônica — Curso, inscrição, turma e material didático — 27/09/2026

## Prévia renderizada no servidor para QA — 24/10/2026

O laboratório introduz `communication_builder_render_email()`, renderer PHP controlado que consome exclusivamente MJML aprovado pela gramática canônica, devolve HTML tabular e texto simples, e está exposto somente por endpoint autenticado de prévia. Essa saída está **expressamente excluída da fila de entrega**; não é autorização de substituição do pipeline legado. A paridade de apresentação entre esse renderer e o compilador MJML do navegador precisa de inspeção visual/recebimento em clientes de e-mail antes da promoção.

## Documento visual separado da entrega — 24/10/2026 (PR experimental)

Migração 111 adiciona `communication_builder_drafts` com chave estrangeira para `communication_campaigns`. O documento MJML versionado tem validação canônica no PHP, revisão otimista, referências à versão pública das imagens e pode ser salvo somente se a campanha pertence à `activity` selecionada e permanece em `draft`. **A tabela nunca é consultada pelo envio legado** e não deve se tornar fonte de produção sem pipeline de renderização seguro, equivalência de prévia e verificação de audiência. Campanhas legadas e textos já enviados são imutáveis. Essa tabela descreve um documento editável de laboratório, não um e-mail efetivamente entregue.

## Laboratório de composição por blocos — 22/10/2026 (não publicado)

Como experimento isolado da questão #290, o CMS passa a estudar a edição de e-mails com GrapesJS/MJML, separado do documento `body_html` atualmente salvo e do renderizador `communication_email_document()`. Nenhuma campanha pode usar diretamente o HTML emitido pelo navegador como fonte de envio antes de validação e compilação canônicas no servidor. O construtor experimental não manipula matrícula, consentimento, audiência ou fila. Migrar rascunhos requer preservação exata das mensagens legadas e da identidade de cada imagem por ID da Biblioteca.

Este documento substitui, para regras de negócio, a interpretação transitória em que a página raiz do workshop era usada como entidade `curso`. Páginas continuam sendo a autoridade editorial; `activity` continua sendo apenas o limite técnico do site.

## Princípio estrutural

As responsabilidades são distintas:

- **Página CMS** é conteúdo editorial e continua sendo editada, publicada, versionada e renderizada pelo CMS normal.
- **Curso** é a entidade de domínio que organiza inscrição, turmas, aulas, material e matrículas.
- **Inscrição** representa o interesse/adesão de uma pessoa a um curso e pode existir sem turma definida.
- **Turma** é uma execução concreta do curso.
- **Matrícula** liga a identidade global do aluno a uma turma concreta.

A sequência é:

`pessoa → inscrição no curso → disponibilidade/pagamento → atribuição de turma → matrícula`

Não é:

`formulário → turma padrão → matrícula automática`.

Uma inscrição confirmada ou paga pode permanecer **sem turma** até que a organização das disponibilidades esteja concluída.

## Curso

`courses` é a identidade estável do curso. Ele pode apontar para recursos editoriais já existentes:

- `public_page_id` → página CMS pública do curso;
- `registration_form_id` → formulário CMS usado para inscrição.

Esses recursos não se tornam objetos especiais. A página pública continua sendo `cms_pages`; o formulário continua sendo `cms_forms`.

Criar um curso na administração significa **associar uma página CMS existente à entidade de domínio**. Não cria site, `activity`, renderer, editor ou árvore editorial paralelos.

## Formulário e inscrições

Cada curso pode ter um formulário de inscrição associado. A submissão de um formulário com `purpose='enrollment'` passa a registrar explicitamente `course_id` quando a associação é inequívoca.

`cms_form_submissions` continua sendo o registro original da resposta e preserva:

- formulário utilizado;
- página em que foi enviado;
- snapshot do formulário;
- payload completo, inclusive múltiplas opções de disponibilidade;
- pagamento e estado administrativo.

`course_id` define a qual curso aquela inscrição pertence.

A associação com turma (`cohort_id`) é independente. Nenhuma rotina pode escolher uma turma automaticamente apenas porque a inscrição foi confirmada ou paga.

A matrícula só é criada quando existem simultaneamente:

1. inscrição confirmada/paga;
2. curso identificado;
3. turma explicitamente atribuída;
4. turma pertencente ao mesmo curso.

## Identidade do aluno

`student_users` continua sendo identidade global. A mesma pessoa pode ter várias inscrições e várias matrículas:

`student_user → inscrições em cursos diferentes`

`student_user → course_enrollments → course_cohorts → course`

A administração de um curso mostra apenas as matrículas daquele curso; a identidade não é duplicada.

## Turmas e aulas

`course_cohorts.course_id` e `course_lessons.course_id` são as autoridades de escopo.

`activity_id` e `workshop_page_id` podem permanecer durante a transição para compatibilidade e backfill, mas não definem mais o curso.

A liberação progressiva continua pertencendo à combinação:

`turma + aula → released_at`

Uma aula pode estar bloqueada, agendada ou liberada para cada turma.

## Material didático

Material didático **continua sendo página CMS normal**.

Não existe editor de material, renderer de material ou tabela que replique o conteúdo da página.

`course_material_pages` é somente uma relação:

`course ↔ cms_page`

Assim, uma página pode ser criada/editada normalmente em **Páginas** e associada ao curso em **Curso → Material**. Remover a associação não remove a página.

O ponto de entrada **Curso → Material** é uma visão contextual das mesmas páginas do CMS. O botão **Editar página** abre o editor CMS normal.

## Seções e aulas

As seções continuam sendo as seções existentes no documento CMS, identificadas pelo `data-cms-section` estável.

`course_material_sections` armazena apenas a regra educacional:

`course_id + page_id + section_key → lesson_id`

Não armazena HTML.

Regra de disponibilidade:

- seção sem mapeamento de aula → disponível a qualquer aluno matriculado naquele curso;
- seção mapeada para uma aula → disponível somente quando essa aula estiver liberada para a turma do aluno.

Uma aula pode liberar seções em várias páginas e uma página pode conter seções de várias aulas.

O filtro é obrigatório no servidor: seção bloqueada não deve ser enviada no HTML.

## Administração

A organização principal passa a ser:

`Cursos → curso selecionado → Inscrições / Turmas / Alunos / Aulas / Material`

### Cursos

A tela não cria uma `activity`. Ela associa uma página CMS existente como página pública de um curso e permite ligar um formulário de inscrição existente.

### Inscrições

A tela de inscrições nunca mistura candidatos de cursos diferentes por padrão. Primeiro existe contexto de curso; dentro dele aparecem as inscrições daquele curso.

Disponibilidade, pagamento e turma são dimensões independentes.

### Material

Lista páginas CMS associadas ao curso e suas seções. Associação de seção com aula é feita nesta visão contextual, mas **Editar página** usa o editor normal.

## Migração e compatibilidade

A migração é não destrutiva.

Os vínculos transitórios `workshop_page_id` introduzidos anteriormente são usados apenas para inferir cursos existentes quando isso é inequívoco. O conteúdo editorial não é movido nem duplicado.

`course_page_sections` legado é copiado para o novo relacionamento `course_material_sections` quando a aula já possui curso inequívoco.

Inscrições antigas recebem `course_id` somente quando a associação por formulário/página é inequívoca. Estado ambíguo permanece explícito; não se adivinha por título ou texto.

## Invariantes obrigatórios

1. nenhum fluxo cria `activity` para representar curso;
2. material usa `cms_pages` e o editor CMS existente;
3. inscrição confirmada sem turma não cria matrícula;
4. uma turma só recebe inscrição do mesmo curso;
5. uma página de material só expõe conteúdo a matrícula do curso associado;
6. seção vinculada a aula bloqueada não chega ao HTML;
7. um aluno pode ter inscrições/matrículas em vários cursos sem duplicar identidade;
8. a administração não mistura inscrições de cursos diferentes como lista padrão.


## Agenda integrada — decisão de produto de 12/10/2026 (#234)

A identidade pedagógica continua em `course_lessons` (`course_id`, título, ordem, material e recursos). Cada turma configura a **ocorrência** da mesma aula em `cohort_lesson_releases`, estendida por `migrations/098_course_cohort_agenda.php` com data/hora UTC, fuso IANA, duração, URL, instruções e estado operacional. A Agenda é uma **projeção**, não um novo cadastro de aula. Encontros sem data continuam administráveis mas não aparecem como agendados; data e link nunca são inventados.

`released_at` continua sendo a **única autoridade de liberação** da combinação turma + aula. Marcar um encontro, atribuir um link ou concluir uma transmissão não libera material, seções CMS nem ferramentas protegidas. A ficha única `Cursos → Turma → Aulas` mantém encontro e liberação no mesmo contexto, com escolha explícita de manter/alterar a liberação.

A turma herda `courses.agenda_default_capacity` (10, salvo configuração) ou utiliza `course_cohorts.agenda_capacity`. Vagas são compartilhadas entre estudantes com `course_enrollments.status='active'` e ouvintes com `course_listener_reservations.status='confirmed'`, sem contar duas vezes a mesma pessoa. Os gatilhos SQLite são parte da fronteira transacional: nenhuma outra rota de criação de matrícula pode exceder o limite. Reduzir capacidade não expulsa ninguém; mostra excesso e impede novos ingressos.

O retorno como ouvinte exige identidade ativa e matrícula elegível de outro `cohort_id` do **mesmo curso** (a turma de origem pode ter terminado). A reserva pertence à turma inteira, tem fila cronológica e idempotência `UNIQUE(student_id,cohort_id)`; não cria inscrição financeira, segunda matrícula nem direito a material/testes. A confirmação só ocorre após **ambos** `agenda_enrollment_closes_at` e `agenda_listeners_open_at`; antes disso a fila não ocupa vagas. Cancelamento convoca o primeiro elegível, sob transação SQLite com `BEGIN IMMEDIATE`. O cronograma do encontro é distinto dos prazos comerciais. Links e instruções privados só entram na resposta autenticada de matrícula ativa ou ouvinte confirmado com elegibilidade atual. Ausência de turmas futuras não representa promessa de retorno ilimitado.

A atribuição ou transferência de inscrição paga em `course_registration_assign_cohort()` é transacional: modifica a turma e reconcilia a matrícula no mesmo commit. Se a capacidade impedir a matrícula, desfaz inclusive a troca de turma e apresenta uma mensagem administrativa de indisponibilidade; não é permitido informar sucesso com matrícula recusada. Desvinculação ou desativação de matrícula reavalia ouvintes e libera a capacidade da turma anterior, respeitando a fila.

### Auditoria de atribuição/transfêrencia — 13/10/2026 (#243)

A atribuição de uma inscrição reconcilia **somente a submissão operada**; reconciliações globais de pagamentos e inscrições continuam disponíveis nas operações próprias, mas não são efeitos colaterais de mudar turma. Apenas turma `active` do mesmo curso pode receber nova atribuição. Uma linha de matrícula pertencente a outra submissão ou criada manualmente não pode ter a sua origem substituída por `ON CONFLICT`: a operação deve apresentar conflito explícito. A alteração da submissão, a desativação histórica, a matrícula de destino e as reservas/filas da Agenda são uma seção crítica SQLite `BEGIN IMMEDIATE`; erro por capacidade ou integridade desfaz o conjunto. A desvinculação de turma utiliza a mesma transação e reavalia a fila liberada. Inscrições não confirmadas podem ter turma pré-definida sem criar matrícula. Não alterar autorização/material/liberação `released_at` durante remanejamento.

A reconciliação acionada pelo login/ativação delega à autoridade canônica quando existe o domínio `courses`, proibindo a escolha de turma padrão pelo fluxo legado. O hook de shutdown não pode executar reconciliação global depois das ações `assign_cohort`/`unassign_cohort`; nessas ações, a transação da operação é a única reconciliação de matrícula. O fallback legado permanece somente para schemas sem `courses`.

Quando o pagamento/estado administrativo deixa de autorizar matrícula, a reconciliação desativa a participação vinculada à inscrição e reprocessa a capacidade da turma desocupada, inclusive sua fila cronológica de ouvintes; reconciliação de outras inscrições não faz parte desse evento pontual.

### Identidade de conta ativada versus dados históricos — 15/10/2026

Os dados de uma inscrição confirmada são uma fonte válida para **criar e completar** o perfil de uma conta ainda não ativada. Uma vez ativada a conta (`student_users.activated_at` presente), nome, e-mail, CPF e demais dados passam a ter como autoridade o cadastro do aluno, inclusive quando outras inscrições suas forem reconciliadas. `student_account_upsert_from_submission()` e `student_enrollment_student_from_submission()` devem preservar o perfil ativado e continuar conciliando a identidade global e a matrícula sem duplicar contas. Dados históricos de inscrição não são mecanismos de redefinição de e-mail ou de senha.

A operação de autenticação ainda apresenta um risco arquitetural independente: chamadas gerais de `student_account_reconcile_confirmed_registrations()` no login e na ativação percorrem inscrições sem relação com a tentativa atual. A correção desse efeito colateral deverá separar a reconciliação por eventos administrativos da verificação de credenciais, sem reintroduzir criação implícita de turma ou matrícula. A proteção dos perfis ativados não equivale, por si só, à remoção dessas reconciliações globais.

### Correspondência conjunta de CPF e e-mail — 26/10/2026

Quando não existe `student_id` explícito e confiável na submissão, a descoberta automática de identidade exige que **CPF e e-mail localizem a mesma conta**. Os resultados possíveis são:

- CPF e e-mail apontam para a mesma conta: reutilizar essa identidade;
- nenhum dos dois aponta para conta existente: criar nova identidade com os dois valores;
- somente CPF ou somente e-mail aponta para conta existente: conflito parcial, sem associação ou atualização;
- CPF e e-mail apontam para contas diferentes: conflito de identidade, sem associação ou atualização.

Uma submissão já vinculada por `student_id` não redescobre a identidade. Conta ativada preserva o perfil atual mesmo se a inscrição histórica trouxer valor antigo; qualquer identificador que aponte para outra conta continua bloqueante. Conta ainda não ativada só pode receber complemento de dados quando o par CPF/e-mail confirma conjuntamente o mesmo `student_id` já vinculado.

Conflitos são estados operacionais recuperáveis: importação registra a linha com erro e Inscrições informa que os dados precisam ser revisados. Não se escolhe silenciosamente um identificador como vencedor e não se cria matrícula. Criação/atualização de conta, vínculo da submissão e matrícula pertencem à mesma unidade transacional por inscrição ou linha importada; qualquer falha desfaz todas as escritas dessa unidade. Quando a Agenda estiver disponível, sua trava canônica deve envolver o `SAVEPOINT` da unidade antes de qualquer reconciliação de vagas, evitando iniciar uma segunda transação SQLite na mesma conexão.

### Transação compartilhada de matrícula e identidade — 15/10/2026

Durante a atribuição, transferência ou confirmação de inscrição paga, `course_agenda_with_lock()` é o proprietário do `BEGIN IMMEDIATE` do SQLite. A reconciliação pode atualizar a conta de um aluno já existente (`student_account_upsert_from_submission()` → `student_account_merge_profile_from_payload()` → `student_account_update_profile()`). Essa atualização de perfil **não pode executar outro `BEGIN TRANSACTION` nem confirmar a transação de matrícula por conta própria**.

A atualização de perfil usa `SAVEPOINT student_account_profile_update`: sem transação externa, o `RELEASE` conclui a atualização; dentro de `BEGIN IMMEDIATE`, participa da transação existente. Falhas fazem `ROLLBACK TO SAVEPOINT` para não deixar nome e perfil parcialmente alterados, e a rotina externa mantém a responsabilidade pelo `COMMIT` ou `ROLLBACK` da matrícula completa. Não usar somente `PDO::inTransaction()` para detectar `BEGIN IMMEDIATE` executado por SQL bruto: a detecção varia entre versões de PDO SQLite.

Regressão em `tools/test-student-accounts.php`: aluno existente sob `BEGIN IMMEDIATE`, rollback da operação externa, erro de escrita durante a atualização de perfil e atualização independente fora de transação externa.

### Confirmação financeira e exclusão — revisão de 13/10/2026 (#243)

`confirm`, `pending` e `archive` no painel Inscrições passam a operar somente a submissão selecionada, com pagamento e eventual matrícula na mesma transação de escrita SQLite e sob a trava da Agenda. Uma inscrição paga sem turma continua válida. Uma inscrição paga **com** turma só pode ser confirmada se a matrícula correspondente ficar ativa; falta de vagas, conflito de proveniência ou erro de reconciliação desfazem o estado financeiro e a matrícula, com erro informado no painel. Desativar ou arquivar inscrição reavalia a fila de ouvintes da turma. O hook de shutdown não executa reconciliação em lote após essas ações. Exclusão definitiva remove somente a inscrição e sua matrícula de origem, preserva identidade e registros independentes, e reavalia a fila no mesmo commit. `released_at` não muda. Esta decisão substitui a menção histórica à reconciliação financeira global de `PROJECT_STATE.md`.

### Compatibilidade de migração ao instalar do zero — 17/10/2026

A regressão HTTP autenticada sobre uma instalação temporária identificou uma dependência de ordem: a migração 013 prepara páginas e formulários de inscrição e chama a função CMS de finalidade, mas a coluna de finalidade em cms_forms só é criada na migração 069. A função cms_form_set_purpose passa a aceitar exclusivamente esse estágio pré-069, retornando a finalidade solicitada sem tentar escrever uma coluna inexistente; a migração 069 permanece responsável por persistir a classificação de registros legados a partir de form_key. Em bancos já migrados, o UPDATE explícito segue obrigatório.

O teste tools/test-form-purpose-pre-migration.php protege a transição dos dois schemas e a persistência posterior. A regressão completa de instalação isolada executa as migrations reais; não substituir o problema por uma base SQLite parcial que simplesmente pule a migração 013.

## Encontros complementares / revisão — 20/10/2026 (PR de implementação)

Uma **revisão opcional** não é uma aula curricular adicional. A fonte de verdade é `course_review_sessions` com `course_id`, `cohort_id` opcional, título, data UTC, fuso IANA, duração, URL e estado. A revisão não ganha `course_lessons`, não cria `cohort_lesson_releases`, não altera progresso curricular, não libera seções do CMS e não cria matrículas. A distinção é de domínio; uma única revisão é projetada na Agenda global por `course_review_student_meetings()`, agregada à consulta dos encontros regulares, e editada dentro da superfície existente **Curso/Turma → Aulas**. Não há editor nem calendário concorrente.

Revisões restritas à turma são visíveis somente a matrículas ativas daquela turma e ouvintes confirmados com matrícula ativa em outra turma do mesmo curso. Revisões para o curso inteiro são visíveis a matrículas ativas em qualquer turma desse curso, com deduplicação por aluno. Nenhuma inscrição pendente, pessoa de outro curso ou usuário sem matrícula autoriza a leitura da URL. O agendamento não dispara e-mail sem decisão explícita da administração; a comunicação usa o domínio de comunicações abaixo.

**Comunicação acadêmica × newsletter**: `communication_campaigns` representa um rascunho ou envio para audiência derivada de `courses`, `course_cohorts`, `course_enrollments` e ouvintes confirmados. Aviso operacional exige curso selecionado e não depende do opt-in de publicidade. Newsletter exige `communication_preferences.newsletter_opt_in=1`, marcado ativamente na Conta e revogável no perfil ou link da mensagem. Não reinterpretar consentimento do formulário ou leitura do aviso de privacidade como inscrição em newsletter.

A fila `communication_deliveries` grava um registro por campanha e conta (`UNIQUE(campaign_id,student_id)`); destinatários são verificados novamente antes do envio. O CMS permite processamento **manual imediato**, por ação administrativa autenticada em lotes individuais, e processamento **automático opcional** pelo worker CLI `admin/communications-worker.php`. Os dois caminhos reutilizam o mesmo mecanismo de despacho, limite diário e lock de concorrência, fora das transações de matrícula; o envio manual depende da permanência da página aberta para processar toda a fila. Cancelamento afeta apenas mensagens ainda pendentes, com histórico preservado. URLs privados de encontros não são incluídos automaticamente no corpo das campanhas. Credenciais não pertencem ao Git. A instalação do código não comprova a entrega externa sem teste de recebimento.

### Ajuste do transporte de comunicações — 22/10/2026

A escolha do transporte pertence ao serviço canônico de comunicações, não à identidade do curso ou matrícula. A fila continua isolada do fluxo transacional das ações dos alunos. O administrador pode usar o serviço nativo `mail()` do servidor sem Brevo, ou Brevo se estiver configurado. O status de entrega no CMS informa aceite, não entrega na caixa de entrada. Remetente e monitoramento de cron têm configuração operacional na interface administrativa, enquanto credenciais continuam privadas.

### Agendamento, caixa acadêmica e evidência de entrega — 26/10/2026

`communication_campaigns.scheduled_at` agenda a liberação de um rascunho, não congela sua audiência. O worker só pode converter uma campanha vencida em fila depois de recalcular curso, turma, seleção individual, opt-in e mídia; envio manual comum não antecipa esse instante. Falha de validação mantém o rascunho bloqueado com estado administrativamente recuperável. Cancelar o agendamento remove o bloqueio e não cria entregas.

Avisos operacionais administrador → aluno criam `student_communication_inbox` para cada participante elegível no mesmo lock do enfileiramento. A entrada pertence à conta e campanha, tem leitura individual e continua acessível mesmo se o endereço estiver suprimido. Newsletter nunca entra nessa caixa. Eventos transacionais do Brevo são correlacionados a `communication_deliveries` ou `admin_student_notice_outbox`; aceite, entrega, devolução e leitura são conceitos distintos. Devolução permanente, endereço inválido, bloqueio e spam suprimem somente a combinação conta + endereço, permitindo correção cadastral sem apagar o histórico anterior.

### Brevo gerenciado pelo CMS — 22/10/2026

A credencial do serviço externo pode ser cadastrada em **Admin → Comunicações**, sem acesso do autor a arquivos do servidor. A entidade de envio continua única: `communication_send_message()` obtém a credencial de armazenamento cifrado do CMS, mantendo como contingência a configuração privada legada do servidor. Validação de chave, remetente e envio de teste pertencem à administração, não às ações dos alunos. O cadastro da chave não altera consentimento, audiência, controle de matrículas, enfileiramento, isolamento de domínio ou direitos de acesso. Agendamento real da entrega permanece dependente de cron CLI na hospedagem.

### Correção de Comunicações e URL de site — 22/10/2026 (PR atual)

A antiga tela expunha ao administrador o erro técnico `Configure public_url como endereço HTTPS` ao enviar teste Brevo. O adaptador de e-mail externo erroneamente exigia a URL pública para autenticar e enviar mensagem simples; newsletter necessita dessa URL apenas para o link de descadastro. A correção central removeu a dependência de URL do envio Brevo, criou a migração 107 (`communication_site_origin`, administrável pelo CMS, validado HTTPS, compatível com `config/local.php` sem Host header) e manteve links em newsletter e avisos gerados a partir da origem validada. Newsletter nova sem URL é impedida de entrar na fila; newsletters já enfileiradas aguardam configuração sem consumir tentativas. A página de Comunicações foi dividida em quatro áreas autônomas: Visão geral, Avisos dos alunos, Mensagens e Configuração. Design usa primitivas existentes e CSS de feature em `assets/admin-operations.css`. A mudança não altera públicos-alvo, consentimento nem preferências; serviços API continuam atrás de autenticação e CSRF. Testes de validação HTTPS, ausência de URL, formulários, fila e integração visual foram adicionados. Publicação de pacote não equivale a instalação ou entrega real.



## Destinatários individuais e editor WYSIWYG de Comunicações — 22/10/2026

Revisão de produto: filtro de curso/turma determina uma lista de alunos elegíveis, não implica envio para todos. Em **Comunicações → Mensagens**, o administrador vê nome e e-mail e pode marcar/desmarcar individualmente, selecionar todos ou nenhum e pesquisar, com quantidade conferida na revisão. A migração aditiva 108 persiste os IDs explicitamente escolhidos em `communication_campaign_targets`; `selection_mode` mantém retrocompatibilidade para campanhas anteriores. IDs escolhidos devem continuar matriculados/elegíveis e, em newsletter, com opt-in explícito, no salvamento, enfileiramento e despacho; escolha individual nunca substitui a autorização. O endpoint de leitura da audiência exige autenticação administrativa.

O corpo da mensagem passa a ser editado visualmente: negrito, itálico, sublinhado, listas, links HTTPS e limpar formatação, com persistência de `body_html` estritamente sanitizado no servidor e alternativa `body_text`. Brevo recebe htmlContent + textContent; PHP mail usa multipart/alternative para mensagens ricas. Não permitir scripts, CSS arbitrário, imagens externas, URLs perigosas ou conteúdo de terceiros. Rascunhos históricos em texto simples são mantidos. Testes expandem a segurança e o fluxo real de UI. Publicar/instalar/confirmar entrega são estados distintos.



## Imagens e modelos visuais de e-mails — 22/10/2026

O editor de **Admin → Comunicações → Mensagens** oferece três modelos de e-mail compatíveis com clientes de correio: **Essencial, Editorial e Destaque**, derivados da identidade do site (nome, cores de texto, fundo e acento) por `cms_site_settings()` e `cms_design_settings()`. O design do site não é copiado como HTML: o renderer usa tabelas de apresentação e estilos inline conservadores, mantendo suporte aos clientes de e-mail. A migração aditiva 109 guarda `template_key` em cada rascunho; registros anteriores assumem `minimal`. O teste ao administrador, a prévia e o dispatch usam o mesmo `communication_email_document()`; alterações no Design afetam novos renders, por isso conferir a prévia antes de despachar.

O editor permite **inserir imagens públicas da Biblioteca**, inclusive via upload direto que reutiliza o `media_create()` canônico (sem armazenamento paralelo). O seletor da mídia é autenticado e sua mutação exige CSRF. O HTML de rascunho armazena exclusivamente tokens estruturados `<figure data-email-media-id="..." data-email-alt="..."></figure>`; URLs de imagem fornecidas pelo editor/cliente não são persistidas, e o sanitizer rejeita `img`, `script`, SVG, estilos arbitrários e fontes externas. A inserção exige texto alternativo, até 250 caracteres; o máximo é 12 imagens por mensagem. Ao salvar e enviar, `communication_media_asset()` verifica `kind=image`, visibilidade pública, não arquivamento, versões processadas e pertencimento à Biblioteca. O renderer escolhe uma derivada JPEG/PNG, quando possível, e gera a URL absoluta usando `communication_site_url()`; mídia privada ou arquivada é bloqueada. Imagens já publicadas em e-mail não podem ser revogadas das caixas de entrada, mas futuras tentativas são bloqueadas quando deixam de ser públicas. Ao inserir imagens é necessário configurar o endereço público HTTPS do site.

A composição fornece prévia **desktop e celular** dentro de um `iframe` sandboxed com `srcdoc` derivado do renderer do e-mail. O HTML enviado à Brevo e pelo fallback PHP é produzido pela mesma função, com versão em texto simples que inclui a descrição das imagens. O editor continua com campo simples acessível quando JavaScript não está disponível. Uploads seguem o pipeline original de mídia (original imutável, derivados reconstruíveis). Regredir seleção de imagem pública, negação de imagem privada/arquivada, URL base segura, escolha/persistência de modelo, sandbox da prévia e integração de transporte.

Esta entrega não inclui construtor universal de páginas, HTML livre, recursos remotos arbitrários, nem automação de templates comerciais. O contrato existente de audiência, consentimento e fila não foi alterado. Código publicado não equivale à instalação no servidor ou confirmação da entrega real.

