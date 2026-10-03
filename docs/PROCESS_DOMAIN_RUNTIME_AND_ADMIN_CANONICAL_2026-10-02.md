# Processos de laboratório — modelo de domínio, estados reais e administração

Data: 2026-10-02
Status: canônico para a área de processos do aluno e para a administração

Este documento complementa `STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md` e existe para impedir que a implementação volte a tratar o processamento como um wizard linear ou como um procedimento automatizado controlado pelo sistema.

## 1. Regra central

Associar um processo a um registro **não significa iniciar nem avançar a revelação**.

O sistema deve distinguir, de forma persistente e auditável:

1. **Processo global** — definição administrável do workshop.
2. **Versão global publicada** — snapshot imutável de uma definição global.
3. **Processo pessoal** — roteiro salvo pelo aluno e editável por ele.
4. **Plano associado ao registro** — snapshot do roteiro escolhido para aquela fotografia, já com seus valores de duração, temperatura, agitação, solução e demais parâmetros.
5. **Posição declarada no processo** — até onde o aluno informa que a revelação real chegou.
6. **Sessão de laboratório** — estado operacional auxiliar do timer e da interface; não é fato laboratorial.
7. **Etapas realizadas** — fatos consolidados no Caderno a partir da progressão real declarada e dos valores do snapshot.
8. **Metadados de registro** — indicam origem e eventuais correções sem confundir interação de interface com acontecimento físico.

A sequência do roteiro representa a sequência física/química do processo. O usuário pode deixar de usar o aplicativo durante parte da revelação e retomá-lo depois. Isso não significa que as etapas intermediárias deixaram de acontecer, foram interrompidas ou foram executadas fora de ordem.

Fatos já consolidados não podem ser silenciosamente reescritos por uma troca de plano ou por uma alteração administrativa.

## 2. Autoridade dos dados

### 2.1 Processos globais

A autoridade é o banco de dados, com versionamento. PHP não deve conter receitas operacionais como fonte canônica em tempo de execução.

Uma versão publicada é imutável. Para alterar um processo global:

`versão publicada → criar rascunho → editar → revisar → publicar nova versão`

Planos existentes continuam ligados ao snapshot anterior.

### 2.2 Catálogos laboratoriais

Etapas e reveladores são dados de domínio, não detalhes escondidos da aplicação. Devem ter chave estável e metadados administráveis. Desabilitar uma entrada significa impedir novos usos; não significa apagar seu significado de registros históricos.

### 2.3 Snapshot por fotografia

Ao aplicar um processo padrão ou pessoal a uma fotografia, o registro recebe sua própria cópia dos parâmetros. Esses valores são os valores efetivos daquele processamento até que o aluno os altere explicitamente.

Alterar duração, temperatura, agitação ou outro parâmetro no laboratório modifica somente o snapshot daquela fotografia. A definição global/pessoal que originou o snapshot não é modificada retroativamente.

### 2.4 Registro factual

`student_process_steps` representa etapas consolidadas como realizadas no Caderno.

Se o aluno declara que chegou a uma etapa posterior do roteiro, as etapas anteriores necessárias àquela posição são consideradas realizadas e podem ser materializadas com os valores atuais do snapshot. O sistema não exige que o aluno tenha usado o timer ou confirmado cada tela durante o laboratório.

Abrir uma etapa para consulta, iniciar/pausar/reiniciar um timer, fechar a aba, perder rede ou trocar de dispositivo não autoriza o sistema a criar, interromper, concluir ou invalidar fatos.

## 3. Posição do processo e sessão de timer

A **posição do processo** e a **sessão do timer** são estados diferentes.

### 3.1 Posição do processo

A posição só muda por ação explícita que declare onde a revelação real está, por exemplo:

- `Estou nesta etapa`;
- `Ir para próxima etapa`;
- `Concluir processamento` na última etapa.

Consultar uma etapa anterior ou posterior é leitura e não muda a posição.

Ao avançar para uma etapa posterior, as etapas anteriores ainda não consolidadas são registradas usando os valores atuais do snapshot. Isso representa a sequência real do processo sem exigir acompanhamento contínuo do aplicativo.

### 3.2 Timer

Para uma etapa temporizada, a sessão persistida admite:

- `idle`: timer disponível e ainda não iniciado;
- `running`: contagem em curso, com instante absoluto de término no servidor;
- `paused`: tempo restante persistido;
- `elapsed`: contagem chegou a zero.

`elapsed` **não equivale** a etapa concluída nem avança a revelação. O usuário pode permanecer fisicamente naquela etapa, avançar alguns segundos depois ou ter continuado sem o aplicativo.

Iniciar, pausar, reiniciar ou abandonar o timer também não altera o status factual da etapa.

### 3.3 Agitação

A temporização da agitação admite:

- sem temporização de agitação;
- contínua;
- periódica.

Agitação periódica possui dois parâmetros distintos:

- duração de cada agitação;
- intervalo entre o **início** das agitações.

`10 s a cada 60 s` significa `0:00–0:10`, `1:00–1:10`, `2:00–2:10` etc.

Em agitação contínua, a indicação permanece ativa durante toda a contagem da etapa. Alterar tempo ou agitação modifica o snapshot daquela fotografia, não o processo-base.

## 4. Concorrência, reload e interrupções técnicas

O servidor é a autoridade da sessão auxiliar do timer. O cliente pode manter estado local apenas como cache de interface.

Cada alteração de timer usa:

- `revision` para detectar uma aba ou dispositivo desatualizado;
- `client_token` para tornar uma repetição de requisição idempotente;
- `plan_step_id` para impedir que uma aba antiga altere o timer de outra etapa.

Fechar a aba, recarregar, bloquear a tela, perder rede ou abrir o mesmo processamento em outro aparelho não pode reiniciar silenciosamente o timer.

Essas ocorrências são **interrupções técnicas do acompanhamento**, não interrupções da revelação. Nenhuma delas cria um `student_process_step` factual nem metadata de “etapa interrompida”.

## 5. Alterações administrativas

O administrador deve poder, pela interface:

- criar processo global;
- editar um rascunho;
- adicionar, editar, ordenar e remover etapas do rascunho;
- publicar nova versão;
- consultar versão ativa e histórico;
- arquivar e reativar processo global;
- administrar catálogo de etapas;
- administrar catálogo de reveladores;
- identificar quantos planos usam versões de um processo;
- alterar definições futuras sem modificar registros e planos já existentes.

Ações administrativas de domínio devem produzir log de mudança.

## 6. Matriz obrigatória de cenários reais

Os testes não podem se limitar ao caminho ideal. A cobertura funcional e visual deve representar, no mínimo, os cenários abaixo.

### A. Associação sem avanço

A1. Associar padrão global e voltar ao Caderno sem avançar o processo.
A2. Associar processo pessoal e fechar o navegador.
A3. Trocar o roteiro antes de qualquer etapa consolidada.
A4. Associar roteiro hoje e revelar dias depois.
A5. Abrir um roteiro associado e escolher explicitamente registro retroativo.

### B. Uso no laboratório

B1. Abrir a primeira etapa sem iniciar timer e continuar podendo avançar.
B2. Iniciar, pausar e retomar o timer sem criar fato laboratorial.
B3. Reiniciar o timer sem alterar a posição do processo.
B4. Tempo chegar a zero sem avanço automático.
B5. Consultar uma etapa posterior sem mudar a posição real.
B6. Declarar uma etapa posterior depois de ter seguido a revelação sem o app; etapas intermediárias são consolidadas pelos valores do snapshot.
B7. Alterar o tempo de uma etapa naquela fotografia e preservar o processo-base.
B8. Agitação periódica com duração e intervalo entre inícios.
B9. Agitação contínua durante toda a contagem.
B10. Reutilização do primeiro banho na segunda revelação sem novo consumo indevido.
B11. Concluir a última etapa e retornar ao Caderno.

### C. Interrupções técnicas

C1. Reload com cronômetro rodando.
C2. Fechar a aba e reabrir enquanto o cronômetro continua.
C3. Bloquear/desbloquear o telefone.
C4. Perder rede depois de iniciar e recuperar a conexão.
C5. Duplo toque/reenvio da mesma ação.
C6. Duas abas abertas no mesmo timer.
C7. Abrir o processamento em outro aparelho.
C8. Aba antiga tentar alterar um timer já atualizado em outro cliente.
C9. Nenhum cenário C pode gerar automaticamente fato, interrupção ou avanço do processo.

### D. Registro retroativo e retomada

D1. Registrar processo inteiro já concluído.
D2. Informar data de realização diferente da data de registro.
D3. Usar o app em parte da revelação, continuar fisicamente sem ele e depois declarar a posição posterior correta.
D4. Começar com acompanhamento e completar o restante retroativamente.
D5. Corrigir depois os parâmetros de uma etapa consolidada quando os valores efetivos diferiram do snapshot.

### E. Exceções reais do laboratório

E1. Tempo efetivo diferente do valor originalmente herdado.
E2. Temperatura diferente.
E3. Inserir etapa adicional quando o procedimento real exigiu uma etapa não prevista.
E4. Repetir uma etapa quando isso efetivamente aconteceu.
E5. Remover explicitamente do roteiro daquela fotografia uma etapa que excepcionalmente não integrou o processo; não usar `skipped` como inferência automática.
E6. Usar químico diferente e registrar a alteração.
E7. Não reutilizar um banho quando o roteiro previa reutilização.

### F. Correções

F1. Corrigir parâmetro de uma etapa já registrada sem apagar etapas posteriores.
F2. Corrigir consumo de inventário sem criar baixa dupla.
F3. Corrigir registro retroativo depois de salvo.
F4. Tentar alterar registro revisado e receber a política correspondente, sem alteração silenciosa.

### G. Administração e versionamento

G1. Editar um processo nunca utilizado.
G2. Criar nova versão de processo já utilizado.
G3. Publicar nova versão enquanto um aluno tem a versão anterior associada.
G4. Publicar nova versão enquanto um aluno usa o snapshot anterior no laboratório.
G5. Arquivar um processo com histórico existente.
G6. Reativar processo arquivado.
G7. Desabilitar item de catálogo sem destruir registros históricos.
G8. Criar novo processo global usando entradas administradas dos catálogos.

### H. Inventário

H1. Consumo previsto diferente do efetivamente registrado.
H2. Correção de etapa estornar movimento anterior e aplicar o novo uma única vez.
H3. Roteiro associado ou timer iniciado não consumir inventário por si só.
H4. Registro retroativo não inventar consumo se o usuário não informou consumo.

## 7. Gate obrigatório desta área

Nenhuma tranche subsequente compensa uma falha nesta base. A sequência obrigatória é:

`modelagem dos estados reais → implementação → fixtures representativas → renderização desktop/mobile → inspeção visual crítica pelo próprio desenvolvimento → correções → reinspeção → testes de mudança/interrupção técnica → CI → merge → conferência do pacote de produção`

CI verde sem inspeção visual não encerra o trabalho.

Fixtures que escondem quantidade de etapas, ações importantes ou estados intermediários são inválidas mesmo que os testes passem.

## 8. Critérios visuais

A auditoria deve incluir, no mínimo:

- lista de processos globais;
- processo global publicado sem rascunho;
- processo com rascunho em edição e roteiro longo;
- histórico de versões;
- catálogos de etapas e reveladores;
- escolha do roteiro pelo aluno;
- roteiro apenas associado;
- navegação entre etapas sem avanço;
- timer `idle`, `running`, `paused` e `elapsed`;
- agitação periódica;
- agitação contínua;
- ajustes de tempo/agitação expandidos;
- progressão para etapa posterior;
- registro retroativo;
- processo concluído.

Desktop e mobile são superfícies independentes de validação. A densidade do fixture deve representar o conteúdo real.

## 9. Regra de continuidade

Antes de iniciar Dashboard + Curso + Material, esta camada deve estar funcionalmente estável, administrável e visualmente auditada. Caso um cenário da matriz exponha um erro estrutural, ele deve ser corrigido aqui antes de avançar.
