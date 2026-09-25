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

## Turmas

A turma mantém identidade própria e pode ter nome, slug, estado, início, fim, observações e definição como padrão alterados sem recriar matrículas.

## Aulas e liberação por turma

A administração de **Aulas** gerencia somente a aula e seu estado de liberação para cada turma. `cohort_lesson_releases.released_at` possui três estados sem criar colunas artificiais:

- `NULL` — bloqueada;
- data/hora no futuro — agendada;
- data/hora igual ou anterior ao momento atual — liberada.

A interface de Aulas oferece **Bloquear**, **Liberar agora** e **Agendar**. A autorização no servidor compara a data/hora real; um `released_at` futuro não conta como aula liberada.

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

O fluxo móvel acompanha a ordem do trabalho:

1. **Exposição** — foto/anexo da cena; filme/lote; EI/ISO; diafragma; tempo calculado; reciprocidade; condição da luz; relação entre claras e sombras.
2. **Revelação** — revelador; diluição; temperatura; tempo; movimentação/agitação; **branqueador usado**; observações; foto/anexo do resultado.
3. **Revisar e enviar** — ficha e imagens reunidas antes da avaliação.

O campo **Branqueador** permite escolher valores usados na pesquisa (Ácido peracético ou Cloreto férrico) e também aceitar outro texto, porque o registro deve descrever o processo efetivamente utilizado sem limitar experimentações futuras.

As fotografias ficam em armazenamento próprio do teste e são servidas somente após autorização.

### Propriedade, exclusão e visibilidade

O autor pode excluir definitivamente seu teste; a exclusão remove ficha, mensagens, registros de mídia e arquivos físicos.

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
- a Área do aluno não apresenta “Páginas protegidas” como domínio administrativo;
- a Biblioteca de mídia continua sendo a autoridade de mídia editorial;
- o registro de teste persiste e exibe o branqueador;
- visibilidade do teste continua valendo para ficha, imagens e conversa;
- CI, browser regression, build e dry-run passam antes do merge.
