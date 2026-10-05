# Caderno — contrato de registro não linear — revisão de 05/10/2026

Este documento substitui, para o Caderno de Processos, qualquer regra anterior que trate o registro como wizard, fluxo de execução, progressão obrigatória ou máquina de estados.

## Princípio

**O Caderno observa e auxilia o processo; ele não governa o processo.**

Tudo que seria trivial fazer num caderno de papel deve continuar trivial. O digital acrescenta vantagens — estrutura, busca, roteiro reutilizável, timer, cálculos, estoque, imagens e análise — sem exigir que o usuário explique ao sistema em que momento ou fase da experiência está.

O sistema não diferencia “vou revelar”, “estou revelando” e “já revelei”. Essa distinção não pertence ao Caderno.

## Registro

Exposição, Processamento, Resultado e anotações pertencem ao mesmo registro e podem ser preenchidos, corrigidos ou deixados vazios em qualquer ordem.

Consequências obrigatórias:

- nenhuma parte libera ou bloqueia outra;
- salvar uma parte não força navegação para outra;
- Resultado pode existir sem Processamento completo;
- um registro pode permanecer parcial indefinidamente;
- ausência de dados significa apenas ausência de dados no Caderno;
- o sistema não conclui que uma operação física ocorreu ou deixou de ocorrer a partir da navegação da interface.

`/aluno/teste.php?id=...` mantém as partes na mesma página, com âncoras para acesso rápido. Links antigos `?view=...` podem apenas convergir para essas âncoras.

## Roteiro associado

Um roteiro é uma **referência associada ao registro**.

Associar um roteiro:

- não inicia processamento;
- não cria etapa atual;
- não cria próxima etapa;
- não cria pendência;
- não movimenta estoque;
- não impede qualquer outra parte do registro.

Todas as etapas do roteiro ficam imediatamente disponíveis. O usuário pode abrir qualquer uma delas em qualquer ordem.

Trocar o roteiro substitui a referência associada. Anotações livres e demais dados do registro não são apagados. Quando houver etapas equivalentes entre a referência anterior e a nova, checks compatíveis podem ser preservados. O sistema não deve reconstruir uma sequência física a partir do histórico.

## Etapas e checks

Cada etapa de um roteiro possui um check independente `concluída / não concluída`.

O check é **somente um dado do Caderno**. Ele nunca é uma permissão.

O usuário pode:

- marcar qualquer etapa manualmente;
- desmarcar qualquer etapa;
- marcar etapas fora de ordem;
- editar os dados de uma etapa marcada;
- abrir o timer de uma etapa marcada;
- deixar qualquer conjunto de etapas sem marcação para sempre.

O timer chegar ao fim pode marcar automaticamente aquela etapa como concluída. Essa marcação continua reversível.

A interface pode informar `3 de 7 etapas marcadas`. Ela não deve transformar isso em `faltam 4`, `pendente`, `processamento incompleto`, `próxima etapa` ou equivalente.

Completar todos os checks também não fecha nem bloqueia o roteiro.

## Experimentos interrompidos ou abandonados

É normal uma experiência terminar antes do fim de um roteiro. Se o resultado já estiver previsível na primeira revelação, o usuário pode simplesmente parar ali.

O Caderno não interpreta etapas não marcadas como trabalho a concluir. Um roteiro com apenas algumas etapas marcadas continua sendo um registro válido e pode conter Resultado, imagem e observações.

Não existe obrigação de criar um estado especial para distinguir uma tentativa abandonada de uma tentativa que será retomada. Se o pesquisador quiser registrar essa informação, ela pertence às anotações ou ao Resultado.

## Timer

Timer é ferramenta de uma etapa, não execução controlada pelo sistema.

Cada etapa temporizada pode ter seu próprio timer. O usuário pode abrir o timer de qualquer etapa a qualquer momento e voltar a usá-lo depois. Abrir, pausar, reiniciar ou fechar um timer não determina sequência de processamento.

Quando o timer chega a zero:

1. o timer fica em estado `elapsed` para aquela etapa;
2. o check da etapa pode ser marcado automaticamente uma vez;
3. o usuário continua livre para desmarcar, editar ou usar o timer novamente.

Estado de timer é estado operacional da ferramenta, nunca prova ou autoridade sobre o processo físico.

## Etapas livres

O usuário pode registrar etapas sem associar um roteiro.

Adicionar, editar ou remover uma etapa livre afeta somente aquela anotação. Remover uma etapa não remove as seguintes e não desfaz movimentações de estoque.

A ordem visual dessas anotações é apenas organização do Caderno, não validação da sequência química.

## Estoque

Estoque é um domínio independente do check e do timer.

Nenhuma destas ações pode baixar, devolver ou descartar material automaticamente:

- associar roteiro;
- abrir timer;
- timer chegar ao fim;
- marcar etapa;
- desmarcar etapa;
- editar etapa;
- remover etapa do Caderno.

Movimentação de estoque é uma ação explícita do usuário.

Isso é necessário porque **uso químico, desgaste da solução e variação física de volume são eventos diferentes**. Soluções podem ser de uso único ou reaproveitáveis; o sistema não deve deduzir o destino de uma solução apenas porque uma etapa foi marcada.

## Resultado

Resultado é o que o pesquisador decidiu documentar sobre a tentativa. Não significa “positivo final obtido depois de todas as etapas”.

Pode registrar, por exemplo, que uma tentativa foi abandonada após a primeira revelação. Imagem e observações continuam opcionais e independentes dos checks do roteiro.

## Análise

O Caderno registra. **A Análise interroga o conjunto de registros.**

A vantagem digital está em manter dados suficientemente estruturados para permitir posteriormente filtros, comparações, tabelas, gráficos e relações entre variáveis como filme, EI, exposição, revelador, diluição, temperatura, tempos, câmeras, roteiros, etapas usadas e resultado.

Registros parciais continuam válidos para análise. Uma experiência abandonada pode ser informação relevante.

A camada de análise deve distinguir associação de causalidade: pode mostrar padrões e relações presentes nos dados, mas não afirmar causa quando os registros não sustentam essa conclusão.

## Biblioteca do Caderno

`/aluno/caderno.php` apresenta **registros**, não uma progressão `01 → 02 → 03`.

A ação principal é `Abrir registro`. Resumos podem dizer fatos como `roteiro associado`, `4 etapas marcadas`, `resultado registrado` ou equivalentes. Eles não prescrevem próxima ação.

Comparar, duplicar, compartilhar e excluir permanecem ações secundárias.

## Linguagem de interface

Preferir linguagem documental e instrumental:

- Associar roteiro;
- Alterar roteiro;
- Abrir roteiro;
- Abrir timer;
- Marcar como concluída;
- Desmarcar etapa;
- Editar dados;
- Movimentar estoque;
- Adicionar anotação.

Evitar linguagem de controle de workflow:

- iniciar processamento;
- retomar processamento;
- finalizar processamento;
- etapa atual;
- próxima etapa;
- continuar laboratório;
- processamento pendente;
- faltam N etapas;
- vou revelar agora;
- já revelei;
- registrar retrospectivamente.

## Exceção acadêmica

Um registro com status acadêmico `reviewed` pode continuar preservado como documento avaliado. Essa é uma regra de avaliação do curso, não uma regra do processo fotográfico. Mesmo nesse estado, todas as partes continuam consultáveis.

## Casos de aceitação obrigatórios

A implementação deve permitir, sem perda de dados e sem bloqueio por sequência:

1. criar um registro vazio e sair;
2. preencher apenas Exposição;
3. registrar Resultado sem completar Processamento;
4. associar um roteiro e abrir imediatamente qualquer etapa;
5. abrir o timer da última etapa sem marcar as anteriores;
6. marcar manualmente qualquer conjunto de etapas em qualquer ordem;
7. desmarcar uma etapa já marcada;
8. editar uma etapa marcada;
9. deixar o timer chegar ao fim e obter o check automático daquela etapa;
10. reiniciar e reutilizar o timer de uma etapa já marcada;
11. abandonar uma tentativa com poucas etapas marcadas e ainda registrar Resultado;
12. trocar o roteiro sem reconstruir artificialmente uma sequência física;
13. adicionar, editar e remover uma etapa livre sem afetar outras etapas;
14. usar o Caderno sem qualquer movimentação automática de estoque;
15. registrar movimentação de estoque separadamente quando desejar;
16. acessar Exposição, Processamento e Resultado em qualquer ordem no desktop e no mobile;
17. usar registros parciais em comparação e análise.

## Gate

Mudanças estruturais no Caderno só podem ser consideradas prontas depois de testes automatizados e inspeção visual desktop/mobile. Os testes devem proteger este contrato, e não preservar expectativas antigas de wizard, progressão física ou distinção temporal que este documento aboliu.
