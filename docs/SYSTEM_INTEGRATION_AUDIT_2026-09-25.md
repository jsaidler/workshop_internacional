# Auditoria de integração do sistema — 25/09/2026

## Objetivo

Esta auditoria parte de uma regra arquitetural simples: **antes de criar uma nova interface, tabela ou fonte de verdade, verificar se o domínio já possui autoridade canônica no sistema**.

O CMS já evoluiu de um site de um único workshop para uma aplicação que administra várias atividades, páginas, formulários, turmas, aulas, matrículas, testes, mídia e conteúdo autenticado. Parte do código, porém, ainda carrega nomes e decisões do modelo inicial. O resultado são fontes concorrentes de identidade, integrações especiais para o primeiro workshop e camadas legadas que podem voltar a produzir interfaces paralelas.

A estratégia definida aqui é conservadora: corrigir a arquitetura **sem perda de dados**, por migração aditiva, backfill, leitura compatível e retirada gradual das fontes antigas. Tabelas e campos legados não devem ser apagados no mesmo release que introduz a nova autoridade.

## Decisão central: três níveis de identidade

O sistema deve distinguir explicitamente três objetos:

1. **Marca da instalação** — `JSaidler Fotografia`.
2. **Curso / workshop / atividade** — entidade hoje armazenada em `activities`.
3. **Página** — entidade editorial em `cms_pages`.

Esses níveis não devem competir entre si.

### 1. Marca global

`JSaidler Fotografia` é a identidade global do produto/CMS. Ela deve alimentar, quando aplicável:

- wordmark do admin;
- header da Área autenticada;
- login e ativação de conta;
- metadados institucionais globais;
- links para a raiz da instalação.

A marca não deve ser repetida em cada atividade nem ficar hardcoded em shells diferentes.

### 2. Curso / workshop = `activity`

`activities` já é a chave usada por páginas, formulários, turmas, analytics e demais operações. Portanto não será criado um segundo objeto `courses`.

A alteração é semântica e de integração: no admin, `activity` deixa de ser apresentada como “site” e passa a ser **Curso / workshop** ou **Atividade**, conforme o contexto.

Cada atividade continua preservando seu `id`, `slug`, status e todos os relacionamentos existentes.

### 3. Página

`cms_pages` continua sendo a autoridade do conteúdo editorial de uma URL. Título de página, rótulo de navegação e SEO da página não são substitutos do nome do curso.

As páginas de uma atividade também precisam ter **relação estrutural entre si**. Pertencer à mesma atividade não basta para expressar que uma página é a apresentação principal do curso e outra é uma página subordinada daquele mesmo curso, como ocorre com a página do workshop de positivo direto e sua página de inscrição.

A hierarquia editorial de páginas deve ser independente da URL pública: relacionar uma página à sua página-mãe não autoriza alterar automaticamente o slug ou quebrar links existentes.

## Problemas encontrados

### P0 — identidade duplicada e concorrente

Hoje existem pelo menos três fontes diferentes para nomes que o usuário percebe como identidade:

- `activities.public_title` — nome público do curso;
- `cms_site_settings.siteName` — nome localizado do “site”;
- `cms_site_settings.wordmark` — marca do header.

Além disso, a Área autenticada ainda possui `Direct Positive Workshop` hardcoded no shell. O admin também chama `activities` de “Sites”.

Isso permite estados incoerentes: alterar um nome em uma tela não garante que header, Área autenticada, SEO e seleção de curso usem o mesmo valor.

**Correção proposta:**

- criar configuração global de marca da instalação;
- manter `activities` como entidade de curso/workshop;
- localizar o nome público da atividade por idioma;
- remover `siteName` e `wordmark` do papel de fontes concorrentes de identidade, preservando os valores existentes até a migração estar validada;
- remover strings de marca hardcoded dos shells.

### P0 — nome público da atividade não é localizado

`activities.public_title` contém um único texto, enquanto páginas e formulários são localizados (`pt-BR`, `en`). Hoje parte dessa diferença acaba sendo absorvida por `siteName`, produzindo a duplicação acima.

**Modelo-alvo:** tabela aditiva `activity_locales` ou equivalente, com pelo menos:

- `activity_id`;
- `locale`;
- `public_title`;
- timestamps.

O campo atual `activities.public_title` permanece como fallback de compatibilidade durante a migração.

### P0 — páginas de uma atividade formam hoje uma lista plana

`cms_pages` já possui `activity_id`, portanto a relação curso → páginas existe. O que falta é a relação **página → página subordinada** dentro da própria atividade. A administração atual ordena todas as páginas do locale numa única lista; uma página de inscrição, material complementar, FAQ ou outra página operacional aparece no mesmo nível da página principal do workshop.

Isso perde informação semântica importante. Exemplo canônico:

```text
Positivo direto em filme de raio-X
└── Inscrição
```

A página `Inscrição` não é outro curso, nem apenas uma página qualquer no mesmo saco. Ela pertence ao mesmo curso e é subordinada à sua página principal.

**Modelo-alvo:** adicionar relação estrutural aditiva em `cms_pages`, preferencialmente `parent_page_id` nullable, com regras:

- pai e filho devem pertencer à mesma `activity`;
- por padrão devem pertencer ao mesmo locale;
- ciclos são proibidos;
- página sem pai continua sendo página de nível superior da atividade;
- a home/página principal do curso pode ter páginas filhas;
- hierarquia editorial não muda slug nem URL automaticamente;
- `sort_order` deve ser interpretado entre irmãos quando a hierarquia estiver ativa;
- arquivar uma página-pai não deve apagar nem arquivar filhos implicitamente.

A migração não deve tentar adivinhar toda a árvore a partir de títulos ou slugs. Todas as páginas existentes permanecem no nível atual até uma relação ser definida explicitamente ou houver uma regra de backfill inequivocamente segura. No caso conhecido do workshop de positivo direto, a página de inscrição poderá ser vinculada explicitamente à página principal depois que o recurso existir.

A tela `Páginas` deve mostrar a árvore visualmente, e não uma lista plana. Exemplo:

```text
Positivo direto em filme de raio-X · Inicial
    Inscrição
    Material do workshop
Pinhole Lambe-Lambe
    Lista de interesse
```

A área `Navegação` continua controlando exposição no menu, rótulos e ordem de navegação, mas não deve manter uma segunda hierarquia concorrente. A estrutura pai/filho pertence a `cms_pages`; a navegação apenas decide quais nós dessa estrutura aparecem publicamente.

### P0 — páginas equivalentes em idiomas diferentes não possuem relação explícita

A existência de `locale` separa documentos, mas hoje não há uma identidade que diga que duas páginas são versões linguísticas do mesmo objeto editorial. Com a hierarquia de páginas, esse vazio fica mais evidente: a versão EN da landing e sua página de interesse/inscrição precisam poder corresponder às equivalentes PT sem depender de título ou slug igual.

Não usar `parent_page_id` para ligar traduções. São relações diferentes.

**Modelo recomendado:** introduzir posteriormente uma identidade editorial compartilhada, como `page_group_uuid`/`translation_key`, preservando os `id` atuais. Isso permite:

- alternância de idioma para a página equivalente;
- `hreflang` correto;
- comparação de estrutura entre locales;
- hierarquia equivalente sem obrigar os slugs a serem iguais;
- impedir que “trocar idioma” leve apenas para a home por falta de correspondência conhecida.

Nenhuma correspondência histórica deve ser inferida automaticamente quando houver ambiguidade.

### P0 — contexto visual da Área autenticada é resolvido pela primeira matrícula

Quando uma tela da Área autenticada não recebe atividade explícita, o shell procura a primeira matrícula do usuário e usa o Design daquela atividade. Para um usuário matriculado em dois cursos, isso torna a aparência dependente da ordem dos registros.

**Correção proposta:**

- dashboard geral, login, conta e navegação global usam marca/tema global da instalação;
- telas pertencentes a um objeto específico — teste, curso, material — resolvem a atividade pelo próprio objeto;
- nunca inferir o contexto visual pela “primeira matrícula”.

### P0 — acesso de página novo e listagem de material usam vocabulários diferentes

O editor novo grava acesso integral de página como `public`, `authenticated` ou `activity`. Entretanto, `student_account_pages_for_enrollment()` ainda procura somente `access_level='enrolled'`.

Isso pode fazer uma página corretamente marcada como “Participantes deste curso” deixar de aparecer no dashboard do usuário, mesmo que o renderer autorize seu acesso.

**Correção sem perda:** durante a transição, a consulta deve aceitar `activity` e o valor legado `enrolled`. Depois do backfill e da verificação de instalações, `enrolled` deixa de ser gravado e permanece apenas como compatibilidade de leitura por um período definido.

### P0 — texto de compartilhamento dos testes contradiz a regra vigente

A autoridade funcional já define que a visibilidade `private`, `cohort` ou `course` vale para **todo o registro**, inclusive a conversa. Ainda existe texto em `aluno/testes.php` afirmando que a conversa continua privada.

Não é um problema de banco; é uma regressão de integração entre regra e interface. Deve ser corrigida e coberta por teste textual/funcional.

### P0 — nomenclatura do branqueador

O termo canônico da pesquisa passa a ser **Solução peroxiacética**. Não usar “Ácido peracético” como opção do registro experimental.

O campo continua aceitando texto livre, porque seu objetivo é registrar o processo efetivamente utilizado.

## Integrações de alta prioridade

### P1 — `student_*` é nomenclatura legada, não modelo de identidade

O sistema já precisa autorizar um usuário autenticado em páginas que não pertencem necessariamente a um curso. Portanto `student_users` funciona, na prática, como conta da aplicação.

Renomear tabelas imediatamente teria risco desnecessário. A migração correta é:

1. manter tabelas e IDs existentes;
2. introduzir APIs/funções com vocabulário genérico de conta (`account_*`) sobre os mesmos registros;
3. migrar chamadas novas para a camada genérica;
4. manter wrappers `student_*` temporariamente;
5. só considerar renomeação física depois de toda dependência ter sido eliminada e validada.

Assim ganhamos a abstração correta sem tocar nos IDs, hashes, perfis, matrículas ou sessões históricas.

### P1 — formulários de inscrição têm automação implícita por `form_key='registration'`

A reconciliação de contas e matrículas procura especificamente formulários cuja chave seja `registration`. Isso funciona para o caso original, mas não representa uma capacidade genérica do CMS.

**Modelo-alvo:** o formulário declara uma finalidade/automação explícita, por exemplo:

- formulário comum;
- lista de interesse;
- inscrição que pode gerar matrícula.

O formulário continua sendo `cms_forms`; não se cria um segundo editor. A configuração apenas determina a automação posterior a uma submissão confirmada.

**Migração:** backfill dos formulários `registration` existentes para a nova finalidade; manter a leitura do `form_key` como fallback até a validação.

### P1 — submissão histórica e perfil atual já estão corretamente separados; reforçar a autoridade

`cms_form_submissions` preserva snapshot e resposta histórica. `student_users` / `student_profiles` representam identidade e dados reutilizáveis atuais. Essa separação é correta e deve ser mantida.

Melhoria necessária: tornar essa autoridade explícita em todas as telas. Editar perfil não reescreve submissões antigas; corrigir administrativamente uma inscrição histórica não deve silenciosamente sobrescrever o perfil atual sem uma ação específica.

### P1 — configurações comerciais são específicas do primeiro workshop

`workshop_locale_settings` guarda preço por idioma e `workshop_settings_apply_to_document()` injeta o valor em chaves específicas como `hero-price` e `interest-form`.

Isso é uma integração especial do primeiro produto e não escala para vários cursos.

**Modelo-alvo:** variáveis comerciais pertencem à atividade e ao locale, sem depender do nome do workshop nem de um elemento `hero-price`. A página e o formulário podem referenciar a mesma variável canônica quando necessário.

A migração deve copiar `workshop_locale_settings.planned_price` para o novo registro genérico e manter o caminho antigo como fallback até confirmar equivalência.

Não transformar todo texto comercial em banco estruturado: apenas valores que realmente precisam ser reutilizados em mais de um ponto devem virar variáveis.

### P1 — atividade raiz exerce dois papéis

Hoje `is_root=1` faz a primeira atividade responder pela raiz `/`, enquanto atividades adicionais usam `/<slug>/`. Isso mistura:

- site/marca global da instalação;
- primeiro curso/workshop.

A separação futura deve permitir uma raiz institucional/global e cursos em seus próprios contextos, mas **sem quebrar URLs existentes**.

Estratégia:

- manter a atividade raiz e suas URLs como alias legado;
- introduzir identidade global sem alterar rotas no primeiro passo;
- somente depois decidir se `/` passa a ser um hub institucional/catálogo;
- preservar redirects permanentes se alguma URL pública mudar.

### P1 — tabelas legadas ainda existem após unificações

Há estruturas históricas que não devem voltar a receber novos dados:

- `student_materials` — antecede o uso de `cms_pages` como material;
- `student_enrollments` — antecede `course_enrollments`;
- `student_private_media` — antecede `media_assets.visibility`;
- `course_page_sections` — antecede a regra `data-cms-*` na própria seção.

A presença dessas tabelas pode ser necessária para upgrades e auditoria. O problema é permitir que código novo volte a tratá-las como autoridade.

**Melhoria:** marcar em documentação e testes como **legacy/read-only**. Novos writes devem falhar em regressão. Só remover fisicamente após uma versão de verificação que prove que nenhuma instalação ainda depende delas.

### P1 — documento LGPD está parcialmente desatualizado em relação à arquitetura nova

O documento ainda descreve página protegida apenas como `access_level=enrolled` e mídia exclusiva em `storage/student-media/`. O sistema já migrou para regras genéricas de CMS e Biblioteca de mídia privada.

Atualizar a documentação de privacidade sem alterar a finalidade jurídica dos dados: apenas corrigir a descrição técnica para refletir o sistema vigente.

## Integrações de média prioridade

### P2 — Biblioteca de mídia é global; falta explicitar “uso” e contexto

`media_assets` não possui `activity_id`, portanto a Biblioteca já é global. Isso é coerente com uma marca que reutiliza ativos entre cursos.

Não criar bibliotecas por curso.

Melhoria útil: mostrar “usado em” (páginas, formulários, slots, eventualmente curso de origem editorial) antes de permitir arquivamento/exclusão. O contexto é relação de uso, não propriedade exclusiva do asset.

### P2 — Design por atividade precisa de herança, não escolha arbitrária

Design hoje é salvo por atividade e locale. Isso pode continuar útil para páginas de cursos distintos, mas a aplicação global precisa de um baseline institucional.

Modelo recomendado:

`design efetivo = design global da instalação + overrides explícitos da atividade/locale`

Para não perder personalizações existentes, os valores atuais das atividades devem ser tratados inicialmente como overrides completos. A simplificação ocorre somente depois da comparação visual e nunca por reset automático.

### P2 — SEO deve conhecer marca + curso + página

Defaults atuais carregam nomes do primeiro workshop. O SEO futuro deve poder compor de forma previsível:

`Página — Curso | JSaidler Fotografia`

sem obrigar essa forma quando houver título SEO explicitamente editado. Valores existentes de SEO permanecem prioritários; a composição serve apenas como fallback.

### P2 — idioma da Área autenticada

A Área autenticada é hoje essencialmente PT-BR, enquanto o CMS suporta cursos/páginas em inglês. Em uma conta com matrícula internacional, isso se torna uma inconsistência.

Melhoria futura: `preferred_locale` na conta, com fallback pela atividade acessada e, por último, locale padrão da instalação. Não inferir idioma pela primeira matrícula.

### P2 — timezone de agendamentos

O agendamento de seção e aula usa hoje o timezone global da aplicação. Para cursos internacionais isso pode se tornar ambíguo.

Melhoria futura: timezone por atividade, herdando o timezone global quando ausente. Migração aditiva, sem reinterpretar timestamps históricos silenciosamente.

### P2 — métricas precisam de visão agregada sem perder o recorte por curso

`analytics_events` já guarda `activity_id`, o que é correto. O dashboard, porém, opera somente no recorte de uma atividade.

Melhoria: adicionar uma visão global de portfólio que compare cursos/workshops e permita entrar no detalhe existente. Não duplicar eventos nem criar outro coletor.

### P2 — ciclo de vida de curso, formulário e matrícula

`activities.status`, `course_cohorts.status`, status de páginas/formulários e estado de inscrição são independentes. Isso é necessário, mas hoje falta uma representação operacional clara do ciclo de vida.

Uma atividade pode evoluir por estados como preparação, inscrições abertas, em andamento, encerrada e arquivada, sem que isso apague páginas, respostas ou turmas. A definição exata desses estados deve vir da operação real antes de virar código.

## Coisas que já estão integradas corretamente e não devem ser recriadas

### Mídia

`media_assets` é global e possui `visibility`. Mídia editorial privada não ganha novo uploader. Slots de página apenas referenciam assets existentes.

### Estrutura de página

O HTML do documento CMS e `data-cms-section` são a estrutura real. Não criar tabela paralela de seções para edição.

A hierarquia entre páginas pertence a `cms_pages`; ela não deve criar um segundo documento de conteúdo nem uma segunda árvore de seções.

### Acesso de seção

Audiência e disponibilidade pertencem à própria seção do CMS e são avaliadas server-side.

### Matrícula

`course_enrollments` relaciona conta e turma e preserva referência à submissão de origem.

### Histórico de inscrição

`cms_form_submissions` permanece snapshot histórico; perfil reutilizável não o substitui.

### Analytics

Os eventos já carregam `activity_id`; a melhoria necessária é somente agregação administrativa.

## Plano de migração sem perda de dados

Qualquer refatoração das integrações acima deve seguir esta sequência:

1. **Adicionar, não substituir** — criar nova tabela/campo/configuração sem remover a fonte antiga.
2. **Backfill determinístico** — copiar valores existentes e registrar de onde vieram.
3. **Dual-read com precedência nova** — ler a fonte nova; quando ausente, usar a antiga.
4. **Single-write novo** — depois do backfill, novos edits escrevem na autoridade nova; compatibilidade antiga fica somente para leitura quando necessário.
5. **Regressão de equivalência** — testes devem comparar identidade, URLs, páginas, matrículas, formulários e permissões antes/depois.
6. **Observação em produção** — pelo menos um ciclo de atualização deve manter estruturas legadas intactas.
7. **Deprecação explícita** — marcar caminhos antigos como read-only e contar usos restantes.
8. **Remoção tardia** — apagar coluna/tabela somente quando não houver leitura, escrita nem instalação dependente dela.

Nunca usar migração que “reconstrói defaults” como substituto dos dados existentes. Conteúdo e configurações já editados no CMS têm precedência sobre seeds.

Para hierarquia de páginas especificamente:

- adicionar `parent_page_id` sem alterar `id`, slug, URL ou conteúdo das páginas atuais;
- páginas existentes começam com `parent_page_id=NULL`;
- definir relações pai/filho explicitamente no admin;
- somente depois adaptar listagem, navegação, breadcrumbs e seletores para consumir a mesma árvore;
- não gerar redirects nem URLs aninhadas como efeito colateral da migração;
- se URLs aninhadas forem desejadas no futuro, tratá-las como decisão de roteamento separada com aliases/redirects preservando os endereços históricos.

## Ordem recomendada de execução

### Fase A — coerência imediata, sem mudança estrutural pesada

- corrigir “Ácido peracético” para **Solução peroxiacética**;
- corrigir copy de conversa compartilhada para refletir a visibilidade integral do teste;
- fazer a listagem de material aceitar `activity` e `enrolled` durante a transição;
- declarar estruturas legadas como read-only em regressão;
- atualizar `PRIVACY_LGPD.md` para a arquitetura vigente.

### Fase B — identidade multi-curso e estrutura editorial

- criar configuração global `JSaidler Fotografia`;
- criar identidade localizada de atividade;
- migrar nomes existentes sem apagar `public_title`, `siteName` ou `wordmark` no primeiro release;
- trocar UI “Sites” por “Cursos e workshops”;
- remover marca hardcoded de admin/Área autenticada;
- resolver design da Área autenticada por contexto explícito;
- adicionar `parent_page_id` e transformar `Páginas` em árvore por atividade/locale sem alterar URLs;
- permitir associar a página de inscrição à página principal do workshop de positivo direto;
- preparar identidade de tradução de página separada da relação pai/filho.

### Fase C — integrações genéricas de produto

- finalidade/automação explícita dos formulários;
- variáveis comerciais genéricas por atividade/locale;
- timezone por atividade;
- idioma preferencial da conta;
- visão agregada de métricas;
- breadcrumbs e navegação pública consumindo a hierarquia canônica de páginas quando aplicável.

### Fase D — limpeza posterior

Somente depois de uma versão estável com telemetria/testes:

- retirar writes antigos;
- remover fallbacks comprovadamente não usados;
- avaliar remoção física de tabelas/colunas legadas;
- avaliar separação da raiz institucional das URLs históricas da atividade raiz;
- avaliar rotas aninhadas apenas se houver benefício real, sempre preservando aliases/redirects dos slugs atuais.

## Regra permanente de arquitetura

Antes de implementar uma capacidade nova, responder explicitamente:

1. Qual é a entidade?
2. Qual interface já é autoridade dessa entidade?
3. Qual é a fonte única de verdade?
4. A capacidade é global, de atividade, de locale, de página, de seção, de turma, de aula, de usuário ou de registro?
5. Existe uma estrutura antiga contendo os mesmos dados?
6. Como migrar sem apagar nem reinterpretar dados existentes?
7. Se envolver páginas: a relação é **atividade → página**, **página → página filha** ou **página → equivalente em outro locale**? Não misturar esses três vínculos.

Se a resposta levar à criação de uma segunda interface para o mesmo objeto, a arquitetura deve ser reconsiderada antes de escrever código.
