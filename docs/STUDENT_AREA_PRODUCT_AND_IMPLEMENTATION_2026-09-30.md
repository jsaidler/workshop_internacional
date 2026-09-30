# Área do aluno — produto e implementação

Data: 2026-09-30
Status: canônico para a próxima etapa de produto

Este documento congela as decisões tomadas para a evolução da Área do aluno. Ele substitui propostas exploratórias anteriores quando houver conflito.

## 1. Princípio de produto

A Área do aluno deixa de ser apenas um seletor de cursos com material e testes. Ela passa a ter quatro responsabilidades separadas:

- **Meus cursos**: conteúdo, aulas, material e interação pedagógica da matrícula.
- **Caderno de Processos**: histórico pessoal de exposição, processamento e resultado.
- **Ferramentas**: aplicações práticas reutilizáveis, liberadas pelas matrículas ou comuns a todos os alunos.
- **Conta**: dados e segurança da conta.

O Caderno e as Ferramentas são globais. Não pertencem à navegação local de um curso.

Dentro de um curso ficam **Visão geral**, **Material** e **Dúvidas**.

## 2. Acesso às ferramentas

Uma ferramenta existe uma única vez na plataforma. A matrícula apenas concede acesso.

Uma ferramenta pode ser:

- comum a todos os alunos ativos;
- liberada por um curso;
- liberada por vários cursos.

A ferramenta não é copiada por curso. Recursos internos de uma ferramenta — presets, curvas, fórmulas ou outros dados — também podem ter regras próprias de disponibilidade.

Ferramentas sem acesso não aparecem para o aluno. A Área do aluno não deve assumir aparência de marketplace com recursos bloqueados ou cadeados.

O construtor de câmera/pinhole está fora deste escopo e pertence a outro curso futuro.

## 3. Caderno de Processos

### 3.1. Identidade

O antigo conceito visual de **Testes** passa a ser **Caderno de Processos**. Cada item é um **registro**.

O Caderno pertence ao aluno. Um registro pode ter contexto de curso/turma ou ser pessoal. A associação pedagógica permite avaliação, compartilhamento e discussão, mas o registro continua pertencendo ao aluno.

### 3.2. Guiado significa fácil, não explicativo

O Caderno não é material didático, receita nem apostila. A teoria permanece no material do curso.

O caráter didático do Caderno está na redução de esforço:

- mostrar apenas a decisão/campos pertinentes à etapa atual;
- sugerir o próximo passo conforme o caminho já registrado;
- salvar progressivamente;
- permitir repetir um processo anterior;
- calcular informações derivadas em vez de pedir que o aluno as calcule;
- permitir anotações opcionais e desvios sem quebrar o fluxo.

Não devem existir blocos explicativos, receitas ou textos longos dentro do fluxo do Caderno.

### 3.3. Positivo e negativo não são modos de entrada

Não existe escolha inicial “positivo” ou “negativo”. Exposição e primeira revelação são comuns.

Depois da primeira revelação, o caminho é determinado pelo processamento efetivamente realizado. O resultado positivo/negativo pode ser inferido ou usado posteriormente como metadado, mas não determina a estrutura inicial do registro.

### 3.4. Fluxo guiado

Base comum:

1. cena/exposição;
2. primeira revelação;
3. lavagem com água ou banho interruptor;
4. próximo tratamento.

A partir do próximo tratamento, o caminho se ramifica. O catálogo inicial inclui:

- hipossulfito/fixador;
- solução peroxiacética;
- cloreto férrico;
- dicromato;
- permanganato;
- outro.

Rotas principais:

- fixação → lavagem → secagem;
- peroxiacética → lavagem → segunda revelação → lavagem → secagem;
- cloreto férrico → lavagem quando aplicável → amônia → lavagem → segunda revelação → lavagem → secagem;
- dicromato e permanganato seguem suas etapas de limpeza/lavagem e convergem para a segunda revelação;
- qualquer etapa pode ser substituída ou complementada por uma etapa livre.

Cada etapa pode ter **Anotações** opcionais. O caminho realizado deve permanecer visível como uma sequência compacta.

### 3.5. Reveladores

Os dois reveladores do curso aparecem primeiro:

- Parodinal;
- Brewed Caffenol.

O catálogo também pode oferecer reveladores analógicos de uso corrente e sempre oferece **Outro**. O catálogo não transforma o Caderno em guia de receitas.

O sistema precisa conhecer apenas o comportamento necessário à interface:

- concentrado/solução estoque: recebe quantidade de revelador + quantidade de água;
- preparação fresca: recebe volume preparado/usado e, quando houver, um preparo pessoal salvo;
- outro: aceita os campos genéricos necessários.

Parodinal se comporta como Rodinal para registro de diluição.

Qualquer Caffenol é preparado na hora e nunca é tratado como solução pronta armazenada.

### 3.6. Quantidades e diluição

Para concentrados e soluções estoque, o aluno registra o que realmente mediu, por exemplo:

- revelador: 10 ml;
- água: 550 ml.

O sistema calcula e exibe:

- diluição: 1+55;
- volume de trabalho: 560 ml.

A diluição é informação derivada, não um campo que o aluno precisa calcular.

O mesmo dado serve ao Inventário: no exemplo acima, a baixa é de 10 ml do revelador, não de 560 ml.

### 3.7. Preparos salvos

O aluno pode salvar preparos habituais e reutilizá-los em novos registros. Os valores são preenchidos como ponto de partida e continuam editáveis no registro atual.

Podem existir vários preparos por revelador.

Para Caffenol, um preparo salvo pode guardar os ingredientes e quantidades usados na preparação fresca. Ele não cria estoque de Caffenol pronto.

### 3.8. Operações do Caderno

O Caderno deve permitir:

- novo registro;
- repetir/duplicar um registro anterior;
- comparar dois registros destacando parâmetros e etapas iguais/diferentes;
- anexar fotografia da cena e do resultado;
- compartilhar quando houver contexto pedagógico e regra de acesso;
- continuar o registro sem perder etapas já preenchidas.

## 4. Inventário do laboratório

O **Inventário do laboratório** é uma ferramenta global.

Ele controla dois tipos de recursos:

### 4.1. Insumos

Produtos e matérias-primas existentes no laboratório, por exemplo café, carbonato, ácido ascórbico, sulfito, hidróxido, paracetamol, cloreto férrico, amônia e fixador.

Campos essenciais: nome, categoria, quantidade, unidade, lote/identificação opcional, data de entrada/preparo, validade opcional, local, quantidade mínima opcional e anotações.

### 4.2. Soluções preparadas armazenáveis

Soluções que realmente podem permanecer preparadas entram como lotes/itens de estoque, por exemplo um lote de Parodinal.

Caffenol é explicitamente uma preparação de uso imediato: seus ingredientes podem existir no inventário, mas o Caffenol pronto não vira estoque.

### 4.3. Movimentações e integração

Toda alteração de saldo relevante deve gerar movimentação. O sistema não deve apenas substituir silenciosamente o número atual.

Um registro do Caderno pode apontar para o item/lote utilizado. Ao confirmar o uso, o consumo é lançado automaticamente no Inventário.

Preparações frescas podem dar baixa nos ingredientes utilizados.

O histórico de um item deve permitir chegar aos registros de processo que consumiram aquele material.

O Inventário deve permanecer simples; não é um ERP de laboratório.

## 5. Ferramentas globais

O catálogo inicial é:

- **Reciprocidade** — reaproveita o cálculo já existente e continua disponível dentro do fluxo de exposição quando necessário;
- **Exposição** — relações entre tempo, abertura, EI e EV, com reciprocidade quando pertinente;
- **Temporizador de laboratório** — etapas e agitação com interface adequada ao uso durante processamento;
- **Calibração** — guarda referências pessoais de processo, incluindo formação do branco para determinada configuração;
- **Preparo de soluções** — dimensiona quantidades das fórmulas liberadas pelas matrículas;
- **Inventário do laboratório** — insumos, soluções armazenáveis e movimentações.

Uma ferramenta não deve fingir autoridade onde a pesquisa ainda não estabeleceu uma regra. Não criar calculador automático universal de N/N±, compensador universal de temperatura, simulador ortocromático ou avaliação estética por IA.

## 6. Dúvidas

**Dúvidas** permanece dentro do curso porque depende de contexto pedagógico.

Não é um fórum genérico. O objetivo é resolver perguntas e conservar soluções úteis.

MVP:

- pergunta privada ou de turma;
- assunto/tópico;
- possibilidade de vincular um registro do Caderno;
- respostas de alunos quando a pergunta for da turma;
- resposta do professor;
- estado aberto/resolvido;
- possibilidade de destacar uma solução confirmada;
- busca posterior das questões resolvidas.

## 7. Anotações no material

As anotações entram já nesta etapa.

Elas pertencem ao aluno e ao material, preferencialmente à **seção** identificada pelo CMS, e não a uma coordenada visual frágil.

Requisitos:

- ação discreta “Anotar” nas seções disponíveis ao aluno;
- uma anotação persistente por aluno/seção, editável e removível;
- salvamento sem transformar o material em editor;
- acesso posterior às notas dentro do próprio material;
- vínculo com `page_id`, `section_key` e, quando existente, `lesson_id`.

O material continua sendo a autoridade de conteúdo; a anotação é uma camada pessoal.

## 8. Funcionalidades secundárias adiadas

Não entram automaticamente nesta rodada:

- favoritos/minha referência;
- agenda do curso;
- preparação para próximo encontro;
- glossário;
- fornecedores;
- exportação do Caderno;
- estatísticas pessoais.

Podem ser avaliadas depois da validação do novo núcleo.

## 9. Compatibilidade

A implementação deve preservar registros, mídias, mensagens e avaliações existentes.

Os nomes técnicos `student_tests` e URLs antigas podem permanecer temporariamente como compatibilidade interna, mas a interface do aluno usa **Caderno**, **registro** e **processo**.

Novos dados de etapas, preparos, inventário, dúvidas, acesso a ferramentas e anotações são normalizados em tabelas próprias. Não armazenar a nova lógica inteira em campos JSON do registro legado.

## 10. Ordem de implementação

1. schema e serviços de domínio;
2. navegação global e Caderno global;
3. fluxo guiado por etapas + preparos salvos + integração básica com Inventário;
4. operações repetir e comparar;
5. área Ferramentas e regras de acesso;
6. Inventário completo;
7. demais ferramentas;
8. Dúvidas dentro do curso;
9. anotações no material;
10. regressão responsiva, compatibilidade, CI, merge e deploy.

A implementação deve entregar fatias completas e utilizáveis; não criar entradas de navegação para páginas vazias.