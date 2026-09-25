# Privacidade e LGPD — desenho operacional

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
- eventual uso futuro de marketing, newsletter ou ferramentas de terceiros além das finalidades atuais.

Esses pontos não devem ser preenchidos por suposição no código. Devem ser definidos a partir da operação real e, quando necessário, de orientação jurídica/contábil específica.
