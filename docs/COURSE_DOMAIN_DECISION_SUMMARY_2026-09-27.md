# Decisões canônicas — domínio de curso

1. Curso é entidade de domínio (`courses`), distinta de `activity`, página e formulário.
2. Página pública e formulário de inscrição são recursos CMS existentes associados ao curso.
3. Inscrição pertence ao curso e pode registrar múltiplas disponibilidades sem turma definida.
4. Pagamento, estado da inscrição e atribuição de turma são dimensões independentes.
5. Matrícula só existe depois da atribuição explícita de uma turma do mesmo curso.
6. `student_users` é identidade global; uma pessoa pode ter inscrições e matrículas em vários cursos.
7. Aulas pertencem ao curso; liberações pertencem à combinação turma + aula.
8. Material didático continua sendo `cms_pages`; `course_material_pages` é apenas a relação curso ↔ página.
9. Seções continuam sendo seções do documento CMS; `course_material_sections` só mapeia seção ↔ aula.
10. `Curso → Material` é outro ponto de entrada para as mesmas páginas e o mesmo editor.
11. Seção sem aula mapeada é conteúdo geral do curso; seção mapeada depende da liberação da aula para a turma.
12. Bloqueio é server-side: seção não liberada não é enviada no HTML.
13. Inscrições e Área do aluno administrativas começam pelo curso, evitando listas misturadas entre cursos.
