# Área do aluno — experiência canônica

Data: 2026-09-30
Status: canônico e posterior ao passe visual do PR #157.

> **Navegação:** as regras de navegação deste documento foram supersedidas por `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md`, atualizado para a arquitetura mobile-first v2 de cinco destinos em 13/10/2026. Em qualquer conflito sobre barra inferior, logo, voltar, breadcrumbs, estado ativo, mobile-first ou arquitetura global/contextual, o documento de 07/10 prevalece.

Este documento prevalece sobre decisões anteriores de navegação e apresentação da Área do aluno quando houver conflito. O domínio e os dados definidos em `STUDENT_AREA_PRODUCT_AND_IMPLEMENTATION_2026-09-30.md` continuam válidos; o que muda aqui é a forma como o aluno encontra e usa essas capacidades.

## 1. Princípio

A interface não deve exigir que o aluno compreenda a arquitetura interna do sistema, a diferença entre escopo global e escopo de matrícula ou a classificação técnica de uma funcionalidade.

A navegação responde ao que a pessoa veio fazer:

- **Início** — orientação e acesso rápido.
- **Cursos** — estudar: aulas, material e dúvidas.
- **Caderno** — registrar: exposição, processamento e resultado.
- **Laboratório** — fazer: exposição, temporização, calibração, preparo químico, inventário e preparos salvos.

**Conta** é configuração e não possui o mesmo peso das quatro tarefas principais. Permanece acessível no cabeçalho.

## 2. Início

`/aluno/` é uma página de orientação, não o curso selecionado e não um dashboard de métricas.

Ela deve permitir entender a área sem aprender sua estrutura previamente. Os três eixos são apresentados em linguagem de ação:

- Estudar → Cursos;
- Registrar → Caderno;
- Fazer → Laboratório.

A página pode mostrar contexto útil já existente — matrícula disponível, último registro e quantidade de instrumentos — mas não inventa progresso, última atividade ou recomendação que o sistema não possua.

### Início operacional mobile-first — 12/10/2026
A home não escolhe uma capacidade exclusiva e não deve apresentar somente links genéricos. O encontro futuro agendado e autorizado é a informação temporal prioritária quando existe, mas o retorno do professor, o trabalho em registro, o material disponível, cursos, dúvidas e registros recentes devem permanecer acessíveis sem atravessar um menu genérico. Tarefas autônomas não são escondidas pelo destaque de outra tarefa.

- Somente encontros futuros marcados `scheduled`, derivados da Agenda por matrícula regular ou ouvinte confirmado, aparecem como próximo encontro; status cancelado/realizado e datas passadas são excluídos. Link só com esquema http(s), e link ausente não deve tornar o encontro inacessível na Agenda.
- Material da matrícula só ganha ação direta se a disponibilidade canônica permitir leitura. Múltiplas matrículas geram múltiplas entradas, cada qual com seu `cohort`; não bloquear a home atrás de escolher curso.
- Novo registro abre na própria home a gaveta curta compartilhada com o Caderno; POST é tratado na própria home pela função canônica \`student_process_create_record()\`; erro mantém a gaveta e o rascunho na home, sucesso abre o registro. Recursos restritos seguem regra de autorização e não aparecem como se estivessem liberados. A ação que leva à biblioteca de Processamentos é nomeada Processamentos, não Temporizador, já que a URL antiga redireciona à biblioteca.
- Dados não persistidos de leitura, presença, desempenho, pendência laboratorial ou 'novidade' não podem ser inferidos. Caderno pode mostrar rascunho/revisão, mas nunca obrigatoriedade de sequência física.
- O renderer da home é uma unidade compartilhada entre produção e a fixture de auditoria. Registrar separadamente evidência visual em 320, 390 e desktop, temas claro e escuro, estados variados, rolagem, drawer e mudanças entre estados.
- O projeto PWA instalável fica separado da navegação e exige manifesto, ícones, armazenamento seguro e validação; não configurar cache de páginas autenticadas por antecipação.

## 3. Cursos

`/aluno/cursos.php` concentra seleção e contexto de curso. Ao abrir um curso, o aluno encontra disponibilidade de aulas, material e dúvidas.

A navegação contextual `Visão geral / Material / Dúvidas` é secundária e pertence ao conteúdo do curso; não deve parecer uma segunda barra global.

Disponibilidade de aula não é progresso do aluno. A interface usa termos como `Disponível`, `Agendada`, `Aguardando` e `aulas disponíveis`.

## 4. Caderno

O Caderno continua global e pessoal. O objeto principal é o registro.

A hierarquia visual de uma linha é:

1. título do registro;
2. caminho do processo ou indicação de ausência de processamento;
3. contexto e compartilhamento;
4. data e atualização;
5. ações secundárias.

Ações de operação não podem ser texto solto. Repetir, comparar, salvar acesso, excluir e demais operações aparecem como controles com affordance de botão. Ações destrutivas usam o modificador visual destrutivo global.

## 5. Laboratório

A antiga apresentação `Ferramentas` deixa de ser um catálogo de pequenos aplicativos e passa a ser **Laboratório**.

Os instrumentos são organizados pela situação de uso:

- **Exposição** — exposição equivalente e reciprocidade;
- **Processamento** — temporizador e calibração;
- **Química e estoque** — preparo de soluções e inventário;
- **Preparos salvos** — configurações pessoais reutilizáveis no Caderno.

Os URLs técnicos anteriores podem permanecer por compatibilidade. A linguagem da interface usa Laboratório.

## 6. Contrato visual

A Área do aluno herda tokens e primitivas globais. Não cria uma segunda paleta, tipografia, botão, campo ou sistema de tema.

Regras de composição:

- hierarquia deve ser percebida antes da leitura detalhada;
- títulos de aplicação não usam escala de landing page;
- uma superfície não vira card apenas para existir;
- divisores e espaço substituem caixas aninhadas sempre que não houver necessidade semântica de contenção;
- botões têm aparência de botão; links de texto ficam reservados a navegação contextual, breadcrumbs e links dentro de conteúdo;
- ações do mesmo grupo compartilham altura, alinhamento e espaçamento;
- formulário termina com uma faixa de ações clara, nunca com botão solto entre campos;
- estados e metadados são secundários ao objeto e à ação principal;
- no mobile, a navegação inferior contém somente Início, Cursos, Caderno e Laboratório;
- Conta permanece no cabeçalho e não ocupa um quinto destino primário.

## 7. Mobile e uso em laboratório

A versão mobile não é apenas o desktop estreitado.

- ações principais ocupam largura útil quando isso reduz erro de toque;
- controles de processo têm alvos grandes;
- ações persistentes podem ficar acima da navegação inferior durante fluxos longos;
- tabelas comparativas podem rolar, mas o restante da página não cria overflow horizontal;
- informação secundária quebra para a linha seguinte em vez de comprimir títulos e controles.

## 8. Compatibilidade

Nenhuma mudança desta etapa altera schema, propriedade de registros, regras de acesso, persistência ou contratos de matrícula.

`/aluno/ferramentas.php` permanece como URL do Laboratório por compatibilidade. Rotas existentes dos instrumentos também permanecem.

## 9. Critérios de regressão

A regressão deve impedir:

- retorno de `Meus cursos / Caderno / Ferramentas / Conta` como quatro destinos equivalentes;
- retorno da raiz `/aluno/` como workspace de curso;
- uso de `Progresso` para simples disponibilidade de aulas;
- ações operacionais renderizadas como texto sem affordance;
- cards promocionais na listagem de instrumentos;
- Conta ocupando a navegação inferior;
- quebra da autoridade visual global;
- perda de contexto de curso, Caderno, inventário, preparos, dúvidas ou material.

## 10. Tranche D — Dashboard, Curso e Material — 04/10/2026

Esta tranche explicita como as três superfícies acadêmicas consomem o domínio canônico de curso/turma/matrícula/aula/material sem criar um segundo modelo.

### Dashboard

O Dashboard responde à pergunta **“o que faço agora?”**. A prioridade não é simplesmente o registro mais recente.

1. Se existe um registro do Caderno que ainda exige uma ação real, ele pode ocupar a ação principal: exposição/processamento incompletos, resultado ainda não registrado, revisão solicitada ou registro de curso pronto para revisão/envio.
2. Registros enviados ou revisados deixam de ser prioridade. Um registro pessoal com processamento e resultado já documentados também deixa de monopolizar a tela.
3. Sem trabalho pendente no Caderno e com uma única matrícula, o Dashboard pode levar diretamente ao primeiro material atualmente utilizável, preservando `cohort`.
4. Com múltiplas matrículas, a escolha do curso é necessária porque muda o próximo contexto; com uma única matrícula essa escolha não é repetida.
5. Sem matrícula ativa, o Caderno pessoal continua sendo um próximo passo válido.

Não existe estado canônico de “conteúdo novo” ou “não lido”. Uma liberação muda o que está disponível, mas não deve ser anunciada como novidade até existir um modelo persistente de leitura/ciência do aluno.

### Curso

A unidade editorial do material é a **página CMS associada ao curso**. A aula não substitui essa estrutura: ela funciona como regra acadêmica de disponibilidade das seções associadas.

- páginas permanecem na ordem editorial definida por `course_material_pages`;
- uma página pode estar `Disponível`, `Parcial`, `Agendada` ou `Aguardando` conforme as seções mapeadas e a disponibilidade da turma;
- seções não associadas a aula permanecem livres dentro da página autorizada;
- aulas já liberadas não precisam ser duplicadas em uma segunda coluna de “progresso”; somente liberações futuras/pendentes podem aparecer como contexto secundário;
- a ausência de material e a ausência de aulas possuem estados vazios próprios;
- a troca de curso só aparece quando existe mais de uma matrícula.

### Material

O Material continua sendo uma página editorial CMS de leitura. O HTML bloqueado continua sendo removido no servidor antes da camada de anotações.

A superfície do aluno acrescenta somente contexto acadêmico externo ao conteúdo editorial:

- curso e turma atuais;
- retorno explícito ao curso;
- Dúvidas preservando `cohort`;
- navegação anterior/próximo entre páginas atualmente utilizáveis do mesmo curso;
- preservação de `cohort` em todos esses percursos;
- Caderno de anotações existente sem alterar a estrutura editorial da página.

Esse contexto não deve se transformar em uma segunda barra global nem em um dashboard dentro do material. No telefone ele permanece no fluxo normal da leitura; não pode cobrir o conteúdo nem depender de blur/transparência persistente.


### Agenda: prioridade temporal e uso no telefone (12/10/2026, PR #242)

No Início, a Agenda deve ser primeiro acesso rápido para alunos matriculados e primeiro link da gaveta Atalhos. Na Agenda, exibir encontros futuros agendados primeiro (incluindo o intervalo de duração registrada) e recolher encontros anteriores/cancelados numa seção consultável. Não usar a ausência de mudança administrativa para afirmar realização do encontro. As próximas turmas e suas vagas permanecem subordinadas às regras canônicas de ouvinte; detalhes de datas podem ser expandidos sem gerar outra fonte. Links de encontro expostos ao aluno exigem URL HTTPS válida e autorização conferida pelo serviço de agenda, sem acesso ativo no histórico.


### Reconstrução mobile-first v2 (13/10/2026)

A seção histórica que exige quatro destinos é supersedida: **Início / Agenda / Cursos / Caderno / Laboratório** são cinco áreas globais. A Home deve usar o mesmo dado autorizado que a Agenda, sem duplicar matrículas ou inventar presença. A mudança visual é incremental e não significa que todas as áreas tenham sido migradas. Autoridade de navegação: `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md` seção 17.

### Home acadêmica v6.2 — revisão de qualidade (19/10/2026, PR #267)

Os encontros prioritários usam a janela de 48 horas sem antecipar urgência; o horário exibido é convertido para o fuso cadastrado e o nome do fuso mostrado na Home deve coincidir com a autoridade da Agenda (`course_agenda_timezone_label`). A central de notificações possui título próprio no cabeçalho, ainda que permaneça no destino global Início. A conferência visual abrange 320, 390 e 1440 px, temas e estados acadêmicos, sem substituir os contratos funcionais e de autorização.


### Materiais do positivo direto e bibliografia comum — 19/10/2026

- A lista de compras do curso **Positivo Direto em Filme de Raio-X** é uma página CMS normal, associada apenas ao curso canônico em `course_material_pages`. Todas as quatro seções (`materiais-introducao`, `materiais-laboratorio`, `materiais-quimicos`, `materiais-opcionais`) são vinculadas à **primeira aula preexistente** do curso por `course_material_sections`. O filtro da página é aplicado no servidor por matrícula ativa e pela liberação efetiva da aula na turma; não alterar `released_at` para viabilizar o conteúdo. Antes da liberação, nenhuma lista é enviada no HTML.
- A bibliografia original de João Saidler foi transcrita da publicação **“Bibliografia” (24/01/2026)** da newsletter fotográfica, mantendo títulos, erros originais, ordem e referências. Ela é uma página CMS própria de acesso `authenticated` da atividade raiz, **sem associação a curso ou turma**, com link condicional na Home do aluno.
- A listagem de químicos foi extraída do e-mail enviado aos alunos **“Receitas, materiais e algumas referências para trabalhar com filme de raio-X” (07/09/2026)**. A seção de equipamentos inclui as especificações expressamente solicitadas. Sugestões de compra são pontos de pesquisa em fornecedores, não preços ou estoques garantidos; produtos particularmente perigosos não recebem links de compra direta.
- Bandeja de marmita **não** equivale automaticamente a bandeja de alumínio: alumínio é inadequado em contato com banhos cáusticos, ácidos ou cloreto férrico. A seleção precisa ser de plástico compatível, pelo menos 600 ml, com margem funcional e bandejas separadas por banho. Vácuo de rolha para vinho jamais é recomendado para líquidos oxidantes, peracéticos ou amônia.
- Migração `100_student_materials_and_shared_bibliography.php` faz criação única, com conflito de slug não destrutivo, e não altera páginas já existentes, matrículas, datas, estados das aulas ou navegação pública. Requer inspeção em base com cursos reais após instalação. Testes em `tools/test-student-resource-pages.php` verificam a liberação, isolamento entre cursos e acesso comum.


### Revisão editorial da página de compras — rascunho (19/10/2026)

- Migração `101_student_materials_review_draft.php`: substitui **apenas o documento de rascunho** de `materiais-positivo-direto` pela versão textual revisada solicitada e **revoga explicitamente** sua publicação anterior (`published_document_json=NULL`, `published_revision=NULL`, `published_at=NULL`, `show_in_nav=0`). Não existe conteúdo público de materiais enquanto o administrador não publicar manualmente a revisão.
- Mantém `status='active'` para a página permanecer acessível no editor do CMS. NÃO usar `status='archived'`, que retiraria também da edição. A rota pública `index.php` só serve documento com `published_document_json` presente, e a navegação do aluno filtra páginas de material sem documento publicado.
- Preserva `page_id`, curso, `access_level='activity'`, as quatro chaves `data-cms-section` preexistentes, seus vínculos com a primeira aula e todas as datas/estados de liberação; portanto o controle da aula permanecerá íntegro quando houver aprovação editorial futura.
- Cinco espaços de imagem usam o componente nativo do editor `data-cms-image-placeholder` para serem preenchidos pela Biblioteca de mídia: bandeja plástica, garrafa âmbar 1 L, garrafa de cerveja 300 ml, pipeta graduada com pera e bomba manual para vinho. Nenhuma imagem foi gerada nem inserida sem seleção.
- A Bibliografia comum permanece intacta e publicada. A migração de rascunho é idempotente e não substitui alterações posteriores feitas pelo administrador ao texto de revisão.
- A atualização da HostGator continua **exclusivamente manual** em Admin → Sistema e atualizações → Instalar atualização, e precisa ser executada para que o rascunho substitua os dados da instalação real. A publicação editorial subsequente é outra ação e exige aprovação explícita.
