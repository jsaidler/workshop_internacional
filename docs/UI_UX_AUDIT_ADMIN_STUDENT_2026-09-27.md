# Auditoria UI/UX — administração e área do aluno — 27/09/2026

## Estado de partida

Esta auditoria parte do domínio de cursos já reconciliado na instalação: uma pessoa é global, inscrição é diferente de matrícula, turma pertence a um curso, material continua sendo página CMS e a liberação progressiva é feita pelas aulas da turma.

O objetivo desta etapa não é alterar novamente o modelo de dados. É fazer a interface refletir o modelo que já foi estabilizado.

Regra de implementação para UI: **procurar → consumir → identificar lacuna → ampliar globalmente somente se necessário → consumir**. Não se cria um segundo botão, campo, validação, editor, renderer ou controle de tema para resolver um caso local.

---

# 1. Arquitetura de informação da administração

## Problema

A administração herdou a sequência de modelagens anteriores. A área chamada **Inscrições** reunia Cursos, Inscrições, Pessoas, Formulários, respostas genéricas e Integridade. Isso obrigava o administrador a entender relações internas do banco para localizar uma tarefa.

## Autoridade de navegação

A navegação principal passa a representar domínios de trabalho:

- **Conteúdo**
  - Páginas
  - Formulários
  - Respostas
  - Navegação
  - Visual
  - SEO
- **Cursos**
  - Cursos
  - Inscrições
  - Pessoas
- **Métricas**
- **Mídia**
- **Configurações**
  - Sistema e atualizações
  - Estrutura do site
  - Diagnóstico de dados

`Integridade` deixa de disputar espaço com trabalho cotidiano e permanece disponível como diagnóstico técnico.

## Curso como contexto administrativo

Ao abrir um curso, a navegação contextual deve ser única e estável:

1. Visão geral
2. Inscrições
3. Turmas
4. Alunos
5. Aulas
6. Material

**Inscrições não é um botão lateral fora dessa navegação.** É uma dimensão do próprio curso.

A Visão geral concentra indicadores e configuração de baixa frequência. O curso não ganha editor próprio: sua página e seu formulário continuam sendo editados nas ferramentas globais do CMS.

## Nascimento explícito do papel de curso

Uma página CMS pode receber o papel de página pública de um curso. Essa decisão nasce em **Páginas**, no menu da própria página:

- `Usar esta página como curso` quando ainda não existe relação;
- `Administrar curso` quando já existe.

A tela Cursos não oferece um segundo fluxo de criação de conteúdo e não duplica a página.

## Cursos — visão operacional

A lista de cursos deve mostrar rapidamente o estado de cada curso: inscrições, turmas, alunos e aulas. A ação principal é entrar no contexto do curso, não configurar infraestrutura.

## Pessoas

`student_users` continua sendo a autoridade de identidade global. Portanto:

- **Pessoas** mostra a pessoa independentemente de cursos;
- **Curso → Alunos** mostra matrículas daquele curso;
- o nome do aluno contextual abre a identidade global;
- inscrições e matrículas aparecem separadas no detalhe da pessoa.

---

# 2. Inscrições como ferramenta operacional

## Problema

A tela já separava estados tecnicamente, mas ainda se comportava como uma lista de registros. O trabalho real é decidir quem pagou, quem ainda precisa de turma e quais disponibilidades permitem formar uma turma.

## Resumo obrigatório

No contexto de um curso, a tela deve apresentar contadores independentes para:

- todas as inscrições;
- aguardando pagamento;
- confirmadas sem turma;
- com turma;
- canceladas.

Pagamento e atribuição de turma são dimensões diferentes. Confirmar pagamento nunca escolhe turma automaticamente.

## Disponibilidade agregada

A interface deve agregar as opções de disponibilidade registradas no formulário e mostrar quantas pessoas marcaram cada opção. Isso transforma a tela numa ferramenta de formação de turma, e não apenas numa caixa de entrada.

A agregação usa os valores e rótulos do snapshot do próprio formulário. Não cria uma nova autoridade de disponibilidade.

---

# 3. Uma única barra superior canônica para o aluno

## Problema

Existiam duas experiências de cabeçalho:

1. a barra da aplicação em `/aluno/*`;
2. a barra pública normal do CMS quando o aluno abria uma página de material.

Além disso, algumas telas criavam uma segunda faixa no topo (`student-appbar`) para voltar ao curso e mostrar estado da turma.

Isso produz a sensação de duas aplicações diferentes e de duas barras superiores concorrentes.

## Regra canônica

Há **uma única barra superior do aluno**, usada tanto pela aplicação quanto por páginas CMS abertas em contexto de material.

A barra global contém apenas ações globais:

- marca;
- Meus cursos;
- Conta;
- tema;
- identidade/sair.

`Testes` não é uma entidade global paralela ao curso. Testes pertencem ao contexto educacional do curso/turma.

Quando uma página CMS está sendo consumida como material por um aluno matriculado, o renderer continua sendo o renderer normal do CMS, mas o **shell visual público é substituído pelo shell canônico do aluno**. Não aparece simultaneamente a barra pública do site.

## Cabeçalho de contexto do curso

Abaixo da barra global existe um cabeçalho de conteúdo, não sticky, com:

- `← Meus cursos`;
- nome do curso;
- turma;
- estado da turma;
- navegação contextual: **Visão geral / Material / Testes**.

Esse bloco pode aparecer na visão geral, nos testes e nas páginas CMS de material. Ele não é uma segunda topbar.

---

# 4. Área do aluno orientada ao curso

## Arquitetura

A estrutura percebida deve ser:

```text
Meus cursos
└── Curso
    ├── Visão geral
    ├── Material
    └── Testes

Conta
```

Quando houver apenas uma matrícula, a aplicação pode abrir diretamente esse contexto. Quando houver mais de uma, `Meus cursos` é o seletor.

Acesso direto a Testes sem `cohort` pode continuar apresentando o seletor quando houver múltiplas matrículas; isso é fallback de navegação, não a estrutura principal.

## Material

Um curso pode ter várias páginas de material. Elas aparecem como lista de recursos, uma linha por página CMS. Abrir um recurso leva à própria página CMS, com o shell do aluno e as seções já filtradas pela liberação das aulas.

## Aulas

Estados usam linguagem direta: **Liberada / Agendada / Aguardando**. O aluno vê o que está disponível sem precisar interpretar `released_at` ou conceitos técnicos.

## Testes

Testes são acessados a partir do contexto da turma. A lista, a ficha de teste e os testes compartilhados devem manter o mesmo cabeçalho de contexto do curso e não criar outra barra superior.

---

# 5. Hierarquia visual de aplicação

A área do aluno usa a identidade visual do site, mas não deve se comportar como uma landing page em todas as telas.

Critérios:

- títulos de aplicação menores e com line-height confortável;
- caixa alta não é padrão obrigatório para títulos funcionais;
- conteúdo útil aparece mais cedo na dobra;
- metadados ficam subordinados à ação principal;
- listas de recursos não viram uma sequência de botões primários concorrentes;
- desktop e mobile mantêm a mesma hierarquia conceitual.

A navegação inferior mobile, quando presente, representa apenas navegação global e não usa numeração decorativa `01 / 02 / 03`.

---

# 6. Ritmo vertical e espaçamento

A falta de espaçamento entre títulos, campos, botões e blocos é tratada como defeito estrutural, não cosmético.

## Escala de referência

- 4 px — microajuste;
- 8 px — elementos intimamente relacionados;
- 12 px — rótulo/controle e controles pequenos;
- 16 px — componentes relacionados;
- 24 px — grupos dentro de uma seção;
- 32 px — blocos de conteúdo;
- 48 px — seções principais;
- 64 px ou mais — mudança grande de contexto.

Os valores podem ser consumidos por tokens/variáveis já existentes ou por variáveis globais da superfície, mas a relação semântica deve permanecer.

## Responsabilidade do espaço

O container/componente de composição é responsável pelo ritmo entre seus filhos. Não se corrige cada ocorrência espalhando `margin-top` arbitrário em páginas individuais.

São defeitos de regressão:

- título encostado no bloco anterior;
- campo/fieldset encostado no campo anterior;
- texto de ajuda colado ao próximo controle;
- botão imediatamente após input/textarea sem respiro;
- card encostado em card sem intenção compositiva;
- alerta/estado vazio colado ao cabeçalho;
- área destrutiva sem separação suficiente;
- breakpoint mobile que zera o espaçamento vertical.

Também se revisa o inverso: elementos do mesmo grupo não devem ser separados como se fossem seções diferentes.

---

# 7. Responsividade e validação

A auditoria deve cobrir desktop e mobile para:

- navegação global;
- contexto do curso;
- formulários;
- filtros e indicadores;
- listas de material e testes;
- estados vazios;
- áreas destrutivas;
- tabelas e cards administrativos.

## Segurança e autoridade

A reorganização visual não pode mudar a autorização. Ao final, permanecem obrigatórias regressões para:

- curso conhecido falhar fechado;
- aluno do curso A não acessar material do curso B;
- seção não liberada não chegar no HTML;
- mídia privada de seção bloqueada não ser resolvida/entregue;
- nenhuma retomada de fallback legado por `activity_id` quando o curso é conhecido.

---

# Resultado esperado desta rodada

A administração deve poder ser usada sem conhecimento do esquema do banco. A área do aluno deve parecer uma única aplicação mesmo quando o material é uma página CMS. Curso, pessoa, inscrição, turma, matrícula, aula e material continuam com suas autoridades de domínio atuais; a UI apenas passa a representá-las de forma coerente.