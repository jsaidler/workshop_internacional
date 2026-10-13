# Cursos — reconstrução mobile-first da área do aluno

Estado: implementado na branch `feat/student-mobile-first-rebuild-2026-10-13`, ainda não integrado em produção.

## Intenção e navegação

Cursos é o destino global para estudo e conversas acadêmicas; Agenda é destino independente. A unidade de navegação de Cursos é a turma autorizada, identificada por `cohort_uuid`. O aluno com uma matrícula abre diretamente o workspace dessa turma; o aluno com mais de uma escolhe explicitamente o contexto antes de abrir material. A troca de curso volta à lista, sem inferir seleção.

No telefone (320 e 390 px), a página oferece: título do curso e turma, ligação contextual para Material e Dúvidas, alertas pedagógicos somente quando há fatos que exigem atenção, páginas de material em sua ordem CMS, estado real de cada página e divulgação progressiva das próximas liberações. No desktop, a mesma organização funciona com sidebar global.

## Autoridades de domínio preservadas

- `student_enrollment_list`, `student_enrollment_dashboard_context`: matrículas e seleção exclusivamente autorizadas.
- `student_enrollment_pages_for_enrollment`: páginas acadêmicas no escopo correto, sem revelar páginas de outras turmas.
- `course_material_page_release_summary` e `cms_access_lesson_release_state`: disponibilidade por turma; `available`, `partial`, `scheduled`, `blocked`. A interface informa o estado, mas o acesso continua regulado pelo CMS ao abrir a página.
- `student_feedback_course_attention` e `student_feedback_state`: retornos e dúvidas contextualizados.
- Links de material e dúvidas preservam `cohort=<uuid>`. Não há contadores de progresso inventados, presença presumida, ou mudança da regra de liberação.

## Implementação

- Rota `aluno/cursos.php`: coleta e valida a projeção autorizada.
- `app/student_courses_view.php`: renderer visual compartilhável que não consulta o banco nem decide permissões.
- `assets/student-courses.css`: autoridade CSS própria, carregada pelo shell somente em `/aluno/cursos.php`.
- `tools/browser-fixture/student-courses.php`: mesmo renderer com dados controlados.
- `tools/browser-tests/student-courses-operational.spec.cjs`: estados de matrícula vazia/única/múltipla, materiais disponíveis/parciais/agendados, retorno pedagógico e responsividade em 320/390/1440, temas, overflow, destinos e scoped links.

A rota de edição de dúvidas, o leitor CMS e os processos permanecem existentes e devem ser testados no percurso autenticado, não duplicados nesta view.

## Gates antes do merge

Não integrar até: validação e auditoria visual do SHA final em verde; inspeção de capturas 320/390/1440; navegação entre lista, curso, material e dúvidas; verificação real com aluno de uma matrícula e de várias; manter o PR em draft enquanto Caderno e Laboratório ainda não forem migrados para a v2.
