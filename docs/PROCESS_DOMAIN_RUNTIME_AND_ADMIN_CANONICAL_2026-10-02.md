# Processos de laboratório — modelo de domínio, estados reais e administração

Data: 2026-10-02
Status: canônico para a área de processos do aluno e para a administração

Este documento complementa `STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md` e existe para impedir que a implementação volte a tratar o processamento como um wizard linear.

## 1. Regra central

Associar um processo a um registro **não significa iniciar nem declarar uma execução**.

O sistema deve distinguir, de forma persistente e auditável:

1. **Processo global** — definição administrável do workshop.
2. **Versão global publicada** — snapshot imutável de uma definição global.
3. **Processo pessoal** — roteiro salvo pelo aluno e editável por ele.
4. **Plano associado ao registro** — snapshot do roteiro escolhido para aquela fotografia.
5. **Sessão de laboratório** — estado operacional temporário da execução ao vivo, inclusive cronômetro.
6. **Etapas realizadas** — fatos registrados no Caderno; não são o mesmo que o plano.
7. **Metadados de registro** — indicam se os fatos foram registrados ao vivo, retroativamente ou de forma mista.

O plano pode mudar antes da execução. Fatos já registrados não podem ser silenciosamente reescritos por uma troca de plano ou por uma alteração administrativa.

## 2. Autoridade dos dados

### 2.1 Processos globais

A autoridade é o banco de dados, com versionamento. PHP não deve conter receitas operacionais como fonte canônica em tempo de execução.

Uma versão publicada é imutável. Para alterar um processo global:

`versão publicada → criar rascunho → editar → revisar → publicar nova versão`

Planos existentes continuam ligados ao snapshot anterior.

### 2.2 Catálogos laboratoriais

Etapas e reveladores são dados de domínio, não detalhes escondidos da aplicação. Devem ter chave estável e metadados administráveis. Desabilitar uma entrada significa impedir novos usos; não significa apagar seu significado de registros históricos.

### 2.3 Registro factual

`student_process_steps` representa o que foi efetivamente registrado como realizado. O software pode comparar esses fatos com um plano e avisar sobre desvios, mas não deve impedir o registro factual porque a sequência real divergiu do roteiro.

## 3. Estados da execução ao vivo

O cronômetro e a etapa atual não podem depender apenas de `sessionStorage` ou da aba do navegador.

Para uma etapa temporizada, a sessão persistida admite:

- `idle`: etapa atual, cronômetro ainda não iniciado;
- `running`: cronômetro em curso, com instante absoluto de término no servidor;
- `paused`: tempo restante persistido;
- `elapsed`: tempo chegou a zero, mas a etapa ainda não foi confirmada como concluída.

`elapsed` **não equivale** a etapa concluída. O tempo pode terminar e o aluno ainda precisar realizar uma ação física antes de confirmar a etapa.

A conclusão da etapa cria/associa o fato realizado e só então o plano avança.

## 4. Concorrência, reload e interrupção

O servidor é a autoridade da sessão ao vivo. O cliente pode manter estado local apenas como cache de interface.

Cada alteração de estado usa:

- `revision` para detectar uma aba ou dispositivo desatualizado;
- `client_token` para tornar uma repetição de requisição idempotente;
- `plan_step_id` para impedir que uma aba antiga altere a etapa seguinte.

Fechar a aba, recarregar, bloquear a tela, perder rede ou abrir o mesmo processamento em outro aparelho não pode reiniciar silenciosamente o cronômetro.

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

### A. Associação sem execução

A1. Associar padrão global e voltar ao Caderno sem executar.
A2. Associar processo pessoal e fechar o navegador.
A3. Trocar o roteiro antes de qualquer etapa executada.
A4. Associar roteiro hoje e executar dias depois.
A5. Abrir um roteiro associado e escolher explicitamente registro retroativo.

### B. Execução ao vivo

B1. Iniciar primeira etapa temporizada e concluir normalmente.
B2. Pausar e retomar a mesma etapa.
B3. Reiniciar o cronômetro explicitamente.
B4. Tempo chegar a zero sem concluir imediatamente a etapa.
B5. Etapa sem duração e conclusão manual.
B6. Reutilização do primeiro banho na segunda revelação sem novo consumo.
B7. Concluir a última etapa e retornar ao Caderno.

### C. Interrupções técnicas

C1. Reload com cronômetro rodando.
C2. Fechar a aba e reabrir enquanto o cronômetro continua.
C3. Bloquear/desbloquear o telefone.
C4. Perder rede depois de iniciar e recuperar a conexão.
C5. Duplo toque/reenvio da mesma ação.
C6. Duas abas abertas no mesmo passo.
C7. Abrir a execução em outro aparelho.
C8. Aba antiga tentar alterar uma etapa que já avançou em outro cliente.

### D. Registro retroativo e misto

D1. Registrar processo inteiro já concluído.
D2. Informar data de realização diferente da data de registro.
D3. Registrar apenas parte já realizada e continuar ao vivo.
D4. Começar ao vivo e terminar o restante retroativamente.
D5. Registrar sequência real diferente do roteiro associado.

### E. Desvios de laboratório

E1. Tempo real diferente do planejado.
E2. Temperatura real diferente.
E3. Inserir etapa não prevista.
E4. Repetir uma etapa.
E5. Pular uma etapa planejada.
E6. Usar químico diferente.
E7. Não reutilizar um banho que o roteiro previa reutilizar.
E8. Abandonar o processamento antes da secagem e documentar o estado real.

### F. Correções

F1. Corrigir parâmetro de uma etapa já registrada sem apagar etapas posteriores.
F2. Corrigir consumo de inventário sem criar baixa dupla.
F3. Corrigir registro retroativo depois de salvo.
F4. Tentar alterar registro revisado e receber a política correspondente, sem alteração silenciosa.

### G. Administração e versionamento

G1. Editar um processo nunca utilizado.
G2. Criar nova versão de processo já utilizado.
G3. Publicar nova versão enquanto um aluno tem a versão anterior associada.
G4. Publicar nova versão enquanto um aluno está executando a anterior.
G5. Arquivar um processo com histórico existente.
G6. Reativar processo arquivado.
G7. Desabilitar item de catálogo sem destruir registros históricos.
G8. Criar novo processo global usando entradas administradas dos catálogos.

### H. Inventário

H1. Consumo planejado diferente do real.
H2. Correção de etapa estornar movimento anterior e aplicar o novo uma única vez.
H3. Roteiro associado sem execução não consumir inventário.
H4. Registro retroativo não inventar consumo se o usuário não informou consumo.

## 7. Gate obrigatório desta área

Nenhuma tranche subsequente compensa uma falha nesta base. A sequência obrigatória é:

`modelagem dos estados reais → implementação → fixtures representativas → renderização desktop/mobile → inspeção visual crítica pelo próprio desenvolvimento → correções → reinspeção → testes de mudança/interrupção → CI → merge → conferência do pacote de produção`

CI verde sem inspeção visual não encerra o trabalho.

Fixtures que escondem quantidade de etapas, ações administrativas ou estados intermediários são inválidas mesmo que os testes passem.

## 8. Critérios visuais

A auditoria deve incluir, no mínimo:

- lista de processos globais;
- processo global publicado sem rascunho;
- processo com rascunho em edição e roteiro longo;
- histórico de versões;
- catálogos de etapas e reveladores;
- escolha do roteiro pelo aluno;
- roteiro apenas associado;
- execução `idle`, `running`, `paused` e `elapsed`;
- execução parcial;
- registro retroativo;
- processo concluído.

Desktop e mobile são superfícies independentes de validação. A densidade do fixture deve representar o conteúdo real.

## 9. Regra de continuidade

Antes de iniciar Dashboard + Curso + Material, esta camada deve estar funcionalmente estável, administrável e visualmente auditada. Caso um cenário da matriz exponha um erro estrutural, ele deve ser corrigido aqui antes de avançar.
