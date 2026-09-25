# Área do aluno — estado canônico

Este documento é a autoridade funcional da Área do aluno. A implementação deve reutilizar os domínios já existentes do CMS em vez de criar sistemas paralelos.

## Princípios de arquitetura

- **Design** é autoridade de tipografia, cores e tema. A Área do aluno carrega `template/page.css` e os tokens dinâmicos configurados em `Design`; `assets/student-area.css` define somente layout e comportamento do aplicativo.
- **Atividade/curso** é autoridade do nome público do curso (`activities.public_title`). Esse nome é editável em `Site → Navegação → Identidade`, junto de Nome do site e Wordmark.
- **Biblioteca de mídia** (`media_assets`) é a única autoridade para mídia editorial reutilizável, pública ou privada.
- **Fotos de testes** não pertencem à biblioteca editorial: são anexos privados de um registro experimental do aluno e possuem ciclo de vida próprio.
- **Página protegida** continua sendo uma `cms_page` normal. Não existe editor, stylesheet ou sistema editorial paralelo para materiais de curso.
- **Liberação por aula** é feita no servidor. Conteúdo bloqueado não é enviado ao HTML do aluno.

## Conta, perfil e matrículas

A conta de aluno é única entre cursos e pode possuir várias matrículas. Uma pessoa entra no sistema por dois caminhos autorizados:

1. inscrição `registration` confirmada;
2. importação histórica por **CSV**, vinculada explicitamente a uma turma existente.

`student_users` representa a conta; `student_profiles`, os dados reutilizáveis; `course_cohorts`, as turmas; `course_enrollments`, as matrículas.

O primeiro acesso usa e-mail + CPF como prova inicial de identidade. O CPF não é senha permanente. Depois da validação, o aluno reconhece o Aviso de Privacidade e cria a própria senha.

### Importação histórica

O formato canônico é CSV com cabeçalho:

```text
Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP
```

Nome, E-mail e CPF são obrigatórios. A tela `Alunos → Importar CSV` oferece o modelo para download e um exemplo de linha. A interface é renderizada corretamente pelo PHP; não depende de JavaScript para renomear campos, trocar `accept` ou reescrever o `action` do formulário.

## Turmas

Uma turma mantém identidade interna própria (`cohort_uuid`/`id`). O administrador pode editar nome, slug, estado, início, fim, observações e definição como turma padrão sem recriar matrículas ou liberações.

## Registro de testes

A Área do aluno possui um caderno operacional em `/aluno/testes.php`. O fluxo móvel acompanha a ordem real do trabalho:

1. **Exposição** — fotografia/anexo da cena; filme/lote; EI/ISO; diafragma; tempo calculado; tempo corrigido por reciprocidade; condição da luz; relação entre claras e sombras.
2. **Revelação** — revelador; diluição; temperatura; tempo; movimentação/agitação; observações; fotografia/anexo do resultado.
3. **Revisar e enviar** — conferência da ficha e das imagens antes de solicitar avaliação.

As fotografias ficam em `storage/student-test-media/`, fora do acesso HTTP direto, e são servidas somente após autorização.

### Propriedade, exclusão e visibilidade

O aluno é proprietário dos testes que cria. Ele pode excluir definitivamente um teste independentemente do estado da avaliação. A exclusão remove ficha, mensagens, registros de mídia e arquivos físicos das fotografias.

Cada teste possui visibilidade explícita:

- `private` — somente autor e administração;
- `cohort` — autor, administração e alunos com matrícula ativa na mesma turma;
- `course` — autor, administração e alunos com matrícula ativa no mesmo curso/site.

Não existe publicação anônima na web. Compartilhar um teste disponibiliza somente ficha técnica e imagens. **A conversa de avaliação entre aluno e professor permanece privada.**

### Avaliação

Estados:

- `draft` — registro em andamento;
- `submitted` — aguardando avaliação;
- `needs_revision` — ajustes solicitados;
- `reviewed` — revisão concluída.

A conversa `student_test_messages` permanece vinculada ao teste e pode continuar depois da revisão.

## Conteúdo protegido continua sendo CMS

`access_level=public` torna a página pública; `access_level=enrolled` exige matrícula ativa. Administradores podem inspecionar a página completa.

Uma página protegida não recebe CSS próprio. O HTML editorial usa os mesmos componentes globais do site. É proibido resolver problemas criando uma segunda família tipográfica, uma paleta paralela, `<style>` local ou classes “globais” que na prática existam somente para uma página.

## Estrutura e liberação por aula

Cada unidade editorial liberável possui `data-cms-section`. O sistema usa:

- `course_page_sections`: seção → aula;
- `cohort_lesson_releases`: aula → estado de liberação para uma turma.

`student_page_filter_document()` remove do DOM as seções cujas aulas não estão liberadas **antes** da resposta HTML. Não existe `display:none` para esconder material bloqueado.

Em `Admin → Inscrições → Área do aluno → Páginas protegidas`, a lista mostra as seções reais encontradas no documento, agrupadas pela aula à qual pertencem. A ação **Visualizar como turma** abre a página passando pelo mesmo filtro server-side usado para alunos. Isso é a verificação administrativa canônica de que a segmentação funciona.

Para o caderno `caderno-positivo-direto`, capa, índice, divisores de Aula 1/2/3 e unidades editoriais continuam sendo elementos reais no HTML. Os divisores de aula também são mapeados, para que uma aula bloqueada não deixe um cabeçalho órfão.

## Mídia editorial privada

A biblioteca `media_assets` é a única fonte de mídia editorial.

Cada asset possui `visibility = public|private`:

- **Pública** — uso normal no site;
- **Privada** — sem entrega direta normal; pode ser vinculada a conteúdo protegido.

Upload, título, metadados e mudança de privacidade acontecem somente em **Admin → Mídia**. Uma mídia que já esteja usada em conteúdo público não pode ser transformada silenciosamente em privada.

Os slots do material (`data-private-media-slot`) armazenam somente a relação com um `media_asset` através de `course_page_media_slots`. Em **Páginas protegidas** não existe segundo uploader: a interface apenas mostra o slot e permite **Vincular mídia / Trocar mídia / Remover vínculo**, escolhendo imagens privadas existentes na biblioteca.

Dados históricos de `student_private_media` são migrados para `media_assets` antes de a interface paralela deixar de ser usada. Fotos produzidas pelos alunos em testes permanecem separadas porque não são mídia editorial reutilizável.

## Caderno “Positivo direto em filme de raio-X”

A fonte editorial são os conteúdos efetivamente enviados aos participantes por e-mail. O corpo técnico não pode ser resumido, reescrito ou completado com conhecimento externo apenas para ajustar a composição.

Estrutura canônica:

- capa;
- índice;
- **Aula 01 — Filme e exposição**;
- unidades sobre filme ortocromático, dupla emulsão, construção do positivo, exposição/energia, reciprocidade, EI e luz de segurança;
- **Aula 02 — Processos químicos para positivos**;
- unidades sobre segurança, imagem latente, reveladores, receitas, branqueamento, segunda revelação, parâmetros, comparação EI 200 × EI 400 e materiais;
- **Aula 03 — Revisão de resultados**;
- leitura dos resultados, repetibilidade e registro dos testes.

Cada assunto continua sendo uma `data-cms-section` independente para edição e liberação.

## Administração

`Admin → Inscrições → Área do aluno` é organizado por objeto:

1. Visão geral;
2. Turmas;
3. Alunos;
4. Testes;
5. Aulas;
6. Páginas protegidas.

Não existe “Operações e testes”.

### Inscrições

Cancelar/arquivar e excluir são ações distintas. Em `Admin → Inscrições → Respostas`, a inscrição possui uma área destrutiva explícita para **Excluir inscrição definitivamente**. Quando a inscrição originou uma matrícula, essa matrícula é removida. Conta, perfil e testes do aluno não são apagados silenciosamente.

## Segurança e regressão

A regressão deve provar, no mínimo:

- Área do aluno consome o Design global e não declara autoridade própria de fonte/paleta;
- nome público do curso é editável no contexto do site atual;
- exclusão de teste remove registros e arquivos físicos;
- visibilidade de teste respeita `private|cohort|course` e nunca compartilha a conversa de avaliação;
- uma turma com apenas Aula 1 liberada recebe HTML sem as seções de Aula 2 e Aula 3;
- mídia editorial privada vem de `media_assets` e Páginas protegidas não contém uploader paralelo;
- importação histórica é CSV nativo no HTML/PHP;
- exclusão permanente de inscrição continua disponível;
- CI, browser regression, build e dry-run passam antes do merge.
