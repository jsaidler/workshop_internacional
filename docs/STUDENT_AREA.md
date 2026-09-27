# Usuários, cursos e testes — estado canônico

Este documento é a autoridade funcional para contas autenticadas, matrículas, turmas, aulas e registros de testes. A regra central é: **cada domínio permanece na interface que já é sua autoridade**. Não criar uma segunda interface para páginas, seções, mídia, design ou navegação dentro da Área do aluno.

## Autoridades do sistema

- **CMS / Editor de páginas** — páginas, seções, estrutura editorial, acesso de página, acesso de seção e disponibilidade/agendamento de seção.
- **Design** — tipografia, cores e tema. Área autenticada reutiliza os tokens globais; CSS específico define somente layout/comportamento.
- **Biblioteca de mídia** — única autoridade para mídia editorial reutilizável, pública ou privada.
- **Contas autenticadas** — identidade, sessão, perfil e senha. O termo histórico `student_*` ainda existe no código, mas “aluno” não é a abstração global de autorização.
- **Cursos / atividades** — vínculo do usuário com um curso.
- **Turmas** — agrupamento de matrículas dentro do curso.
- **Aulas** — unidade didática e condição de liberação por turma.
- **Testes** — registro experimental do usuário, fotografias do teste, visibilidade e conversa de avaliação.

Antes de criar qualquer tela administrativa nova, deve-se verificar se a entidade já possui interface canônica no CMS. Se existe editor de seção, autorização da seção pertence a ele. Se existe Biblioteca de mídia, conteúdo protegido não ganha uploader próprio.

## Contas, perfil e matrículas

`student_users` representa hoje a conta autenticada; `student_profiles`, dados reutilizáveis; `course_cohorts`, turmas; `course_enrollments`, matrículas. A nomenclatura histórica não altera a regra: uma conta pode futuramente acessar conteúdo autenticado que não pertença a um curso.

Uma pessoa entra no sistema por inscrição confirmada ou importação histórica por CSV. O primeiro acesso usa e-mail + CPF como prova inicial de identidade; depois o usuário cria senha própria.

### Experiência de Conta

`Conta` é o destino autenticado para o gerenciamento da identidade global e não pertence a um curso específico. A tela reúne três responsabilidades já existentes, sem criar backend paralelo:

- **Dados pessoais** — perfil global reutilizável em novas inscrições;
- **Segurança** — acesso à alteração de senha pelo fluxo canônico `/aluno/senha.php`;
- **Sessão** — encerramento da sessão pelo endpoint canônico `/aluno/logout.php`.

O formulário de perfil usa a grade responsiva da Área do aluno. No celular, os campos passam a uma única coluna; não existe grid inline de desktop que ignore as regras responsivas.

A ação **Sair** precisa permanecer encontrável no celular mesmo que o logout rápido do cabeçalho desktop esteja oculto nesse viewport. Ambos usam o mesmo endpoint `POST` e o mesmo escopo CSRF `student-logout`.

A alteração de senha iniciada por `Conta` retorna para `Conta` após sucesso. A tela de alteração autenticada também oferece retorno explícito para `Conta`. O fluxo de primeiro acesso continua separado: enquanto a conta ainda está sendo ativada, não se apresenta navegação de conta autenticada.

## Turmas

A turma mantém identidade própria e pode ter nome, slug, estado, início, fim, observações e definição como padrão alterados sem recriar matrículas.

## Aulas e liberação por turma

A administração de **Aulas** gerencia somente a aula e seu estado de liberação para cada turma. `cohort_lesson_releases.released_at` possui três estados sem criar colunas artificiais:

- `NULL` — bloqueada;
- data/hora no futuro — agendada;
- data/hora igual ou anterior ao momento atual — liberada.

A interface de Aulas oferece **Bloquear**, **Liberar agora** e **Agendar**. A autorização no servidor compara a data/hora real; um `released_at` futuro não conta como aula liberada.

## Dashboard do aluno

A primeira tela autenticada deve representar as mesmas regras de autorização e disponibilidade usadas pelo backend. Ela não possui interpretação própria de `released_at` nem uma lista paralela de material.

Para cada matrícula:

- `NULL` aparece como aula aguardando/bloqueada;
- uma data futura aparece como **agendada** e não entra no total de aulas liberadas;
- uma data passada ou presente aparece como **liberada** e entra no total;
- a classificação deriva de `cms_access_lesson_release_state()`.

A lista de material da matrícula deriva das páginas publicadas do CMS:

- páginas `activity`/legado `enrolled` da atividade;
- páginas `cohort` apenas quando `access_cohort_id` corresponde à turma da matrícula.

Páginas públicas, páginas apenas `authenticated`, páginas de outra turma, páginas arquivadas ou sem documento publicado não são apresentadas como material daquele curso no dashboard. Isso não muda a autoridade de acesso do CMS; apenas impede que a Área do aluno anuncie conteúdo diferente do que a própria matrícula autoriza.

### Contexto de curso e turma

A Área do aluno não persiste um “curso atual” como nova entidade. O contexto é a própria matrícula existente, identificada pelo `cohort_uuid` na navegação.

- com uma única matrícula ativa, o workspace dessa matrícula abre diretamente;
- com mais de uma matrícula, `Cursos` primeiro funciona como seletor e o usuário escolhe qual matrícula deseja abrir;
- um `cohort_uuid` só pode selecionar uma matrícula que já esteja presente na lista ativa da conta;
- trocar de workspace não altera autorização nem matrícula no banco;
- ao selecionar um curso, o shell recebe a atividade correspondente e usa os tokens de Design desse curso;
- o estado da turma é mostrado explicitamente: `active` = **Turma ativa**, `closed` = **Turma encerrada**;
- `closed` não ganha restrição nova implicitamente: este estado é informativo até que uma política funcional específica seja documentada;
- quando o curso não possui aulas cadastradas, a interface mostra **Sem aulas cadastradas** em vez de um progresso artificial `0/0`.

### Continuidade dentro do material

O material continua sendo renderizado pela página normal do CMS. Não existe um segundo renderer, template de caderno ou cópia do conteúdo dentro da Área do aluno.

Quando uma pessoa autenticada abre uma página de curso com acesso `activity`/legado `enrolled` ou `cohort`, o renderer público pode acrescentar somente uma camada de **contexto da sessão** ao redor do conteúdo canônico:

- a matrícula é resolvida por `student_enrollment_material_context()`;
- quando o link veio do workspace, o `cohort_uuid` da URL preserva exatamente aquela matrícula;
- em página `cohort`, a turma autorizada pela própria página é a autoridade, mesmo que a URL traga outro `cohort_uuid`;
- páginas `public` e páginas genéricas `authenticated` não recebem contexto de curso;
- o editor/preview nunca recebe essa UI dependente da sessão do aluno;
- o link **Área do aluno** do cabeçalho retorna ao workspace da matrícula quando o contexto existe;
- uma barra discreta oferece **Voltar ao curso**, **Testes** e **Conta**, além de identificar curso e turma;
- a barra é interface de navegação, não conteúdo editorial, e portanto nunca é persistida no documento CMS.

## CMS: acesso e disponibilidade de páginas e seções

Estrutura e autorização pertencem ao próprio CMS. Cada elemento `data-cms-section` continua sendo a seção real mostrada no editor; não existe uma lista paralela em “Páginas protegidas”.

Ao selecionar a seção no editor, as propriedades de acesso ficam junto das demais propriedades da seção.

### Audiência

- **Todos os visitantes** (`public`);
- **Usuários autenticados** (`authenticated`);
- **Participantes deste curso** (`activity`);
- **Turma específica** (`cohort`).

### Disponibilidade

- **Imediata**;
- **Agendada** — data/hora inicial e, opcionalmente, final;
- **Controlada por aula** — a seção referencia uma aula e usa a liberação daquela aula para a turma do usuário.

Os dois eixos são independentes. Conceitualmente:

```text
pode_ver = audiência_permitida
           AND janela_temporal_aberta
           AND condição_de_aula_satisfeita
```

A configuração é persistida no próprio HTML da seção com atributos `data-cms-*`; o renderer do site remove seções sem autorização **antes de enviar o HTML ao navegador**. Não existe `display:none` como mecanismo de segurança.

Isso permite, por exemplo, uma seção de qualquer página institucional visível apenas a usuários autenticados, sem fingir que ela é “material de aluno”.

O acesso da página inteira também pertence às configurações da própria página no editor: pública, usuários autenticados ou participantes do curso.

### Migração do modelo antigo

Vínculos históricos de `course_page_sections` são migrados para `data-cms-availability="lesson"` + `data-cms-lesson-id` nos documentos CMS. A tabela antiga deixa de ser autoridade de edição. A antiga tela `Área do aluno → Páginas protegidas` não faz parte da arquitetura canônica e não deve aparecer como destino administrativo.

## Mídia editorial privada

A Biblioteca `media_assets` é a fonte única. Um asset pode ser público ou privado no próprio gerenciador. Não existe segundo uploader para material de curso.

Fotos produzidas pelos usuários nos testes continuam separadas porque são anexos de um registro experimental individual, não mídia editorial reutilizável.

## Registro de testes

### Índice de testes por matrícula

`Testes` usa a mesma matrícula que estrutura o workspace de Cursos. O `cohort_uuid` na navegação identifica contexto; ele não cria estado persistido nem modifica a autorização do registro.

- com uma matrícula ativa, o índice abre diretamente nesse contexto;
- com várias matrículas, o índice mostra primeiro um seletor de curso/turma, sem misturar registros de todos os cursos;
- UUID desconhecido não cai silenciosamente em outra matrícula;
- o shell usa o Design da atividade selecionada;
- a criação de teste fica presa ao `cohort_id` da matrícula aberta, validado no servidor; o usuário não recebe um seletor transversal de turmas;
- **Seus testes** lista apenas testes cujo `cohort_id` é o da matrícula selecionada;
- em **Compartilhados com você**, `cohort` exige a mesma turma e `course` pode trazer registros de outra turma da mesma atividade;
- outro curso nunca aparece no contexto selecionado;
- mudança de visibilidade retorna ao mesmo workspace quando o `cohort_uuid` de origem é válido;
- links para ficha própria ou compartilhada podem transportar `cohort_uuid` somente como contexto de retorno; autorização continua derivada do teste e das matrículas reais.

### Workflow da ficha

O fluxo móvel acompanha a ordem do trabalho:

1. **Exposição** — foto/anexo da cena; filme/lote; EI/ISO; diafragma; tempo calculado; reciprocidade; condição da luz; relação entre claras e sombras.
2. **Revelação** — revelador; diluição; temperatura; tempo; movimentação/agitação; **branqueador usado**; observações; foto/anexo do resultado.
3. **Revisar e enviar** — ficha e imagens reunidas antes da avaliação.

O avanço entre etapas editáveis precisa salvar a etapa atual. A faixa 01/02/03 informa posição no fluxo; ela não é uma navegação livre capaz de abandonar alterações ainda não submetidas.

- Exposição avança por **Salvar exposição e continuar**;
- Revelação avança por **Salvar revelação e continuar**;
- não existe um botão separado de salvar e, ao lado, um link que pule diretamente para Revisão;
- na Revisão, **Editar exposição** e **Editar revelação** são os retornos explícitos para corrigir uma etapa; depois da edição, o avanço volta a passar pelo respectivo salvamento;
- o sistema não fabrica “etapa concluída” com base em campo preenchido. Os estados persistidos continuam sendo os estados do teste (`draft`, `submitted`, `needs_revision`, `reviewed`).

A ficha própria preserva o contexto da matrícula sem torná-lo autoridade do teste:

- o contexto de retorno precisa corresponder à própria turma do teste;
- um `cohort_uuid` pertencente a outra matrícula é descartado como contexto;
- POSTs, upload/remoção de mídia, revisão e exclusão carregam apenas um contexto já validado;
- o shell usa o Design da atividade real do teste.

Na ficha compartilhada, a visibilidade continua sendo a autoridade de acesso e o contexto serve somente à navegação:

- `cohort` aceita como retorno somente a matrícula do leitor naquela mesma turma;
- `course` aceita uma matrícula ativa do leitor na mesma atividade, inclusive outra turma do curso;
- matrícula de outra atividade nunca é contexto válido;
- quando mais de uma matrícula da mesma atividade poderia servir e a origem não informa qual delas, o sistema não escolhe uma arbitrariamente;
- o shell usa o Design da atividade do teste e o retorno a **Testes** preserva o workspace quando houver contexto inequívoco.

O campo **Branqueador** permite escolher valores usados na pesquisa (**Solução peroxiacética** ou **Cloreto férrico**) e também aceitar outro texto, porque o registro deve descrever o processo efetivamente utilizado sem limitar experimentações futuras.

As fotografias ficam em armazenamento próprio do teste e são servidas somente após autorização.

### Propriedade, exclusão e visibilidade

O autor pode excluir definitivamente seu teste; a exclusão remove ficha, mensagens, registros de mídia e arquivos físicos. Quando a exclusão foi iniciada dentro de um workspace válido, o retorno permanece nesse mesmo índice de Testes.

Visibilidade:

- `private` — autor e administração;
- `cohort` — mesma turma;
- `course` — mesmo curso.

A configuração vale para o registro inteiro: ficha, imagens e conversa de avaliação/dúvidas. Usuários que recebem um teste compartilhado podem ler o registro e a conversa, mas não escrever no teste alheio.

## Administração canônica

`Admin → Inscrições → Área do aluno` administra somente:

1. Visão geral;
2. Turmas;
3. Alunos;
4. Testes;
5. Aulas.

Páginas, seções e mídia não pertencem a essa área.

## Regressão obrigatória

Os testes devem provar que:

- o editor existente contém controles de audiência e disponibilidade para a seção selecionada;
- uma seção `authenticated` funciona em uma página comum, independentemente de matrícula;
- `activity` e `cohort` exigem matrícula correspondente;
- uma seção agendada não aparece antes do início e desaparece depois do encerramento;
- uma seção controlada por aula não aparece quando `released_at` é `NULL` ou futuro e aparece quando a data já chegou;
- o dashboard do aluno distingue `blocked`, `scheduled` e `released` sem usar a mera existência de `released_at` como booleano;
- apenas aulas realmente `released` entram na contagem exibida como liberadas;
- material `cohort` aparece somente para a matrícula da turma correspondente;
- uma única matrícula abre diretamente e múltiplas matrículas exigem escolha explícita;
- `cohort_uuid` desconhecido não seleciona outra matrícula por fallback;
- `active` e `closed` são comunicados sem mudar silenciosamente a política de acesso;
- curso sem aulas não aparece como `0/0 aulas liberadas`;
- `Conta` apresenta perfil, alteração de senha e logout em desktop e celular;
- o formulário de perfil usa a grade responsiva e fica em uma coluna no celular;
- o logout de `Conta` continua sendo POST + CSRF e a alteração de senha autenticada permite retornar à Conta;
- página de material `activity` preserva o `cohort_uuid` solicitado quando ele pertence à conta;
- página `cohort` usa a turma autorizada pela página e não outro contexto sugerido na URL;
- páginas `public` e `authenticated` genéricas não recebem a barra de curso;
- a barra de contexto aparece em desktop e celular sem overflow e não entra no editor;
- Testes com várias matrículas exige escolha de contexto antes de criar ou listar registros;
- criação de teste não pode trocar o `cohort_id` para fora da matrícula aberta;
- testes próprios de outra turma não aparecem no contexto selecionado;
- compartilhamento `cohort` fica na turma e compartilhamento `course` pode atravessar turmas apenas dentro da mesma atividade;
- o workspace de curso abre Testes já com o seu `cohort_uuid`;
- Exposição e Revelação avançam somente por uma ação que salva a etapa atual;
- a faixa de etapas não oferece um atalho que abandone dados editados antes do submit;
- a ficha própria rejeita como contexto de retorno um `cohort_uuid` de outra turma;
- a ficha compartilhada preserva `cohort` apenas na mesma turma e `course` apenas dentro da mesma atividade;
- a exclusão retorna ao workspace validado quando houver contexto;
- a Área do aluno não apresenta “Páginas protegidas” como domínio administrativo;
- a Biblioteca de mídia continua sendo a autoridade de mídia editorial;
- o registro de teste persiste e exibe o branqueador;
- visibilidade do teste continua valendo para ficha, imagens e conversa;
- CI, browser regression, build e dry-run passam antes do merge.

## Auditoria de integração

A integração entre marca global, cursos/workshops, páginas, formulários, contas, turmas, mídia, variáveis comerciais e estruturas legadas está documentada em `docs/SYSTEM_INTEGRATION_AUDIT_2026-09-25.md`. Qualquer refatoração desses domínios deve seguir o plano aditivo e sem perda de dados definido ali.

A auditoria factual de UX da Área do aluno e a ordem dos blocos A1–A7 estão em `docs/STUDENT_AREA_UX_AUDIT_2026-09-27.md`.
