# Implementação da auditoria ampla — 25/09/2026

Este documento acompanha `SYSTEM_WIDE_AUDIT_2026-09-25.md` e registra o que deixou de ser apenas diagnóstico e passou a ser regra executável do sistema.

## Princípio de implementação

A auditoria não será resolvida apagando estruturas históricas no mesmo release em que uma autoridade nova é consolidada. O caminho é:

1. impedir novos writes e efeitos colaterais nas autoridades erradas;
2. normalizar os valores legados que já possuem equivalência inequívoca;
3. manter compatibilidade de leitura onde ainda for necessária;
4. adicionar regressões;
5. só então retirar fisicamente estruturas antigas.

## Primeiro tranche implementado

### 1. Identidade global da instalação

`app/brand.php` define `JSaidler Fotografia` como identidade global da instalação. A Área autenticada passa a usar essa autoridade em vez de `Direct Positive Workshop` hardcoded.

Quando uma tela da Área autenticada não pertence explicitamente a uma atividade, ela usa o design global/default. O shell deixa de escolher aparência a partir da primeira matrícula do usuário. Uma tela que recebe uma atividade explícita continua podendo usar o design daquela atividade.

### 2. Renderer público legado confinado ao curso raiz

`index.php` continua aceitando temporariamente o renderer público antigo somente como fallback de migração da atividade raiz. Uma atividade não-raiz sem home CMS publicada não pode mais cair silenciosamente na landing page do workshop de positivo direto.

Toda ocorrência do fallback raiz gera `legacy_public_renderer_fallback` no log. Isso permite medir quando a retirada completa do renderer antigo se torna segura.

### 3. Vocabulário de acesso normalizado

A migração `067_normalize_page_activity_access.php` converte `cms_pages.access_level='enrolled'` para o valor canônico `activity`.

A autorização continua aceitando `enrolled` temporariamente como compatibilidade de leitura. Código novo não deve gravar esse valor.

### 4. Escritas editoriais retiradas da Área do aluno

`admin/student-area.php` passa a ser uma fachada de compatibilidade sobre a implementação anterior, agora preservada temporariamente em `admin/student-area-legacy.php`.

A fachada:

- redireciona `view=pages` para `Admin → Páginas`;
- rejeita com HTTP 410 os writers legados `set_page_access`, `save_section_map`, `bind_page_media` e `unbind_page_media`;
- remove da interface a aba, o indicador e o card `Páginas protegidas`;
- explicita que páginas, acesso editorial e mídia pertencem ao CMS.

O arquivo legado permanece neste release apenas para evitar uma reescrita simultânea da administração de turmas, alunos, testes e aulas. Ele não é uma autoridade editorial e será decomposto em trabalho posterior.

### 5. Migrações serializadas

`database()` agora executa `run_migrations()` sob um `flock(LOCK_EX)` em `storage/migrations.lock`.

Isso impede que duas primeiras requisições concorrentes após uma atualização tentem aplicar a mesma migration simultaneamente. O lock é de processo/arquivo e não altera o formato do SQLite persistente.

### 6. Build valida toda a sequência de migrations

`tools/build-dist.php` deixa de validar apenas `001` a `019`. A validação agora descobre todos os arquivos de migration e exige:

- nome no formato `NNN_nome.php`;
- início em `001`;
- sequência numérica contínua;
- ausência de prefixos numéricos duplicados.

O teste de build cobre explicitamente um gap em `020` e uma duplicação de `020`, além de confirmar que um build inválido não altera o `dist` anterior.

## Segundo tranche implementado — leituras puras e criação explícita de curso

### 7. Consultas de páginas e formulários não fazem mais seed

As APIs de leitura do CMS deixam de criar conteúdo como efeito colateral.

- `cms_pages()`, `cms_page_home()`, `cms_page_by_slug()` e `cms_nav_pages()` apenas consultam `cms_pages`;
- `cms_forms()` e `cms_form_by_key()` apenas consultam `cms_forms`;
- abrir `Admin → Páginas` não chama `cms_pages_seed()`;
- abrir `Admin → Formulários` não chama `cms_forms_seed()`;
- uma atividade vazia pode permanecer genuinamente vazia até uma ação explícita de criação ou aplicação de template.

As funções `cms_pages_seed()` e `cms_forms_seed()` permanecem temporariamente como primitivas explícitas de inicialização para templates e migrações históricas. Elas não são mais parte do caminho normal de leitura.

### 8. Sitemap é somente leitura

`cms_sitemap_entries()` não chama mais `cms_pages_seed()` ao percorrer atividades.

Uma atividade vazia, portanto, não ganha páginas simplesmente porque um crawler ou administrador requisitou o sitemap. Somente páginas já existentes, publicadas, públicas e indexáveis participam da descoberta.

### 9. Criação de curso exige template explícito

`activity_create()` deixa de assumir silenciosamente que um curso novo deve copiar a atividade raiz ou receber o workshop de Positivo Direto.

A criação administrativa agora possui templates explícitos:

- `blank` / **Em branco** — cria apenas a entidade curso/workshop e não cria páginas, formulários nem `content_documents` do produto anterior;
- `direct-positive` / **Positivo direto** — aplica deliberadamente os defaults históricos desse workshop.

`blank` é o default neutro do repositório. Um `copyId` continua sendo uma operação explícita separada; cópia de curso e aplicação de template não podem ocorrer simultaneamente.

### 10. Setup do template Positivo Direto é de inicialização, não de manutenção editorial

`workshop_cms_setup_activity()` verifica o estado antes de aplicar o template. Se a atividade já possui página ou formulário CMS ativo, a função retorna sem alterar o conteúdo existente.

Isto fecha o comportamento anterior em que uma rotina de setup podia republicar ou substituir formulário, página de inscrição e home já editados. O template passa a valer somente para uma atividade vazia.

A decisão é deliberadamente conservadora: estado parcial não é “reparado” automaticamente por essa rotina. Reparos de migração continuam precisando identificar de maneira inequívoca o estado-fonte antes de escrever.

### 11. Regressão do segundo tranche

`tools/test-read-purity-activity-templates.php` valida que:

- consultas de páginas/formulários sobre uma atividade vazia retornam vazio sem criar linhas;
- lookups de home, slug, navegação e formulário inexistente não fazem seed;
- gerar sitemap de uma atividade vazia não cria conteúdo;
- `Admin → Páginas` e `Admin → Formulários` não carregam chamadas de seed;
- template `blank` não cria conteúdo específico do primeiro workshop;
- template `direct-positive` cria o conjunto inicial esperado;
- reaplicar o setup de Positivo Direto depois de edição não substitui título de página nem de formulário.

O teste faz parte do CI regular.

## Deliberadamente ainda não implementado

Os itens abaixo permanecem no roadmap porque exigem migração coordenada de autoridade e testes próprios; misturá-los aos tranches acima aumentaria o risco de perda ou sobrescrita de conteúdo.

- desligar writers de `student_enrollments`, `student_materials`, `content_documents` e `interest_submissions` após backfill/verificação;
- remover definitivamente `template/public.php`, `public_locale.php` e a camada `content_documents`;
- eliminar o seed transitório de `content_documents` ainda existente dentro do renderer/serviço legado antes de remover essa camada;
- retirar o fallback de `workshop_settings_seed()` ainda existente em `workshop_prices()` quando o legado de preço deixar de ser necessário;
- fazer mídia privada usar o mesmo contexto efetivo de autorização da página/seção CMS;
- adicionar `parent_page_id` e identidade de tradução/equivalência de páginas;
- finalidade explícita do formulário no lugar de `form_key='registration'`;
- lifecycle administrativo próprio de matrícula;
- consolidação das gerações de CSS/JavaScript do editor;
- rate limiting do login administrativo e do endpoint canônico de formulários;
- política deliberada para EXIF/GPS de fotografias compartilhadas em testes.

## Regra transitória

Enquanto `admin/student-area-legacy.php` existir, alterações editoriais não podem ser adicionadas novamente a esse arquivo. Qualquer nova operação de página, seção, acesso ou mídia deve ser implementada no CMS/editor e na Biblioteca de mídia.

Enquanto o renderer público legado existir, ele é permitido somente para a atividade raiz e sua utilização deve continuar observável por log.

Enquanto `cms_pages_seed()` e `cms_forms_seed()` existirem, só podem ser chamados por instalação, migração deliberada ou aplicação explícita de template. Nunca por listagem, lookup, sitemap, renderer ou simples abertura de uma tela administrativa.

## Critérios para o próximo tranche

O próximo conjunto deve continuar removendo autoridades paralelas sem apagar estruturas históricas prematuramente. Prioridades imediatas:

1. separar autenticação de matrícula de forma completa e desativar novos writes em `student_enrollments`;
2. tornar `student_materials` definitivamente somente-leitura/legado e eliminar chamadas executáveis restantes;
3. definir o plano de retirada de `content_documents` e `public_locale.php` sem perder o fallback raiz ainda observado em produção;
4. iniciar a política contextual de autorização para mídia privada editorial.
