# CMS — acesso e agendamento de páginas e seções — 25/09/2026

## Problema corrigido

A implementação anterior administrava estrutura editorial em `Área do aluno → Páginas protegidas`. Isso duplicava uma entidade que já existe no editor de páginas e acoplava autorização ao conceito de “aluno”. Como consequência, uma seção autenticada em qualquer outra página exigiria uma segunda interface e novas exceções.

## Decisão canônica

O CMS é autoridade de estrutura e acesso. A mesma seção que aparece no painel **Seções** do editor recebe suas propriedades de audiência e disponibilidade no inspetor dessa seção.

Não existe uma segunda árvore de seções para conteúdo autenticado.

## Modelo da seção

Atributos persistidos no próprio HTML CMS:

- `data-cms-access="authenticated|activity|cohort"`; ausência = público;
- `data-cms-cohort-id="ID"` quando a audiência é uma turma;
- `data-cms-availability="scheduled|lesson"`; ausência = imediata;
- `data-cms-visible-from="YYYY-MM-DDTHH:MM"`;
- `data-cms-visible-until="YYYY-MM-DDTHH:MM"`;
- `data-cms-lesson-id="ID"` quando a disponibilidade depende de aula.

A autorização é avaliada no servidor. Se a regra não for satisfeita, a seção é removida do DOM antes da renderização pública.

## Audiência

- público;
- qualquer usuário autenticado;
- participante com matrícula ativa no curso/atividade;
- participante de uma turma específica.

“Aluno” não é uma audiência global: é uma relação de uma conta com curso/turma.

## Disponibilidade

- imediata;
- janela agendada por data/hora;
- controlada pela liberação de uma aula.

Audiência e disponibilidade são independentes.

## Aulas

`cohort_lesson_releases.released_at` representa:

- `NULL`: bloqueada;
- futuro: agendada;
- passado/presente: liberada.

A interface existente de **Aulas** recebe as ações Bloquear, Liberar agora e Agendar. Uma seção configurada como “Controlada por aula” consulta esse estado para a turma da matrícula atual.

## Migração

Os vínculos históricos `course_page_sections` são transportados para os atributos `data-cms-availability="lesson"` e `data-cms-lesson-id` em documentos CMS já existentes. A tabela histórica deixa de ser autoridade editorial.

O destino administrativo `Páginas protegidas` é descontinuado: páginas e seções voltam ao editor; mídia permanece na Biblioteca de mídia.

## Cache e segurança

Páginas com regras de seção ou acesso não público recebem headers privados/no-cache. O navegador nunca recebe seções às quais o usuário não tem acesso.

## Regressão

A suíte deve testar audiência autenticada sem matrícula, participante de curso, turma específica, janela agendada, aula com liberação futura e aula após o horário de liberação.
