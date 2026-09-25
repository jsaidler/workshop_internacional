# Área do aluno — correção de arquitetura de informação — 24/09/2026

A administração da Área do aluno usa uma única superfície organizada por objeto: Visão geral, Turmas, Alunos, Testes, Aulas e Páginas protegidas.

A antiga categoria artificial “Operações e testes” não faz parte da navegação canônica. A rota anterior permanece apenas como redirecionamento de compatibilidade.

Ações pertencem ao objeto correspondente:

- edição de dados dentro de Turmas;
- importação de planilha dentro de Alunos;
- fila, ficha, imagens, avaliação e dúvidas dentro de Testes.

A correção também mantém o contrato visual do admin: nenhuma dessas telas usa estilos locais ou atributos `style`. Necessidades reutilizáveis de espaçamento e grade de mídia pertencem ao CSS compartilhado do admin.
