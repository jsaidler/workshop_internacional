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

**Selecionar, aplicar ou associar um processamento salvo a um registro nunca significa iniciar o laboratório.** Essa ação apenas cria o snapshot do roteiro dentro do Caderno.

O Modo laboratório só pode ser iniciado por uma ação explícita do aluno, apresentada como execução — por exemplo, **Executar agora** ou **Entrar no modo laboratório**. Depois de associado um roteiro e antes de qualquer etapa ser executada, o estado do registro deve permanecer neutro e oferecer, com hierarquia equivalente, pelo menos:

- executar agora com acompanhamento;
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

## 8. Consequência para o roadmap atual

A Tranche D (Dashboard + Curso + Material) fica bloqueada até que a tranche corretiva anterior resolva:

1. suporte explícito a múltiplos caminhos de processamento no Caderno, incluindo registro retroativo e retomada parcial;
2. distinção entre documentação e execução;
3. consumo de inventário seguro em registros retroativos;
4. inspeção visual integral da área do aluno no estado resultante.
