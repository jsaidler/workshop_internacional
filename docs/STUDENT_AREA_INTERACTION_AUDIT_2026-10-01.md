# Área do aluno — auditoria de interação e mecânicas — 2026-10-01

Esta auditoria substitui a lógica de corrigir telas isoladamente. O critério não é “qual formulário existe nesta página”, mas **qual tarefa o aluno está executando e qual continuidade essa tarefa exige**.

## Regra de interação

Há três classes de ação:

1. **Edição local** — o aluno está trabalhando no mesmo objeto e espera permanecer no mesmo ponto. Não deve haver reload, retorno ao topo nem perda de contexto. Exemplos: anotações, respostas, compartilhamento, movimentação de inventário, edição de metadados, salvar uma alteração numa etapa.
2. **Transição deliberada de tarefa** — a ação termina um estágio e abre outro contexto que o aluno explicitamente escolheu. Navegação é correta. Exemplos: criar um novo registro e abri-lo; salvar Exposição e seguir para Processamento; abrir uma dúvida ou um registro compartilhado.
3. **Ação destrutiva ou estrutural** — deve ser explícita, confirmável e não pode ser confundida com edição normal. Exemplos: excluir registro, remover anotação, arquivar item, apagar uma etapa e todas as posteriores.

A tecnologia deve seguir essa semântica. Não converter todo POST em AJAX indiscriminadamente e não usar reload como mecanismo universal de sincronização.

## Material e anotações

### Problema
O fluxo foi construído como formulário POST de página. Isso faz uma ação de leitura/anotação se comportar como envio de formulário administrativo. Guardar scroll e restaurar depois é um remendo, não a solução.

### Contrato
- criar, editar, remover, reassociar e tornar geral são **edições locais**;
- nenhuma dessas ações recarrega a página;
- o texto permanece exatamente no ponto de leitura;
- o destaque e o painel são atualizados no DOM depois da confirmação do servidor;
- erro de gravação mantém texto e seleção disponíveis;
- “Virar dúvida” é uma transição deliberada para o ambiente de dúvidas e pode navegar;
- a dúvida mantém o vínculo com a anotação e a citação original.

## Caderno — lista de registros

### Criar registro
Transição deliberada: criar e abrir o novo registro é correto.

### Compartilhamento
É edição local. Alterar Privado/Turma/Curso não deve recarregar o Caderno nem fechar o menu. O estado visual deve mudar somente após confirmação do servidor.

### Duplicar
Transição deliberada: duplicar e abrir a cópia é coerente porque a próxima ação provável é trabalhar na cópia.

### Excluir
Ação destrutiva. Deve continuar separada das ações de edição e exigir confirmação clara.

## Registro — Exposição

### Salvar e seguir para Processamento
Transição deliberada: navegar para Processamento faz sentido porque o próprio CTA declara a mudança de etapa.

### Anexar/remover imagem da cena
Edição local. Não deve submeter o formulário inteiro nem recarregar a tela. Upload e remoção devem preservar os campos ainda não salvos e o ponto de trabalho.

## Registro — Processamento

### Adicionar etapa
É uma mutação do mesmo registro, não uma troca de página. O log, a próxima decisão e o formulário devem atualizar sem reload.

### Editar etapa
Edição local. Salvar volta ao log no mesmo ponto e atualiza a etapa sem recriar a página inteira.

### Remover etapa e posteriores
Ação destrutiva estrutural. Deve permanecer explicitamente separada da edição comum.

### Cronômetro
Estado efêmero local. Nunca deve depender de submit/reload. Navegação acidental com cronômetro ativo deve produzir aviso quando puder fazer o aluno perder uma medição.

## Registro — Resultado

### Texto e imagens de resultado
Edição local. Salvar texto, anexar e remover imagem não devem reposicionar o aluno nem reconstruir a tela.

### Enviar para avaliação
Transição de estado deliberada. Pode atualizar a própria tela sem navegação; o requisito é deixar inequívoco que o registro entrou em outro estado e ficou sujeito às regras de revisão.

### Mensagens com professor
Edição local. Enviar mensagem deve inserir a mensagem na conversa sem reload.

## Dúvidas

### Criar dúvida
Transição deliberada: após publicar, abrir a própria discussão é coerente.

### Responder / resolver
Edição local. Responder deve acrescentar a mensagem à conversa e resolver deve atualizar o estado sem recarregar a página.

### Dúvida originada de anotação
A citação não deve ser copiada apenas como texto solto. Deve existir vínculo persistente com a anotação de origem para que o contexto possa ser reencontrado mesmo quando o material mudar.

## Inventário

### Novo item
O aluno permanece no Inventário. Criar item é edição local: inserir o novo item na lista e manter a tela no contexto atual.

### Entrada / saída
Edição local. Registrar movimento deve atualizar saldo e histórico imediatamente, sem fechar o item nem voltar ao topo.

### Editar metadados
Edição local. Não deve exigir uma página separada se o item já está aberto no Inventário; edição pode ocorrer em painel/dialog contextual.

### Arquivar
Ação estrutural. Deve pedir confirmação e, depois de confirmada, remover o item ativo da lista sem reload.

## Predefinições de revelação

Criar e editar são tarefas locais do mesmo catálogo. O modelo é lista + editor contextual (dialog/painel), preservando a posição. O parâmetro `?editar=` permanece somente como fallback/endereço capaz de renderizar o mesmo editor quando JavaScript não está disponível; não é a mecânica principal.

Remover é destrutivo e deve pedir confirmação.

## Referências de calibração

Mesma regra das predefinições: lista + editor contextual. Não usar navegação para `?editar=` como mecânica principal. Como as referências ainda não alimentam automaticamente outros fluxos, não aumentar seu destaque até existir integração real.

## Ferramentas rápidas

Calculadoras, reciprocidade e temporizador são utilidades locais. Devem responder imediatamente no cliente. Nenhuma delas deve produzir navegação ou depender de salvar estado para funcionar.

## Curso e material

Navegação entre aulas/seções é navegação real e pode trocar URL/página. Ferramentas auxiliares do estudo — anotações, progresso local, ações sobre trecho — não devem deslocar a leitura.

## Arquitetura executada nesta revisão

A implementação adota **progressive enhancement**: o backend mantém POST + redirect como fallback sem JavaScript, mas a experiência normal da Área do aluno trata edições locais sem navegação do navegador.

### Camada comum

`assets/student-local-actions.js` passou a ser carregado pelo shell da Área do aluno e estabelece uma mecânica comum para mutações locais. Ele envia o formulário por `fetch`, deixa o servidor executar a mesma validação e persistência já existentes e, quando necessário, substitui somente a região da interface afetada. Os runtimes de bancada e processamento foram tornados reinicializáveis e recebem `student:local-update` depois de uma substituição parcial.

### Estado por área

| Área / ação | Classe | Implementação desta revisão |
|---|---|---|
| Material: criar/editar/remover/reassociar/desvincular anotação | edição local | JSON + atualização direta do DOM; sem reload; posição de leitura preservada |
| Material: transformar anotação em dúvida | transição deliberada | navega para Dúvidas com vínculo persistente à anotação e à citação |
| Caderno: visibilidade Privado/Turma/Curso | edição local | atualização do cartão sem reload e sem fechar o contexto |
| Caderno: criar registro | transição deliberada | cria e abre o registro |
| Caderno: duplicar registro | transição deliberada | cria e abre a cópia |
| Caderno: excluir | destrutiva | fluxo separado permanece |
| Exposição: salvar e seguir | transição deliberada | navegação para Processamento permanece explícita |
| Exposição/Resultado: anexar ou remover imagem | edição local | upload/remoção interceptados e região do registro atualizada localmente |
| Processamento: adicionar/desfazer etapa | edição local / estrutural | atualização local do log, próxima decisão e formulário; confirmação quando destrutiva |
| Processamento: editar etapa | edição local | editor contextual em dialog; salva no endpoint original e atualiza o registro sem navegar |
| Processamento: cronômetro | estado efêmero | permanece no cliente; navegação acidental com timer ativo recebe proteção |
| Resultado: salvar texto | edição local | atualização local |
| Resultado: enviar para avaliação | transição de estado | estado e interface atualizados no próprio registro |
| Resultado: mensagem | edição local | conversa atualizada sem reload |
| Dúvidas: criar | transição deliberada | abre a discussão criada |
| Dúvidas: responder/resolver | edição local | thread/estado atualizados sem reload |
| Inventário: criar item | edição local | lista e histórico atualizados localmente |
| Inventário: entrada/saída | edição local | saldo e histórico atualizados sem fechar item |
| Inventário: arquivar | estrutural | confirmação + atualização local |
| Predefinições: criar/editar | edição local | editor contextual + lista atualizada sem reload |
| Predefinições: remover | destrutiva | confirmação + atualização local |
| Calibração: criar/editar | edição local | editor contextual + lista atualizada sem reload |
| Ferramentas rápidas | edição local | permanecem client-side |

### Contrato de regressão

`tools/test-student-interaction-continuity.php` impede regressões arquiteturais básicas: runtime comum no shell, cobertura das mutações locais do registro, upload via `requestSubmit`, editor contextual de etapa apontando para seu endpoint original, regiões locais de Inventário/Dúvidas/Predefinições/Calibração e anotações sem mecanismo de reload/sessionStorage.

A suíte de navegador das anotações também verifica Chromium e WebKit e deve comprovar que salvar uma anotação não altera URL nem posição de leitura.

## Critério de revisão para toda mudança

Antes de implementar qualquer ação na Área do aluno, classificar explicitamente como **edição local**, **transição deliberada** ou **ação destrutiva/estrutural**. Se uma edição local causar reload, mudança de scroll, fechamento de contexto ou perda de dados não salvos, a interação está errada mesmo que o backend esteja funcional.
