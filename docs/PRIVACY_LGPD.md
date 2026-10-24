# Privacidade e LGPD — desenho operacional

## Teste visual somente para a conta administrativa — 24/10/2026

A prova de e-mail do editor MJML envia uma única mensagem mediante ação e confirmação explícitas, sempre para o endereço associado ao administrador autenticado, nunca para um endereço digitado ou extraído de matrícula. O servidor revalida o documento e a proveniência das imagens, aplica CSRF e um intervalo mínimo de 30 segundos entre tentativas. O transporte existente recebe somente HTML gerado no PHP. Esse teste não adiciona alunos à fila, não amplia o consentimento, não altera inscrições e não permite disparo em massa do layout experimental.

## Armazenamento de composição experimental — 24/10/2026

O MJML criado no construtor pode ser armazenado **apenas mediante salvamento explícito** e somente para uma campanha em rascunho da atividade autenticada. Fica em `communication_builder_drafts`, separado do corpo enviado, com histórico de revisão atual e IDs de mídias públicas. A API exige CSRF para escrita e restringe acesso por atividade e estado; alterações simultâneas não sobrescrevem o documento sem aviso. Não são processados novos destinatários nem capturados dados de alunos adicionais. A opção não gera e-mails, rastreadores ou transmissão ao provedor externo.

## Laboratório isolado de e-mails por blocos — 22/10/2026

O protótipo GrapesJS/MJML exige login administrativo, lê somente a Biblioteca pública pelo endpoint existente e não persiste arquivos de mídia adicionais nem envia e-mails; desde 24/10 permite salvar composições MJML isoladas, sob autenticação/CSRF e com restrição a rascunhos, conforme seção posterior. O novo endpoint experimental de validação recebe MJML somente mediante autenticação e token CSRF, analisa a estrutura no servidor, consulta a Biblioteca para verificar referências públicas e descarta o documento após responder; não há armazenamento. Os dados exibidos no quadro demonstrativo não provêm de alunos. Imagens vindas de URLs arbitrárias são rejeitadas na exportação do laboratório, mas isso não substitui validação no servidor caso a ferramenta evolua para uso efetivo. O projeto não altera controles de opt-in/opt-out, matrículas ou tratamento dos destinatários.

Este documento descreve o comportamento técnico do site em relação a dados pessoais. Ele não substitui avaliação jurídica das obrigações fiscais, contratuais ou regulatórias aplicáveis ao negócio.

## Princípios aplicados

1. **Finalidade** — os dados coletados nos formulários são usados para inscrição, pagamento, organização das turmas, realização do curso, acesso ao material e atividades diretamente ligadas a esses serviços.
2. **Necessidade** — dados específicos de uma inscrição permanecem no registro daquela inscrição; somente dados estáveis de identificação/contato passam ao perfil reutilizável.
3. **Transparência** — a inscrição informa a criação da conta após a confirmação da matrícula e o reaproveitamento revisável de dados. O site possui uma página pública `privacidade` editável no CMS.
4. **Acesso e correção** — o usuário autenticado pode consultar e corrigir o perfil em `/aluno/perfil.php`. O caminho histórico `/aluno/` não altera a natureza da conta: a identidade autenticada pode ser usada por regras de acesso que não dependam de matrícula.
5. **Segurança desde a concepção** — sessão protegida, limitação de tentativas, senha armazenada somente por hash, CPF não utilizado como senha permanente, mídia privada com entrega autorizada e conteúdo protegido sem cache público.
6. **Separação de finalidades** — comunicação necessária à inscrição/curso não deve ser confundida com autorização genérica de marketing.

## CPF

O formulário atual coleta CPF para a operação da inscrição e emissão de documento fiscal.

No modelo de conta:

- o primeiro acesso recebe e-mail + CPF apenas para reconhecer a inscrição confirmada;
- o CPF normalizado é comparado por HMAC (`cpf_lookup_hash`);
- a cópia recuperável destinada ao preenchimento posterior fica criptografada (`cpf_ciphertext`);
- a credencial permanente é uma senha escolhida pelo usuário e armazenada por `password_hash()`;
- o CPF nunca é transformado na senha permanente da conta.

## Registros de inscrição

`cms_form_submissions` continua sendo o registro histórico da inscrição. Ele preserva o snapshot do formulário e as respostas efetivamente enviadas.

A conta autenticada não substitui nem reescreve o histórico da inscrição. Após confirmação, a submissão recebe referências para `student_id` e `cohort_id`. A nomenclatura `student_*` é histórica e não define a abstração global de autenticação.

## Perfil reutilizável

`student_profiles` contém somente os dados estáveis necessários para facilitar novas inscrições:

- CPF;
- telefone;
- Instagram;
- endereço;
- cidade/UF;
- CEP.

Nome e e-mail permanecem em `student_users`.

Não são incorporados automaticamente ao perfil dados específicos de um curso, como disponibilidade, forma de pagamento ou tamanho de material escolhido.

Editar o perfil atual não deve reescrever silenciosamente submissões históricas. Da mesma forma, uma correção administrativa de uma inscrição antiga não deve substituir o perfil atual sem uma ação específica para isso.

## Matrícula

A conta não precisa ser entendida como pertencente a um único curso. A matrícula é a relação entre uma conta e uma turma de uma atividade.

A origem operacional atual de uma matrícula pode ser uma inscrição confirmada ou uma importação histórica administrada. Na reconciliação de inscrições confirmadas, o sistema:

- cria ou encontra a conta pela identidade já conhecida;
- atualiza o perfil reutilizável com os dados confirmados quando apropriado;
- cria/reativa a matrícula na turma;
- vincula a submissão à conta e à turma.

Reverter/cancelar a inscrição desativa a matrícula vinculada sem apagar automaticamente a conta ou o histórico.

## Aviso de Privacidade

A migração 047 cria, quando ausente, uma página pública `privacidade` dentro do próprio `cms_pages`. Ela é conteúdo normal do CMS e pode ser revisada editorialmente sem deploy.

No primeiro acesso, o usuário precisa confirmar que leu o aviso. Esse registro serve como evidência de transparência; não deve ser interpretado como se toda operação do site dependesse juridicamente de consentimento.

## Segurança de conteúdo e autorização

A autorização editorial pertence ao próprio CMS.

Uma página pode ser:

- pública;
- restrita a usuários autenticados;
- restrita a participantes da atividade/curso.

Uma seção pode ainda combinar audiência e disponibilidade:

- pública, autenticada, participante do curso ou turma específica;
- imediata, agendada por data/hora ou controlada pela liberação de uma aula.

Para conteúdo não público:

- é necessária a sessão e/ou matrícula exigida pela regra concreta;
- o renderer remove seções sem autorização **antes de enviar o HTML ao navegador**;
- cabeçalhos HTTP impedem cache público e indexação quando a resposta depende de autenticação;
- esconder conteúdo apenas com CSS não é mecanismo de segurança.

A nomenclatura legada `access_level='enrolled'` pode existir durante a migração, mas não é mais a única forma de proteção nem a autoridade conceitual do sistema.

## Mídia privada

A Biblioteca `media_assets` é a autoridade única para mídia editorial reutilizável. Um asset pode ser público ou privado no próprio gerenciador de mídia. Páginas e slots apenas referenciam assets existentes; conteúdo protegido não possui uploader editorial paralelo.

Mídia editorial privada é entregue somente após autorização adequada. Estruturas históricas como `student_private_media` permanecem apenas para migração/compatibilidade enquanto instalações antigas são consolidadas na Biblioteca.

Fotografias anexadas a testes dos usuários são diferentes: pertencem ao registro experimental individual (`student_test_media`), seguem a visibilidade do teste e não entram na Biblioteca editorial reutilizável.

## Visibilidade e exclusão dos testes

O autor controla a visibilidade de um teste como `private`, `cohort` ou `course`. Essa configuração vale para a ficha, as imagens e a conversa de avaliação/dúvidas.

O autor pode excluir definitivamente um teste próprio. A exclusão deve remover ficha, mensagens, registros de mídia e arquivos físicos associados ao teste, sem afetar a conta ou outras matrículas.

## Retenção e eliminação

O sistema não define um prazo jurídico arbitrário para apagar registros. Prazos de inscrição, pagamento, documento fiscal e histórico contratual dependem das obrigações efetivamente aplicáveis.

Antes de automatizar expiração, deve ser definida uma política de retenção por categoria de dado. Até essa definição, a operação deve permitir tratamento administrativo de solicitações de acesso, correção e eliminação quando juridicamente aplicável.

Estruturas legadas não devem ser apagadas no mesmo release que introduz sua substituição. A migração segue o princípio de adicionar, copiar/backfill, validar, tornar read-only e somente depois remover, conforme `docs/SYSTEM_INTEGRATION_AUDIT_2026-09-25.md`.

## Itens que ainda dependem de decisão operacional/jurídica

- canal público específico para solicitações de privacidade, se for desejado separar esse contato das comunicações usuais;
- prazos de retenção para cada categoria de registro;
- procedimento administrativo para pedidos de eliminação que atinjam registros sujeitos a obrigação de conservação;
- revisão jurídica e operacional do uso de fornecedor externo de e-mail, retenção de logs de envio e contratos de tratamento de dados; veja política de newsletter abaixo.

Esses pontos não devem ser preenchidos por suposição no código. Devem ser definidos a partir da operação real e, quando necessário, de orientação jurídica/contábil específica.

## Comunicações acadêmicas e newsletter — implementação em branch (20/10/2026)

Avisos acadêmicos necessários para cursos e turmas têm finalidade distinta de comunicações promocionais. **Não** existe opt-in de publicidade implícito por inscrição, pagamento, aceite do aviso de privacidade ou presença de e-mail em arquivo histórico. A preferência promocional inicia desativada, pode ser ativada/desativada na Conta e desativada por link pessoal validado no servidor. Uma desativação após a campanha entrar na fila bloqueia o envio ainda pendente; não suprime avisos de serviço necessários.

Os destinatários de campanhas são derivados de matrículas atualmente elegíveis e, quando direcionados a uma turma, ouvintes confirmados desse curso. O provedor recebe somente nome, e-mail, assunto e mensagem estritamente necessários à entrega. A lista completa de alunos, CPF, experimentos e fichas nunca é transmitida ao provedor. Logs internos armazenam status, tentativas e identificador de entrega, sem persistir credenciais nem duplicar conteúdo da mensagem por destinatário. O conjunto ainda requer avaliação de retenção e contrato de tratamento de dados antes de operação real.

A fila pode ser processada imediatamente por ação administrativa autenticada, sem agendamento, enquanto o painel permanece aberto; o worker agendado é opcional. O transporte utiliza Brevo configurado ou serviço PHP da hospedagem, com verificação de elegibilidade e consentimento na origem. A decisão de público permanece no site, não no provedor. É obrigatória verificação funcional do descadastro e da entregabilidade antes da primeira campanha.

## Avisos do aluno ao administrador — 21/10/2026 (em revisão)

O administrador pode processar manualmente somente os avisos de sua conta, cancelar pendentes e reprocessar falhas após confirmação do risco de duplicação. Os avisos guardam status, erro e destinatário; não são apagados para simular cancelamento. O cron é opcional. Essa ação não autoriza envio de conteúdo privado do aluno além dos metadados já previstos.


Alertas operacionais para a conta administrativa do curso têm finalidade de acompanhamento pedagógico, distinta de newsletter e marketing. O evento guarda somente referências e status da entrega. A mensagem enviada inclui nome, curso, turma, identificação do item e link de administração, sem anexos ou corpo da dúvida/comentário. O acesso ao item exige autenticação administrativa. O aluno não precisa aderir à newsletter para que a equipe receba avisos necessários ao acompanhamento de sua matrícula. A habilitação por categoria pertence à preferência individual de cada administrador; não há coleta de comportamento de navegação ou rastreamento de leitura no canal de alertas.

## Credenciais do serviço de e-mail gerenciadas pelo CMS — 22/10/2026

O administrador autenticado pode cadastrar e rotacionar a chave API Brevo pela interface. A credencial é protegida com AES-256-GCM e chave derivada de segredo persistido da instalação, nunca retornando ao HTML, logs, testes públicos ou e-mails. A migration 106 adiciona exclusivamente armazenamento cifrado e estado de verificação, sem modificar a base de alunos ou o consentimento da newsletter. A autenticação da chave consulta apenas a API de conta Brevo, sem enviar dados de estudantes. O provedor recebe informações de destinatários apenas no envio autorizado, respeitando opt-in e regras dos avisos acadêmicos. Separar credencial válida de remetente verificado e entrega comprovada.


## Destinatários individuais e editor WYSIWYG de Comunicações — 22/10/2026

Revisão de produto: filtro de curso/turma determina uma lista de alunos elegíveis, não implica envio para todos. Em **Comunicações → Mensagens**, o administrador vê nome e e-mail e pode marcar/desmarcar individualmente, selecionar todos ou nenhum e pesquisar, com quantidade conferida na revisão. A migração aditiva 108 persiste os IDs explicitamente escolhidos em `communication_campaign_targets`; `selection_mode` mantém retrocompatibilidade para campanhas anteriores. IDs escolhidos devem continuar matriculados/elegíveis e, em newsletter, com opt-in explícito, no salvamento, enfileiramento e despacho; escolha individual nunca substitui a autorização. O endpoint de leitura da audiência exige autenticação administrativa.

O corpo da mensagem passa a ser editado visualmente: negrito, itálico, sublinhado, listas, links HTTPS e limpar formatação, com persistência de `body_html` estritamente sanitizado no servidor e alternativa `body_text`. Brevo recebe htmlContent + textContent; PHP mail usa multipart/alternative para mensagens ricas. Não permitir scripts, CSS arbitrário, imagens externas, URLs perigosas ou conteúdo de terceiros. Rascunhos históricos em texto simples são mantidos. Testes expandem a segurança e o fluxo real de UI. Publicar/instalar/confirmar entrega são estados distintos.



## Imagens e modelos visuais de e-mails — 22/10/2026

O editor de **Admin → Comunicações → Mensagens** oferece três modelos de e-mail compatíveis com clientes de correio: **Essencial, Editorial e Destaque**, derivados da identidade do site (nome, cores de texto, fundo e acento) por `cms_site_settings()` e `cms_design_settings()`. O design do site não é copiado como HTML: o renderer usa tabelas de apresentação e estilos inline conservadores, mantendo suporte aos clientes de e-mail. A migração aditiva 109 guarda `template_key` em cada rascunho; registros anteriores assumem `minimal`. O teste ao administrador, a prévia e o dispatch usam o mesmo `communication_email_document()`; alterações no Design afetam novos renders, por isso conferir a prévia antes de despachar.

O editor permite **inserir imagens públicas da Biblioteca**, inclusive via upload direto que reutiliza o `media_create()` canônico (sem armazenamento paralelo). O seletor da mídia é autenticado e sua mutação exige CSRF. O HTML de rascunho armazena exclusivamente tokens estruturados `<figure data-email-media-id="..." data-email-alt="..."></figure>`; URLs de imagem fornecidas pelo editor/cliente não são persistidas, e o sanitizer rejeita `img`, `script`, SVG, estilos arbitrários e fontes externas. A inserção exige texto alternativo, até 250 caracteres; o máximo é 12 imagens por mensagem. Ao salvar e enviar, `communication_media_asset()` verifica `kind=image`, visibilidade pública, não arquivamento, versões processadas e pertencimento à Biblioteca. O renderer escolhe uma derivada JPEG/PNG, quando possível, e gera a URL absoluta usando `communication_site_url()`; mídia privada ou arquivada é bloqueada. Imagens já publicadas em e-mail não podem ser revogadas das caixas de entrada, mas futuras tentativas são bloqueadas quando deixam de ser públicas. Ao inserir imagens é necessário configurar o endereço público HTTPS do site.

A composição fornece prévia **desktop e celular** dentro de um `iframe` sandboxed com `srcdoc` derivado do renderer do e-mail. O HTML enviado à Brevo e pelo fallback PHP é produzido pela mesma função, com versão em texto simples que inclui a descrição das imagens. O editor continua com campo simples acessível quando JavaScript não está disponível. Uploads seguem o pipeline original de mídia (original imutável, derivados reconstruíveis). Regredir seleção de imagem pública, negação de imagem privada/arquivada, URL base segura, escolha/persistência de modelo, sandbox da prévia e integração de transporte.

Esta entrega não inclui construtor universal de páginas, HTML livre, recursos remotos arbitrários, nem automação de templates comerciais. O contrato existente de audiência, consentimento e fila não foi alterado. Código publicado não equivale à instalação no servidor ou confirmação da entrega real.

