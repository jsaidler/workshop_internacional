# Limites deste bloco

Este bloco não cria um segundo sistema editorial para cursos ou material.

Não foram criados:

- editor de material;
- renderer de material;
- componentes visuais específicos de material;
- cópias de páginas CMS;
- cópias de seções CMS;
- `activity` por curso.

As novas tabelas de material são exclusivamente relacionais e apontam para `cms_pages` e `course_lessons` existentes.
