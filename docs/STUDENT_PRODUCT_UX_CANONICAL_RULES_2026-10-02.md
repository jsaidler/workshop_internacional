# Área do aluno — regras canônicas de UX — 02/10/2026

Este documento complementa e prevalece sobre quaisquer suposições conflitantes feitas nas tranches anteriores do redesign da área do aluno.

## 1. Não presumir caminho único

A área do aluno não pode modelar uma tarefa como se existisse apenas uma sequência válida de uso.

Antes de desenhar ou alterar qualquer fluxo, devem ser identificados os caminhos reais pelos quais um aluno pode chegar ao mesmo objetivo, incluindo uso síncrono da ferramenta, registro posterior e retomada parcial.

No Caderno, documentação do experimento e controle da execução são responsabilidades distintas.

Para processamento, pelo menos estes caminhos são canônicos:

1. **Executar agora no laboratório** — o aluno escolhe ou monta um processamento, aplica ao registro e acompanha a execução pelo Modo laboratório.
2. **Registrar processamento já realizado** — o aluno já revelou fora do sistema e quer apenas documentar o que efetivamente fez.
3. **Retomar/completar um registro parcial** — o aluno iniciou o acompanhamento no sistema, mas parte da execução ocorreu fora dele; depois completa o registro real sem ser obrigado a simular etapas já executadas.

Nenhum desses caminhos deve ser tratado como erro ou exceção improvisada. A interface deve deixar a escolha explícita quando ela for relevante.

### Invariante: associar não é executar

**Selecionar, aplicar ou associar um processamento salvo a um registro nunca significa iniciar a execução.** Essa ação cria o snapshot do roteiro dentro do Caderno.

A execução só começa por uma ação explícita do aluno. Um fluxo pode preservar a intenção já declarada — por exemplo, **Vou revelar agora** — durante a escolha do roteiro e abrir o Modo laboratório depois da associação, desde que a execução propriamente dita continue dependendo do comando **Iniciar** do laboratório.

Quando não existe intenção anterior conhecida, um roteiro associado e ainda não iniciado deve permanecer neutro e permitir:

- abrir o laboratório;
- registrar o processamento como já realizado;
- trocar o roteiro.

Um roteiro associado e ainda não iniciado não pode aparecer no Caderno como “em execução”, “processamento iniciado” ou equivalente.

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
- a interface diferencia claramente documentar, preparar, executar e revisar?

Se a solução depende de uma única sequência idealizada de uso, o fluxo ainda não está suficientemente modelado.

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
- adequação à operação física de laboratório quando pertinente.

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
2. distinção entre documentação e execução;
3. consumo de inventário seguro em registros retroativos;
4. simplificação do percurso de processamento, sem repetir a mesma decisão em telas sucessivas;
5. remoção de barras móveis que sobrepõem conteúdo e de transparência/blur nessas superfícies;
6. redução objetiva da carga textual e da quantidade de cartões no mobile;
7. inspeção visual integral da área do aluno no estado resultante, seguida de correção dos problemas encontrados.