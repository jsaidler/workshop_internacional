# Gerenciador de processamentos e modo laboratório — 01/10/2026

## Decisão canônica

O temporizador isolado deixa de ser o modelo de trabalho para processamento químico. Processamento passa a ser a entidade central: uma sequência reutilizável de etapas que pode ser usada tanto pelo Caderno quanto pelo modo laboratório.

A arquitetura distingue três objetos com responsabilidades diferentes:

1. **Processamento salvo (template)** — roteiro reutilizável editado pelo aluno.
2. **Plano do registro (snapshot)** — cópia imutável do roteiro escolhida para uma fotografia do Caderno.
3. **Etapa executada** — registro do que efetivamente aconteceu no laboratório, persistido em `student_process_steps` somente quando o aluno conclui a etapa.

Essa separação impede que uma alteração futura no processamento salvo reescreva registros históricos do Caderno e impede que etapas apenas planejadas sejam tratadas como já executadas.

## Gerenciador

A superfície `/aluno/processamentos.php` é a autoridade operacional dos roteiros do aluno e funciona como **biblioteca pessoal**, não como formulário principal.

A hierarquia é:

1. processamentos que o aluno já salvou;
2. padrões atuais do workshop como pontos de partida;
3. criação do zero como alternativa secundária.

Cada processamento possui:

- nome e descrição;
- sequência ordenada de etapas;
- tipo de etapa conforme o catálogo já existente do Caderno;
- tempo da etapa;
- intervalo de aviso de agitação;
- temperatura, agitação e observações quando aplicáveis;
- dados de revelador quando a etapa é uma revelação;
- possibilidade de usar uma predefinição de revelação como origem dos valores, que são copiados para o roteiro;
- relação explícita de reutilização quando uma etapa usa o mesmo banho de uma etapa anterior.

O aluno pode criar, editar, duplicar e excluir processamentos. Dentro do roteiro, as etapas podem ser adicionadas, **editadas em lugar**, removidas e reordenadas. O processamento salvo pode ser iniciado diretamente no modo laboratório ou aplicado a um registro do Caderno.

Ações destrutivas são visíveis e separadas das ações de fluxo normal. Excluir um template nunca apaga snapshots já aplicados a registros do Caderno.

## Padrões do workshop

O gerenciador oferece processamentos padrão como pontos de partida canônicos do workshop. Eles não são objetos compartilhados mutáveis: ao escolher um padrão, o sistema cria uma cópia pertencente ao aluno. A partir daí ela é um processamento salvo normal, que pode ser editado sem alterar o padrão de origem.

Os padrões atuais combinam as duas condições de primeira revelação trabalhadas no workshop com as duas rotas de branqueamento:

- **Positivo direto — Parodinal EI 200 — FeCl₃ + amônia**;
- **Positivo direto — Parodinal EI 400 — FeCl₃ + amônia**;
- **Positivo direto — Parodinal EI 200 — peracética**;
- **Positivo direto — Parodinal EI 400 — peracética**.

Contrato das revelações nesses quatro padrões:

- EI 200: **10 ml de Parodinal + água até 550 ml, 26 °C, 7 min, agitação leve**;
- EI 400: **20 ml de Parodinal + água até 550 ml, 26 °C, 7 min, agitação leve**;
- **a segunda revelação repete exatamente os parâmetros da primeira revelação** do mesmo padrão;
- **o mesmo banho de revelador preparado para a primeira revelação é reaproveitado na segunda**. Não se prepara uma segunda solução e esse reaproveitamento não deve ser contabilizado como novo consumo de revelador.

A reutilização é parte do domínio, não apenas texto explicativo. O payload da segunda revelação usa `reuse_source_stage_key = first_development`. Esse vínculo deve sobreviver à cópia do padrão, ao snapshot do Caderno e ao registro da etapa executada. Quando a etapa possui essa relação de reutilização, ela não pode gerar nova baixa de inventário.

Contrato das demais etapas:

- todas as lavagens: **1 min**;
- banho de branqueamento: **1 min 30 s**;
- banho de amônia: sem tempo padrão fixo;
- secagem: sem tempo padrão fixo.

Os padrões atuais não oferecem seleção de revelador porque essas quatro condições são especificamente de Parodinal. Brewed Caffenol e outras condições históricas permanecem disponíveis para processamentos montados pelo aluno, mas não são apresentados como padrão vigente do workshop.

Quando o padrão é escolhido a partir de um registro do Caderno, primeiro é criada a cópia do aluno e em seguida o sistema aplica um snapshot dessa cópia ao registro. Alterações posteriores no padrão canônico ou no processamento salvo não reescrevem o plano já associado à fotografia.

## Integração com o Caderno

A tela de processamento do Caderno mantém o modo livre existente, mas ele passa a ser alternativa ad hoc. O caminho canônico é selecionar um Processamento e executá-lo no Modo laboratório.

Ao escolher um processamento salvo:

1. o sistema verifica que o registro ainda não possui etapas executadas;
2. cria um `student_process_plan` associado ao registro;
3. copia todas as etapas do template para `student_process_plan_steps`;
4. abre o executor em `/aluno/processar.php?test=<id>`.

O plano é um snapshot. Alterar, duplicar ou excluir o template depois disso não altera o plano associado ao registro.

Uma etapa do plano só entra em `student_process_steps` quando o usuário pressiona **Concluir etapa**. Para isso o executor reutiliza `student_process_add_flexible_step()`, preservando a autoridade já existente para gravação do processo real e sincronização dos dados legados do Caderno.

## Modo laboratório

`/aluno/processar.php` executa uma sequência etapa por etapa.

A tela prioriza operação de bancada:

- etapa atual em destaque;
- solução ou banho relevante;
- instrução operacional curta quando necessária;
- cronômetro grande;
- aviso de agitação;
- próxima etapa visível antes da transição;
- progresso da sequência inteira;
- início, pausa e reinício explícitos;
- conclusão explícita da etapa.

Quando a segunda revelação reutiliza o revelador, o runner mostra explicitamente **“Reutilize o banho da primeira revelação”** e informa que não se prepara outro banho nem se registra novo consumo.

**A próxima etapa nunca começa automaticamente.** O fim de um tempo representa apenas o fim da contagem. Entre duas etapas existe uma operação física — esvaziar uma solução, iniciar uma lavagem, colocar outro banho etc. — e o software não pode registrar ou temporizar essa transição como se já tivesse acontecido.

## Contrato de temporização

O relógio não é calculado por `remaining--` a cada `setInterval`.

Ao iniciar uma etapa, o executor registra conceitualmente:

```text
endAt = Date.now() + remainingSeconds
remaining = endAt - Date.now()
```

`setInterval` serve apenas para atualizar a interface. Portanto atrasos de scheduling, throttling do navegador ou um curto período em segundo plano não acumulam erro na contagem.

O estado da etapa em execução é salvo em `sessionStorage`, incluindo `endAt`, tempo restante e estado de conclusão. Recarregar a mesma etapa durante uma execução não deve reiniciar silenciosamente a contagem.

## Tela ativa

Ao iniciar o processamento, o executor solicita `navigator.wakeLock.request('screen')` quando a Screen Wake Lock API estiver disponível.

Contrato:

- enquanto o modo laboratório estiver ativo, a interface tenta manter a tela ligada;
- ao voltar para uma aba visível, o wake lock é solicitado novamente quando necessário;
- a interface informa quando a tela está sendo mantida ativa;
- navegadores sem suporte ou recusas da API são apresentados como limitação real, sem indicar falsamente que a proteção está ativa;
- pausar deliberadamente a etapa libera o wake lock;
- o fim do cronômetro não inicia automaticamente a etapa seguinte.

## Inspeção visual

Processamentos e Modo laboratório fazem parte do gate visual obrigatório da área do aluno. A suíte `student-visual-audit` deve gerar estados próprios de:

- biblioteca de processamentos;
- editor de roteiro com edição de etapa;
- runner em etapa temporizada com instrução de reutilização;
- desktop e mobile.

O artefato automatizado serve para disponibilizar as renderizações. A aprovação exige inspeção humana das imagens e correção de problemas antes de merge.

## Dados

A migração `079_student_process_templates_and_runner.php` adiciona:

- `student_process_templates`;
- `student_process_template_steps`;
- `student_process_plans`;
- `student_process_plan_steps`.

As tabelas existentes `student_process_steps`, `student_saved_preparations`, inventário e catálogo de etapas continuam sendo autoridades dos seus respectivos domínios. A migração é aditiva e não reescreve registros laboratoriais existentes.

## Relação com a ferramenta anterior

A entrada `lab_timer` permanece como chave técnica de permissão por compatibilidade, mas sua apresentação passa a ser **Processamentos**. O temporizador simples deixa de ser oferecido como ferramenta principal em `Ferramentas` e no toolbox.

O JavaScript legado do timer pode continuar existindo temporariamente enquanto houver consumidores antigos, mas a nova operação canônica é o gerenciador + executor de processamento.

## Limites atuais

- etapas futuras de um plano já iniciado ainda não são editadas dentro do runner;
- padrões complexos de agitação (agitação inicial + duração de cada ciclo) ainda não fazem parte do schema; o contrato atual usa intervalo de aviso;
- execução avulsa de um template usa o runner sem gerar registro no Caderno;
- templates ainda não vinculam automaticamente um item de inventário a cada banho; o registro real continua sendo a autoridade para consumo;
- a integração futura de inventário deve respeitar `reuse_source_stage_key` para impedir dupla baixa do mesmo banho.

Esses limites não alteram a arquitetura. Evoluções devem preservar a separação **template → snapshot do registro → execução real**.
