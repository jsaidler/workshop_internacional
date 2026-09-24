# Área do aluno — estado canônico

## Regra de identidade

Não existe cadastro manual de aluno.

Um participante se torna aluno quando uma inscrição do formulário `registration` é confirmada (`status=converted` ou pagamento confirmado). A confirmação é reconciliada automaticamente com a conta do aluno e com a turma correspondente.

A conta é única entre cursos. A mesma pessoa pode ter várias matrículas sem duplicar credenciais ou preencher novamente todos os dados estáveis do perfil.

## Primeiro acesso

O primeiro acesso usa **e-mail da inscrição + CPF somente com números** como prova inicial de identidade.

O CPF não é senha permanente e nunca é gravado como `password_hash`. Depois da identificação inicial:

1. o aluno lê o Aviso de Privacidade;
2. cria uma senha própria com no mínimo 12 caracteres;
3. a conta é marcada como ativada;
4. os acessos seguintes usam e-mail + senha.

A tentativa de primeiro acesso usa a mesma limitação de tentativas do login. O CPF é indexado no perfil por HMAC para comparação e a cópia recuperável usada no preenchimento de formulários fica criptografada em repouso com chave derivada de `app_secret`.

## Pessoa, turma e matrícula

- `student_users`: identidade da conta e credencial.
- `student_profiles`: dados reutilizáveis de identificação e contato.
- `course_cohorts`: turmas de uma atividade/curso.
- `course_enrollments`: matrícula confirmada de uma conta em uma turma.
- `cms_form_submissions.student_id/cohort_id`: vínculo histórico entre inscrição, conta e turma.

A tabela antiga `student_enrollments` permanece somente para compatibilidade/migração. Ela não é a fonte canônica das novas matrículas.

## Reaproveitamento de dados

O perfil mantém os dados estáveis usados nas inscrições: nome, e-mail, CPF, telefone, Instagram e endereço.

Quando um aluno autenticado abre outro formulário do CMS, esses dados podem ser usados como valores iniciais. O formulário continua editável: o aluno revisa os dados antes de enviar. Respostas específicas do curso — disponibilidade, forma de pagamento, tamanho do suporte e outras escolhas — não fazem parte do perfil reutilizável.

O aluno pode consultar e corrigir o perfil em `/aluno/perfil.php`.

## Conteúdo protegido: páginas normais do CMS

Material didático não usa mais a tabela `student_materials` como sistema editorial paralelo.

O conteúdo é uma página comum de `cms_pages`, com o mesmo rascunho/publicação, slug, editor WYSIWYG e renderer das páginas públicas. A diferença é o campo:

- `access_level=public`: página pública;
- `access_level=enrolled`: exige conta autenticada e matrícula ativa naquela atividade.

Quando uma página é protegida, ela deixa de aparecer automaticamente na navegação pública. O acesso do aluno parte da Área do aluno e continua usando a URL normal do CMS.

A tabela antiga `student_materials` permanece somente para compatibilidade histórica e não deve receber novos conteúdos.

## Caderno técnico do workshop

A migração `048_seed_positive_handbook.php` cria e publica uma página CMS protegida com slug `caderno-positivo-direto` e título **Caderno de processo — Positivo Direto em Filme de Raio-X**.

Esse conteúdo não é um placeholder. A página já nasce com o material técnico do workshop organizado em seções editoriais reais: filme e dupla emulsão, exposição e energia, reciprocidade, EI, imagem latente, reveladores, Parodinal, Brewed Caffenol, caminhos negativo/positivo, solução peracética, cloreto férrico + amônia, segunda revelação, quatro parâmetros, hipótese de desenvolvimento normal, materiais e registro de testes.

A capa permanece visível para qualquer matriculado com acesso à página. As seções de filme/exposição são associadas à `aula-1`; as seções de química, processo e registro são associadas à `aula-2`. A `aula-3` não recebe conteúdo artificial apenas para preencher uma etapa: ela continua reservada à revisão dos resultados, e novos conteúdos podem ser associados manualmente se forem criados.

A página usa apenas diagramas técnicos construídos em HTML/SVG. Não deve receber ilustrações genéricas de câmeras ou equipamento inventado. Fotografias de equipamento só entram quando forem imagens documentais reais do projeto.

## Liberação por aula

O CMS já identifica seções por `data-cms-section`. A área do aluno usa essa identidade existente; não cria um segundo formato de página.

- `course_lessons`: aulas do curso;
- `course_page_sections`: associação entre uma seção da página e uma aula;
- `cohort_lesson_releases`: estado de liberação daquela aula para cada turma.

Se uma seção estiver ligada a uma aula ainda bloqueada, ela é **removida do documento no servidor antes da renderização**. Não existe `display:none`, comentário oculto ou HTML bloqueado enviado ao navegador.

Se uma seção não estiver associada a aula alguma, ela é considerada conteúdo comum e permanece visível para qualquer matriculado com acesso à página.

## Múltiplas turmas

Cada atividade pode ter várias turmas. Uma delas pode ser marcada como padrão para novas inscrições confirmadas.

A inscrição confirmada pode ser movida para outra turma no admin sem recriar a conta do aluno. Se um aluno tiver mais de uma matrícula ativa na mesma atividade, a Área do aluno inclui a identidade da turma ao construir o link da página protegida.

## Mídia privada

Imagens exclusivas de páginas protegidas não devem ser servidas de `/uploads/`.

`student_private_media` guarda metadados no SQLite e o arquivo físico em `storage/student-media/`, caminho já bloqueado para acesso HTTP pelo `.htaccess` raiz.

No conteúdo do CMS a referência persistente é:

`/aluno/media.php?asset=<uuid>`

Antes de a página ser enviada ao aluno, essa referência é transformada em uma URL assinada e temporária vinculada a:

- conta autenticada;
- página;
- turma;
- prazo de validade.

`/aluno/media.php` valida todos esses elementos e somente então lê o arquivo privado e o entrega com `Cache-Control: private, no-store`.

Isso impede uma URL pública permanente para o arquivo. Como em qualquer aplicação web, um usuário autorizado ainda pode copiar visualmente o conteúdo que recebeu.

## Administração

`Admin → Inscrições → Área do aluno` concentra:

- turmas e turma padrão;
- aulas;
- liberação/bloqueio de aulas por turma;
- definição de páginas públicas ou exclusivas de matriculados;
- associação das seções da página às aulas;
- upload de imagens privadas vinculadas a uma página protegida;
- alunos originados de inscrições confirmadas;
- associação/reassociação de inscrições confirmadas a turmas.

Não há formulário de “criar aluno”.

A tela administrativa possui folha visual própria (`assets/admin-student-area.css`), carregada somente no workspace da Área do aluno. O objetivo é preservar a linguagem do restante do admin sem apresentar a operação como uma sequência de controles crus: hierarquia, cartões, linhas, estados e ações têm tratamento visual específico, inclusive em telas estreitas.

## Compatibilidade e migração

A migração `047_student_accounts_cohorts_privacy.php` é aditiva. A migração 046 já publicada não é reescrita.

A 047 cria o novo modelo e migra vínculos antigos de `student_enrollments` para uma turma padrão. A migração 048 adiciona o primeiro material protegido real do workshop dentro do próprio CMS. As tabelas antigas são mantidas para permitir rollback e leitura histórica, mas novos fluxos devem usar o modelo descrito neste documento.

## Limite de proteção

Autenticação, filtro server-side, armazenamento privado, URLs assinadas, `no-store` e `noindex` protegem contra acesso público e redistribuição casual. Nenhum sistema web impede de forma absoluta que um aluno autorizado fotografe a tela ou reproduza manualmente aquilo que conseguiu ler.
