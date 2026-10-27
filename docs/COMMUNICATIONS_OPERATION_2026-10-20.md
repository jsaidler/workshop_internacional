# Comunicações por e-mail — operação

## Central operacional visível - 27/10/2026

Ao abrir **Admin → Comunicações**, a administração deve reconhecer imediatamente as quatro jornadas disponíveis: criar campanha, agendar envio, acompanhar entrega e gerir avisos. O cabeçalho operacional mostra contagens de agendamentos, entregas e devoluções e identifica a caixa acadêmica persistente. Esses indicadores derivam das mesmas campanhas e eventos do sistema; não constituem uma camada paralela de métricas. Em **Mensagens**, a régua Compor → Selecionar → Revisar ou agendar → Acompanhar permanece visível em desktop e celular. O agendamento continua sendo confirmado somente depois de o rascunho ser salvo e a audiência revisada.

## Agendamento, caixa acadêmica e acompanhamento - 26/10/2026

Uma mensagem salva pode ser agendada em **Mensagens → Revisão do envio**. O rascunho fica bloqueado até **Cancelar agendamento** ou até a liberação pelo worker. No horário previsto, o sistema recalcula matrícula, turma, seleção individual, opt-in e disponibilidade de mídia. Falha nessa validação não cria envio parcial: o painel mostra que o agendamento requer atenção e permite cancelá-lo para revisão. O botão de envio imediato não antecipa mensagens agendadas.

Todo aviso acadêmico enfileirado também aparece em **Área do aluno → Notificações**. A caixa interna é persistente, tem estado de leitura por aluno e permanece disponível quando o e-mail daquele aluno está suprimido. Newsletters continuam exclusivamente no canal de e-mail e exigem opt-in.

Com Brevo conectado e a URL HTTPS do site definida, **Configuração → Acompanhamento de entrega** registra o endpoint autenticado `/communications-webhook.php`. O painel diferencia aceite do provedor de entrega confirmada e mostra devoluções ou erros por destinatário. O CMS não configura rastreamento de abertura nem de clique. Hard bounce, endereço inválido, bloqueio e spam criam uma supressão local; futuras campanhas registram esse destinatário como ignorado, sem nova chamada ao provedor. Spam e descadastro também removem o opt-in promocional.

O agendamento com o painel fechado e o recebimento de callbacks dependem, respectivamente, do worker CLI ativo e da acessibilidade pública HTTPS do endpoint. Testes locais e CI usam transportes simulados: não configuram webhook real, não enviam e-mails e não comprovam entregabilidade.

## Enviar agora e cancelar a fila — 22/10/2026 (PR em revisão)

A aba **Avisos dos alunos** também oferece **Enviar avisos agora**, **Cancelar avisos pendentes** e **Reprocessar falhas**. O envio manual não requer cron e processa exclusivamente avisos destinados ao administrador autenticado. Usa o mesmo lock do worker agendado e um destinatário por requisição. Cancelamentos preservam histórico com estado ignorado e motivo identificado. Uma falha de transporte ou interrupção pode ocorrer depois do aceite externo; esses itens não são reenviados automaticamente, e a repetição exige confirmação expressa devido ao risco de duplicação. A página deve permanecer aberta até concluir os lotes. O cron é opcional também para avisos.

O envio encadeado após **Confirmar e enviar agora** ou **Reprocessar falhas** usa sinalizador de sessão de uso único, emitido somente depois de POST administrativo com CSRF; parâmetros GET não acionam disparos. As operações de recuperação de mensagens abandonadas e conclusão de campanhas respeitam a área administrativa selecionada durante o processamento manual. O agendamento da hospedagem continua opcional; mensagens manuais são processadas apenas enquanto o painel estiver aberto. 

O histórico também permite **Reprocessar falhas** somente para destinatários marcados como `failed`, com confirmação expressa sobre a possibilidade de duplicação quando o retorno do provedor foi inconclusivo. A ação não inclui destinatários `sent`, `skipped` ou cancelados e inicia o processamento manual novamente. O histórico também permite **Cancelar pendentes** de uma campanha específica. A fila global não reenvia automaticamente entregas em estado de resultado incerto: falhas do provedor são marcadas com `provider_failure`, mídia indisponível com `media_unavailable`, erros de audiência com `audience_validation_error` e processamentos abandonados com `processing_outcome_unknown`; são preservadas como `failed` para investigação antes de qualquer novo envio. Isso reduz duplicações decorrentes de respostas de rede ambíguas, embora nenhum envio HTTP a provedor externo possa garantir exatamente uma entrega após interrupção de rede sem idempotência comprovada do provedor.


O CMS dispõe dos controles **Enviar agora** e **Cancelar fila** em Visão geral e Mensagens. O botão **Confirmar e enviar agora** inicia uma campanha nova e também processa itens de campanhas previamente enfileiradas. O servidor processa uma requisição de e-mail por chamada administrativa autenticada, preservando o lock do worker CLI e as verificações de matrícula, consentimento, elegibilidade, limite diário e histórico. A interface realiza chamadas sucessivas enquanto permanece aberta. É um **envio manual imediato**, não um agendamento: cron permanece opcional para continuidade periódica quando a tela não está aberta. O envio não continua automaticamente em segundo plano após fechar a página, salvo se um cron independente estiver ativo.

O botão **Cancelar fila** marca entregas ainda pendentes como ignoradas pelo motivo `cancelled_by_admin`, sem exclusão de registros. Entregas já aceitas pelo transporte e aquela em processamento não podem ser canceladas. O cron também passa a reivindicar mensagens uma por vez, permitindo cancelar os demais itens entre as requisições do provedor. O envio manual do administrador é limitado à activity selecionada e não executa nem cancela avisos administrativos gerados por ações dos alunos (canal separado). O histórico diferencia cancelamentos integrais/parciais. Em caso de limite diário atingido, credencial/remetente incorreto ou requisição falha, a fila continua preservada; o painel apresenta pendentes e falhas. Não interpretar aceite API como entrega comprovada.


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
3. Em **Admin → Comunicações → Configuração**, informe o endereço HTTPS do site; ele será usado nos links de descadastro e acompanhamento, sem exigir edição de `config/local.php`. A extensão `curl` do PHP é necessária para a integração Brevo.
4. **Opcional:** somente se desejar continuidade periódica com o painel fechado, configure uma tarefa cron no painel da hospedagem: `php /CAMINHO/DA/APLICACAO/admin/communications-worker.php --limit=30`. Não utilize URL HTTP. Comece com intervalo de 15 minutos, conforme os limites do provedor. **O botão Enviar agora funciona sem cron.**
5. O painel informa a execução do worker opcional e as filas pendentes. Após o teste individual, confirme uma pequena campanha apenas com destinatários autorizados e use **Enviar agora** para iniciar o envio diretamente. Confira o recebimento, os links e o descadastro.
6. Para campanhas em escala, confirme limites diários, supressões, bloqueios, devoluções e reclamações junto ao serviço de envio usado.

## Gerenciar o Brevo diretamente pela administração — 22/10/2026

**Nova autoridade para a configuração do provedor.** O administrador autenticado acessa **Admin → Comunicações → Envio de e-mails → Brevo**, cadastra a chave API v3 no formulário de senha e confirma em **Conectar Brevo**. Antes de substituir a configuração, o CMS testa a chave por uma chamada autenticada a `GET https://api.brevo.com/v3/account`. Se falhar, a chave anterior continua ativa; a interface mostra erro operacional, nunca a credencial. A conta de administrador pode **Substituir chave**, **Testar conexão** ou **Remover chave**. Nome e e-mail do remetente continuam editáveis em **Envio de e-mails**; **Enviar teste** verifica a aceitação da mensagem e o recebimento deve ser conferido no e-mail do administrador.

**Persistência privada:** a migração 106 cria `communication_brevo_credentials`. O valor da chave é cifrado com **AES-256-GCM**, nonce aleatório e tag de autenticação. A chave criptográfica é derivada de `app_secret`, segredo já preservado pela instalação em `config/local.php`; o banco guarda apenas ciphertext/nonce/tag/data de validação. O HTML sempre renderiza o campo de senha vazio e jamais devolve a chave ao navegador. Requisições de alteração usam sessão administrativa e CSRF; a página não deve ser armazenada em cache. A chave também não aparece em logs, métricas nem respostas de erro. **Não modificar `app_secret` arbitrariamente**: isso impede decifrar a credencial já salva, que precisará ser substituída.

**Precedência e compatibilidade:** primeiro uma chave salva no CMS, depois a configuração privada legada `brevo_api_key`/`BREVO_API_KEY`; a chave legada continua funcional sem migração destrutiva. Ao remover a chave salva, uma eventual chave legada no servidor volta a prevalecer. Se nenhuma chave está disponível, permanece o transporte da hospedagem `mail()`. Se a chave Brevo existe mas o remetente é inválido, não alternar silenciosamente de volta à hospedagem; exigir correção do remetente. A integração Brevo utiliza o nome de remetente efetivamente salvo na interface.

**Limites de validação:** autenticar a chave comprova que a API aceita essa credencial; não comprova domínio autenticado, remetente autorizado nem entrega de e-mails. Após configurar o remetente, execute um envio de teste e confirme recebimento. A entrega automática dos avisos requer a tarefa CLI `admin/communications-worker.php` agendada na hospedagem; a tela já exibe a última execução e as filas pendentes. Não apresentar a instalação do CMS, ativação real do cron ou entregabilidade como concluídas por CI ou publicação no GitHub.

**Precedência documental:** esta decisão substitui as instruções anteriores que exigiam edição manual de `config/local.php` ou variáveis de ambiente para cadastrar uma chave Brevo. Essas opções permanecem apenas para instalações legadas ou operação de contingência.

## Comunicações por tarefas e URL do site — 22/10/2026 (correção)

**Correção da causa raiz:** a API transacional `POST /v3/smtp/email` não depende da URL pública do site; `communication_provider_configuration()` não deve exigir `public_url`. Essa URL só é necessária onde o e-mail contém links absolutos, principalmente para o descadastro da newsletter e acesso administrativo dos avisos. O endpoint de teste do Brevo, o cadastro da chave e o teste transacional não podem ser bloqueados por falta desse parâmetro.

**Nova configuração canônica:** migração 107 registra um endereço público HTTPS validado na tabela `communication_site_origin`, editável em **Admin → Comunicações → Configuração → Endereço do site**. O endereço antigo `public_url` do arquivo privado permanece fallback quando válido; o valor definido pelo CMS prevalece. Nunca utilizar `HTTP_HOST` ou `X-Forwarded-Host` da requisição como URL de e-mail: são entradas controláveis por um solicitante e poderiam contaminar links enviados. URLs inválidas, caminhos inesperados, credenciais, parâmetros e fragmentos são recusados. O editor de configuração aponta o campo específico, sem expor nomes internos.

**Envio correto:** o serviço Brevo pode validar a chave e enviar mensagens transacionais mesmo se a URL pública não estiver definida. Antes de enfileirar newsletter, a interface exige URL válida para que o descadastro funcione. Filas antigas de newsletter com URL ausente são mantidas pendentes, sem incrementar tentativas nem bloquear o processamento de avisos acadêmicos. O processamento de notificações administrativas usa a mesma URL validada para gerar links absolutos; não altera o conteúdo privado nem o consentimento.

**Interface:** página `Admin → Comunicações` composta de quatro áreas navegáveis via URL estável: **Visão geral**, **Avisos dos alunos**, **Mensagens** e **Configuração**. A visão geral mostra provedor, filas, execução do trabalhador, ações e problemas reais. Avisos reúne as preferências e histórico administrativo. Mensagens reúne redação, prévia, envio e histórico de campanhas. Configuração contém Brevo, remetente, URL HTTPS, teste individual e estado do trabalhador. A mesma interface é acessível em telas pequenas; os controles são os já existentes no sistema visual do admin, com CSS específico mantido em `assets/admin-operations.css`. Não reproduzir bastidores técnicos nos textos da interface.

**Aceite:** regressões para URL de configuração vazia, validação de conexão Brevo sem URL, links newsletter, preservação da fila, navegação autenticada por abas, gravação do endereço, fluxo de campanha e responsividade. Integração na branch de produção e publicação `production-dist` não significam instalação na hospedagem; entrega real depende de remetente autorizado, teste recebido e rotina automática CLI ativa.


## Composição rica e destinatários individuais — 22/10/2026 (em revisão)

A interface de Mensagens passa a apresentar lista real de alunos elegíveis por filtro de finalidade, curso e turma, com nome, e-mail, busca local, selecionar todos e desmarcar todos. O administrador pode escolher uma única pessoa ou um subconjunto em vez de enviar automaticamente a todos. O botão de revisão exibe a quantidade escolhida e o envio exige rascunho salvo. A busca **não** reduz a seleção: ela filtra visualmente a lista, preservando pessoas já marcadas fora do resultado exibido.

**Autoridade:** `communication_audience()` continua a verificar vínculo de curso, turma e consentimento promocional; o endpoint autenticado `admin/communications-audience.php` só mostra elegíveis, sem expor CPF ou dados privados. A migração 108 adiciona `communication_campaign_targets`, `selection_mode` e `body_html`, sem modificar filas antigas. Rascunhos novos com seleção explícita persistem IDs escolhidos em transação; IDs fora de escopo são rejeitados. Enfileiramento calcula interseção com elegibilidade atual e worker repete esta checagem, sem acrescentar destinatários automaticamente. Rascunhos legados preservam modo anterior (`all`) até que sejam editados com escolha explícita. Nenhum usuário pode contornar o opt-in da newsletter por seleção individual.

**Editor:** composição de texto diretamente em `contenteditable` com ações de negrito, itálico, sublinhado, listas, links HTTPS e remoção de formatação. Armazena HTML sanitizado por allowlist no servidor, sem scripts, estilos arbitrários, imagens externas ou pixels de rastreamento; deriva texto simples alternativo. `communication_send_brevo()` envia conteúdo HTML e texto, e `communication_send_php()` usa MIME multipart/alternative quando há HTML. Rascunhos anteriores em texto simples continuam legíveis e editáveis. O editor mantém textarea fallback se JavaScript estiver desativado.

**Aceite:** testar seleção individual, falta de destinatário, cross-course, consentimento newsletter, persistência, rechecagem pré-envio e privacidade, XSS em HTML e href, HTML/plain enviados ao transporte, navegação e comportamento do editor WYSIWYG em navegador real. Não confundir aceitação do provedor com entrega.

## Semântica
- **Aviso acadêmico**: exige seleção de curso; consulta matrículas ativas e, quando houver turma selecionada, inclui seus ouvintes confirmados elegíveis. Não é afetado pelo opt-out de novidades.
- **Newsletter**: exige opção afirmativa registrada no banco, inclusive quando o administrador seleciona uma turma. Ninguém é inscrito por força de importação CSV, formulário antigo ou compra de curso.
- Destinatários são recalculados ao entrar na fila e antes da tentativa de envio; alterações de vínculo e opt-out invalidam envios pendentes. E-mails não são enviados enquanto se salva curso, revisão ou material.
- Revisão opcional aparece na Agenda e não altera `course_lessons` nem a liberação `released_at`.
- A fila depende de um transporte ativo: o servidor da hospedagem pode enviar via PHP sem credenciais de terceiros. Se o transporte rejeitar um e-mail, a tentativa falha e permanece rastreável para nova tentativa, observados os limites.

## Limitações e decisões pendentes
- O acompanhamento de entrega depende de Brevo, endpoint HTTPS público e configuração explícita no painel; no transporte PHP nativo permanece disponível somente o aceite local, sem callback de entrega.
- A supressão permanece ligada ao endereço que gerou o evento. Corrigir o endereço da conta permite nova entrega sem apagar a evidência anterior; novo opt-in isolado não reabilita um endereço suprimido.
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


## Imagens e modelos visuais de e-mails — 22/10/2026

O editor de **Admin → Comunicações → Mensagens** oferece três modelos de e-mail compatíveis com clientes de correio: **Essencial, Editorial e Destaque**, derivados da identidade do site (nome, cores de texto, fundo e acento) por `cms_site_settings()` e `cms_design_settings()`. O design do site não é copiado como HTML: o renderer usa tabelas de apresentação e estilos inline conservadores, mantendo suporte aos clientes de e-mail. A migração aditiva 109 guarda `template_key` em cada rascunho; registros anteriores assumem `minimal`. O teste ao administrador, a prévia e o dispatch usam o mesmo `communication_email_document()`; alterações no Design afetam novos renders, por isso conferir a prévia antes de despachar.

O editor permite **inserir imagens públicas da Biblioteca**, inclusive via upload direto que reutiliza o `media_create()` canônico (sem armazenamento paralelo). O seletor da mídia é autenticado e sua mutação exige CSRF. O HTML de rascunho armazena exclusivamente tokens estruturados `<figure data-email-media-id="..." data-email-alt="..."></figure>`; URLs de imagem fornecidas pelo editor/cliente não são persistidas, e o sanitizer rejeita `img`, `script`, SVG, estilos arbitrários e fontes externas. A inserção exige texto alternativo, até 250 caracteres; o máximo é 12 imagens por mensagem. Ao salvar e enviar, `communication_media_asset()` verifica `kind=image`, visibilidade pública, não arquivamento, versões processadas e pertencimento à Biblioteca. O renderer escolhe uma derivada JPEG/PNG, quando possível, e gera a URL absoluta usando `communication_site_url()`; mídia privada ou arquivada é bloqueada. Imagens já publicadas em e-mail não podem ser revogadas das caixas de entrada, mas futuras tentativas são bloqueadas quando deixam de ser públicas. Ao inserir imagens é necessário configurar o endereço público HTTPS do site.

A composição fornece prévia **desktop e celular** dentro de um `iframe` sandboxed com `srcdoc` derivado do renderer do e-mail. O HTML enviado à Brevo e pelo fallback PHP é produzido pela mesma função, com versão em texto simples que inclui a descrição das imagens. O editor continua com campo simples acessível quando JavaScript não está disponível. Uploads seguem o pipeline original de mídia (original imutável, derivados reconstruíveis). Regredir seleção de imagem pública, negação de imagem privada/arquivada, URL base segura, escolha/persistência de modelo, sandbox da prévia e integração de transporte.

Esta entrega não inclui construtor universal de páginas, HTML livre, recursos remotos arbitrários, nem automação de templates comerciais. O contrato existente de audiência, consentimento e fila não foi alterado. Código publicado não equivale à instalação no servidor ou confirmação da entrega real.



### Blocos simples e reaproveitamento de mensagens

A barra de edição permite inserir título, separador e botão com link HTTPS, além de texto, listas e imagens. O sanitizer mantém apenas `h2`, `hr` e links com `data-email-button=1` e URL HTTPS válida; o renderer adiciona estilo inline ao botão e ao separador sem aceitar CSS arbitrário do usuário. O histórico oferece **Duplicar**: cria novo rascunho editável com modelo, texto, imagens e interseção dos destinatários ainda elegíveis. Duplicar nunca reutiliza a fila da comunicação original nem adiciona destinatários que não sejam elegíveis atualmente.
