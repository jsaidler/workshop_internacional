# Implementação da auditoria ampla — 25/09/2026

Este documento acompanha `SYSTEM_WIDE_AUDIT_2026-09-25.md` e registra o que deixou de ser apenas diagnóstico e passou a ser regra executável do sistema.

## Princípio de implementação

A auditoria não será resolvida apagando estruturas históricas no mesmo release em que uma autoridade nova é consolidada. O caminho é:

1. impedir novos writes e efeitos colaterais nas autoridades erradas;
2. normalizar os valores legados que já possuem equivalência inequívoca;
3. manter compatibilidade de leitura onde ainda for necessária;
4. adicionar regressões;
5. só então retirar fisicamente estruturas antigas.

## Implementado nesta PR

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

## Deliberadamente ainda não implementado nesta PR

Os itens abaixo permanecem no roadmap porque exigem migração coordenada de autoridade e testes próprios; misturá-los ao primeiro tranche aumentaria o risco de perda ou sobrescrita de conteúdo.

- retirar `cms_pages_seed()` e `cms_forms_seed()` de todos os read paths;
- transformar criação de atividade em template explícito `em branco` / `positivo direto`;
- desligar writers de `student_enrollments`, `student_materials`, `content_documents` e `interest_submissions` após backfill/verificação;
- remover definitivamente `template/public.php`, `public_locale.php` e a camada `content_documents`;
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

## Critérios para o próximo tranche

O próximo conjunto deve atacar primeiro os efeitos colaterais de leitura e a criação de atividades. Antes de tornar `Em branco` o padrão real, é obrigatório garantir que visitar Páginas, Formulários, sitemap ou uma rota pública vazia não execute seed específico do primeiro workshop.
