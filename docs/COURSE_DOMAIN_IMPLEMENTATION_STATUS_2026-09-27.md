# Estado de implementação — domínio de Curso — 27/09/2026

Implementação correspondente à arquitetura `COURSE_DOMAIN_REGISTRATION_MATERIAL_ARCHITECTURE_2026-09-27.md`.

## Implementado neste bloco

- entidade `courses` separada de `activity` e de `cms_pages`;
- associação de curso com página pública CMS existente e formulário CMS existente;
- inscrições de formulário `purpose='enrollment'` passam a receber `course_id` quando a associação é inequívoca;
- confirmação/pagamento não atribui turma automaticamente;
- matrícula só é criada depois de `cohort_id` explícito e validado para o mesmo curso;
- turmas e aulas passam a ter `course_id` canônico;
- `course_material_pages` associa páginas CMS existentes sem duplicá-las;
- `course_material_sections` associa seções CMS existentes a aulas do curso sem armazenar HTML;
- material didático continua sendo conteúdo de `cms_pages`: mesmo editor, documento, componentes, mídia, histórico, publicação, renderer e sistema visual;
- `Curso → Material` é somente outro ponto de entrada para as mesmas `cms_pages`, nunca um editor ou renderer paralelo;
- uma mesma aula pode liberar seções em várias páginas e uma mesma página pode conter seções de várias aulas;
- seção sem mapeamento de aula permanece disponível ao aluno matriculado; seção mapeada depende da liberação daquela aula para a turma;
- liberação progressiva é filtrada no servidor usando matrícula → turma → curso → aula, removendo do HTML as seções ainda bloqueadas;
- Área do aluno consome aulas e páginas de material do curso;
- compartilhamento de testes com visibilidade `course` passa a significar o mesmo curso canônico;
- nova administração `Cursos` e `Inscrições` opera com contexto explícito de curso;
- `Área do aluno` administrativa deixa de apresentar uma lista misturada da activity e passa a escolher o curso.

## Compatibilidade transitória

`activity_id` e `workshop_page_id` permanecem como compatibilidade para dados anteriores. As rotas novas preferem `course_id` e só recorrem às autoridades antigas quando o registro ainda não foi migrado de forma inequívoca.

`admin/submissions.php` permanece disponível como **Outras respostas** para formulários que não sejam a gestão canônica de inscrições por curso.

## Segurança da migração

A migração 073 não duplica nem move páginas. Ela cria relações para páginas e seções existentes.

Um trigger de integridade impede que uma inscrição de curso com turma ainda indefinida produza `course_enrollment` por acidente, inclusive se algum caminho legado tentar fazê-lo.

Backfills só usam associações existentes que já são inequívocas. Estados ambíguos permanecem sem associação para correção administrativa.

## Regressões novas

- `tools/test-course-domain-material.php`: curso, formulário, turma, aula, material, seções e proibição de matrícula antes da atribuição explícita de turma;
- `tools/test-course-material-release-filter.php`: garante que Material consome páginas e seções CMS existentes, entra pelo editor canônico e que o renderer filtra no servidor as seções vinculadas a aulas bloqueadas;
- `tools/test-course-admin-context.php`: garante que Inscrições e Área do aluno começam pelo curso e que Material não cria uma autoridade editorial paralela;
- `tools/test-course-sharing-scope.php`: visibilidade de teste `course` restrita a matrículas do mesmo curso.

As regressões existentes de CMS/editor continuam obrigatórias, garantindo que o novo ponto de entrada de Material não crie editor, renderer ou sistema visual paralelo.

## Validação

A suíte completa chegou a verde no run `36348483779` antes das atualizações exclusivamente documentais finais: PHP, JavaScript, smoke/domain tests, Chromium/Playwright, build e dry-run de distribuição passaram integralmente. O head final deve repetir a mesma suíte antes do merge.
