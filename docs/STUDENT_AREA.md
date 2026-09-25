# Área do aluno — estado canônico

## Regra de identidade

A conta de aluno é única entre cursos e pode possuir várias matrículas. Existem dois caminhos administrativos autorizados para que uma pessoa passe a existir na Área do aluno:

1. uma inscrição do formulário `registration` é confirmada (`status=converted` ou pagamento confirmado);
2. um participante de turma anterior é incorporado por uma **importação administrativa de planilha**, vinculada explicitamente a uma turma existente.

A importação histórica não é um cadastro manual livre. Ela exige uma planilha de origem e registra lote, linha, resultado e turma de destino para auditoria. O sistema reaproveita uma conta já existente quando e-mail/CPF identificam a mesma pessoa e recusa conflitos em que os dois identificadores apontam para contas diferentes.

O primeiro acesso usa **e-mail + CPF somente com números** como prova inicial de identidade, inclusive para contas importadas. O CPF não é senha permanente. Depois da validação inicial, o aluno reconhece o Aviso de Privacidade e cria a própria senha.

## Perfil e novas inscrições

`student_users` representa a conta; `student_profiles`, os dados reutilizáveis; `course_cohorts`, as turmas; `course_enrollments`, as matrículas. Nome, e-mail, CPF, telefone, Instagram e endereço podem preencher uma nova inscrição para um aluno autenticado, sempre permitindo revisão. Respostas específicas do curso continuam pertencendo à inscrição. O aluno consulta e corrige o perfil em `/aluno/perfil.php`.

Uma planilha histórica usa `Nome`, `E-mail` e `CPF` como colunas obrigatórias. `Telefone`, `Instagram`, `Endereço`, `Cidade/UF` e `CEP` são opcionais. O relatório de importação **não armazena CPF em texto**; mantém apenas linha, nome, e-mail, resultado e mensagem de erro. O CPF entra somente pelo mesmo mecanismo de HMAC + criptografia já usado pelo perfil.

## Turmas

Uma turma mantém identidade interna própria (`cohort_uuid`/`id`) e pode ter seus metadados corrigidos depois da criação sem recriar matrículas ou liberações.

O admin pode editar:

- nome;
- slug;
- estado (`active`, `closed`, `archived`);
- data inicial;
- data final;
- observações administrativas;
- definição como turma padrão para novas matrículas confirmadas.

Slug precisa ser único dentro da atividade. Turma arquivada não pode ser padrão. Alterar nome, datas ou slug não altera a identidade interna da turma nem desloca seus alunos.

## Registro e acompanhamento de testes

A Área do aluno possui um caderno operacional de testes em `/aluno/testes.php`. Ele não substitui o material didático; registra a experimentação realizada pelo aluno e concentra a avaliação posterior.

Cada registro pertence a **um aluno e uma turma ativa** e possui os campos usados no próprio caderno do workshop para repetibilidade:

- título e data do teste;
- filme e lote;
- ISO usado como referência;
- diafragma;
- tempo inicialmente calculado;
- tempo corrigido pela reciprocidade, quando necessário;
- condição da luz;
- diferença entre regiões claras e sombras que o aluno quer preservar;
- revelador;
- diluição;
- temperatura;
- tempo de revelação;
- movimentação/agitação;
- observações livres.

O aluno pode ainda anexar até seis imagens JPEG, PNG ou WebP, de até 12 MB cada. Esses arquivos ficam em armazenamento privado (`storage/student-test-media/`) e só são servidos depois de autenticação e verificação de propriedade. Não existe URL pública direta para o arquivo armazenado.

### Estados do teste

- `draft` — rascunho ainda sendo registrado;
- `submitted` — aluno declarou que está pronto para avaliação;
- `needs_revision` — o professor pediu ajuste, nova informação ou nova tentativa;
- `reviewed` — avaliação concluída; a ficha técnica fica preservada e bloqueada para edição, mas a conversa pode continuar.

Rascunhos também aparecem para o administrador, mas a fila prioriza registros enviados para avaliação.

### Avaliação e dúvidas

Cada teste possui uma conversa própria (`student_test_messages`). Aluno e professor podem continuar perguntas e respostas no mesmo histórico, evitando que a análise se perca em mensagens separadas.

O professor pode:

- abrir a ficha completa;
- ver as imagens enviadas;
- responder com avaliação ou orientação;
- marcar como `needs_revision`;
- marcar como `reviewed`;
- devolver para `submitted` caso precise reabrir formalmente a avaliação.

A conversa permanece associada ao teste mesmo depois de ele ser marcado como revisado.

## Conteúdo protegido continua sendo conteúdo do CMS

Material didático é uma página comum de `cms_pages`. Não existe um sistema editorial paralelo.

- `access_level=public`: página pública;
- `access_level=enrolled`: exige aluno autenticado com matrícula ativa;
- administrador autenticado: acesso integral à página protegida para inspeção, inclusive seções ainda não liberadas aos alunos.

Uma página protegida não aparece na navegação pública e recebe `noindex`. O acesso continua usando sua URL normal do CMS.

### Regra visual das páginas

Uma página de material **não possui stylesheet próprio**. O HTML editorial usa componentes, classes e atributos de layout do sistema visual geral do site.

É proibido resolver uma página protegida com `<style>` local, propriedades visuais inline ou criando no stylesheet global uma nova família de classes feita, na prática, apenas para aquela página. Mover um CSS específico da página para `assets/cms.css` não o transforma em componente global.

**Componentes novos são permitidos quando a necessidade é real, mas precisam nascer como componentes globais e reutilizáveis do CMS/site**, com nome, contrato visual e comportamento genéricos, disponíveis para qualquer página compatível e cobertos por regressão no mesmo nível. O critério não é “já existia antes”; o critério é “é realmente um componente do sistema, e não um objeto exclusivo disfarçado de global”.

Para o caderno atual, os componentes já existentes (`section`, `statement-grid`, `format`, `format-inner`, `format-heading`, `format-grid`, `format-card`, `process-list`, `process-item`, `section-label`, `statement-copy` e `technical-note`) são suficientes para capa, índice, divisores de aula, receitas, comparações e unidades editoriais. `Design → CSS adicional` continua sendo a camada editorial final para exceções deliberadas do site, não um lugar para esconder um subsistema exclusivo de uma página.

## Caderno “Positivo direto em filme de raio-X”

A página `caderno-positivo-direto` tem como fonte editorial os conteúdos efetivamente enviados aos participantes por e-mail:

- “Receitas, materiais e algumas referências para trabalhar com filme de raio-X” — 07/09/2026;
- “Segundo Encontro: Processos químicos para positivos” — 18/09/2026.

### Integridade editorial da fonte

O conteúdo técnico desses e-mails entra **integralmente** no material. A edição pode reorganizar a ordem, criar títulos, separar assuntos, agrupar trechos e distribuir conteúdo entre aulas, mas não pode resumir, substituir, simplificar ou reescrever o corpo técnico apenas para caber em um formato arbitrário.

São removidos somente elementos circunstanciais da comunicação com a turma: saudações, datas de encontro, pedidos de endereço/tamanho de suporte, chamadas para publicação em rede social e outros recados administrativos. Quando uma frase mistura recado e conteúdo técnico, preserva-se o conteúdo técnico e retira-se apenas o trecho circunstancial.

Quando os registros divergem, a divergência é preservada explicitamente. Exemplo: o primeiro e-mail define `15/550` como volume final de 550 ml; o segundo registra literalmente `10 ml` ou `20 ml de Parodinal + 550 ml de água`. O sistema não transforma silenciosamente uma notação na outra.

### Estrutura editorial do caderno

O material precisa ser reconhecível como publicação e como sequência de aulas, não como reprodução visual de um e-mail. A estrutura canônica é:

- capa do material;
- índice das três aulas;
- divisor visual **Aula 01 — Filme e exposição**;
- unidades editoriais sobre filme ortocromático, dupla emulsão, construção do positivo, exposição/energia, reciprocidade, EI e luz de segurança;
- divisor visual **Aula 02 — Processos químicos para positivos**;
- unidades editoriais sobre segurança, imagem latente, reveladores, receitas, branqueamento, segunda revelação, parâmetros de desenvolvimento, comparação EI 200 × EI 400 e materiais;
- divisor visual **Aula 03 — Revisão de resultados**;
- conteúdo dos próprios e-mails sobre leitura dos resultados, repetibilidade e registro dos testes.

Cada assunto continua sendo uma `data-cms-section` independente para edição e liberação. A separação visual entre aulas e unidades é feita com componentes globais do site.

A Aula 3 não recebe conteúdo inventado: ela usa somente o que os e-mails já dizem sobre avaliar as chapas, localizar mudanças na escala, modificar uma variável por vez e registrar as condições do teste.

## Infográficos privados

Os elementos visuais explicativos do material são **infográficos ilustrados gerados a partir de relações concretas descritas nos e-mails**, não fotografias simuladas, não grafismos HTML/SVG, não equipamentos inventados e não explicações genéricas de fotografia química.

A página contém slots semânticos de mídia privada onde uma imagem explicativa realmente reforça o texto:

- `filme-ortocromatico` — resposta ortocromática: vermelhos mais escuros; verdes e azuis mais claros;
- `dupla-emulsao-positivo` — emulsão nos dois lados da base e sua relação com densidade máxima e transparência;
- `energia-positivo` — uma única exposição, diferenças de EV, prata formada na primeira revelação, remoção no branqueamento e densidade/transparência final;
- `reciprocidade-energia` — diferença entre baixa energia na cena e falha de reciprocidade, usando fórmula e exemplos registrados no e-mail;
- `ei-zonas` — EI 200 × EI 400 como deslocamento de uma zona e perda de separação nas regiões baixas;
- `imagem-latente-prata` — haletos, alteração pela luz, imagem latente, `Ag⁺ + elétron → Ag⁰` e prata metálica;
- `negativo-positivo` — depois da primeira revelação, o que fica e o que sai para formar negativo ou positivo;
- `branqueamentos-rotas` — rota peracética por oxidação e rota FeCl₃ → AgCl → limpeza separada com amônia;
- `parametros-revelacao` — concentração, tempo, agitação e temperatura como parâmetros que atuam por mecanismos diferentes.

O infográfico é uma ilustração editorial. Não deve parecer uma fotografia de laboratório nem uma “página pronta” colocada dentro da página do site. Título, legenda, fórmulas extensas e explicação permanecem no HTML/CMS quando já existem no texto; a imagem serve para tornar visível a relação explicada no trecho correspondente.

O HTML persistido contém apenas `<figure data-private-media-slot="…">`. O arquivo real é enviado pelo admin e associado ao slot. Se um slot ainda não tiver arquivo, ele é removido da resposta pública: nenhum texto de placeholder ou instrução de desenvolvimento aparece para o aluno.

Os arquivos ficam em `storage/student-media/`, fora do acesso HTTP direto. Para alunos, o renderer troca a referência persistente por URL assinada e temporária vinculada a aluno, página e turma. Para administradores autenticados, a mesma mídia pode ser visualizada diretamente durante a inspeção da página protegida.

## Liberação por aula

As seções da página usam `data-cms-section`. `course_page_sections` liga uma seção a uma aula; `cohort_lesson_releases` controla a liberação por turma. Uma seção bloqueada é removida no servidor antes do HTML ser enviado ao aluno. Não existe `display:none` para conteúdo ainda não liberado.

Os próprios divisores `caderno-aula-1`, `caderno-aula-2` e `caderno-aula-3` são mapeados às aulas correspondentes. Assim uma aula ainda bloqueada não deixa no documento um cabeçalho anunciando conteúdo que o aluno não pode acessar.

O bypass administrativo é deliberado: um administrador precisa conseguir verificar a página completa e suas mídias sem possuir uma matrícula de aluno.

## Administração e escala

`Admin → Inscrições → Área do aluno` continua concentrando configuração estrutural: Visão geral, Turmas, Aulas, Páginas protegidas e Alunos.

`Admin → Inscrições → Operações e testes` concentra tarefas operacionais que não pertencem ao CMS editorial:

- **Dados das turmas** — corrigir nome, slug, período, estado, observação e turma padrão;
- **Importar alunos** — incorporar histórico por CSV/XLSX com relatório por linha;
- **Testes dos alunos** — fila de avaliação, filtros por turma/estado, imagens e conversa.

As duas superfícies usam o mesmo sistema de UI/UX do restante da administração. Listas extensas usam componentes compartilhados, filtros, busca e tabelas com overflow responsivo. A tela não deve depender de carregar todos os alunos e todos os registros em uma única coluna crescente.

## LGPD e segurança

O tratamento de dados segue finalidade, necessidade, transparência e segurança. O CPF é usado como prova inicial de identidade, indexado por HMAC e mantido de forma recuperável apenas quando necessário ao perfil, criptografado em repouso. Senhas são hashes e o CPF nunca é gravado como `password_hash`.

Importações históricas não persistem CPF no relatório operacional. Imagens de testes ficam fora do webroot e são entregues apenas após autenticação e autorização. Tanto a Área do aluno quanto a mídia de teste usam `no-store`; páginas privadas recebem `noindex`.

Autenticação, filtro server-side, armazenamento privado, URLs protegidas e cabeçalhos de cache reduzem exposição e redistribuição casual. Nenhum sistema web impede que um usuário autorizado fotografe a tela ou reproduza manualmente o conteúdo recebido.
