# Gate de validação — curso/inscrição/material — 27/09/2026

Este bloco só pode ser incorporado à branch canônica depois de o CI completo ficar verde.

Critérios mínimos:

1. sintaxe PHP/JS sem erros;
2. migração 073 executável em estado legado;
3. inscrição confirmada sem turma não cria matrícula;
4. atribuição explícita a turma do mesmo curso cria matrícula;
5. curso lista apenas suas inscrições e alunos;
6. página de material continua sendo `cms_page` e abre no editor normal;
7. seções mapeadas a aula bloqueada são removidas no servidor;
8. seções sem mapeamento permanecem disponíveis para aluno matriculado;
9. material de um curso não é autorizado por matrícula em outro curso;
10. teste compartilhado como `course` não cruza cursos;
11. suíte existente de editor/CMS e Playwright continua verde.

Se qualquer regressão antiga falhar por uma premissa explicitamente substituída pela nova arquitetura, o teste deve ser atualizado para o novo contrato sem enfraquecer a verificação funcional correspondente.
