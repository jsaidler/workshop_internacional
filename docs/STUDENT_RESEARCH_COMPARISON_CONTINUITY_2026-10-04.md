# Área do Aluno — comparação e continuidade da pesquisa — 04/10/2026

## Objetivo

A comparação do Caderno existe para responder uma pergunta simples e verificável: **o que foi registrado de modo diferente entre duas experimentações e como os resultados observados se apresentam lado a lado?**

Ela não é um painel de métricas, não atribui nota ao resultado e não tenta inferir causalidade. O sistema mostra diferenças registradas; a interpretação continua sendo do fotógrafo, apoiada pelos próprios resultados e anotações.

## Autoridades

- `student_tests` continua sendo a autoridade de cada experimentação: exposição, contexto, resultado e estado de avaliação.
- `student_process_steps` continua sendo a autoridade do processamento que de fato foi registrado em cada experimentação.
- `student_test_media` continua sendo a autoridade das imagens de cena e resultado.
- `source_test_id` registra somente a relação de continuidade: qual registro foi usado como ponto de partida para a nova tentativa.
- `research_intent` registra, antes da nova tentativa, **o que o aluno pretende mudar ou observar**.

A relação de origem não transforma dois registros em versões do mesmo objeto. Cada experimentação continua autônoma e preserva os seus próprios fatos.

## Comparação descritiva

`aluno/comparar-processos.php` deve separar claramente três planos:

1. **O que mudou** — apenas campos cujo conteúdo registrado difere entre A e B;
2. **Resultados** — imagens e anotações de resultado apresentadas lado a lado, sem alegação causal;
3. **Dados registrados** — exposição e processamento completos como evidência para conferência.

Os campos comparáveis de exposição são: data, filme, lote, EI, abertura, tempo sem reciprocidade, tempo com reciprocidade, condição de luz e faixa tonal/intenção.

No processamento, a comparação segue a posição das etapas e observa nome/químico, diluição, volume total, temperatura, tempo e movimentação. Espaços e diferenças apenas de caixa não devem fabricar uma mudança.

Quando não houver diferença técnica nos campos comparáveis, a interface deve dizer isso explicitamente. O sistema nunca deve substituir essa ausência por uma conclusão sobre equivalência visual ou química.

## Continuidade da pesquisa

Depois de comparar, o aluno pode escolher um dos registros como base e declarar o que pretende mudar na próxima tentativa.

A nova tentativa:

- recebe `source_test_id` apontando para a base escolhida;
- recebe `research_intent` com a intenção declarada;
- começa como um novo registro `draft`;
- herda somente os dados de exposição usados como ponto de partida;
- não herda resultado, mensagens de avaliação nem etapas de processamento como fatos já executados;
- preserva o contexto pessoal/curso da origem quando esse contexto ainda pode ser usado pelas regras normais de matrícula.

Não copiar as etapas executadas é uma regra semântica importante: uma etapa registrada pertence ao que aconteceu no registro de origem. Copiá-la para um novo teste faria o sistema afirmar que um processamento futuro já ocorreu.

## Contexto no novo registro

Um registro derivado mostra, de forma discreta, a sua origem e a intenção da próxima tentativa. Deve oferecer acesso direto a `Comparar com origem`.

Esse contexto não cria uma timeline paralela, não altera o fluxo Exposição → Processamento → Resultado e não interfere no ciclo de avaliação pedagógica da Tranche E.

## Entrada pelo Caderno

O Caderno não volta a ter seleção permanente ou checkboxes de comparação. A ação aparece em `Mais ações` como **Comparar com outro registro**. Assim, a comparação nasce de um registro concreto e o aluno escolhe somente a segunda referência.

A comparação também pode ser aberta sem uma origem pré-selecionada; nesse caso, a própria tela pede dois registros.

## Limites de interpretação

- diferença registrada não significa causa demonstrada;
- ausência de diferença registrada não significa processos idênticos em tudo o que aconteceu no laboratório;
- o sistema não classifica resultado como melhor/pior;
- o sistema não calcula uma variável dominante;
- o sistema não fabrica diferenças a partir de campos vazios equivalentes, espaços ou capitalização.

A formulação pública deve permanecer no domínio de **diferenças registradas**, **resultado observado** e **próxima tentativa**.

## Persistência e compatibilidade

A migração `084_student_research_lineage.php` adiciona `source_test_id` e `research_intent` a `student_tests`. Leituras do serviço de pesquisa devem tolerar banco ainda não migrado; a criação de uma continuação, por outro lado, exige o esquema novo para não perder a relação de pesquisa silenciosamente.

A exclusão da origem usa `ON DELETE SET NULL`: a experimentação derivada continua existindo, mas deixa de afirmar uma origem que já não está disponível.

## Enquadramento no material pedagógico

O conteúdo que usa Caderno, Exposição, Processamento, Resultado, avaliação, comparação e continuidade é apresentado ao aluno como **Prática**. O identificador histórico interno `caderno-aula-3` pode ser preservado, mas não define a função pedagógica nem a nomenclatura pública desse bloco.

Prática acontece **entre o segundo e o terceiro encontro**. Por isso, suas seções devem ser liberadas com a Aula 2, e não depender da liberação do terceiro encontro. O fim da Aula 2 apenas introduz o trabalho autônomo; Prática oferece as orientações necessárias enquanto o aluno produz e registra suas próprias tentativas.

O terceiro encontro não possui uma seção de conteúdo fixo no material. Sua proposta aparece no último parágrafo de Prática: partir das experiências realizadas pelos alunos para observar resultados, discutir diferenças registradas e analisar em conjunto as decisões de exposição e processamento. Não é exigido um resultado “correto”.

O fluxo pedagógico permanece `registro → observação → avaliação → comparação → próxima tentativa`, com as mesmas invariantes semânticas deste documento: cálculo ou roteiro não equivalem a fato realizado, comparação é descritiva e uma continuação não copia processamento executado nem resultado da tentativa de origem.

### Regra editorial da Prática

O material de Prática não deve funcionar como manual da interface. Screenshots reais da Área do Aluno permanecem úteis para auditoria visual do produto, mas não devem ser incorporados ao texto pedagógico apenas para demonstrar telas. No material, recursos visuais devem explicar relações, decisões e sequências do processo fotográfico.

A versão corrente usa placeholders editoriais nos pontos em que um infográfico futuro agrega compreensão: fluxo Exposição → Processamento → Resultado, Caderno como registro, exposição, diferença entre roteiro e processamento realizado, resultado avaliado, comparação descritiva e continuidade da pesquisa. Esses placeholders não representam telas do sistema e não devem ser preenchidos automaticamente com screenshots do produto.

A antiga unidade autônoma **Ferramentas da área do aluno** deixa de fazer parte do fluxo principal. As ferramentas permanecem disponíveis no produto e recebem apenas um convite breve depois de **Criar a próxima tentativa**. O enquadramento é de apoio: cálculos, consultas e organização do laboratório podem ser explorados conforme fizerem sentido para a prática, mas não substituem o Caderno nem o registro factual de cada tentativa.

## Gate visual e funcional

A Tranche F acrescenta ao inventário visual obrigatório:

- comparação com um registro já escolhido e escolha do segundo;
- comparação analítica com diferenças, resultados e criação da próxima tentativa;
- registro derivado mostrando origem e intenção de pesquisa.

Esses estados devem ser renderizados em desktop e telefone, sem overflow horizontal e sem reintroduzir controles permanentes de comparação no Caderno.

A auditoria do material pedagógico deve, separadamente, provar que os screenshots do produto não reaparecem dentro de Prática, que os placeholders editoriais permanecem legíveis em desktop e telefone e que a unidade autônoma de Ferramentas não foi reintroduzida.

O gate funcional deve provar pelo menos:

- migração das colunas de linhagem;
- normalização que evita diferenças cosméticas;
- detecção de diferenças de exposição e processamento;
- criação de uma nova tentativa com origem e intenção persistidas;
- ausência de cópia de resultado e de processamento executado;
- integração contextual entre Caderno, comparação e registro derivado.
