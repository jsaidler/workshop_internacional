# Área do aluno — auditoria de produto UX/UI — 01/10/2026

## Baseline auditado

Baseline visual: artefato `student-visual-audit` run 146, gerado no head final do PR #174, imediatamente antes do merge em produção. Foram inspecionadas as renderizações desktop 1440×1000 e mobile 390×844 para Início, Curso, Caderno, Novo registro, Exposição, Processamento do Caderno, Resultado, Ferramentas, Inventário e toolbox, além do código das superfícies novas de Processamentos e Modo laboratório.

A auditoria não usa o gate automatizado como aprovação de design. As imagens foram inspecionadas visualmente e confrontadas com o código que define ações e estados.

## Diagnóstico transversal

O problema principal não é um componente isolado. A área do aluno possui boa parte das funções necessárias, mas ainda se comporta como um conjunto de funcionalidades adicionadas em épocas diferentes. A interface exige conhecimento implícito do sistema e distribui ações equivalentes de maneiras diferentes.

### P0 — bloqueios de produto

1. **Gerenciamento de entidades incompleto ou pouco descobrível.** Em Processamentos, uma etapa existente pode ser movida ou removida, mas não editada. O processamento completo pode ser editado em nome/descrição e excluído, porém essas ações não seguem uma gramática de entidade compartilhada com Caderno e Inventário.
2. **Processamento possui duas linguagens concorrentes.** O fluxo livre do Caderno ainda apresenta etapa + campos + cronômetro como unidade principal, enquanto o novo domínio introduz Processamento como roteiro reutilizável e Modo laboratório. O aluno pode não compreender qual é o fluxo canônico.
3. **A interface não representa suficientemente o domínio químico.** Parâmetros iguais não bastam para explicar que a segunda revelação reutiliza o mesmo banho da primeira. Reutilização, preparação e consumo precisam ser entidades semânticas visíveis quando relevantes.
4. **Inspeção visual automatizada não cobre Processamentos e Modo laboratório como superfícies completas.** O fluxo que está sendo mais alterado não possui ainda a mesma evidência visual sistemática das telas antigas.

### P1 — problemas de UX recorrentes

1. **Hierarquia muito baseada em títulos grandes + divisórias.** Há bom contraste e identidade, mas várias telas desktop ficam excessivamente vazias e pouco informativas. A hierarquia editorial do site é aplicada a superfícies operacionais que precisam de maior densidade e orientação.
2. **Ação primária nem sempre corresponde à tarefa principal.** Em alguns fluxos o aluno recebe um formulário inteiro antes de entender a finalidade, e em outros precisa abrir menus como `Mais ações` para descobrir operações sobre a entidade.
3. **Orientação pedagógica insuficiente.** Os rótulos dizem o nome do campo, mas raramente explicam por que preencher, quando usar ou qual consequência terá. Isso é especialmente grave em Exposição, Processamento, Inventário e criação de registros.
4. **Estados vazios são tecnicamente corretos, mas pouco educativos.** Frases como “Você ainda não salvou nenhum processamento” informam ausência, mas não ajudam o aluno a entender o valor da ferramenta ou o próximo passo.
5. **Desktop e mobile preservam quase a mesma lógica informacional.** A responsividade evita overflow, porém o mobile ainda é frequentemente uma reorganização da página desktop, em vez de priorizar a tarefa de campo/laboratório.
6. **Ferramentas competem com fluxos principais.** Toolbox, Ferramentas, Processamentos, Inventário, Predefinições e referências aparecem como bancada de utilidades, mas sem uma hierarquia suficientemente pedagógica entre “ferramenta de cálculo”, “dados persistentes” e “execução de processo”.

## Auditoria por fluxo

### 1. Entrada / Dashboard

**Estado atual**

A tela oferece continuidade do último registro e atalhos para curso e Caderno. No mobile a continuidade fica mais clara que no desktop.

**Problemas**

- desktop excessivamente esparso para a quantidade de informação;
- pouca explicação sobre o que o aluno pode fazer na área como um todo;
- não diferencia bem “continuar uma tarefa” de “consultar material”;
- atalhos não expressam prioridade ou progressão pedagógica além do registro recente.

**Direção**

Dashboard deve responder primeiro “onde parei?” e “o que faz sentido fazer agora?”. Material, Caderno e atividade laboratorial entram como continuidade real, não como coleção de atalhos equivalentes.

### 2. Cursos e materiais

**Estado atual**

A tela de curso é relativamente limpa e separa Material de Aulas. No mobile, a leitura é adequada.

**Problemas**

- pouca orientação sobre ordem sugerida de estudo;
- estados de aula e material são informativos, mas não conduzem suficientemente o aluno;
- em desktop há uma relação fraca entre bloco principal e lateral, com muito espaço vazio;
- Dúvidas aparece como ação lateral, sem contextualização sobre quando usar.

**Direção**

Preservar a simplicidade, adicionando progressão e contexto pedagógico apenas onde ajudam a decidir o próximo passo.

### 3. Caderno — lista de registros

**Estado atual**

A lista comunica título, contexto, estado e próxima ação do registro. É uma das superfícies mais próximas da direção desejada.

**Problemas**

- `Mais ações` esconde operações importantes sobre o registro;
- ações de entidade não seguem um padrão compartilhado com Processamentos e Inventário;
- estados como privado/pessoal/turma competem visualmente com o estado real do fluxo experimental;
- falta uma explicação curta do papel do Caderno para alunos novos: relacionar exposição, processamento e resultado.

**Direção**

Manter a lista como centro de pesquisa, tornar operações de entidade previsíveis e usar estado do experimento como informação principal.

### 4. Novo registro

**Estado atual**

Dialog compacto com título, data e contexto.

**Problemas**

- `Contexto` exige que o aluno entenda a consequência de Pessoal versus Curso sem explicação suficiente;
- título “Opcional” não ajuda a nomear de forma útil um experimento;
- o dialog apresenta criação como preenchimento administrativo, e não como início de um registro de pesquisa.

**Direção**

Manter criação curta, mas explicar consequência do contexto e orientar nomeação sem introduzir formulário extenso.

### 5. Caderno — Exposição

**Estado atual**

Fluxo em três etapas, com Cena, campos técnicos e ação para avançar.

**Problemas**

- quase todos os campos têm o mesmo peso visual;
- não há separação pedagógica clara entre identificação do material, medição e parâmetros realmente usados;
- `EI / ISO` e reciprocidade aparecem como dados pressupostos, embora sejam conceitos importantes do workshop;
- campos derivados e campos informados pelo aluno não são diferenciados suficientemente;
- a orientação “o que preciso registrar para conseguir comparar depois?” não aparece.

**Direção**

Organizar o formulário conforme a lógica da captura e da análise futura, com ajuda curta nos conceitos que produzem erro frequente.

### 6. Caderno — Processamento livre

**Estado atual**

A página trabalha com próxima etapa, seleção da etapa, parâmetros, cronômetro e histórico.

**Problemas**

- concorre conceitualmente com o novo domínio de Processamentos reutilizáveis;
- expõe campos de revelação como se cada etapa fosse uma preparação independente;
- mistura definição do processo, execução e registro histórico na mesma superfície;
- timer local aumenta a ambiguidade agora que existe Modo laboratório;
- a página exige grande conhecimento prévio para ser utilizada corretamente.

**Direção**

O modo livre permanece possível, mas deixa de ser o caminho principal. A interface deve priorizar aplicar/usar um Processamento e executar no Modo laboratório; registro manual fica explicitamente identificado como alternativa avançada/ad hoc.

### 7. Processamentos — biblioteca

**Estado atual**

A mesma página mostra padrões do workshop, formulário de criação e processamentos do aluno.

**Problemas**

- três intenções diferentes competem verticalmente: escolher padrão, criar do zero e abrir biblioteca;
- cards de padrão e cards pessoais usam aparência próxima, apesar de terem naturezas diferentes;
- os padrões apresentam descrição corrida em vez de resumo operacional escaneável;
- falta ação de duplicar processo pessoal;
- edição e exclusão não seguem uma ação de entidade uniforme;
- estado vazio pessoal não explica por que salvar um processamento ou qual caminho é recomendado;
- contexto “para um registro do Caderno” muda botões, mas não reorganiza suficientemente a decisão do aluno.

**Direção**

Biblioteca pessoal como superfície principal. Padrões do workshop funcionam como ponto de partida claramente identificado. Criação do zero é alternativa secundária. Cada item deve expor resumo operacional e ações previsíveis.

### 8. Processamentos — editor

**Estado atual**

Nome e descrição podem ser alterados; etapas podem ser adicionadas, removidas e reordenadas.

**Problemas**

- **não existe edição de uma etapa já criada**, apenas remover e recriar;
- setas ↑/↓ e `Remover` são controles de implementação, não uma experiência de edição madura;
- formulário `Adicionar etapa` aberto ocupa muito espaço e domina a leitura da sequência;
- sequência não evidencia relações entre etapas, como reutilização do mesmo banho;
- dados mais importantes de cada etapa aparecem comprimidos em uma linha textual;
- exclusão do processamento não é tratada como zona destrutiva consistente e contextualizada;
- o aluno precisa entender schema de etapa em vez de editar um roteiro químico.

**Direção**

A sequência é o objeto principal. Cada etapa abre edição clara; reordenar deve ser compreensível; relações como “reutiliza o banho da 1ª revelação” devem ser explícitas; adicionar etapa fica subordinado ao roteiro existente.

### 9. Modo laboratório

**Estado atual**

A implementação nova já aponta para a direção correta: etapa atual grande, relógio grande, progresso, próxima etapa, timeline e wake lock.

**Problemas / lacunas a validar visualmente**

- falta cobertura completa nos artefatos de inspeção visual atuais;
- solução/banho e instrução física precisam ser mais importantes que metadados internos;
- reutilização de banho deve aparecer como instrução operacional quando aplicável;
- controles precisam ser validados em uso mobile com uma mão e com tela apoiada na bancada;
- etapas sem tempo precisam de linguagem operacional tão clara quanto etapas temporizadas.

**Direção**

Interface excepcionalmente enxuta durante execução. Nenhuma edição complexa dentro do runner. Preparação e orientação acontecem antes; durante a etapa, apenas o necessário para agir corretamente.

### 10. Resultado e avaliação

**Estado atual**

Imagem, anotações e envio para avaliação estão disponíveis.

**Problemas**

- `Anotações sobre o resultado` é amplo demais para aluno iniciante;
- não conecta visualmente o resultado às decisões de exposição/processamento que o produziram;
- avaliação do curso aparece como bloco subsequente, mas sem explicar o que deve estar pronto antes do envio.

**Direção**

Ajudar o aluno a observar de maneira útil sem criar questionário rígido. Antes de enviar para avaliação, deixar claro o que será compartilhado.

### 11. Inventário e preparações

**Estado atual**

Inventário apresenta itens e quantidades e permite movimentação. É legível no mobile.

**Problemas**

- a tela responde “quanto existe”, mas pouco “para que isso será usado”;
- item, preparação, predefinição e solução não são semanticamente diferenciados de forma suficiente na experiência;
- movimentação fica subordinada e pouco orientada;
- não há ainda uma representação coerente da reutilização de solução entre etapas do processamento.

**Direção**

Inventário deve responder “o que tenho disponível para trabalhar?” e conectar material/preparação ao uso laboratorial sem automatizar consumos incorretamente.

### 12. Ferramentas e toolbox

**Estado atual**

Calculadoras, receitas, Inventário, Processamentos e referências coexistem como bancada de utilidades.

**Problemas**

- mistura ferramentas efêmeras de cálculo com entidades persistentes;
- toolbox replica parte da página Ferramentas e pode criar dois caminhos equivalentes;
- a representação visual antiga ainda inclui Temporizador, embora o domínio canônico esteja migrando para Processamentos + Modo laboratório;
- quantidade de campos em sequência torna a página pesada no mobile.

**Direção**

Separar mentalmente: cálculo rápido, preparação/consulta e execução de processo. Toolbox serve para acesso rápido, não para duplicar uma aplicação completa.

## Fundação visual — conclusão da inspeção

A identidade escura, tipografia e uso de linhas finas são coerentes e devem ser preservados. O redesign não deve trocar a linguagem visual por um dashboard genérico.

O que precisa mudar é a **gramática de produto**:

- reduzir monumentalidade onde a tarefa exige densidade;
- reservar títulos muito grandes para mudanças reais de contexto;
- usar resumos operacionais escaneáveis em vez de parágrafos dentro de cards;
- padronizar menu/ações de entidade;
- diferenciar ação primária, secundária e destrutiva;
- introduzir ajuda contextual curta;
- tornar estados vazios instrutivos;
- priorizar sequência e estado do trabalho sobre estrutura de dados;
- projetar mobile pela tarefa, não somente por breakpoint.

## Backlog inicial por prioridade

### Tranche A — fundação + núcleo experimental

1. ampliar `student-visual-audit` para Processamentos (biblioteca/editor) e Modo laboratório;
2. criar gramática compartilhada de ações de entidade e zona destrutiva;
3. criar padrão de orientação pedagógica e estado vazio;
4. redesenhar biblioteca de Processamentos;
5. adicionar edição real de etapa e duplicação de processamento;
6. representar reutilização do banho entre primeira e segunda revelação;
7. reorganizar entrada Caderno → Processamento → Laboratório;
8. inspecionar e corrigir runner em desktop e mobile.

### Tranche B — Caderno

1. reorganizar Exposição pela lógica de captura;
2. reduzir o Processamento livre à alternativa explícita;
3. conectar Resultado às condições registradas;
4. padronizar ações dos registros e estados vazios;
5. revisar Novo registro e contexto Curso/Pessoal.

### Tranche C — Inventário e bancada

1. separar materiais, preparações e predefinições na linguagem da interface;
2. revisar movimentações e consequências;
3. integrar reutilização/consumo sem dupla baixa;
4. simplificar Ferramentas e toolbox.

### Tranche D — entrada e conteúdo

1. Dashboard orientado a continuidade;
2. progressão em Curso/Material;
3. Dúvidas, comunicação e Conta sob a mesma gramática.

## Gate de qualidade desta revisão

Uma correção visual não encerra um item desta auditoria. Cada item só é fechado quando a tarefa correspondente estiver funcional de ponta a ponta, tiver orientação adequada para alunos, tiver sido inspecionada visualmente em desktop/mobile e tiver testes de comportamento que cubram a operação principal.