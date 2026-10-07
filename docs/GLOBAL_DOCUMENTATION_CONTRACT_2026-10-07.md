# Regra global — documentação é parte da implementação

Data: 2026-10-07  
Status: **regra operacional global, sem exceção.**

Esta regra não pertence apenas à Área do aluno nem apenas a este repositório. Ela define o modo de trabalho para **qualquer projeto, repositório, documento, sistema, artefato ou fluxo** em que exista documentação prévia relevante.

## 1. Princípio

**Documentação que não é consultada antes do trabalho não cumpre sua função.**

Se existe documentação aplicável, ela é entrada obrigatória da tarefa. Não é material opcional, referência posterior nem registro para ser atualizado depois que decisões já foram tomadas.

Nenhuma alteração deve começar por código, layout, texto, banco, automação, configuração ou artefato antes de identificar e consultar a documentação relevante.

## 2. Preflight obrigatório para qualquer tarefa

Antes de modificar qualquer coisa:

1. descobrir quais documentos podem reger o escopo;
2. identificar quais são canônicos, quais são históricos e quais foram supersedidos;
3. ler integralmente os documentos canônicos aplicáveis;
4. conferir estado atual, decisões anteriores, restrições, fluxos, nomenclatura, arquitetura e critérios de validação;
5. resolver qualquer conflito entre documentação e pedido atual antes de implementar;
6. só então desenhar ou alterar a solução.

Este preflight vale inclusive para:

- correção aparentemente pequena;
- hotfix;
- mudança de uma linha;
- ajuste visual;
- refatoração interna;
- alteração de copy;
- teste;
- fixture;
- migration;
- deploy;
- documentação;
- tarefa urgente.

Urgência não suspende contexto.

## 3. Descoberta da documentação

A busca deve considerar, conforme o projeto:

- `PROJECT_STATE.md` ou equivalente;
- `AGENTS.md`;
- README;
- handoffs;
- ADRs;
- documentos canônicos de produto/UX/arquitetura;
- contratos de dados/API;
- documentação de deploy/operação;
- inventários visuais;
- decisões de segurança;
- documentos de pesquisa e conteúdo quando forem fonte do produto;
- arquivos citados por outros documentos como autoridade.

Não basta abrir o documento cujo nome parece mais próximo da tarefa. É obrigatório seguir referências e relações de precedência que possam alterar a decisão.

## 4. Precedência e conflito

Documentos devem declarar quando substituem regras anteriores.

Quando houver conflito:

- não escolher silenciosamente a regra mais conveniente;
- identificar a autoridade posterior/canônica;
- se a intenção atual do usuário muda o contrato, atualizar o contrato como parte da mesma mudança;
- não manter teste, fixture ou implementação antiga apenas para satisfazer documentação obsoleta;
- não alterar documentação histórica como se nunca tivesse existido: marcar sua precedência/supersessão quando necessário.

Uma instrução explícita atual do usuário pode mudar uma decisão anterior. Nesse caso, a nova decisão deve ser incorporada à documentação canônica antes ou junto da implementação.

## 5. Documentação antes e durante, não como justificativa posterior

É proibido usar documentação apenas para descrever depois o que já foi feito.

Quando a tarefa muda comportamento, arquitetura, UX, domínio ou operação:

1. o contrato existente é consultado;
2. a decisão nova é confrontada com ele;
3. o contrato é atualizado no mesmo conjunto de trabalho;
4. a implementação segue o contrato atualizado;
5. testes e fixtures passam a proteger a mesma regra.

Documentar depois sem ter consultado antes não é conformidade.

## 6. Escopo global

Esta regra vale para qualquer projeto e qualquer domínio.

Não é necessário haver um arquivo com este mesmo nome em todo repositório para a obrigação existir. A existência de documentação relevante em qualquer forma já aciona o preflight.

Quando um projeto possuir instruções próprias de documentação, elas se somam a esta regra. Não a substituem por uma prática menos rigorosa.

## 7. Ausência de documentação

Se não existir documentação aplicável:

- isso deve ser constatado antes da implementação;
- não se inventa uma autoridade inexistente;
- a tarefa pode prosseguir com base no pedido e no estado real do projeto;
- se a decisão criada for estrutural ou recorrente, ela deve passar a ser documentada.

## 8. Regra de fechamento

Uma tarefa não está concluída se:

- contradiz documentação canônica;
- altera um contrato sem atualizar a documentação;
- deixa documentos canônicos mutuamente conflitantes;
- mantém teste/fixture exigindo comportamento já abandonado;
- registra uma decisão sem definir sua precedência;
- a implementação só “funciona” porque a documentação relevante foi ignorada.

## 9. Obrigação de consulta recorrente

Documentação não é lida uma única vez por projeto e depois presumida de memória.

Ela deve ser consultada novamente:

- ao iniciar nova tarefa;
- ao mudar de subsistema;
- ao retomar trabalho depois de pausa/handoff;
- antes de uma alteração estrutural;
- quando surgir comportamento inesperado;
- quando um teste antigo entrar em conflito com o produto;
- antes de afirmar que uma implementação está de acordo com o projeto.

**Memória não substitui documentação canônica.**
