# Comunicações por e-mail — operação

Estado: recursos de comunicações integrados ao CMS; a correção da configuração de envio de 22/10/2026 está em revisão. O status da hospedagem é independente do Git. Referências de autoridade: `docs/COURSE_DOMAIN_REGISTRATION_MATERIAL_ARCHITECTURE_2026-09-27.md`, `docs/PRIVACY_LGPD.md` e `AGENTS.md`.

## O que já existe
- Área administrativa `Admin → Comunicações`: criação/edição de rascunhos, seleção de curso/turma, prévia da audiência, envio de teste, confirmação e fila.
- `Conta → Novos cursos e atividades`: opção promocional inicialmente desmarcada.
- `newsletter-sair.php`: cancelamento por link assinado; uma campanha já enviada não pode ser desfeita.
- `admin/communications-worker.php`: execução **exclusivamente via CLI**, verificando novamente as matrículas e preferências antes de enviar.
- `communication_campaigns` e `communication_deliveries`: status persistente, até três tentativas, deduplicação por campanha/usuário. Status `sent` confirma apenas aceite da API pelo provedor, não entrega à caixa de entrada.

## Habilitar a entrega real
1. Em **Admin → Comunicações → Envio de e-mails**, escolha o nome e um endereço de remetente do seu domínio. Salve e use o botão de teste para a própria conta administrativa. O sistema utiliza por padrão o serviço de e-mail da hospedagem via `mail()`, sem exigir conta Brevo. É necessário que a hospedagem aceite o envio; confirme o recebimento e verifique a pasta de spam.
2. Para usar o Brevo, cadastre a chave API diretamente no painel **Admin → Comunicações → Brevo** e configure um remetente autorizado. Não a inclua no Git, no HTML, nem em mensagens ou logs. O CMS guarda a chave cifrada em armazenamento privado.
3. Verifique o `public_url` HTTPS do CMS no arquivo privado `config/local.php`: ele gera links de descadastro e de acompanhamento. O `curl` do PHP é necessário somente para a integração Brevo.
4. Configure tarefa cron no painel da hospedagem, com caminho absoluto do PHP e do site: `php /CAMINHO/DA/APLICACAO/admin/communications-worker.php --limit=30`. Não utilizar URL HTTP. Comece com cron a cada 15 minutos, conforme limites de seu plano.
5. O painel informa a última execução desse worker e as filas pendentes. Após o teste individual, confirme uma pequena campanha apenas com destinatários autorizados e valide entrega, links e descadastro.
6. Para campanhas em escala, confirme limites diários, supressões, bloqueios, devoluções e reclamações junto ao serviço de envio usado.

## Gerenciar o Brevo diretamente pela administração — 22/10/2026

**Nova autoridade para a configuração do provedor.** O administrador autenticado acessa **Admin → Comunicações → Envio de e-mails → Brevo**, cadastra a chave API v3 no formulário de senha e confirma em **Conectar Brevo**. Antes de substituir a configuração, o CMS testa a chave por uma chamada autenticada a `GET https://api.brevo.com/v3/account`. Se falhar, a chave anterior continua ativa; a interface mostra erro operacional, nunca a credencial. A conta de administrador pode **Substituir chave**, **Testar conexão** ou **Remover chave**. Nome e e-mail do remetente continuam editáveis em **Envio de e-mails**; **Enviar teste** verifica a aceitação da mensagem e o recebimento deve ser conferido no e-mail do administrador.

**Persistência privada:** a migração 106 cria `communication_brevo_credentials`. O valor da chave é cifrado com **AES-256-GCM**, nonce aleatório e tag de autenticação. A chave criptográfica é derivada de `app_secret`, segredo já preservado pela instalação em `config/local.php`; o banco guarda apenas ciphertext/nonce/tag/data de validação. O HTML sempre renderiza o campo de senha vazio e jamais devolve a chave ao navegador. Requisições de alteração usam sessão administrativa e CSRF; a página não deve ser armazenada em cache. A chave também não aparece em logs, métricas nem respostas de erro. **Não modificar `app_secret` arbitrariamente**: isso impede decifrar a credencial já salva, que precisará ser substituída.

**Precedência e compatibilidade:** primeiro uma chave salva no CMS, depois a configuração privada legada `brevo_api_key`/`BREVO_API_KEY`; a chave legada continua funcional sem migração destrutiva. Ao remover a chave salva, uma eventual chave legada no servidor volta a prevalecer. Se nenhuma chave está disponível, permanece o transporte da hospedagem `mail()`. Se a chave Brevo existe mas o remetente é inválido, não alternar silenciosamente de volta à hospedagem; exigir correção do remetente. A integração Brevo utiliza o nome de remetente efetivamente salvo na interface.

**Limites de validação:** autenticar a chave comprova que a API aceita essa credencial; não comprova domínio autenticado, remetente autorizado nem entrega de e-mails. Após configurar o remetente, execute um envio de teste e confirme recebimento. A entrega automática dos avisos requer a tarefa CLI `admin/communications-worker.php` agendada na hospedagem; a tela já exibe a última execução e as filas pendentes. Não apresentar a instalação do CMS, ativação real do cron ou entregabilidade como concluídas por CI ou publicação no GitHub.

**Precedência documental:** esta decisão substitui as instruções anteriores que exigiam edição manual de `config/local.php` ou variáveis de ambiente para cadastrar uma chave Brevo. Essas opções permanecem apenas para instalações legadas ou operação de contingência.

## Semântica
- **Aviso acadêmico**: exige seleção de curso; consulta matrículas ativas e, quando houver turma selecionada, inclui seus ouvintes confirmados elegíveis. Não é afetado pelo opt-out de novidades.
- **Newsletter**: exige opção afirmativa registrada no banco, inclusive quando o administrador seleciona uma turma. Ninguém é inscrito por força de importação CSV, formulário antigo ou compra de curso.
- Destinatários são recalculados ao entrar na fila e antes da tentativa de envio; alterações de vínculo e opt-out invalidam envios pendentes. E-mails não são enviados enquanto se salva curso, revisão ou material.
- Revisão opcional aparece na Agenda e não altera `course_lessons` nem a liberação `released_at`.
- A fila depende de um transporte ativo: o servidor da hospedagem pode enviar via PHP sem credenciais de terceiros. Se o transporte rejeitar um e-mail, a tentativa falha e permanece rastreável para nova tentativa, observados os limites.

## Limitações e decisões pendentes
- A aplicação ainda não recebe webhooks de bounce/reclamação/cancelamento do Brevo. Não considerar taxa de entrega real medida apenas pelo recebimento da API.
- Os links privados para aulas não são inseridos automaticamente em e-mails para evitar circulação indevida de credenciais de reunião. O aluno obtém o URL autorizado na Agenda.
- Não há automação para disparar newsletter no instante da criação de uma turma: o autor decide o conteúdo e confirma o envio.
- A implantação deve ser feita pelo fluxo do CMS: merge após testes, pacote `production-dist` e instalação manual em **Admin → Sistema e atualizações**; merge ou CI isolados não representam produção atualizada.

## Avisos de atividades dos alunos — 21/10/2026

O canal de avisos **aluno → administrador** é independente das campanhas **administrador → alunos**. Após instalação da migração 104:

- Em **Admin → Comunicações → Avisos sobre atividades dos alunos**, cada administrador pode marcar quais eventos deve receber no e-mail da própria conta administrativa. Por padrão, os quatro eventos pedagógicos estão ativados.
- Eventos: dúvida criada, resposta de aluno em dúvida, registro enviado para avaliação e comentário de aluno em registro. Rascunhos, presença, acesso à página, movimentação de estoque, fotos anexadas e etapas de laboratório não geram avisos isolados.
- A fila referencia o objeto original, não copia conteúdo pessoal. Inclui nome do aluno, curso, turma, título e um link de administração; exige login para ler o conteúdo.
- A mesma tarefa cron já usada por `admin/communications-worker.php` processa também o canal administrativo. Preferências desativadas após o evento também são aplicadas antes do envio. O botão **Enviar e-mail de teste** permite verificar o transporte sem simular ação de aluno.
- Quando a chave Brevo está configurada, usa API Brevo; sem ela, usa `mail()` do PHP, tal como o módulo preexistente de alertas de formulário. A disponibilidade de `mail()` e a entregabilidade externa não podem ser inferidas do sucesso dos testes locais. Se o cron não estiver configurado, eventos permanecerão **Na fila**.

A ativação e a entrega devem ser validadas no endereço de e-mail real da conta administrativa depois da instalação. O status `Aceito para envio` não equivale a entrega confirmada ou leitura.

## Correção da experiência de envio — 22/10/2026 (PR de correção)

A tela administrativa não deve exigir que o usuário preencha nomes de variáveis de ambiente para testar o e-mail. A integração Brevo é **opcional**, e a escolha do envio obedece à autoridade única `communication_send_message()`: quando há chave externa e remetente elegível, usa Brevo; caso contrário, usa o `mail()` nativo do PHP, já disponível para avisos de formulários. A função original `communication_send_brevo()` fica restrita à implementação interna do adaptador externo. Os caminhos de teste e envio automático de campanhas, e de avisos de ações de alunos, usam o mesmo transporte.

A seção **Comunicações → Envio de e-mails** permite definir nome/endereço do remetente, testar o envio diretamente à conta administrativa e conferir mensagens pendentes e o horário da última execução do worker. A identidade configurada é persistida em `communication_sender_settings`, migração 105. A credencial Brevo, quando fornecida pela administração, é protegida e persistida exclusivamente cifrada pela migração 106; arquivo local e ambiente permanecem compatíveis com instalações legadas. A página mostra `E-mail da hospedagem` quando o serviço nativo está selecionado.

O resultado de `mail()` ou de uma API externa representa **aceite pelo transporte**, não garantia de entrega nem leitura; o teste precisa ser confirmado na caixa de entrada (inclusive spam). Um e-mail do próprio domínio com DNS autentificado oferece entregabilidade superior; não declarar que a hospedagem enviará corretamente sem teste real.

A mesma migração registra a última execução do worker em `communication_worker_state`. Ausência de registro ou execução antiga sinaliza ao administrador que é preciso configurar/verificar cron na hospedagem; esse indicador não envia mensagens por si só. O agendamento de execução permanece exclusivamente no servidor, não em requisições web de alunos. Mensagens enfileiradas aguardam a execução. As preferências e limites de envio da newsletter não são alterados.
