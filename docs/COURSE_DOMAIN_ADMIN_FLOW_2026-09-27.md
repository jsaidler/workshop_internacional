# Fluxo administrativo canônico — cursos, inscrições e alunos

A administração normal opera no domínio de `course`, não em `activity`.

## Curso

Um curso é configurado associando recursos globais já existentes:

- uma página CMS pública;
- um formulário CMS de inscrição.

Criar ou editar conteúdo continua sendo responsabilidade de `Páginas` e do editor CMS normal.

## Inscrições

A navegação de inscrições começa pelo curso. Dentro de um curso, cada resposta mantém:

- pessoa/identidade;
- payload e snapshot do formulário;
- disponibilidade informada, inclusive múltiplas opções;
- pagamento;
- atribuição de turma, que pode permanecer vazia.

Pagamento confirmado não escolhe turma. A matrícula só nasce quando uma turma do mesmo curso é atribuída explicitamente.

## Alunos

A identidade é global em `student_users`, mas a tela de alunos de um curso mostra apenas matrículas ligadas às turmas desse curso.

A mesma pessoa pode aparecer em cursos diferentes sem duplicação de identidade.

## Aulas e material

Aulas pertencem ao curso. Liberações pertencem à combinação turma + aula.

Material é uma visão contextual das mesmas `cms_pages`:

- associar página existente ao curso;
- abrir a mesma página no editor CMS normal;
- mapear `data-cms-section` existente para uma aula do curso;
- seção sem aula mapeada fica disponível a qualquer matrícula ativa daquele curso;
- seção mapeada só é entregue quando a aula correspondente estiver liberada para a turma.

Não existe editor, renderer ou sistema visual específico para material didático.