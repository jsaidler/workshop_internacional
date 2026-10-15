## Área do aluno — Agenda mobile-first v2 (13/10/2026)

A decisão de produto é reconstruir a experiência, substituindo a antiga disposição acadêmica da Agenda em Cursos. **Agenda é destino global próprio.**

### Interfaces e estado de navegação
- `/aluno/agenda.php?view=encontros` (padrão): encontros programados, primeiro encontro em destaque, próximos em ordem, histórico recolhido, status, vínculo de matrícula/ouvinte, horário local, instruções e link HTTPS quando autorizado.
- `/aluno/agenda.php?view=ouvintes`: turmas elegíveis de retorno como ouvinte, capacidade potencial/efetiva, estado de reserva, datas e ação contextual. Nunca apresenta número de vagas como garantia.
- Troca de visão por link de navegação real, sem estado JavaScript e com Back/Forward. Depois do POST de solicitar/cancelar, PRG retorna à visão de ouvintes e mostra mensagem do backend.

### Autoridade preservada
- `course_agenda_student_meetings`: mesma consulta de encontros para matrícula ativa ou reserva confirmada; nenhuma inferência de presença.
- `course_agenda_student_offers`, `course_agenda_request`, `course_agenda_cancel`, `course_agenda_reconcile_cohort`: mantêm elegibilidade, capacidade agregada, lista de espera e estados.
- `csrf_token('student-agenda')` e `verify_csrf('student-agenda')`: permanecem em todos os POSTs.
- `course_agenda_safe_meeting_url`: HTTPS validado antes de exibir; links históricos e cancelados não são apresentados.
- `student_agenda_date_parts`: somente projeção visual de data e fuso, sem modificar relógio dos encontros no banco.

### Escopo da tranche
- A nova view é `app/student_agenda_view.php`, com o item compartilhado `app/student_agenda_meeting_item.php` e CSS isolado `assets/student-agenda.css` carregado apenas na rota.
- O protótipo aprovado define a hierarquia e a navegação, mas a interface real não simula solicitação nem cria cenários fictícios. Ela lê sempre os dados autorizados da sessão.
- Testes Playwright verificam 8 estados, 320/390/1440, claro/escuro, status de vaga, privacidade dos links e transições via URL. A instalação e testes autenticados na hospedagem continuam pendentes.
\n**Dependência administrativa identificada na Agenda — 13/10/2026:** o redesign das duas visões do aluno não comprova o remanejamento de alunos. Matrículas CSV/manuais não tinham interface de transferência. Novo serviço transacional de remanejamento de participação manual, com validação da migração 098 de capacidade e reconciliação de encontros; POST administrativo mantém inscrição original como autoridade para as demais matrículas. Revisão funcional e testes hospedados continuam pendentes. Ver `docs/ADMIN_COURSE_COHORT_WORKSPACE_2026-10-03.md`.\n