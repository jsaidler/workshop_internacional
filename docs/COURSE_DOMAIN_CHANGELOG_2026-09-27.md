# Changelog — domínio de Curso — 27/09/2026

- documentação canônica criada;
- migração 073 adicionada;
- serviço `app/courses.php` criado;
- bootstrap passa a carregar a autoridade de curso;
- submissões de inscrição são associadas ao curso explicitamente;
- reconciliação de matrícula exige turma explícita;
- renderer e autorização passam a consumir contexto curso/material;
- workspace do aluno usa aulas e páginas associadas ao curso;
- testes compartilhados como `course` usam `course_id`;
- administração ganha entradas `Cursos` e `Inscrições` por curso;
- `Curso → Material` reutiliza páginas, seções e editor CMS existentes;
- regressões de domínio e compartilhamento adicionadas ao CI.
