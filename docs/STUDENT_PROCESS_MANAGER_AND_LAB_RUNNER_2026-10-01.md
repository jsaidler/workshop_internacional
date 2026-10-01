# Gerenciador de processamentos e modo laboratório — 01/10/2026

## Decisão canônica

O temporizador isolado deixa de ser o modelo de trabalho para processamento químico. Processamento passa a ser a entidade central: uma sequência reutilizável de etapas que pode ser usada tanto pelo Caderno quanto pelo modo laboratório.

A arquitetura distingue três objetos com responsabilidades diferentes:

1. **Processamento salvo (template)** — roteiro reutilizável editado pelo aluno.
2. **Plano do registro (snapshot)** — cópia imutável do roteiro escolhida para uma fotografia do Caderno.
3. **Etapa executada** — registro do que efetivamente aconteceu no laboratório, persistido em `student_process_steps` somente quando o aluno conclui a etapa.

Essa separação impede que uma alteração futura no processamento salvo reescreva registros históricos do Caderno e impede que etapas apenas planejadas sejam tratadas como já executadas.

## Gerenciador

A superfície `/aluno/processamentos.php` é a autoridade operacional dos roteiros do aluno.

Cada processamento possui:

- nome e descrição;
- sequência ordenada de etapas;
- tipo de etapa conforme o catálogo já existente do Caderno;
- tempo da etapa;
- intervalo de aviso de agitação;
- temperatura, agitação e observações quando aplicáveis;
- dados de revelador quando a etapa é uma revelação;
- possibilidade de usar uma predefinição de revelação como origem dos valores, que são copiados para o roteiro.

As etapas podem ser adicionadas, removidas e reordenadas. O processamento salvo pode ser iniciado diretamente no modo laboratório ou aplicado a um registro do Caderno.

## Padrões do workshop

O gerenciador oferece processamentos padrão como pontos de partida canônicos do workshop. Eles não são objetos compartilhados mutáveis: ao escolher um padrão, o sistema cria uma cópia pertencente ao aluno. A partir daí ela é um processamento salvo normal, que pode ser editado sem alterar o padrão de origem.

Os padrões iniciais representam as duas rotas de branqueamento trabalhadas na plataforma:

- **Positivo direto — FeCl₃ + amônia:** primeira revelação → lavagem → cloreto férrico → lavagem → banho de amônia → lavagem → segunda revelação → lavagem final → secagem.
- **Positivo direto — peracética:** primeira revelação → lavagem → solução peroxiacética → lavagem → segunda revelação → lavagem final → secagem.

Contrato de tempo desses padrões:

- todas as lavagens: **1 min**;
- banho de branqueamento: **1 min 30 s**;
- primeira e segunda revelações: sem tempo padrão fixo;
- banho de amônia: sem tempo padrão fixo;
- secagem: sem tempo padrão fixo.

Os tempos não fixados permanecem deliberadamente como etapas livres no executor: o padrão não deve inventar uma duração para operações cujo ponto final depende da imagem ou do procedimento adotado.

Ao copiar um padrão, o aluno escolhe **Parodinal** ou **Brewed Caffenol** como revelador das etapas de revelação. Quantidade, diluição, temperatura, agitação e duração continuam editáveis no processamento copiado.

Quando o padrão é escolhido a partir de um registro do Caderno, primeiro é criada a cópia do aluno e em seguida o sistema aplica um snapshot dessa cópia ao registro. Alterações posteriores no padrão canônico ou no processamento salvo não reescrevem o plano já associado à fotografia.

## Integração com o Caderno

A tela de processamento do Caderno mantém o modo livre existente. Ele continua adequado quando o aluno quer construir o processo conforme trabalha.

Como alternativa, a tela oferece **Modo laboratório**, que encaminha ao gerenciador no contexto do registro atual. Ao escolher um processamento salvo:

1. o sistema verifica que o registro ainda não possui etapas executadas;
2. cria um `student_process_plan` associado ao registro;
3. copia todas as etapas do template para `student_process_plan_steps`;
4. abre o executor em `/aluno/processar.php?test=<id>`.

O plano é um snapshot. Alterar ou excluir o template depois disso não altera o plano associado ao registro.

Uma etapa do plano só entra em `student_process_steps` quando o usuário pressiona **Concluir etapa**. Para isso o executor reutiliza `student_process_add_flexible_step()`, preservando a autoridade já existente para gravação do processo real e sincronização dos dados legados do Caderno.

## Modo laboratório

`/aluno/processar.php` executa uma sequência etapa por etapa.

A tela prioriza operação de bancada:

- etapa atual em destaque;
- cronômetro grande;
- aviso de agitação;
- próxima etapa visível antes da transição;
- progresso da sequência inteira;
- início, pausa e reinício explícitos;
- conclusão explícita da etapa.

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

## Limites da primeira implementação

Esta primeira implementação deliberadamente mantém o escopo controlado:

- etapas futuras de um plano já iniciado ainda não são editadas dentro do runner;
- templates permitem reordenação, remoção e adição; edição detalhada de uma etapa existente pode ser evoluída depois;
- padrões complexos de agitação (agitação inicial + duração de cada ciclo) ainda não fazem parte do schema; o primeiro contrato usa intervalo de aviso;
- execução avulsa de um template usa o runner sem gerar registro no Caderno;
- consumo de inventário não é vinculado automaticamente pelo template nesta primeira tranche; o registro real continua sendo a autoridade para consumo.

Esses limites não alteram a arquitetura. Evoluções devem preservar a separação **template → snapshot do registro → execução real**.
