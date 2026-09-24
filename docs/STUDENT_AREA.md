# Área do aluno — estado canônico

## Regra de identidade

Não existe cadastro manual de aluno. Um participante se torna aluno quando uma inscrição do formulário `registration` é confirmada (`status=converted` ou pagamento confirmado). A conta é única entre cursos e pode possuir várias matrículas.

O primeiro acesso usa **e-mail da inscrição + CPF somente com números** como prova inicial de identidade. O CPF não é senha permanente. Depois da validação inicial, o aluno reconhece o Aviso de Privacidade e cria a própria senha.

## Perfil e novas inscrições

`student_users` representa a conta; `student_profiles`, os dados reutilizáveis; `course_cohorts`, as turmas; `course_enrollments`, as matrículas. Nome, e-mail, CPF, telefone, Instagram e endereço podem preencher uma nova inscrição para um aluno autenticado, sempre permitindo revisão. Respostas específicas do curso continuam pertencendo à inscrição. O aluno consulta e corrige o perfil em `/aluno/perfil.php`.

## Conteúdo protegido continua sendo conteúdo do CMS

Material didático é uma página comum de `cms_pages`. Não existe um sistema editorial paralelo.

- `access_level=public`: página pública;
- `access_level=enrolled`: exige aluno autenticado com matrícula ativa;
- administrador autenticado: acesso integral à página protegida para inspeção, inclusive seções ainda não liberadas aos alunos.

Uma página protegida não aparece na navegação pública e recebe `noindex`. O acesso continua usando sua URL normal do CMS.

### Regra visual das páginas

Uma página de material **não possui stylesheet próprio**. O HTML editorial usa os componentes e classes do sistema global e herda integralmente o Design do site. Exceções deliberadas pertencem a `Design → CSS adicional`, que continua sendo a camada editorial final do site.

É proibido resolver uma página protegida com `<style>` local, propriedades visuais inline ou outro sistema visual paralelo.

Materiais longos não devem ser renderizados como uma sequência contínua de parágrafos. O sistema global oferece a família reutilizável `cms-document`: capa, índice, páginas editoriais, divisores de aula, tabelas, fórmulas, notas, etapas e mídia. Esses componentes pertencem ao CSS compartilhado do CMS e podem ser usados por qualquer página; não são estilo exclusivo do caderno.

## Caderno “Positivo direto em filme de raio-X”

A página `caderno-positivo-direto` tem como fonte editorial os conteúdos efetivamente enviados aos participantes por e-mail:

- “Receitas, materiais e algumas referências para trabalhar com filme de raio-X” — 07/09/2026;
- “Segundo Encontro: Processos químicos para positivos” — 18/09/2026.

O CMS pode reorganizar esses conteúdos em seções e hierarquia editorial, mas não substituir a fonte por uma apostila genérica nem preencher lacunas com conteúdo inventado. Quando os registros divergem, a divergência é preservada explicitamente. Exemplo: o primeiro e-mail define `15/550` como volume final de 550 ml; o segundo registra literalmente `10 ml` ou `20 ml de Parodinal + 550 ml de água`. O sistema não transforma silenciosamente uma notação na outra.

### Estrutura editorial do caderno

O material precisa ser reconhecível como publicação e como sequência de aulas, não como reprodução visual de um e-mail. A estrutura canônica é:

- capa do material;
- índice das três aulas;
- divisor visual **Aula 01 — Filme e exposição**;
- páginas editoriais correspondentes ao conteúdo do primeiro encontro;
- divisor visual **Aula 02 — Processos químicos para positivos**;
- páginas editoriais correspondentes ao conteúdo químico/prático;
- divisor visual **Aula 03 — Revisão de resultados**.

Cada assunto continua sendo uma `data-cms-section` independente para edição e liberação, mas recebe apresentação de página por meio dos componentes globais `cms-document-page`. Entre páginas existe separação física/visual clara no fluxo; entre aulas existe um divisor de grande escala no mesmo DNA visual do site.

A `aula-3`, destinada à revisão dos resultados, **não recebe conteúdo artificial**. O divisor existe para representar a terceira etapa do curso e para que sua liberação seja controlada como aula; conteúdo adicional só entra quando houver fonte editorial real.

## Infográficos privados

Os elementos visuais explicativos do material são **infográficos ilustrados gerados**, não fotografias simuladas, não grafismos HTML/SVG e não equipamentos inventados.

A página contém quatro slots semânticos de mídia privada:

- `energia-cena` — distribuição de energia na cena;
- `reciprocidade` — baixa energia e falha de reciprocidade;
- `imagem-latente` — da imagem latente à prata metálica;
- `fluxo-positivo` — fluxo do positivo direto em filme.

O HTML persistido contém apenas `<figure data-private-media-slot="…">`. O arquivo real é enviado pelo admin e associado ao slot. Se um slot ainda não tiver arquivo, ele é removido da resposta pública: nenhum texto de placeholder ou instrução de desenvolvimento aparece para o aluno.

Os arquivos ficam em `storage/student-media/`, fora do acesso HTTP direto. Para alunos, o renderer troca a referência persistente por URL assinada e temporária vinculada a aluno, página e turma. Para administradores autenticados, a mesma mídia pode ser visualizada diretamente durante a inspeção da página protegida.

## Liberação por aula

As seções da página usam `data-cms-section`. `course_page_sections` liga uma seção a uma aula; `cohort_lesson_releases` controla a liberação por turma. Uma seção bloqueada é removida no servidor antes do HTML ser enviado ao aluno. Não existe `display:none` para conteúdo ainda não liberado.

Os próprios divisores `caderno-aula-1`, `caderno-aula-2` e `caderno-aula-3` são mapeados às aulas correspondentes. Assim uma aula ainda bloqueada não deixa no documento um cabeçalho anunciando conteúdo que o aluno não pode acessar.

O bypass administrativo é deliberado: um administrador precisa conseguir verificar a página completa e suas mídias sem possuir uma matrícula de aluno.

## Administração e escala

`Admin → Inscrições → Área do aluno` usa o mesmo sistema de UI/UX do restante da administração. Não existe stylesheet visual exclusivo da Área do aluno.

A superfície é dividida em tarefas: Visão geral, Turmas, Aulas, Páginas protegidas e Alunos. Listas extensas usam componentes compartilhados, filtros, busca, paginação e tabelas com overflow responsivo. A tela não pode depender de carregar todos os alunos e todos os registros em uma única coluna crescente.

Qualquer componente administrativo novo deve ser implementado como padrão reutilizável do admin quando puder aparecer em outras superfícies. Não usar CSS específico de página para compensar uma deficiência do sistema compartilhado.

## LGPD e segurança

O tratamento de dados segue finalidade, necessidade, transparência e segurança. O CPF é usado como prova inicial de identidade, indexado por HMAC e mantido de forma recuperável apenas quando necessário ao perfil, criptografado em repouso. Senhas são hashes e o CPF nunca é gravado como `password_hash`.

Autenticação, filtro server-side, armazenamento privado, URLs assinadas, `no-store` e `noindex` protegem contra acesso público e redistribuição casual. Nenhum sistema web impede que um usuário autorizado fotografe a tela ou reproduza manualmente o conteúdo recebido.
