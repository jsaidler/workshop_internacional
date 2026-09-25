# Evidências complementares — auditoria ampla — 25/09/2026

Este documento é parte integrante de `SYSTEM_WIDE_AUDIT_2026-09-25.md`. Ele registra achados adicionais verificados depois da primeira passagem, com ênfase em subsistemas legados ainda ativos e efeitos colaterais escondidos.

## E1 — existe um terceiro sistema de conteúdo ativo além de `cms_pages`

O diretório `app/content/` mantém `content_documents` com draft, published, histórico e APIs próprias (`content-get`, `content-save`, `content-publish`, `content-restore`). `content_current()` ainda cria conteúdo por seed quando não encontra registro.

Além disso, `activity_create()` copia `content_documents` ao duplicar/criar atividade.

### Risco

O projeto possui gerações diferentes de CMS executáveis ao mesmo tempo. Mesmo que a UI principal já use `cms_pages`, uma rota antiga pode continuar lendo/escrevendo no sistema anterior.

### Destino

- inventariar consumidores ainda vivos dessas APIs;
- congelar `content_documents` como legado/read-only;
- parar de copiá-lo em `activity_create()`;
- migrar somente conteúdo que ainda não esteja representado em `cms_pages`;
- remover endpoints antigos somente após comprovar ausência de uso.

## E2 — `interest_submissions` continua sendo um formulário/respostas paralelo e ativo

`interest-submit.php` continua recebendo POST público, validando o formulário legado e chamando `save_interest()`, que grava em `interest_submissions`.

`admin/interests.php` continua listando, mudando status e excluindo esses registros.

Em paralelo, `form-submit.php` e `cms_form_submissions` já oferecem o fluxo genérico atual.

### Risco

Uma resposta pode cair em um domínio diferente conforme qual página/endpoint a originou. A administração também possui duas interpretações de “Respostas”.

### Destino

- mapear o formulário legado para um `cms_form` canônico;
- preservar integralmente IDs/UUIDs, timestamps, payload, locale, consentimento e status históricos durante a migração;
- novos envios passam a usar somente `form-submit.php`;
- admin de respostas passa a consumir somente `cms_form_submissions`;
- endpoint e tabela legados ficam read-only por uma versão antes da retirada.

## E3 — reconciliação de matrícula é um efeito colateral escondido de POST administrativo

O bootstrap instala `student_account_install_reconciliation_hook()`. O mecanismo reconcilia inscrições em `register_shutdown_function()` depois de determinadas requisições administrativas e procura especificamente formulários `form_key='registration'`.

Quando uma submissão confirmada não tem turma válida, a reconciliação pode chamar `course_default_cohort(..., true)` e criar turma implicitamente.

### Risco

Uma ação aparentemente limitada a editar/confirmar resposta pode gerar conta, matrícula e até turma depois que a resposta HTTP já está sendo encerrada. Isto dificulta auditoria, tratamento de erro e previsibilidade.

### Destino

Substituir o hook por uma operação explícita, transacional e idempotente:

`submissão confirmada` → `automação do formulário = enrollment` → `criar/atualizar conta` → `matrícula`.

Se não houver turma aplicável, a matrícula fica pendente de atribuição; não se cria turma silenciosamente.

## E4 — funções de leitura sincronizam/gravam dados

Além de seeds em `cms_pages`, `cms_forms`, sitemap e `content_current()`, `course_lesson_release_rows()` chama sincronização que insere linhas ausentes de liberação.

### Regra

Queries e renderização precisam ser puras. Linhas derivadas devem ser criadas:

- quando aula/turma é criada;
- em migração;
- ou por comando explícito `ensure`, nunca como efeito colateral de listagem.

## E5 — o bootstrap carrega simultaneamente gerações antigas e novas

`app/bootstrap.php` inclui, em toda requisição, módulos do CMS atual e várias camadas anteriores/específicas: `public_content`, `interest_repository`, `content/content_*`, `student_material`, `workshop_*`, além dos módulos canônicos novos.

### Risco

Mesmo depois de uma autoridade ter sido substituída, qualquer código novo pode continuar chamando acidentalmente a implementação antiga porque ela está sempre disponível no runtime.

### Destino

- bootstrap comum carrega apenas contratos canônicos;
- adapters legados são carregados somente nos endpoints que ainda dependem deles durante a migração;
- depois da migração, remover os endpoints/adapters.

## E6 — segurança de login administrativo é mais simples que a de contas dos participantes

O login de participante já implementa timeout de sessão e bloqueio após tentativas falhas. O login administrativo verifica senha e regenera a sessão, mas não há no próprio fluxo uma política equivalente de tentativas/bloqueio.

### Destino

Antes de ampliar operadores/RBAC, alinhar a proteção básica do admin:

- rate limit por combinação de origem + conta;
- registro de tentativas sem armazenar IP bruto;
- timeout absoluto/inatividade coerente com o risco administrativo;
- audit log para login e ações destrutivas.

Não é necessário introduzir RBAC complexo enquanto houver um único operador.

## E7 — o formulário genérico perdeu o rate limit que existia no formulário legado

O fluxo legado `save_interest()` aplica `submission_rate_limits`. O endpoint genérico `form-submit.php` mantém CSRF, honeypot, tempo mínimo e limite de tamanho, mas a proteção de frequência não é a mesma capacidade genérica.

### Destino

Mover rate limiting para o mecanismo genérico de submissão e configurá-lo por política do formulário/instalação, sem voltar a depender de `interest_submissions`.

## E8 — metadados das fotografias de testes precisam de política explícita

Uploads de testes preservam os bytes do JPEG/PNG/WebP. Para fotografia isto pode ser desejável porque EXIF técnico pode ser útil na análise; porém um teste compartilhado com turma/curso também pode carregar metadados pessoais, inclusive GPS, se presentes no arquivo original.

### Decisão ainda necessária

Não remover EXIF indiscriminadamente. Definir política consciente, por exemplo:

- original privado preservado para o autor/professor;
- derivada compartilhada remove GPS e outros metadados pessoais, preservando quando possível os parâmetros fotográficos relevantes;
- ou opção explícita do usuário ao compartilhar.

Este item exige decisão de produto antes de implementação.

## E9 — hierarquia editorial precisa permanecer independente de URL e tradução

A auditoria de página já definiu três relações separadas:

1. atividade → página;
2. página mãe → página filha;
3. página conceitual → versões localizadas.

A página de inscrição do workshop é filha editorial da página principal do workshop, mas essa relação não deve mudar automaticamente o endereço já publicado. Traduções também não devem ser inferidas por slug igual.

## E10 — o banco executa migrações no primeiro acesso e precisa de serialização explícita

`database()` chama `run_migrations()` no primeiro acesso de cada processo. A migração e o registro em `schema_migrations` não são embrulhados por um lock global no runner.

Com uma única requisição após update isto normalmente funciona, mas duas requisições simultâneas na primeira inicialização podem competir pela mesma migração dependendo do conteúdo dela.

### Destino

- serializar aplicação de migrações com lock/transaction apropriado ao SQLite;
- manter o backup do updater;
- garantir que migrations individuais sejam idempotentes/atômicas quando possível;
- impedir tráfego concorrente de executar a mesma migration durante troca de versão.

## Síntese adicional

A maior dívida não é volume de código: é **sobreposição de gerações do mesmo produto ainda executáveis**.

Hoje há evidência de coexistência de:

- `cms_pages` e `content_documents`;
- `cms_form_submissions` e `interest_submissions`;
- `course_enrollments` e `student_enrollments`;
- `cms_pages` como material e `student_materials`;
- acesso CMS genérico e administração paralela `Páginas protegidas`;
- `media_assets` e slots privados legados;
- conteúdo CMS localizado e tradução hardcoded em `public_locale.php`;
- atividade genérica e setup automático do primeiro workshop.

Portanto a prioridade técnica deve ser **convergência de autoridades**, não adicionar funcionalidades sobre as duas camadas ao mesmo tempo.
