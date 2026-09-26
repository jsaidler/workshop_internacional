# Autoridade de finalidade de formulário e matrícula — 2026-09-26

## Motivo

`form_key` é identidade editorial e não pode decidir efeitos operacionais. Até esta tranche, `registration` acionava tratamento administrativo, reconciliação de conta/matrícula e exclusão de matrícula por convenção escondida.

## Autoridade nova

`cms_forms.purpose` passa a ser a autoridade explícita, com quatro valores:

- `common`: formulário comum;
- `interest`: interesse/pesquisa;
- `registration`: inscrição sem criação automática de matrícula;
- `enrollment`: inscrição que participa do lifecycle de conta, turma e matrícula.

A chave (`form_key`) continua estável e independente da finalidade.

## Migração e preservação

A migração `069_form_purpose_authority.php` é aditiva. Ela cria a coluna, preserva IDs/chaves/submissões e faz backfill conservador: o formulário legado `registration` recebe `enrollment`, mantendo exatamente o comportamento já existente; `interest` recebe `interest`; demais formulários recebem `common`.

O fallback de `cms_form_purpose()` para chaves antigas existe apenas para compatibilidade de leitura antes da migração. Uma vez presente um valor explícito, ele sempre tem precedência — inclusive `common` numa chave historicamente chamada `registration`.

## Interfaces e efeitos

A finalidade é editada no editor canônico de `Admin → Formulários`; não foi criada interface paralela. O painel de respostas, a reconciliação de contas/matrículas, a atribuição de turma e a exclusão da inscrição consultam a finalidade `enrollment`.

O template `Positivo direto` declara explicitamente `enrollment` para o formulário de inscrição e `interest` para a pesquisa EN. Template visual inicial e finalidade operacional são decisões separadas.

## Regressão

`tools/test-form-purpose-enrollment-authority.php` prova o backfill/idempotência e bloqueia o retorno de decisões operacionais baseadas em `form_key='registration'` nos caminhos de matrícula, lifecycle e respostas.
