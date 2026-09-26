# Auditoria ampla do sistema — 25/09/2026

## Escopo

Esta auditoria amplia `SYSTEM_INTEGRATION_AUDIT_2026-09-25.md` e verifica o sistema como produto único: CMS, cursos/workshops, páginas, formulários, autenticação, matrículas, turmas, aulas, testes, mídia, localização, SEO, métricas, administração e migrações.

A pergunta principal não é “qual tela está faltando?”, mas:

> **qual entidade já existe, qual é sua autoridade canônica e quais partes do sistema ainda mantêm uma segunda interpretação dos mesmos dados?**

O objetivo é reduzir duplicação, remover acoplamentos ao primeiro workshop e preparar o CMS para administrar várias atividades sem perder dados, IDs, URLs, conteúdo editado, matrículas, submissões ou mídia existente.

## Mapa de autoridades desejado

| Domínio | Autoridade canônica |
| --- | --- |
| Marca da instalação | configuração global da instalação — `JSaidler Fotografia` |
| Curso / workshop | `activities` + identidade localizada da atividade |
| Página | `cms_pages` |
| Hierarquia de páginas | relação pai/filho em `cms_pages` |
| Tradução/equivalência de páginas | identidade editorial compartilhada, separada de pai/filho |
| Estrutura interna da página | documento CMS / `data-cms-section` |
| Acesso e disponibilidade | regra da página/seção no CMS, avaliada server-side |
| Design | baseline global + overrides explícitos da atividade/locale |
| Formulário | `cms_forms` + finalidade/automação explícita |
| Resposta histórica | `cms_form_submissions` |
| Conta autenticada | conta global, hoje persistida em `student_users`/`student_profiles` |
| Matrícula | `course_enrollments` |
| Turma | `course_cohorts` |
| Aula | `course_lessons` + `cohort_lesson_releases` |
| Teste do participante | `student_tests` + mensagens + anexos próprios |
| Mídia editorial | `media_assets` |
| Analytics | `analytics_events` |

Estruturas legadas podem permanecer temporariamente para compatibilidade, mas não devem continuar recebendo novos dados quando uma autoridade nova já existe.

---

# P0 — incoerências que podem produzir comportamento incorreto ou continuar criando dados no lugar errado

## P0.1 — autenticação continua dependente de matrícula

O CMS já permite definir uma seção como **Usuários autenticados**, independentemente de curso ou turma. Entretanto, o login e a ativação atuais ainda exigem que a conta possua pelo menos uma matrícula ativa.

Isso significa que, conceitualmente, o sistema afirma possuir “usuários autenticados”, mas operacionalmente só permite autenticar participantes matriculados.

### Correção

Separar:

- **autenticação** — a conta é válida, ativa e pode iniciar sessão;
- **autorização** — a conta pode ou não acessar determinada atividade, turma, aula, página ou seção.

Uma página/seção `authenticated` consulta somente a sessão válida. Uma página/seção `activity`, `cohort` ou `lesson` acrescenta a condição de matrícula/liberação.

A criação/ativação de contas também deve admitir origens futuras além de matrícula de curso, sem duplicar tabela de usuário.

## P0.2 — `student_enrollments` ainda é uma autoridade ativa concorrente

Apesar de `course_enrollments` ser o modelo atual, `student_auth.php` ainda:

- lê `student_enrollments` em `student_has_activity()`;
- escreve nessa tabela ao criar/matricular administrativamente;
- altera seu status;
- lista usuários a partir dela.

Ao mesmo tempo, o fluxo novo usa `course_enrollments`. Portanto existem dois modelos de matrícula executáveis no mesmo runtime.

### Correção

1. novos writes somente em `course_enrollments`;
2. leitura temporária compatível somente onde for indispensável à migração;
3. wrappers `student_*` passam a delegar à autoridade nova;
4. teste deve falhar se novo código voltar a executar `INSERT/UPDATE` em `student_enrollments`;
5. remoção física somente após uma versão de observação.

## P0.3 — `student_materials` também continua gravável

O sistema já definiu `cms_pages` como autoridade de material didático, mas `student_auth.php` ainda contém listagem, criação e atualização de `student_materials`.

Isto permite que uma interface ou código futuro reative acidentalmente o material paralelo.

### Correção

Transformar `student_materials` em legado somente-leitura e migrar qualquer chamada restante para `cms_pages`.

## P0.4 — leitura do CMS ainda pode criar conteúdo

Há funções de consulta que chamam `cms_pages_seed()` e `cms_forms_seed()`. O sitemap também chama seed de páginas.

Uma operação de leitura não deve criar registros. Além do efeito colateral inesperado, os seeds atuais são específicos do primeiro workshop.

### Regra nova

> **read/query não grava, não cria e não faz seed.**

Seeds pertencem exclusivamente a instalação, criação explícita de atividade ou aplicação deliberada de template.

## P0.5 — criar uma atividade executa setup específico do workshop de positivo direto

`activity_create()` chama `workshop_settings_seed()`, `workshop_cms_setup_activity()` e refinamentos específicos do Direct Positive X-Ray Film.

Isto torna a entidade genérica `activity` dependente de um produto particular. Um curso novo pode receber formulário, páginas, textos, preços e estrutura que pertencem ao workshop de positivo.

Pior: `workshop_cms_setup_activity()` não atua apenas como seed; quando encontra registros existentes, pode atualizar e republicar formulário, página de inscrição e home.

### Correção

Criação de atividade deve exigir um **template/blueprint explícito**:

- Em branco;
- Positivo direto;
- outros templates futuros.

O template deve executar somente `create-if-absent`. Depois da criação, o banco CMS é a fonte editorial soberana. Uma rotina genérica de bootstrap nunca deve sobrescrever conteúdo já editado.

## P0.6 — camada antiga de localização continua concorrendo com o CMS

`public_locale.php` ainda contém uma segunda versão do conteúdo do workshop: títulos, hero, preço, CTA, textos, captions, formulário e grandes substituições via `strtr()`.

Isso compete com páginas e formulários localizados no CMS e explica por que alterações podem ser feitas num lugar sem repercutir em outro.

### Correção

- CMS localizado passa a ser a única autoridade editorial;
- retirar gradualmente `public_page_texts()`, `public_localize_template()` e traduções hardcoded de formulário;
- locale continua sendo infraestrutura, não fonte de copy;
- suportes de idioma devem ser definidos por atividade, não por uma lista global fixa PT/EN.

## P0.7 — mídia privada ainda depende do antigo modelo “página protegida de aluno”

A Biblioteca de mídia já é global e uma mídia pode ser `public` ou `private`, mas a entrega privada ainda usa `course_page_media_slots`, `student_page_is_protected()`, matrícula, turma e URLs assinadas específicas do aluno.

Ao mesmo tempo, o CMS agora permite uma **seção autenticada dentro de uma página pública**. Nesse caso, o conteúdo da seção pode ser corretamente removido server-side, mas uma mídia privada normal dentro dessa seção ainda não participa da mesma política de autorização.

### Correção

Mídia privada editorial deve usar o **mesmo contexto de autorização da página/seção que a referencia**.

O fluxo ideal é:

1. editor usa `data-media-asset-id` normal;
2. o asset pode ser público ou privado;
3. se privado, sua entrega consulta a regra efetiva do conteúdo que o tornou visível;
4. `course_page_media_slots` vira ponte legada de migração, não arquitetura final.

Isto também deve funcionar para vídeo e outros tipos de mídia, não somente imagem.

## P0.8 — “Páginas protegidas” ainda existe dentro de Área do aluno

A arquitetura definida diz que página, seção, acesso e mídia pertencem ao CMS/editor. Porém `admin/student-area.php` ainda contém uma aba `Páginas protegidas` e ações para:

- mudar acesso da página;
- mapear seção → aula;
- vincular/desvincular mídia.

É justamente a duplicação de interface que a nova arquitetura busca eliminar.

### Correção

`Área do aluno` deve ficar restrita a objetos acadêmicos/operacionais:

- Visão geral;
- Turmas;
- Alunos/participantes;
- Testes;
- Aulas.

Páginas, seções, acesso, agendamento e mídia ficam nas respectivas interfaces existentes do CMS.

## P0.9 — abrir administração ou arquivar turma pode criar uma “Turma atual” automaticamente

`course_default_cohort(..., true)` cria uma nova turma quando não encontra padrão. Esta função é chamada inclusive ao abrir `student-area.php` e pode ser acionada depois do arquivamento da última turma padrão.

Isto produz estado administrativo sem ação explícita do usuário.

### Correção

Não criar turma implicitamente depois do bootstrap inicial.

Se uma inscrição confirmada não puder ser atribuída a uma turma padrão, o sistema deve registrar **matrícula pendente de atribuição** ou exigir uma turma padrão explícita. Nunca inventar nova turma silenciosamente.

## P0.10 — renderer e dashboard ainda usam vocabulários diferentes para o mesmo acesso

`cms_access_page_allowed()` aceita `activity` e o legado `enrolled`. Porém a listagem de material do usuário ainda consulta somente `access_level='enrolled'`.

Resultado possível: a página é autorizada quando acessada diretamente, mas não aparece para o usuário no dashboard.

### Correção imediata

Dual-read temporário de `activity` + `enrolled`, seguido de backfill e aposentadoria do valor antigo.

---

# P1 — integração estrutural necessária para o CMS multi-curso

## P1.1 — página inteira deve usar a mesma política de acesso da seção

Seções já suportam:

- público;
- autenticado;
- atividade;
- turma;
- imediato;
- agendado;
- controlado por aula.

A página inteira ainda usa apenas `access_level`, um modelo menos expressivo.

### Direção

Página e seção devem compartilhar o mesmo conceito de **política de audiência + disponibilidade**, com colunas/metadata aditivas e fallback do `access_level` atual durante a migração.

## P1.2 — navegação possui fonte concorrente com propriedades da própria página

Hoje coexistem:

- `cms_pages.show_in_nav`;
- `cms_pages.nav_title`;
- `cms_pages.sort_order`;
- `cms_site_settings.navigation.items`, com `pageId`, label e ordem próprios.

Com a nova hierarquia pai/filho, manter duas árvores seria especialmente perigoso.

### Direção

- `cms_pages` = estrutura editorial e identidade;
- menu = seleção/apresentação pública dessa estrutura;
- menu pode ter rótulo override e links externos;
- `show_in_nav` / `nav_title` devem ser definidos como fallback legado ou removidos gradualmente;
- nunca duplicar `parent_page_id` dentro da navegação.

## P1.3 — roteamento ainda contém exceções do primeiro workshop

A atividade raiz e a página `inscricao` têm tratamento especial de URL, enquanto outras páginas da raiz usam outro padrão. Além disso, troca de idioma e sitemap procuram equivalente pelo mesmo slug.

### Direção

Criar um conceito explícito de **rota/alias**, preservando URLs existentes. A hierarquia editorial não deve alterar URL automaticamente.

A identidade de tradução da página, quando existir, deve orientar:

- alternância de idioma;
- `hreflang`;
- sitemap;
- equivalentes de locale.

## P1.4 — página e formulário têm ciclos de publicação diferentes

Uma página nova nasce como rascunho. Um formulário novo, hoje, nasce com `draft` e `published` preenchidos simultaneamente.

Isso é uma inconsistência editorial.

### Direção

Formulário deve seguir o mesmo princípio de draft/publicação explícita, salvo quando uma ação for conscientemente “criar e publicar”.

## P1.5 — `form_key='registration'` continua sendo uma automação escondida

Reconciliação de matrícula, mudança de turma e exclusão de inscrição dependem do nome técnico `registration`.

### Direção

Adicionar finalidade/automação explícita ao formulário, por exemplo:

- formulário comum;
- lista de interesse;
- inscrição;
- inscrição que pode gerar matrícula.

A chave continua sendo identidade interna do formulário, não regra de negócio.

## P1.6 — os defaults de formulário ainda descrevem o primeiro produto

Mesmo o schema “genérico” contém experiência em fotografia analógica, equipamento, dias do workshop e primeira turma internacional. `cms_forms_seed()` cria automaticamente PT Registration + EN Interest em qualquer atividade vazia.

### Direção

Mover isto para templates explícitos de atividade/formulário. O núcleo de forms deve conhecer tipos de campo, workflow e automação — não fotografia analógica.

## P1.7 — o grafo “usado em” da mídia é incompleto

`media_usage_all()` encontra referências em páginas e slots legados, mas o CMS também pode referenciar mídia em:

- blocos editoriais de formulários;
- SEO/social image/favicon;
- configurações de site/header/footer;
- posters e outras referências estruturadas.

Se o grafo não enxerga todos esses usos, o sistema pode permitir arquivar uma mídia ainda necessária.

### Direção

Criar um único **media reference graph**, preferencialmente por IDs estáveis. Arquivamento só é permitido quando o grafo inteiro confirma ausência de uso.

## P1.8 — migrações e setup não podem continuar reescrevendo conteúdo editorial livremente

O histórico do projeto contém muitas migrações de conteúdo, o que foi necessário durante a construção inicial. Agora que o CMS é a autoridade editorial, essa prática precisa mudar.

### Regra

Migração de aplicação altera **schema/representação**. Uma correção de conteúdo existente somente pode ocorrer quando:

- é deliberada e documentada;
- identifica inequivocamente o estado-fonte esperado (versão/hash/chave);
- preserva revisão/backup;
- não substitui conteúdo já editado fora do estado conhecido.

Seeds nunca devem reconstituir defaults sobre conteúdo vigente.

## P1.9 — identidade da conta, matrícula e inscrição precisam aparecer separadas no admin

São objetos distintos:

- conta;
- perfil atual;
- submissão histórica;
- matrícula;
- turma.

O sistema já os persiste separadamente em boa parte, mas algumas operações ainda tratam “aluno” como se fosse tudo isso ao mesmo tempo.

### Direção

Ao abrir uma pessoa no admin, mostrar claramente:

- Conta;
- Perfil;
- Matrículas por curso/turma;
- Inscrições/submissões históricas;
- Testes.

Remover matrícula não apaga conta. Excluir submissão não apaga testes. Alterar perfil não reescreve submissão histórica.

---

# P2 — melhorias importantes de produto e operação

## P2.1 — experiência de testes deve ganhar resiliência de aplicativo

O modelo atual de testes está conceitualmente correto ao separar anexos do teste da Biblioteca editorial. A evolução útil é operacional:

- autosave/local draft em telas móveis;
- tolerância a perda de conexão durante upload;
- indicador claro de salvo/não salvo;
- possibilidade de duplicar um teste como ponto de partida para o experimento seguinte;
- histórico/revisão quando um teste já enviado ou avaliado volta a ser editado.

A avaliação do professor deve sempre poder ser interpretada em relação ao estado do teste ao qual respondeu.

## P2.2 — notificações precisam de uma camada própria antes de crescerem

Formulários chamam `mail()` diretamente. Não há fila, retry ou estado de entrega.

Antes de adicionar notificações de teste enviado, resposta do professor, liberação de aula etc., criar uma pequena camada de `notifications/outbox` com:

- tipo do evento;
- destinatário;
- payload;
- estado de entrega;
- tentativas/erro;
- provider configurável.

A interface administrativa continua sendo a fonte de verdade; e-mail é notificação, não armazenamento do processo.

## P2.3 — locale precisa pertencer ao curso e à conta, não à primeira matrícula ou a uma lista global fixa

O núcleo ainda reconhece apenas PT-BR e EN globalmente e parte da interface autenticada é fixa em PT-BR.

### Direção

- atividade declara locales suportados e locale padrão;
- conta pode ter `preferred_locale`;
- página/formulário usa equivalência explícita entre locales;
- fallback: preferência da conta → locale da atividade → padrão global;
- nunca usar “primeira matrícula” como decisão de idioma.

## P2.4 — timezone deve ser propriedade da atividade com fallback global

Agendamentos de seção/aula interpretam datas usando o timezone global da aplicação.

Adicionar timezone à atividade evita ambiguidade em cursos internacionais. Timestamps históricos já persistidos não devem ser reinterpretados silenciosamente.

## P2.5 — analytics já tem `activity_id`; falta visão de portfólio

Não criar outro coletor. O ganho é administrativo:

- visão global de todos os cursos/workshops;
- comparação de sessões, páginas, formulários e conversão;
- drill-down para o dashboard existente por atividade.

Se métricas de aprendizado/testes forem adicionadas no futuro, definir finalidade e retenção antes de coletar comportamento de usuários autenticados.

## P2.6 — escopo global × atividade precisa ficar explícito no admin

Objetos globais:

- marca;
- sistema/update;
- Biblioteca de mídia;
- identidade da conta.

Objetos de atividade:

- páginas;
- formulários;
- design override;
- turmas;
- aulas;
- matrículas;
- testes.

O shell administrativo ainda usa “Site / Sites / Site atual” e uma marca hardcoded. A navegação futura deve deixar claro quando se está no **portfólio global** e quando se está dentro de um **curso/workshop atual**.

## P2.7 — log administrativo é mais útil agora que RBAC completo

Hoje o admin é essencialmente uma única sessão sem papéis. Não há evidência de necessidade imediata de RBAC complexo.

Mas ações destrutivas e de permissão já justificam um log append-only:

- exclusão de inscrição;
- remoção/alteração de matrícula;
- exclusão de teste;
- mudança de visibilidade;
- alteração de acesso/agendamento;
- arquivamento de mídia;
- update/migração.

Registrar ator, ação, entidade, ID, timestamp e resumo não sensível. Papéis/permissões podem ser acrescentados apenas quando houver segundo operador com responsabilidades diferentes.

## P2.8 — ciclo de vida do curso precisa ser definido como operação, não só `status=active`

Antes de codificar novos estados, mapear o fluxo real. Provável distinção futura:

- preparação;
- inscrições abertas;
- inscrições encerradas;
- em andamento;
- concluído;
- arquivado.

Esses estados não podem apagar páginas, turmas, respostas ou matrículas automaticamente.

---

# P3 — limpeza e dívida técnica posterior

## P3.1 — remover wrappers e nomes `student_*` somente depois da migração funcional

A nomenclatura deve evoluir para `account_*`, `course_*`, `test_*`, mas renomear tabelas/funções antes de eliminar autoridades concorrentes aumenta risco sem benefício funcional.

Primeiro delegar e estabilizar; depois renomear.

## P3.2 — retirar arquivos específicos do primeiro workshop do bootstrap genérico

Arquivos `workshop_*` podem permanecer como template/migração do produto, mas não devem participar indiscriminadamente do bootstrap de toda requisição nem da criação de toda atividade.

## P3.3 — remover tabelas legadas somente quando não houver leitura nem escrita

Candidatas já conhecidas:

- `student_enrollments`;
- `student_materials`;
- `student_private_media`;
- `course_page_sections`;
- `course_page_media_slots`, depois que mídia privada usar autorização genérica do CMS.

A remoção física é a última etapa, não a primeira.

---

# O que já está bem encaminhado e deve ser preservado

1. **`course_enrollments`** liga conta à turma e mantém referência à submissão de origem quando existe.
2. **`cms_form_submissions`** preserva o histórico da inscrição separadamente do perfil atual.
3. **`data-cms-section`** representa a estrutura real da página sem tabela paralela de seções.
4. **`cms_access_filter_html()`** faz filtragem server-side das seções; conteúdo bloqueado não deve depender de CSS.
5. **`media_assets`** é uma Biblioteca global; não deve ser clonada por curso.
6. **Fotos de testes** pertencem ao teste, não à Biblioteca editorial.
7. **Visibilidade do teste** (`private`, `cohort`, `course`) é aplicada ao registro inteiro.
8. **Analytics** já carrega `activity_id`; falta agregação, não outro sistema.
9. **Updater** preserva banco/uploads/configuração e valida artefato/checksums; a refatoração arquitetural deve continuar usando o mesmo fluxo de migração segura.

---

# Sequência recomendada de execução

## Fase 0 — impedir que a dívida continue crescendo

1. tornar reads do CMS puros: remover seed de funções de consulta/sitemap;
2. impedir novos writes em `student_enrollments` e `student_materials`;
3. corrigir imediatamente `activity` × `enrolled` na listagem autenticada;
4. retirar `Páginas protegidas` da Área do aluno depois de garantir que tudo esteja disponível no editor canônico;
5. parar criação implícita de turma ao abrir admin/arquivar última turma;
6. separar login/ativação de matrícula;
7. proibir setup específico do Direct Positive na criação genérica de atividade.

## Fase 1 — consolidar modelo multi-curso

1. marca global `JSaidler Fotografia`;
2. identidade localizada da atividade;
3. atividade apresentada como Curso/workshop no admin;
4. `parent_page_id` + árvore de páginas;
5. identidade de tradução de página;
6. navegação consumindo a árvore canônica sem duplicá-la;
7. contexto global × atividade explícito no shell;
8. design global + overrides de atividade.

## Fase 2 — unificar autorização e mídia

1. mesma política de audiência/disponibilidade para página e seção;
2. timezone da atividade;
3. mídia privada entregue pelo contexto genérico da regra CMS;
4. migrar slots privados legados para referências normais da Biblioteca;
5. completar grafo “usado em” de mídia.

## Fase 3 — generalizar produto e operação

1. finalidade/automação do formulário;
2. template explícito de atividade/formulário;
3. variáveis comerciais genéricas por atividade/locale;
4. conta com locale preferido;
5. notifications/outbox;
6. visão global de analytics;
7. audit log administrativo;
8. melhorias de resiliência/revisão nos testes móveis.

## Fase 4 — retirada controlada de legado

Somente depois de pelo menos um ciclo estável em produção:

- remover fallbacks não usados;
- remover camada `public_localize_template` e outros conteúdos hardcoded já migrados;
- retirar módulos `student_material*` antigos;
- remover tabelas legadas comprovadamente sem uso;
- simplificar bootstrap;
- considerar mudança de URLs apenas se houver benefício concreto, sempre com aliases/redirects históricos.

---

# Critérios de regressão obrigatórios durante a refatoração

A migração é considerada segura somente se os testes garantirem, antes/depois:

- mesmos IDs de atividades, páginas, formulários, contas, turmas, matrículas e submissões existentes;
- mesmas URLs públicas ou redirect explícito documentado;
- nenhum conteúdo editorial já editado sobrescrito por seed;
- nenhuma matrícula perdida ou recriada em outra turma;
- conta autenticada independente de matrícula quando a regra exigir apenas `authenticated`;
- acesso `activity`, `cohort`, `lesson` e agendamentos equivalentes;
- mídia pública/privada servida somente para audiência correta;
- histórico de submissões preservado;
- testes, mensagens e anexos preservados;
- nenhuma nova gravação em tabela marcada como legacy/read-only;
- leitura/sitemap não alteram o banco;
- criação de atividade em branco não contém copy, preço, formulário ou página do Direct Positive.

## Regra permanente derivada desta auditoria

Antes de qualquer nova funcionalidade, além das perguntas da auditoria de integração, verificar:

1. **uma leitura pode produzir escrita?** Se sim, separar as responsabilidades;
2. **um módulo genérico contém conteúdo, preço, idioma ou workflow de um produto específico?** Se sim, mover para template/configuração;
3. **o mesmo conceito é gravado em duas tabelas ou duas interfaces?** Se sim, definir autoridade e plano de migração antes de evoluir a feature;
4. **a regra funciona também para uma segunda atividade hipotética vazia?** Se não, ainda existe acoplamento ao primeiro workshop;
5. **a feature depende de “aluno” quando na verdade depende apenas de “usuário autenticado”?** Se sim, corrigir a abstração de identidade/autorização;
6. **a mudança pode sobrescrever conteúdo editado pelo CMS?** Se sim, deve ser bloqueada ou versionada por estado-fonte inequívoco.

## Tranche 2026-09-26 — finalidade de formulário como autoridade operacional

Implementada em `audit/form-purpose-enrollment-authority-2026-09-26`: `cms_forms.purpose` é a autoridade explícita para comum/interesse/inscrição/inscrição que gera matrícula. O comportamento de matrícula deixou de ser decidido por `form_key='registration'`; a migração 069 preserva todas as chaves, IDs, submissões e o comportamento do formulário legado por backfill para `enrollment`. A interface permanece em `Admin → Formulários`. Ver `docs/FORM_PURPOSE_ENROLLMENT_AUTHORITY_2026-09-26.md`.
