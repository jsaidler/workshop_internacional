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

Uma página de material **não possui stylesheet próprio**. O HTML editorial usa o sistema visual geral do site.

A primeira escolha é reutilizar componentes, classes e atributos de layout já existentes. Se uma necessidade legítima não estiver coberta, é permitido criar **um novo componente global e reutilizável**, desde que ele:

- não seja condicionado a slug, ID, locale, página protegida ou ao caderno;
- tenha semântica útil para outras páginas do CMS;
- seja documentado como parte do sistema visual geral;
- seja testado no mesmo nível compartilhado;
- permaneça sujeito a `Design → CSS adicional`, que continua sendo a camada editorial final do site.

É proibido resolver uma página protegida com `<style>` local, propriedades visuais inline ou criando no stylesheet global uma família de classes que, embora esteja num arquivo compartilhado, exista na prática apenas para aquela página. Mover CSS específico da página para `assets/cms.css` ou `template/page.css` não o transforma em componente global.

Para material longo, capa, índice, divisores de aula e unidades editoriais podem usar componentes existentes como `section`, `statement-grid`, `format`, `format-inner`, `format-heading`, `format-grid`, `format-card`, `process-list`, `process-item`, `section-label`, `statement-copy`, `technical-note` e `media-figure`, ou um futuro componente global que cumpra os critérios acima.

## Caderno “Positivo direto em filme de raio-X”

A página `caderno-positivo-direto` tem como fonte editorial os conteúdos efetivamente enviados aos participantes por e-mail:

- “Receitas, materiais e algumas referências para trabalhar com filme de raio-X” — 07/09/2026;
- “Segundo Encontro: Processos químicos para positivos” — 18/09/2026.

### Integralidade da fonte

O conteúdo técnico dos dois e-mails entra **integralmente** na página. Não se resume o texto para caber num número arbitrário de seções e não se substituem explicações, exemplos, ressalvas, hipóteses, receitas ou comparações por uma apostila genérica.

Podem ser removidos apenas elementos que não constituem conteúdo do material: cumprimento, data do próximo encontro, pedido de tamanho do suporte, endereço para envio, convite para publicar experimentações e outros recados circunstanciais da turma. Referências situacionais como “na aula” podem ser neutralizadas editorialmente quando necessário para manter o material atemporal, sem eliminar a explicação técnica que carregam.

O CMS pode reorganizar a ordem dos conteúdos, criar títulos, subtítulos, tabelas, listas e divisões editoriais, desde que preserve o sentido e a integralidade do texto-fonte. Reorganizar não significa resumir.

Quando os registros divergem, a divergência é preservada explicitamente. Exemplo: o primeiro e-mail define `15/550` como volume final de 550 ml; o segundo registra literalmente `10 ml` ou `20 ml de Parodinal + 550 ml de água`. O sistema não transforma silenciosamente uma notação na outra.

### Estrutura editorial do caderno

O material precisa ser reconhecível como publicação e como sequência de aulas, não como reprodução visual de um e-mail. A estrutura canônica é:

- capa do material;
- índice das três aulas;
- divisor visual **Aula 01 — Filme e exposição**;
- unidades editoriais contendo integralmente o conteúdo técnico do primeiro e-mail;
- divisor visual **Aula 02 — Processos químicos para positivos**;
- unidades editoriais contendo integralmente o conteúdo técnico do segundo e-mail;
- divisor visual **Aula 03 — Revisão de resultados**.

Cada assunto continua sendo uma `data-cms-section` independente para edição e liberação. A separação visual entre aulas e unidades usa o sistema visual geral; não existe uma família `cms-document`, `cms-lesson` ou equivalente criada para o caderno.

A `aula-3`, destinada à revisão dos resultados, **não recebe conteúdo artificial**. O divisor existe para representar a terceira etapa do curso e para que sua liberação seja controlada como aula; conteúdo adicional só entra quando houver fonte editorial real.

## Infográficos privados

Os elementos visuais explicativos do material são **infográficos ilustrados gerados a partir de relações concretas descritas nos e-mails**, não fotografias simuladas, não grafismos HTML/SVG, não equipamentos inventados e não explicações genéricas de fotografia química.

A imagem não substitui texto. O texto integral permanece no HTML/CMS e o infográfico entra apenas onde uma relação importante ganha clareza quando visualizada.

A página contém slots semânticos de mídia privada, descobertos automaticamente pela administração a partir do próprio documento:

- `filme-dupla-emulsao` — ortocromatismo e dupla emulsão do Fuji Super HR-U;
- `energia-cena` — uma exposição única, diferenças de EV dentro da cena, quantidade de prata formada na primeira revelação, remoção no branqueamento e densidade/transparência do positivo;
- `reciprocidade` — baixa energia na cena versus falha de reciprocidade, usando a fórmula e os tempos registrados no primeiro e-mail;
- `ei-zonas` — deslocamento da escala ao passar de EI 200 para EI 400 e perda de separação nas regiões de menor energia;
- `imagem-latente` — haletos de prata, alteração pela luz, imagem latente, redução `Ag⁺ + elétron → Ag⁰` e formação de prata metálica;
- `fluxo-positivo` — as duas rotas de branqueamento descritas no segundo e-mail: solução peracética por oxidação e cloreto férrico com formação de AgCl seguida de limpeza separada com amônia, antes da segunda revelação;
- `parametros-revelacao` — diferenças funcionais entre concentração, tempo, agitação e temperatura na primeira revelação.

O infográfico é uma ilustração editorial. Não deve parecer uma fotografia de laboratório nem uma “página pronta” colocada dentro da página do site. Título, legenda, fórmulas extensas e explicação permanecem no HTML/CMS quando já existem no texto; a imagem serve para tornar visível a relação explicada no trecho correspondente.

O HTML persistido contém apenas `<figure data-private-media-slot="…">`. O arquivo real é enviado pelo admin e associado ao slot. Se um slot ainda não tiver arquivo, ele é removido da resposta pública: nenhum texto de placeholder ou instrução de desenvolvimento aparece para o aluno.

Os arquivos ficam em `storage/student-media/`, fora do acesso HTTP direto. Para alunos, o renderer troca a referência persistente por URL assinada e temporária vinculada a aluno, página e turma. Para administradores autenticados, a mesma mídia pode ser visualizada diretamente durante a inspeção da página protegida.

## Liberação por aula

As seções da página usam `data-cms-section`. `course_page_sections` liga uma seção a uma aula; `cohort_lesson_releases` controla a liberação por turma. Uma seção bloqueada é removida no servidor antes do HTML ser enviado ao aluno. Não existe `display:none` para conteúdo ainda não liberado.

Os próprios divisores `caderno-aula-1`, `caderno-aula-2` e `caderno-aula-3` são mapeados às aulas correspondentes. Todas as unidades editoriais de cada envio também são mapeadas à aula correspondente. Assim uma aula ainda bloqueada não deixa no documento cabeçalhos nem trechos de conteúdo que o aluno não pode acessar.

O bypass administrativo é deliberado: um administrador precisa conseguir verificar a página completa e suas mídias sem possuir uma matrícula de aluno.

## Administração e escala

`Admin → Inscrições → Área do aluno` usa o mesmo sistema de UI/UX do restante da administração. Não existe stylesheet visual exclusivo da Área do aluno.

A superfície é dividida em tarefas: Visão geral, Turmas, Aulas, Páginas protegidas e Alunos. Listas extensas usam componentes compartilhados, filtros, busca, paginação e tabelas com overflow responsivo. A tela não pode depender de carregar todos os alunos e todos os registros em uma única coluna crescente.

Qualquer componente administrativo novo deve ser implementado como padrão reutilizável do admin quando puder aparecer em outras superfícies. Não usar CSS específico de página para compensar uma deficiência do sistema compartilhado.

## LGPD e segurança

O tratamento de dados segue finalidade, necessidade, transparência e segurança. O CPF é usado como prova inicial de identidade, indexado por HMAC e mantido de forma recuperável apenas quando necessário ao perfil, criptografado em repouso. Senhas são hashes e o CPF nunca é gravado como `password_hash`.

Autenticação, filtro server-side, armazenamento privado, URLs assinadas, `no-store` e `noindex` protegem contra acesso público e redistribuição casual. Nenhum sistema web impede que um usuário autorizado fotografe a tela ou reproduza manualmente o conteúdo recebido.
