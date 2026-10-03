# Processamento: estado, reversibilidade e controle administrativo

Data: 2026-10-02

Este documento é canônico para a área de processamento do aluno e para o controle administrativo dos processos globais.

## Princípios de produto

1. Associar ou escolher um roteiro é uma intenção. Não inicia laboratório, não avança a revelação e não cria fatos.
2. A sequência do roteiro representa a sequência física/química do processo; a navegação do aplicativo é livre e não reordena essa sequência.
3. Abrir uma etapa, usar um timer ou deixar de usar o aplicativo são ações de interface. A posição real do processo muda somente por declaração explícita do aluno.
4. Quando o aluno declara que chegou a uma etapa posterior, as etapas anteriores necessárias àquela posição são consideradas realizadas com os valores atuais do snapshot daquela fotografia.
5. Escolhas são reversíveis; fatos físicos já consolidados são preservados; somente uma ação explicitamente destrutiva pode apagar dados.
6. Trocar de roteiro nunca deve obrigar o aluno a recriar dados compatíveis do registro, como exposição, data, imagens e anotações, nem transformar estado de timer em fato “interrompido”.
7. Um roteiro global é referência versionada. Depois de associado a um registro, o plano daquele registro é um snapshot independente. Uma publicação administrativa posterior não altera silenciosamente snapshots já usados.
8. O estado auxiliar de timer pertence ao servidor. A página, aba ou aparelho é apenas uma interface para esse timer; seu estado não é o estado factual da revelação.
9. Consumo de inventário acompanha fatos de uso, não escolhas de roteiro ou ações de timer.
10. A interface não expõe a máquina de estados interna como obrigação cognitiva do usuário.
11. Nenhum conhecimento operacional editável deve permanecer oculto em PHP. Código conserva apenas invariantes estruturais e validações de segurança.

## Entidades conceituais

- **Definição global de processo**: identidade administrável de um processo oferecido aos alunos.
- **Versão publicada**: snapshot imutável de nome, descrição e etapas. Publicar uma alteração gera uma nova versão.
- **Rascunho administrativo**: versão editável antes da publicação.
- **Modelo pessoal do aluno**: roteiro independente administrado pelo aluno.
- **Plano do registro**: snapshot completo do roteiro escolhido para uma fotografia, incluindo os valores herdados de cada etapa.
- **Posição declarada**: etapa real que o aluno informa ter alcançado no procedimento.
- **Sessão de timer**: estado recuperável e auxiliar da contagem de tempo da etapa corrente, com pausa e retomada.
- **Etapa factual**: etapa consolidada como fisicamente realizada, por progressão declarada ou registro retroativo.
- **Correção**: emenda explícita a um fato ou parâmetro do snapshot, com rastreabilidade quando aplicável.
- **Movimento de inventário**: consequência de um fato de consumo, não de uma intenção.

## Matriz mínima de uso real

### Mudança de ideia antes de fatos consolidados
- `já revelei` → `vou usar o laboratório`;
- `vou usar o laboratório` → `já revelei`;
- trocar padrão global por modelo pessoal;
- trocar modelo pessoal por padrão global;
- escolher o roteiro errado;
- abrir o registro errado.

Resultado esperado: nenhuma escolha nem estado de timer cria etapas concluídas. A troca é direta e preserva dados comuns.

### Uso durante a revelação
- consultar qualquer etapa do roteiro sem mudar a posição;
- iniciar, pausar, reiniciar ou abandonar um timer sem criar fato;
- continuar a revelação sem o aplicativo e depois declarar uma etapa posterior;
- alterar duração, temperatura ou agitação no snapshot daquela fotografia;
- ultrapassar o tempo do cronômetro sem avanço automático;
- usar agitação periódica com duração e intervalo entre inícios;
- usar agitação contínua;
- iniciar sem o sistema e abrir o sistema no meio do processo.

Resultado esperado: a sequência química continua sendo a do processo. Se o aluno declara que chegou a uma etapa posterior, as anteriores são consolidadas conforme o snapshot vigente; não é necessário reproduzir no aplicativo cada ação física que já aconteceu.

### Exceções reais do procedimento
- tempo, temperatura ou agitação efetivos diferentes dos valores inicialmente herdados;
- inserir ou repetir etapa quando isso efetivamente aconteceu;
- trocar químico ou banho no meio do processo;
- reutilização planejada que vira banho novo;
- banho novo planejado que vira reutilização;
- uma etapa excepcionalmente não integrar aquela realização concreta.

Resultado esperado: exceções são registradas explicitamente. Uma etapa ausente não é inferida porque o usuário navegou adiante; quando necessário, ela é removida explicitamente do roteiro daquela fotografia. Não existe `skipped` automático.

### Correções de erro
- registrar volume/tempo/temperatura incorretos;
- corrigir posteriormente uma etapa já registrada;
- trocar o restante do roteiro após algumas etapas factuais;
- corrigir o snapshot antes de a etapa ser consolidada.

Resultado esperado: a correção é explícita, preserva fatos compatíveis e reconcilia inventário quando necessário. Corrigir uma etapa não apaga automaticamente as posteriores.

### Falhas de interface e infraestrutura
- reload;
- botão voltar;
- fechamento da aba;
- navegador encerrado;
- telefone desligado;
- tela bloqueada;
- queda de rede;
- POST repetido;
- duas abas simultâneas;
- telefone e computador simultâneos;
- retorno horas ou dias depois.

Resultado esperado: timer recuperável pelo servidor, operações idempotentes e nenhuma fabricação de fatos. Uma falha ou abandono da interface jamais significa “etapa interrompida”.

### Mistura de registro retroativo e uso ao vivo
- primeiras etapas registradas depois e restante acompanhado pelo aplicativo;
- usar o aplicativo nas primeiras etapas, continuar sem ele e depois informar a posição alcançada;
- horários aproximados no trecho retroativo;
- parâmetros corrigidos depois de uma etapa já consolidada.

Resultado esperado: a forma de registro não falseia o procedimento físico. Proveniência pode existir por etapa, mas o sistema não exige presença contínua do aplicativo.

### Inventário
- química prevista diferente da usada;
- estoque insuficiente depois da escolha do roteiro;
- quantidade real diferente da planejada;
- reutilização muda consumo;
- correção de etapa já consumida.

Resultado esperado: nenhum consumo ao associar roteiro ou iniciar timer. Consumo nasce do fato e correções usam compensação/reconciliação, evitando baixa duplicada.

### Administração e ciclo de vida global
- administrador corrige um processo já usado;
- nova versão publicada enquanto um aluno possui snapshot anterior;
- arquivar processo antigo;
- corrigir apenas nome/descrição;
- descobrir erro de segurança em uma receita;
- desabilitar para novos usos sem apagar histórico;
- duplicar processo para criar variante;
- alterar etapas, ordem, tempos, temperaturas, agitação e instruções;
- alterar catálogos de reveladores e etapas disponíveis.

Resultado esperado: versões publicadas são imutáveis; histórico permanece legível; nova publicação vale para novas associações; snapshots existentes nunca mudam automaticamente.

### Revisão e encerramento
- aluno corrige antes de enviar;
- revisor pede revisão;
- registro revisado precisa de correção factual excepcional;
- duplicar/arquivar registro.

Resultado esperado: bloqueios são explícitos e existe fluxo administrativo/revisor para reabrir quando necessário, sem edição silenciosa do histórico.

## Política de versionamento de processos globais

- Um processo pode estar ativo ou arquivado.
- Cada processo possui zero ou mais versões.
- Rascunhos são editáveis.
- Publicar congela a versão e a torna a versão ativa para novas escolhas.
- Editar um processo publicado cria ou edita um novo rascunho; nunca modifica a versão publicada.
- Planos do aluno copiam as etapas da versão escolhida e guardam a referência da versão de origem.
- Arquivar impede novas associações, mas não remove histórico.
- Migrar para uma versão nova exige ação explícita; nunca é automático.
- Alterar a versão global não modifica snapshots de fotografias existentes, estejam eles apenas associados ou já em uso.

## Controle administrativo obrigatório

O administrador precisa de uma superfície própria para:

- listar, buscar, criar, duplicar e arquivar processos globais;
- editar rascunhos;
- adicionar, remover e reordenar etapas;
- editar revelador/químico, volume, água, diluição, temperatura, tempo, modo de agitação, duração de cada agitação, intervalo, notas e reutilização;
- visualizar exatamente o roteiro como o aluno verá antes de publicar;
- publicar uma nova versão;
- consultar histórico de versões e dependências;
- gerenciar catálogos operacionais editáveis, como reveladores e tipos de etapa;
- saber quem alterou/publicou e quando.

## Hardcoding: regra de autoridade

Permanece em código somente o que for estrutural, como tipos de estado válidos, regras de permissão, validação e segurança. Nomes de processos, receitas, etapas oferecidas, reveladores, químicos, tempos, temperaturas, agitação, descrições e demais conhecimento de laboratório que o administrador possa legitimamente alterar devem ser dados persistentes administráveis.

## Gate de entrega

Nenhuma correção desta área é considerada pronta apenas por CI verde.

1. modelar estados e transições reais;
2. implementar persistência e regras;
3. implementar UX do aluno e do administrador;
4. renderizar todas as telas afetadas em desktop e mobile;
5. inspecionar visualmente as telas e os roteiros com densidade real;
6. corrigir problemas encontrados;
7. rerenderizar as telas afetadas;
8. executar testes de cenário, integração e regressão;
9. mergear somente depois do gate visual e funcional;
10. confirmar o pacote/deploy de produção.
