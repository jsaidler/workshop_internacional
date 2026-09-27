# Escopo do PR — domínio de curso, inscrições e material

Este PR substitui a interpretação transitória `workshop_page = curso` por uma entidade de domínio explícita `courses`, preservando integralmente o CMS editorial existente.

Inclui:

- curso associado a página CMS e formulário CMS existentes;
- inscrições por curso, sem escolha automática de turma;
- matrícula somente após atribuição explícita de turma;
- turmas e aulas com `course_id` canônico;
- material como relação com `cms_pages`, sem duplicação de conteúdo;
- mapeamento de seções CMS existentes para aulas do curso;
- liberação progressiva server-side por turma + aula;
- administração separada por curso para inscrições e alunos;
- novo ponto de entrada `Curso → Material` que abre o editor CMS canônico;
- compatibilidade não destrutiva com `activity_id` e `workshop_page_id` durante a migração.
