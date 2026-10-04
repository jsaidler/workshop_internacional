# Área do aluno — ciclo pedagógico Resultado → Avaliação → Retorno — 04/10/2026

## Objetivo

Esta tranche fecha o percurso pedagógico que começa na prática e termina em nova ação do aluno. **Resultado não é apenas o último formulário do Caderno:** é o contexto canônico em que a experimentação pode ser enviada para avaliação, receber retorno do professor, ser revisada e permanecer como histórico de pesquisa.

A interface não cria uma caixa de entrada paralela para avaliação. Fotografia, exposição, processamento, resultado, estado da avaliação e conversa permanecem ligados ao mesmo registro.

## Autoridades de domínio

O registro em `student_tests` continua sendo a autoridade da experimentação e do estado de avaliação. A sequência de estados permanece:

`draft → submitted → needs_revision ↔ submitted → reviewed`

Mensagens não substituem esse estado. `student_test_messages` continua sendo a conversa privada vinculada ao resultado.

A tranche acrescenta somente duas marcas de continuidade pedagógica ao registro:

- `feedback_at` — instante do retorno mais recente gerado pelo professor;
- `feedback_seen_at` — instante desse retorno que o aluno já abriu no Resultado ou reconheceu ao responder/reenviar.

Essas marcas não alteram a fotografia, o processamento ou o julgamento da avaliação. Elas existem para responder uma pergunta de produto: **há um retorno do professor que deve voltar à frente da experiência do aluno?**

## O que constitui um retorno

Um novo retorno é criado quando ocorre uma destas ações administrativas já canônicas:

1. o professor envia uma mensagem na conversa da avaliação;
2. o estado muda para `needs_revision`;
3. o estado muda para `reviewed`.

A implementação usa gatilhos de banco para que qualquer caminho administrativo que já escreva em `student_test_messages` ou atualize o estado produza o mesmo evento. A administração não ganha uma segunda implementação de avaliação.

## Regra de leitura e pendência

Abrir o Resultado registra que o retorno corrente foi visto. Responder na conversa ou reenviar uma revisão também reconhece o retorno corrente.

Isso não significa que toda pendência desaparece ao ser lida:

- uma avaliação `reviewed` deixa de ser ação pendente depois de aberta;
- uma mensagem nova em registro `submitted` deixa de ser novidade depois de aberta;
- **`needs_revision` continua sendo ação pendente mesmo depois da leitura**, porque o trabalho necessário só termina quando o aluno reenvia o registro para avaliação.

Assim, “visto” e “resolvido” não são tratados como sinônimos.

## Início

O Início continua representando a **próxima ação real**, mas passa a considerar o ciclo pedagógico.

Ordem de prioridade:

1. retorno do professor que exige atenção;
2. trabalho ainda não concluído no Caderno;
3. material de estudo disponível para uma única matrícula;
4. escolha de curso quando há múltiplas matrículas;
5. estados de espera ou Caderno pessoal já definidos nas tranches anteriores.

Isso evita que uma resposta pedagógica fique escondida atrás de um registro recente sem relação com ela.

## Curso

Curso continua sendo contexto acadêmico, não dashboard de métricas. A seção **Acompanhamento** só aparece quando há algo concreto a tratar na turma:

- retorno de avaliação pendente;
- dúvida própria ainda aberta.

Sem pendência, a seção não existe. Material e liberações continuam ocupando o papel principal da página.

## Resultado e avaliação

O bloco de avaliação fica subordinado ao Resultado e muda conforme o estado real:

- `draft` — oferece **Enviar para avaliação**;
- `submitted` — informa que o registro aguarda avaliação, mantendo a conversa disponível;
- `needs_revision` — mostra o retorno, preserva o mesmo registro e oferece **Enviar revisão para avaliação**;
- `reviewed` — apresenta a avaliação como concluída e preserva a conversa para consulta posterior.

Uma revisão não cria uma segunda fotografia nem um segundo thread. O aluno corrige o próprio registro quando a correção for pertinente e o reenvia.

## Dúvidas e conversas

Existem dois domínios de comunicação e eles não devem ser fundidos:

### Dúvidas gerais, de material ou de prática

Continuam em `student_questions`.

Podem preservar:

- turma;
- anotação/trecho do material;
- registro do Caderno relacionado;
- visibilidade privada ou para a turma.

### Avaliação de um resultado

Continua em `student_test_messages` e pertence ao Resultado avaliado.

A página **Dúvidas e respostas** pode listar essas conversas como acesso contextual, mas abrir uma avaliação sempre retorna ao Resultado. Não copiar mensagens de avaliação para `student_questions` e não manter dois threads para a mesma avaliação.

## Administração

A tranche não reabre a arquitetura administrativa. `admin/tests.php` e as funções canônicas de mensagem/estado continuam sendo o lado do professor.

As novas marcas de retorno são derivadas dessas ações existentes. Qualquer refinamento administrativo nesta tranche deve se limitar a tornar visível uma informação necessária ao ciclo, sem criar nova coleção, nova navegação ou novo fluxo concorrente.

## Migração e compatibilidade

Registros antigos já em `needs_revision` ou `reviewed` recebem `feedback_at` a partir de `reviewed_at` ou, na ausência dele, `updated_at`. Isso faz o retorno histórico aparecer uma vez para o aluno e permite que ele seja reconhecido normalmente.

A ausência das novas colunas antes da migração não deve quebrar leitura: o serviço de feedback detecta disponibilidade do esquema e retorna estado vazio quando necessário.

## Invariantes

1. Resultado é o contexto canônico da avaliação.
2. O professor não cria uma segunda entidade para comentar uma experimentação.
3. Ler retorno não resolve uma revisão solicitada.
4. Responder ou reenviar não apaga o histórico da conversa.
5. Mensagem e estado de avaliação são dimensões distintas.
6. O Início prioriza ação pedagógica real, não contagem ou gamificação.
7. Curso só mostra acompanhamento quando existe algo concreto.
8. Dúvidas de material/prática e conversa de avaliação permanecem domínios distintos, unidos por navegação contextual.
9. Registros pessoais fora de curso não entram no circuito de avaliação.
10. Nenhuma mudança nesta tranche altera as regras físicas ou químicas do Caderno e do Modo laboratório.

## Gate de entrega

A entrega segue o gate canônico da Área do Aluno:

**modelagem → implementação → regressão funcional → renderização integral da área do aluno em desktop e telefone → inspeção visual crítica → correções → reinspeção → CI completo → merge → confirmação do pacote de produção.**

A auditoria visual desta tranche deve acrescentar estados representativos de retorno pedagógico sem retirar nenhuma superfície já obrigatória do inventário anterior.
