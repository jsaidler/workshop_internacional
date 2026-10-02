# Área do aluno — contrato de redesign de produto — 01/10/2026

## Decisão canônica

A área do aluno passa a ser tratada como **um produto único**, e não como uma coleção de telas ou funcionalidades independentes.

O critério de qualidade deixa de ser apenas funcionamento técnico. Uma entrega só é considerada concluída quando atende simultaneamente a quatro requisitos:

1. **correção técnica e de domínio**;
2. **clareza de uso e consistência de UX**;
3. **orientação pedagógica adequada a alunos**;
4. **inspeção visual real em desktop e mobile antes de merge**.

A revisão deve partir dos fluxos do aluno e não da estrutura de arquivos PHP.

## Escopo funcional da auditoria

A área será analisada e reorganizada pelos seguintes fluxos:

- Entrada / Dashboard;
- Cursos e materiais;
- Caderno e registros;
- Processamentos;
- Modo laboratório;
- Inventário e preparações;
- Dúvidas e comunicação;
- Conta e navegação global.

As transições entre essas áreas fazem parte do produto. Não basta cada tela funcionar isoladamente.

## Contrato geral de UX

Toda entidade manipulável deve tornar imediatamente compreensível:

- o que é;
- em que estado está;
- quais informações são relevantes naquele momento;
- o que pode ser feito com ela;
- qual é a ação principal;
- quais são as ações secundárias;
- como editar;
- como duplicar, quando fizer sentido;
- como excluir;
- como voltar sem perder contexto.

Ações importantes não podem depender de descoberta acidental, links escondidos ou padrões diferentes entre telas.

Ações destrutivas devem ser claras, acessíveis e visualmente separadas do fluxo normal.

Visualização e edição devem obedecer a uma gramática previsível em toda a área do aluno.

## UX pedagógica

Os usuários são alunos. A interface não pode pressupor domínio completo da nomenclatura, do fluxo químico ou da lógica interna das ferramentas.

Cada tela deve responder, com o mínimo de texto necessário:

1. **onde estou?**
2. **para que isto serve?**
3. **o que posso fazer aqui?**
4. **qual é o próximo passo?**

A orientação deve aparecer no ponto em que é necessária, sem transformar a interface em apostila.

Regras:

- estados vazios têm função pedagógica e explicam o valor da ferramenta;
- termos técnicos menos óbvios recebem contexto curto quando necessário;
- formulários explicam apenas campos cuja função ou consequência não seja evidente;
- ações importantes explicam suas consequências;
- mensagens de sucesso orientam o próximo passo quando pertinente;
- erros dizem o que ocorreu e como corrigir;
- primeiro uso pode oferecer mais orientação, sem obrigar usuários recorrentes a atravessar a mesma explicação;
- o sistema deve funcionar como extensão prática do workshop, sem exigir que o aluno se lembre de onde cada conceito foi explicado.

A interface deve ensinar o suficiente para permitir uso correto da ferramenta, mas não deve substituir o conteúdo pedagógico do curso.

## Correção de domínio

Nenhuma nova interface ou funcionalidade deve ser projetada a partir de uma abstração genérica quando houver um processo real documentado no workshop.

Caderno, Processamentos, Modo laboratório e Inventário devem refletir a prática efetivamente ensinada e registrada na pesquisa.

A modelagem deve distinguir claramente:

- materiais e soluções preparados;
- etapas do processo;
- reutilização de soluções entre etapas;
- consumo real de inventário;
- parâmetros de exposição e revelação;
- diferenças entre processos atuais e processos históricos.

No processo atual com Parodinal, o banho usado na segunda revelação é **o mesmo revelador preparado e usado na primeira revelação**. A segunda revelação não representa uma nova preparação nem um segundo consumo de revelador.

## Caderno, Processamentos e Laboratório

Esses três fluxos formam o núcleo prático da área do aluno e devem ser tratados como uma cadeia coerente:

**exposição → processamento planejado → execução → resultado → análise**.

O Caderno deve deixar evidente a relação entre exposição, processamento e resultado.

Processamentos devem apresentar informação suficiente para que o aluno compreenda o roteiro antes de usá-lo. Edição, duplicação e exclusão devem ser imediatamente descobríveis.

O modo laboratório é uma interface operacional específica e não uma página comum com um cronômetro. Durante a execução, deve privilegiar:

- etapa atual;
- solução ou banho usado;
- tempo;
- temperatura;
- agitação;
- instrução física necessária;
- próxima etapa;
- progresso geral;
- controles grandes e inequívocos.

Textos explicativos extensos devem desaparecer durante a execução. A orientação necessária deve acontecer antes ou de forma extremamente compacta.

## Mobile

Mobile é contexto real de uso, especialmente no laboratório.

A revisão não pode se limitar a fazer o layout desktop caber em uma tela estreita. Devem ser avaliados especificamente:

- ordem das informações;
- tamanho e distância entre controles;
- legibilidade à distância curta;
- operação com uma mão;
- densidade de conteúdo;
- prioridade das ações;
- navegação e retorno de contexto.

## Inspeção visual obrigatória

Toda alteração relevante da área do aluno deve passar por **inspeção visual humana real** antes de ser considerada pronta.

O processo mínimo é:

**projeto de UX → implementação → renderização real → inspeção visual crítica → correções → nova inspeção → testes → merge**.

A inspeção deve cobrir, quando aplicável:

- desktop;
- mobile;
- estado vazio;
- estado com conteúdo;
- criação;
- visualização;
- edição;
- exclusão e confirmação;
- mensagens de sucesso;
- erros;
- estados de carregamento ou indisponibilidade;
- fluxo de laboratório.

A inspeção deve avaliar:

- hierarquia visual;
- clareza da ação principal;
- distinção entre ações secundárias e destrutivas;
- legibilidade;
- densidade de informação;
- orientação pedagógica;
- consistência entre telas;
- alinhamento e ritmo visual;
- comportamento dos componentes em viewport pequeno;
- clareza do próximo passo.

`student-visual-audit` e outros testes automatizados são proteção contra regressões, mas **não substituem a inspeção visual**.

Uma tela confusa, mal hierarquizada, visualmente desequilibrada, pouco explicativa ou difícil de operar não está pronta mesmo com todos os testes automatizados verdes.

## Critério de implementação

Novas funcionalidades da área do aluno não devem ser acrescentadas como telas isoladas sem antes verificar sua integração com a arquitetura de produto.

Antes de implementar:

1. identificar o objetivo real do aluno;
2. localizar o fluxo ao qual a função pertence;
3. verificar o domínio real e a documentação pedagógica correspondente;
4. definir estados, ações e consequências;
5. reutilizar a gramática visual e interativa existente ou corrigir a gramática global quando ela for insuficiente;
6. implementar;
7. inspecionar visualmente;
8. corrigir;
9. validar tecnicamente;
10. somente então considerar merge.

## Plano de implementação

O redesign será executado como refatoração progressiva do produto. Não haverá reescrita total nem substituição de todos os fluxos de uma vez. Cada tranche deve preservar os dados existentes e entregar um fluxo operacional completo.

### Etapa 0 — contrato e baseline

- consolidar este documento como autoridade canônica da UX do aluno;
- registrar o estado de produção usado como baseline;
- não iniciar nova funcionalidade isolada antes da auditoria;
- preservar compatibilidade com registros, matrículas, Caderno e processamentos existentes.

### Etapa 1 — auditoria funcional e visual real

Auditar todas as superfícies do aluno em desktop e mobile, cobrindo:

- navegação e retorno de contexto;
- hierarquia visual;
- orientação pedagógica;
- estados vazios e com conteúdo;
- criação, leitura, edição, duplicação e exclusão;
- mensagens de confirmação, sucesso e erro;
- consistência das ações entre entidades;
- formulários e densidade de informação;
- tarefas que exigem conhecimento implícito ou descoberta acidental;
- inadequações específicas de uso no laboratório.

O resultado deve ser uma matriz de problemas por fluxo, com severidade, evidência, correção proposta e dependências. A auditoria não é aprovação: ela define o backlog do redesign.

### Etapa 2 — fundação global da interface

Corrigir primeiro as primitivas e o shell compartilhado para impedir que cada tela receba remendos locais. Devem ser consolidados:

- largura e ritmo vertical do conteúdo;
- tipografia de aplicação;
- cabeçalhos de página e contexto;
- ação primária, secundária e destrutiva;
- menus de ações;
- formulários, ajuda, erro e confirmação;
- alertas e feedback de operação;
- estados vazios;
- listas e cartões de entidade;
- navegação contextual e retorno;
- comportamento mobile e alvos de toque.

Uma primitiva nova só deve existir quando a lacuna for global. CSS local fica restrito à composição específica do fluxo.

### Etapa 3 — núcleo Caderno → Processamentos → Laboratório

É a primeira tranche funcional prioritária.

**Caderno** deve organizar o registro segundo a lógica de pesquisa: exposição → processamento → resultado → análise, deixando evidente o estado de cada parte e o próximo passo.

**Processamentos** deve funcionar como biblioteca pessoal e incluir, de forma clara e completa:

- padrões do workshop;
- processamentos próprios;
- identificação resumida por EI, revelador, rota e duração quando aplicável;
- visualização da sequência;
- criar;
- editar;
- duplicar;
- excluir;
- aplicar a um registro;
- iniciar no laboratório.

Um fluxo de Processamentos não é considerado concluído se alguma dessas ações permanecer oculta, inconsistente ou ausente quando semanticamente aplicável.

**Modo laboratório** deve ser uma superfície operacional própria, com leitura rápida e controles grandes. A interface deve mostrar apenas o contexto necessário para executar a etapa atual com segurança e antecipar a próxima, sem iniciar etapas automaticamente.

### Etapa 4 — domínio e inventário

Após o fluxo de processamento estar semanticamente correto, revisar Inventário e Preparações para representar o uso real de materiais e soluções.

A modelagem e a UI devem distinguir preparação, uso, reutilização e consumo. Em particular, a segunda revelação com Parodinal reutiliza o mesmo banho da primeira e não pode ser representada como nova preparação nem gerar segunda baixa de inventário.

### Etapa 5 — Dashboard, Cursos e Material

Com o núcleo experimental estabilizado:

- Dashboard passa a priorizar continuidade do trabalho e próximos passos reais;
- Cursos e Material adotam a mesma linguagem visual e pedagógica;
- atalhos sem função de continuidade deixam de competir pela atenção;
- a interface preserva contexto de curso sem criar navegações paralelas.

### Etapa 6 — Dúvidas, comunicação e Conta

As áreas restantes são trazidas para a mesma gramática de navegação, ação, orientação e feedback, sem criar um segundo design system.

### Etapa 7 — revisão global e remoção de legado

Somente depois que um fluxo novo substituir integralmente o anterior:

- remover superfícies e CSS legados sem consumidores;
- revisar links e rotas antigas;
- executar nova auditoria transversal;
- verificar consistência desktop/mobile entre todos os fluxos;
- documentar exceções remanescentes.

## Estratégia de entrega

A unidade de entrega é um **fluxo utilizável de ponta a ponta**, e não uma tela isolada.

Exemplo: uma tranche de Processamentos deve entregar listagem, estado vazio, criação, detalhe, edição, duplicação, exclusão e caminhos relevantes de uso. Redesenhar apenas a lista e deixar gerenciamento incompleto não satisfaz este contrato.

Cada PR estrutural da área do aluno deve:

1. declarar qual tarefa do aluno está sendo corrigida;
2. listar os estados e ações cobertos;
3. registrar evidência da inspeção visual em desktop e mobile;
4. registrar correções feitas após a primeira inspeção;
5. executar testes de comportamento do fluxo, além dos testes técnicos existentes;
6. somente ser mesclado quando o head final possuir os gates obrigatórios verdes.

## Critério de conclusão por tranche

Uma tranche só está pronta quando for simultaneamente:

- correta no domínio;
- funcional de ponta a ponta;
- compreensível para um aluno;
- pedagogicamente orientada no ponto necessário;
- consistente com o restante do produto;
- adequada a desktop e mobile;
- inspecionada visualmente de forma humana;
- corrigida após essa inspeção quando necessário;
- coberta por testes relevantes;
- sem regressão de dados ou acesso.

`validate` ou `student-visual-audit` verdes isoladamente não significam conclusão.

## Ordem de trabalho

A revisão começa por uma auditoria da área inteira, seguida pela definição de uma arquitetura de interação comum.

A prioridade de redesign é:

1. Caderno;
2. Processamentos;
3. Modo laboratório;
4. Inventário e preparações;
5. Dashboard / entrada;
6. Cursos e materiais;
7. Dúvidas, comunicação e conta.

Essa ordem pode ser ajustada caso a auditoria revele bloqueios de navegação ou dependências mais críticas, mas nenhuma área deve ser redesenhada como um produto independente.

## Relação com documentação anterior

Este documento complementa e, quando houver conflito, **substitui as decisões de UX da área do aluno** registradas em auditorias anteriores. As decisões administrativas dessas auditorias permanecem válidas quando não forem afetadas por este contrato.

O objetivo desta revisão é elevar a área do aluno ao padrão de um produto educacional coerente: funcional, compreensível, orientado, visualmente consistente e adequado ao uso real durante o workshop.