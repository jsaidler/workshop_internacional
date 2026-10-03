# Processamento: estado, reversibilidade e controle administrativo

Data: 2026-10-02

Este documento é canônico para a área de processamento do aluno e para o controle administrativo dos processos globais.

## Princípios de produto

1. Associar ou escolher um roteiro é uma intenção. Não inicia laboratório e não cria fatos de execução.
2. Iniciar uma etapa, concluir uma etapa, registrar uma etapa já realizada e corrigir um fato são ações distintas.
3. Escolhas são reversíveis; fatos físicos já registrados são preservados; somente uma ação explicitamente destrutiva pode apagar dados.
4. Trocar de rota nunca deve obrigar o aluno a recriar dados compatíveis do registro, como exposição, data, imagens e anotações.
5. Um roteiro global é referência versionada. Depois de associado a um registro, o plano daquele registro é um snapshot independente. Uma publicação administrativa posterior não altera silenciosamente planos já usados.
6. O estado de uma execução em laboratório pertence ao servidor. A página, aba ou aparelho é apenas uma interface para esse estado.
7. Consumo de inventário acompanha fatos de uso, não escolhas de roteiro.
8. A interface não expõe a máquina de estados interna como obrigação cognitiva do usuário.
9. Nenhum conhecimento operacional editável deve permanecer oculto em PHP. Código conserva apenas invariantes estruturais e validações de segurança.

## Entidades conceituais

- **Definição global de processo**: identidade administrável de um processo oferecido aos alunos.
- **Versão publicada**: snapshot imutável de nome, descrição e etapas. Publicar uma alteração gera uma nova versão.
- **Rascunho administrativo**: versão editável antes da publicação.
- **Modelo pessoal do aluno**: cópia independente administrada pelo aluno.
- **Plano do registro**: roteiro escolhido para uma fotografia/registro. Pode ser trocado enquanto não houver conflito com fatos já ocorridos.
- **Sessão de execução**: estado recuperável do laboratório, incluindo etapa atual, cronômetro, pausa e retomada.
- **Etapa factual**: evento que o aluno afirma ter realizado fisicamente.
- **Correção**: emenda explícita a um fato, com rastreabilidade.
- **Movimento de inventário**: consequência de um fato de consumo, não de uma intenção.

## Matriz mínima de uso real

### Mudança de ideia antes da execução
- `já revelei` → `vou revelar agora`;
- `vou revelar agora` → `já revelei`;
- trocar padrão global por modelo pessoal;
- trocar modelo pessoal por padrão global;
- trocar por registro manual;
- escolher o roteiro errado;
- abrir o registro errado.

Resultado esperado: nenhuma escolha cria etapas concluídas. A troca é direta e preserva dados comuns.

### Desvios no laboratório
- tempo, temperatura ou agitação reais diferentes do plano;
- pular, repetir ou inserir etapa;
- trocar químico ou banho no meio do processo;
- reutilização planejada que vira banho novo;
- banho novo planejado que vira reutilização;
- ultrapassar o tempo do cronômetro;
- pausar, abandonar ou retomar horas depois;
- iniciar sem o sistema e abrir o sistema no meio do processo.

Resultado esperado: o plano permanece referência e o registro factual descreve o que realmente aconteceu.

### Correções de erro
- concluir etapa sem querer;
- iniciar etapa errada;
- registrar volume/tempo/temperatura incorretos;
- corrigir posteriormente uma etapa já registrada;
- trocar o restante do roteiro após algumas etapas factuais.

Resultado esperado: a correção é explícita, preserva rastreabilidade e reconcilia inventário quando necessário.

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

Resultado esperado: execução recuperável pelo servidor, operações idempotentes e sem duplicação de fatos.

### Mistura de registro retroativo e execução ao vivo
- primeiras etapas registradas depois e restante acompanhado ao vivo;
- primeiras etapas acompanhadas ao vivo e restante registrado depois;
- horários aproximados no trecho retroativo;
- etapa longa atravessando interrupção/armazenamento.

Resultado esperado: proveniência por etapa. Um único `modo` não pode falsear todo o registro.

### Inventário
- química planejada diferente da usada;
- estoque insuficiente depois da escolha do roteiro;
- quantidade real diferente da planejada;
- reutilização muda consumo;
- correção de etapa já consumida.

Resultado esperado: nenhum consumo ao associar roteiro. Consumo nasce do fato e correções usam compensação/reconciliação, evitando baixa duplicada.

### Administração e ciclo de vida global
- administrador corrige um processo já usado;
- nova versão publicada enquanto há execução ativa;
- arquivar processo antigo;
- corrigir apenas nome/descrição;
- descobrir erro de segurança em uma receita;
- desabilitar para novos usos sem apagar histórico;
- duplicar processo para criar variante;
- alterar etapas, ordem, tempos, temperaturas, agitação e instruções;
- alterar catálogos de reveladores e etapas disponíveis.

Resultado esperado: versões publicadas são imutáveis; histórico permanece legível; nova publicação vale para novas associações; planos iniciados nunca mudam automaticamente.

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
- Migração para uma versão nova só pode ser oferecida a planos ainda não iniciados e exige ação explícita do aluno ou administrador; nunca é automática.

## Controle administrativo obrigatório

O administrador precisa de uma superfície própria para:

- listar, buscar, criar, duplicar e arquivar processos globais;
- editar rascunhos;
- adicionar, remover e reordenar etapas;
- editar revelador/químico, volume, água, diluição, temperatura, tempo, agitação, notas e reutilização;
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