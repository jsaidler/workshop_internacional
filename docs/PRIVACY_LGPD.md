# Privacidade e LGPD — desenho operacional

Este documento descreve o comportamento técnico do site em relação a dados pessoais. Ele não substitui avaliação jurídica das obrigações fiscais, contratuais ou regulatórias aplicáveis ao negócio.

## Princípios aplicados

1. **Finalidade** — os dados coletados nos formulários são usados para inscrição, pagamento, organização das turmas, realização do curso, acesso ao material e atividades diretamente ligadas a esses serviços.
2. **Necessidade** — dados específicos de uma inscrição permanecem no registro daquela inscrição; somente dados estáveis de identificação/contato passam ao perfil reutilizável.
3. **Transparência** — a inscrição informa a criação da conta após a confirmação da matrícula e o reaproveitamento revisável de dados. O site possui uma página pública `privacidade` editável no CMS.
4. **Acesso e correção** — o aluno autenticado pode consultar e corrigir o perfil em `/aluno/perfil.php`.
5. **Segurança desde a concepção** — sessão protegida, limitação de tentativas, senha armazenada somente por hash, CPF não utilizado como senha permanente, mídia exclusiva fora do webroot e páginas protegidas sem cache público.
6. **Separação de finalidades** — comunicação necessária à inscrição/curso não deve ser confundida com autorização genérica de marketing.

## CPF

O formulário atual coleta CPF para a operação da inscrição e emissão de documento fiscal.

No modelo de conta:

- o primeiro acesso recebe e-mail + CPF apenas para reconhecer a inscrição confirmada;
- o CPF normalizado é comparado por HMAC (`cpf_lookup_hash`);
- a cópia recuperável destinada ao preenchimento posterior fica criptografada (`cpf_ciphertext`);
- a credencial permanente é uma senha escolhida pelo aluno e armazenada por `password_hash()`;
- o CPF nunca é transformado na senha permanente da conta.

## Registros de inscrição

`cms_form_submissions` continua sendo o registro histórico da inscrição. Ele preserva o snapshot do formulário e as respostas efetivamente enviadas.

A conta do aluno não substitui nem reescreve o histórico da inscrição. Após confirmação, a submissão recebe referências para `student_id` e `cohort_id`.

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

## Matrícula

A conta não é criada por um formulário administrativo separado. A origem é a inscrição confirmada.

A reconciliação observa as inscrições `registration` confirmadas e:

- cria ou encontra a conta pela combinação de identidade já conhecida;
- atualiza o perfil com os dados confirmados;
- cria/reativa a matrícula na turma;
- vincula a submissão à conta e à turma.

Reverter/cancelar a inscrição desativa a matrícula vinculada sem apagar automaticamente a conta ou o histórico.

## Aviso de Privacidade

A migração 047 cria, quando ausente, uma página pública `privacidade` dentro do próprio `cms_pages`. Ela é conteúdo normal do CMS e pode ser revisada editorialmente sem deploy.

No primeiro acesso, o aluno precisa confirmar que leu o aviso. Esse registro serve como evidência de transparência; não deve ser interpretado como se toda operação do site dependesse juridicamente de consentimento.

## Segurança de conteúdo

Páginas didáticas protegidas usam `cms_pages.access_level=enrolled`.

Para essas páginas:

- é necessária sessão ativa;
- é necessária matrícula ativa na atividade;
- cabeçalho HTTP impede cache público e indexação;
- seções ainda não liberadas são retiradas do HTML no servidor;
- mídias exclusivas ficam em `storage/student-media/` e são entregues apenas por endpoint autenticado com assinatura temporária.

## Retenção e eliminação

O sistema não define um prazo jurídico arbitrário para apagar registros. Prazos de inscrição, pagamento, documento fiscal e histórico contratual dependem das obrigações efetivamente aplicáveis.

Antes de automatizar expiração, deve ser definida uma política de retenção por categoria de dado. Até essa definição, a operação deve permitir tratamento administrativo de solicitações de acesso, correção e eliminação quando juridicamente aplicável.

## Itens que ainda dependem de decisão operacional/jurídica

- canal público específico para solicitações de privacidade, se for desejado separar esse contato das comunicações usuais do workshop;
- prazos de retenção para cada categoria de registro;
- procedimento administrativo para pedidos de eliminação que atinjam registros sujeitos a obrigação de conservação;
- eventual uso futuro de marketing, newsletter ou ferramentas de terceiros além das finalidades atuais.

Esses pontos não devem ser preenchidos por suposição no código. Devem ser definidos a partir da operação real e, quando necessário, de orientação jurídica/contábil específica.
