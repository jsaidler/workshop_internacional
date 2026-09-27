# Invariante editorial — material didático continua sendo página CMS

Material didático não cria uma nova tecnologia editorial.

Toda página associada a um curso permanece em `cms_pages` e continua usando:

- o mesmo editor CMS;
- o mesmo documento HTML;
- os mesmos componentes;
- a mesma mídia;
- a mesma publicação e histórico;
- o mesmo renderer público;
- o mesmo sistema visual.

`course_material_pages` apenas associa uma página existente a um curso.

`course_material_sections` apenas associa uma seção existente da página (`data-cms-section`) a uma aula do curso.

O ponto administrativo `Curso → Material` é uma visão contextual das páginas já existentes. `Editar página` deve abrir o editor CMS canônico. Nenhum editor, renderer, CSS ou sistema de componentes paralelo pode ser criado para material.
