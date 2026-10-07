# Área do aluno — regras canônicas de UX — 02/10/2026

Este documento complementa e prevalece sobre quaisquer suposições conflitantes feitas nas tranches anteriores do redesign da área do aluno.

> **Exceção de precedência:** para navegação global, mobile-first, barra inferior, logo, ação de voltar, breadcrumbs e relação entre navegação global/contextual, prevalece `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md`. Este documento continua válido para modelagem de produto e demais regras de UX que não conflitem com o contrato de navegação posterior.

## 1. Não presumir caminho único

A área do aluno não pode modelar uma tarefa como se existisse apenas uma sequência válida de uso do software.

Antes de desenhar ou alterar qualquer fluxo, devem ser identificados os caminhos reais pelos quais um aluno pode chegar ao mesmo objetivo, incluindo uso síncrono da ferramenta, registro posterior e retomada parcial.

No Caderno, documentação do experimento, navegação da interface e andamento físico do processo são responsabilidades distintas.

Para processamento, pelo menos estes caminhos são canônicos:

1. **Acompanhar no laboratório** — o aluno escolhe ou monta um processamento, aplica ao registro e usa o Modo laboratório como consulta, temporizador e apoio durante a revelação.
2. **Registrar processamento já realizado** — o aluno já revelou fora do sistema e quer documentar o que efetivamente fez.
3. **Retomar/completar um registro parcial** — o aluno usou o sistema em parte do processo, continuou a revelação sem ele e depois retoma a documentação sem ser obrigado a simular interações que não aconteceram no aplicativo.

Nenhum desses caminhos deve ser tratado como erro ou exceção improvisada. A interface deve deixar a escolha explícita quando ela for relevante.

### Invariante: associar não é avançar o processo

**Selecionar, aplicar ou associar um processamento salvo a um registro nunca significa que a revelação começou ou avançou.** Essa ação cria o snapshot do roteiro dentro do Caderno.

O Modo laboratório não controla a revelação. Entrar nele, abrir uma etapa, iniciar ou abandonar um cronômetro, fechar a página, perder conexão ou voltar depois não cria por si só um fato laboratorial.

O andamento do processo só muda por uma declaração explícita do aluno sobre a posição real — por exemplo, **Estou nesta etapa** — ou por um registro retroativo do que foi realizado.

Quando não existe posição declarada, um roteiro associado deve permanecer neutro e permitir:

- abrir o laboratório para consulta e temporização;
- registrar o processamento como já realizado;
- trocar o roteiro.

Um roteiro meramente associado não pode aparecer no Caderno como “em execução”, “processamento iniciado” ou equivalente.

## 2. Registro retroativo de processamento

Ao registrar um processamento já realizado:

- o aluno pode partir de um processamento salvo ou registrar um processo manual/ad hoc;
- ao usar um processamento salvo, o Caderno recebe um snapshot próprio;
- o snapshot pode ser ajustado para refletir desvios reais da execução sem alterar o processo-base;
- etapas podem ser consideradas realizadas sem passar pelo cronômetro uma a uma;
- deve ser possível registrar data, observações e desvios relevantes;
- o aluno pode seguir diretamente para Resultado quando o processamento já terminou;
- não deve haver baixa automática de inventário;
- qualquer consumo retroativo deve ser explícito e opcional;
- regras de reutilização permanecem válidas: o mesmo banho reutilizado entre primeira e segunda revelação não pode gerar dupla baixa.

## 3. Regra de modelagem de produto

Antes de implementar uma tela ou ação, responder:

- quais objetivos diferentes levam o aluno até aqui?;
- quais estados anteriores são possíveis?;
- o aluno pode estar registrando algo que já aconteceu?;
- o aluno pode ter executado parte fora do sistema?;
- há mais de uma forma legítima de concluir a tarefa?;
- a interface diferencia claramente documentar, consultar, temporizar, preparar e revisar?;
- o que acontece se o fotógrafo decidir fazer outra coisa agora?

Se a solução depende de uma única sequência idealizada de uso da interface, o fluxo ainda não está suficientemente modelado.

Também não é aceitável transformar cada distinção conceitual em uma nova tela de decisão. **A interface deve perguntar uma decisão apenas quando ela muda a próxima ação do aluno e deve preservar essa intenção enquanto ela continuar válida.**

## 4. Inspeção visual obrigatória — cobertura total

Inspeção visual passa a ser obrigatória para **todas as telas da área do aluno, sem exceção**.

Não é permitido presumir que uma tela está correta porque:

- não foi modificada na tranche atual;
- já passou por inspeção em uma tranche anterior;
- usa componentes compartilhados;
- o `student-visual-audit` está verde;
- a alteração parece pequena ou indireta;
- desktop está correto e mobile não foi alterado, ou vice-versa.

Toda tranche que modifica a área do aluno deve gerar uma inspeção visual completa do estado atual da área inteira.

## 5. Cobertura mínima da inspeção

A inspeção deve incluir, sempre que a superfície existir:

- Início/Dashboard;
- Curso;
- Material;
- Dúvidas/comunicação;
- Caderno — lista;
- criação de registro;
- Exposição;
- escolha/registro de Processamento;
- Processamento aplicado;
- registro retroativo;
- Resultado;
- Processamentos — biblioteca;
- Processamentos — editor;
- Modo laboratório;
- Inventário;
- edição de item;
- movimentação de estoque;
- Predefinições de revelação;
- editor de predefinição;
- Ferramentas/toolbox;
- Perfil/Conta e demais telas da área do aluno.

Devem ser vistos desktop e mobile e, quando aplicável, estados vazio, com conteúdo, criação, edição, confirmação/destrutivo, sucesso e erro.

## 6. Inspeção humana, não somente geração de screenshots

`student-visual-audit` é um mecanismo de captura e regressão. Ele não aprova design.

As renderizações devem ser efetivamente observadas e criticadas. A revisão deve considerar:

- hierarquia visual;
- legibilidade;
- densidade;
- clareza das ações;
- orientação pedagógica;
- consistência entre telas;
- comportamento mobile;
- estados e feedback;
- continuidade entre fluxos;
- adequação à operação física de laboratório quando pertinente;
- liberdade de navegação sem transformar o software em controlador do procedimento.

Problema encontrado durante a inspeção deve ser corrigido antes do merge e a tela correspondente deve ser reinspecionada.

## 7. Gate de entrega

A sequência obrigatória passa a ser:

**modelagem dos caminhos reais → UX → implementação → renderização de todas as telas → inspeção visual crítica de todas as telas → correções → reinspeção das telas afetadas → testes automatizados → merge → confirmação do pacote de produção.**

Nenhuma tranche da área do aluno é considerada pronta apenas porque suas telas diretamente alteradas parecem corretas.

## 8. Regras de qualidade visual no mobile

No mobile, a leitura e a operação têm prioridade sobre persistência de navegação e ornamentação.

- navegação fixa ou sticky não pode cobrir, atravessar ou disputar espaço visual com o conteúdo;
- barras de navegação sobre o conteúdo não devem usar transparência, `backdrop-filter` ou blur;
- se uma navegação persistente exigir reserva artificial de espaço para não esconder conteúdo, a preferência é torná-la parte do fluxo normal da página;
- títulos, subtítulos, kickers e parágrafos não devem repetir a mesma informação em sequência;
- texto explicativo deve existir somente quando evita erro, esclarece uma consequência ou orienta uma decisão real;
- caminhos equivalentes não precisam ser três ou quatro cards grandes: ações simples devem parecer simples;
- opções avançadas, manuais ou raras devem ficar progressivamente reveladas quando não forem necessárias à maioria dos usos;
- uma tela mobile não deve parecer uma pilha de cartões dentro de cartões quando linhas, separadores ou disclosure resolvem a mesma hierarquia.

A inspeção visual deve tratar **carga textual, quantidade de decisões, número de caixas visuais e oclusão por elementos persistentes como defeitos de produto**, e não como preferência estética.

## 9. Consequência para o roadmap atual

A Tranche D (Dashboard + Curso + Material) permanece bloqueada até que a tranche corretiva atual resolva e valide:

1. suporte explícito a múltiplos caminhos de processamento no Caderno, incluindo registro retroativo e retomada parcial;
2. distinção entre documentação, navegação da interface e andamento físico do processo;
3. consumo de inventário seguro em registros retroativos;
4. simplificação do percurso de processamento, sem repetir a mesma decisão em telas sucessivas;
5. remoção de barras móveis que sobrepõem conteúdo e de transparência/blur nessas superfícies;
6. redução objetiva da carga textual e da quantidade de cartões no mobile;
7. inspeção visual integral da área do aluno no estado resultante, seguida de correção dos problemas encontrados.

## 10. Roteiro, posição real, fatos e reversibilidade

**A sequência pertence ao processo fotográfico. A navegação pertence ao usuário. O estado da interface não é um fato do laboratório.**

O roteiro de revelação possui uma sequência física real. O aplicativo pode ser usado continuamente, parcialmente ou abandonado durante parte do procedimento sem que isso altere o que aconteceu no laboratório.

A aplicação deve distinguir quatro coisas:

- **processo-base** — o padrão versionado ou roteiro pessoal escolhido;
- **snapshot do registro** — a cópia dos valores daquele processo para aquela fotografia;
- **posição declarada no processo** — a etapa real que o aluno afirma ter alcançado;
- **histórico factual** — as etapas que o sistema pode considerar realizadas a partir dessa declaração e dos valores do snapshot, além das correções explícitas feitas pelo aluno.

### Regras da bancada

- abrir ou consultar qualquer etapa é uma ação de leitura e não muda a posição real;
- iniciar, pausar, reiniciar, ajustar ou abandonar um cronômetro não cria nem invalida fatos;
- fechar a página, perder conexão, trocar de dispositivo ou voltar depois não cria uma “interrupção” laboratorial;
- ao declarar **Estou nesta etapa** em uma etapa posterior, as etapas anteriores do roteiro são consideradas realizadas com os valores atuais do snapshot;
- os valores herdados do padrão são valores efetivos até que o aluno os altere naquela fotografia;
- alterar tempo, temperatura, agitação ou outro parâmetro modifica apenas o snapshot daquele registro, nunca o padrão global já publicado;
- uma etapa anterior pode ser consultada depois sem mover a posição do processo para trás;
- se uma etapa excepcionalmente não fez parte daquele processamento, isso deve ser uma edição explícita do roteiro daquela fotografia, e não uma inferência automática de `skipped`;
- o sistema não cria fatos “interrompidos” porque uma tela, timer ou rota deixou de ser usada.

### Temporização e agitação

O cronômetro é uma ferramenta auxiliar e nunca uma condição para que uma etapa tenha acontecido.

A etapa pode definir:

- duração total;
- sem temporização de agitação;
- agitação contínua;
- agitação periódica, com **duração de cada agitação** e **intervalo entre o início de cada agitação**.

Em agitação periódica, `10 s a cada 60 s` significa agitar de `0:00–0:10`, `1:00–1:10`, `2:00–2:10` e assim por diante. Em agitação contínua, a indicação permanece ativa durante toda a contagem da etapa.

Chegar a zero apenas encerra a contagem e emite o aviso correspondente. O cronômetro não avança automaticamente o processo.

### Reversibilidade e mudança de roteiro

**Escolhas são reversíveis; fatos já materializados são preservados; estado operacional da interface não é convertido em história factual.**

Trocar um roteiro não pode obrigar o aluno a recriar o registro nem apagar exposição, mídia, observações, datas ou etapas já materializadas. Também não pode transformar automaticamente um cronômetro em andamento ou uma etapa exibida na tela em uma ocorrência “interrompida”.

Quando houver mudança de roteiro:

- fatos já materializados permanecem intactos;
- o novo roteiro governa apenas o que ainda não foi consolidado como fato;
- fatos incompatíveis com a nova rota não são renomeados para parecer compatíveis;
- a posição operacional e os timers podem ser reinicializados sem criar acontecimentos laboratoriais fictícios;
- a interface explica o que permanece e o que muda.

### Atomicidade obrigatória

Uma mudança de roteiro é uma única operação lógica. A preservação dos fatos existentes, a substituição das próximas etapas, a atualização da posição operacional e o encerramento/reinicialização de sessões auxiliares devem pertencer à mesma transação de banco de dados.

Se qualquer parte da troca falhar:

- o plano anterior continua válido;
- os fatos existentes continuam intactos;
- nenhuma etapa fictícia é criada;
- nenhum evento de auditoria afirma que a troca aconteceu;
- nenhum consumo ou ajuste de inventário parcial pode sobreviver à falha.

O teste de reversibilidade deve incluir deliberadamente falha durante a substituição do plano e verificar rollback integral, além dos caminhos felizes.
