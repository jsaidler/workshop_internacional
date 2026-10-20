# Comunicações por e-mail — operação

Estado: implementado em branch, **não implantado**. Referências de autoridade: `docs/COURSE_DOMAIN_REGISTRATION_MATERIAL_ARCHITECTURE_2026-09-27.md`, `docs/PRIVACY_LGPD.md` e `AGENTS.md`.

## O que já existe
- Área administrativa `Admin → Comunicações`: criação/edição de rascunhos, seleção de curso/turma, prévia da audiência, envio de teste, confirmação e fila.
- `Conta → Novos cursos e atividades`: opção promocional inicialmente desmarcada.
- `newsletter-sair.php`: cancelamento por link assinado; uma campanha já enviada não pode ser desfeita.
- `admin/communications-worker.php`: execução **exclusivamente via CLI**, verificando novamente as matrículas e preferências antes de enviar.
- `communication_campaigns` e `communication_deliveries`: status persistente, até três tentativas, deduplicação por campanha/usuário.

## Habilitar a entrega real
1. Contratar/configurar uma conta Brevo e verificar um domínio/remetente autenticado (SPF, DKIM e DMARC).
2. Definir o remetente verificado e a chave API em **variáveis de ambiente no servidor/cron**: `BREVO_API_KEY` e `BREVO_FROM_EMAIL`. Alternativamente, definir `brevo_api_key` e `email_from` em `config/local.php`, que já está fora do versionamento. **Nunca** armazenar credenciais no repositório, em CSS, HTML ou no CMS.
3. Conferir o `public_url` **HTTPS** da instalação em `config/local.php`, pois ele define o link de descadastro. Verificar se `curl` está habilitado no PHP da hospedagem.
4. Configurar tarefa cron no painel HostGator para executar, com o caminho absoluto do PHP e da aplicação: `php /CAMINHO/DA/APLICACAO/admin/communications-worker.php --limit=30`. Não utilizar URL HTTP para disparar o worker. Começar com cron a cada 15 minutos, de acordo com a quota do plano.
5. Na administração, salvar um rascunho, enviar um teste **apenas** ao administrador, conferir a renderização no provedor, confirmar audiência pequena de contas que realmente optaram por newsletter e verificar entrega e link de descadastro.
6. Monitorar status de rejeição, hard bounce e reclamação no provedor. Antes de escala, validar política de supressão, limite diário e integração de eventos com o fornecedor.

## Semântica
- **Aviso acadêmico**: exige seleção de curso; consulta matrículas ativas e, quando houver turma selecionada, inclui seus ouvintes confirmados elegíveis. Não é afetado pelo opt-out de novidades.
- **Newsletter**: exige opção afirmativa registrada no banco, inclusive quando o administrador seleciona uma turma. Ninguém é inscrito por força de importação CSV, formulário antigo ou compra de curso.
- Destinatários são recalculados ao entrar na fila e antes da tentativa de envio; alterações de vínculo e opt-out invalidam envios pendentes. E-mails não são enviados enquanto se salva curso, revisão ou material.
- Revisão opcional aparece na Agenda e não altera `course_lessons` nem a liberação `released_at`.
- A fila não substitui um provedor: sem credenciais configuradas, o worker falha com erro claro e preserva as mensagens pendentes.

## Limitações e decisões pendentes
- A aplicação ainda não recebe webhooks de bounce/reclamação/cancelamento do Brevo. Não considerar taxa de entrega real medida apenas pelo recebimento da API.
- Os links privados para aulas não são inseridos automaticamente em e-mails para evitar circulação indevida de credenciais de reunião. O aluno obtém o URL autorizado na Agenda.
- Não há automação para disparar newsletter no instante da criação de uma turma: o autor decide o conteúdo e confirma o envio.
- A implantação deve ser feita pelo fluxo do CMS: merge após testes, pacote `production-dist` e instalação manual em **Admin → Sistema e atualizações**; merge ou CI isolados não representam produção atualizada.
