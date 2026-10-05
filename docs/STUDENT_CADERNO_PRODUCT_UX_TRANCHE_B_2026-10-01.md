# Caderno — contrato de registro não linear — revisão de 05/10/2026

Este documento substitui, para o Caderno de Processos, as regras de progressão visual e de “próxima ação” descritas na versão de 01/10/2026 sempre que houver conflito. Ele deve ser lido em conjunto com `STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md`, que permanece canônico para a distinção entre navegação, documentação e fatos do laboratório.

## Objetivo

O Caderno é um **registro de pesquisa**, não um assistente de etapas e não um controlador do processo fotográfico.

Exposição, Processamento e Resultado continuam sendo as três categorias que organizam um registro, porque respondem a três perguntas úteis:

1. **Como expus?** — filme, EI, abertura, tempo e contexto da cena.
2. **Como revelei?** — roteiro ou sequência efetivamente executada.
3. **O que obtive?** — imagem do resultado, observações e, quando aplicável, avaliação do curso.

Essas categorias **não definem uma ordem obrigatória de uso da interface**. Um aluno pode registrar exposição e sair, começar pelo processamento, transcrever um resultado antigo, retomar um registro parcial ou corrigir uma parte depois de já ter preenchido as demais.

## Invariante principal

**O aluno deve conseguir salvar o que tem, sair, voltar depois e alterar uma decisão sem perder fatos já registrados.**

Consequências diretas:

- nenhuma das três partes fica bloqueada por outra;
- salvar Exposição não leva automaticamente a Processamento;
- salvar Processamento não obriga a abrir Resultado;
- Resultado pode ser registrado mesmo sem Exposição ou Processamento completos;
- o estado interno do registro não habilita nem desabilita navegação;
- um registro incompleto é um estado legítimo, não um erro a ser resolvido pela interface.

A única exceção de edição é um registro já avaliado (`reviewed`), que permanece preservado como documento acadêmico. Mesmo nesse estado, **todas as partes continuam consultáveis**; o bloqueio é de escrita, não de navegação.

## Biblioteca do Caderno

`/aluno/caderno.php` deve apresentar cada item como **um registro**, não como uma etapa de um funil.

A lista mostra, de forma derivada dos dados existentes:

- se há Exposição registrada;
- quantas etapas de Processamento foram registradas ou se há um roteiro associado;
- se há Resultado registrado.

Essas informações são resumo documental. Não existe “etapa atual” do Caderno, não existe barra `01 / 02 / 03` e a interface não prescreve uma “próxima ação”.

A ação principal de cada item é **Abrir registro**. Comparar, duplicar, compartilhar e excluir continuam secundárias em `Mais ações`.

## Página do registro

`/aluno/teste.php?id=...` contém Exposição, Processamento e Resultado na **mesma página**, em sequência vertical, com âncoras para acesso rápido.

As três seções ficam sempre presentes e acessíveis. Não há abas bloqueadas nem parâmetro de estado usado para decidir qual parte o aluno pode ver.

Links legados com `?view=exposure`, `?view=process` ou `?view=review` podem ser aceitos apenas como compatibilidade e devem convergir para as âncoras `#exposicao`, `#processamento` e `#resultado`.

### Exposição

A seção registra as condições utilizadas na fotografia. A fotografia da cena permanece opcional e é uma referência de contexto.

A ação primária é **Salvar exposição**. Salvar não implica que o aluno queira processar a fotografia imediatamente e não muda sua posição na página para outra tarefa.

### Processamento

Processamento é uma parte do registro. O aluno pode:

- associar ou alterar um roteiro;
- abrir o Modo laboratório para consulta, temporização e apoio à execução;
- registrar o processamento manualmente ou retroativamente.

Essas opções não representam universos paralelos. Todas produzem ou organizam informação do mesmo registro.

#### Roteiro como ferramenta do registro

A escolha de roteiro ocorre numa superfície subordinada ao registro (`/aluno/registro-roteiro.php?test=...`) e sempre oferece retorno explícito ao Processamento do registro.

Associar um roteiro cria um snapshot para aquela fotografia e **não significa que a revelação começou**.

Alterar o roteiro é permitido mesmo quando já existem fatos materializados:

- etapas factualizadas permanecem intactas;
- exposição, resultado, mídia e observações não são alterados;
- o novo roteiro governa somente o que ainda não foi consolidado como fato;
- timers e posição operacional podem ser reinicializados sem criar fatos fictícios;
- a troca é atômica, conforme o contrato canônico de replanejamento.

#### Modo laboratório

O Modo laboratório é uma operação filha do registro. Abrir, fechar, consultar uma etapa ou usar o cronômetro não muda o histórico factual por si só.

Ao sair do laboratório, o caminho de retorno deve convergir para o mesmo registro, preferencialmente `#processamento`.

#### Registro manual ou retroativo

Registrar algo já feito não exige simular o uso prévio do Modo laboratório. O aluno pode documentar diretamente a execução real e, quando usar um roteiro como base, ajustar o snapshot sem modificar o processo-base.

Consumo retroativo de inventário permanece explícito e opcional.

### Resultado

Resultado fica disponível desde a abertura do registro.

A seção reapresenta um resumo compacto de Exposição e Processamento, quando houver dados, mas a ausência deles não impede o registro do resultado.

A ação primária é **Salvar resultado**. Para registros vinculados a curso, a avaliação e a conversa com o professor continuam nesta mesma seção.

## Reversibilidade e ações destrutivas

A interface diferencia **editar/corrigir** de **apagar histórico**.

Operações destrutivas ou de reversão técnica não ocupam o fluxo principal. Elas ficam em `Mais ações` ou `Mais opções do processamento`, com confirmação adequada.

O usuário não precisa conhecer estados internos como `planned`, `running`, `completed`, `draft` ou combinações equivalentes para navegar no Caderno. Esses estados podem continuar existindo no domínio e no banco de dados, mas não funcionam como regras de navegação.

## Contrato de saída e retorno

Toda operação filha iniciada a partir de um registro deve ter uma saída inequívoca:

- voltar ao registro atual, na seção relevante; ou
- voltar ao Caderno.

Nenhuma operação normal pode deixar o aluno num caminho sem retorno, exigir recriação de dados para escolher outro percurso ou converter uma navegação da interface em fato de laboratório.

## Mobile

No telefone:

- Exposição, Processamento e Resultado permanecem na mesma página;
- os atalhos de seção podem empilhar verticalmente;
- não deve haver barra fixa cobrindo conteúdo;
- ações primárias devem ocupar largura útil suficiente;
- resumos devem reduzir para uma coluna quando necessário;
- opções raras ou destrutivas permanecem progressivamente reveladas.

## Casos de aceitação obrigatórios

A implementação deve suportar, sem perda de dados e sem bloqueio de navegação:

1. criar um registro vazio e sair;
2. preencher apenas Exposição, salvar, voltar ao Caderno e retomar depois;
3. começar por Processamento sem preencher Exposição;
4. registrar Resultado antes de completar Processamento;
5. interromper a documentação de um processamento e retomar mais tarde;
6. alterar o roteiro depois de fatos já materializados, preservando os fatos anteriores;
7. registrar Processamento manualmente sem usar Modo laboratório;
8. editar Exposição depois de já haver Processamento ou Resultado;
9. abrir o seletor de roteiro e cancelar, retornando ao mesmo registro;
10. acessar links legados `?view=...` e convergir para a seção correspondente;
11. navegar entre as três seções no mobile sem bloqueio ou overflow horizontal;
12. consultar integralmente um registro já avaliado, mantendo-o somente leitura.

## Inspeção visual e gate

A alteração estrutural só pode ser considerada pronta para merge depois de **inspeção visual humana**, desktop e mobile, conforme `STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md`.

A inspeção precisa validar a área do aluno inteira no estado resultante e, em especial, os seguintes estados do Caderno:

- biblioteca vazia e com registros;
- criação de registro;
- registro vazio;
- Exposição preenchida isoladamente;
- Processamento sem roteiro;
- roteiro associado ainda não iniciado;
- Processamento parcial;
- alteração de roteiro com fatos preservados;
- registro retroativo/manual;
- Resultado com e sem dados anteriores;
- registro avaliado em somente leitura.

Teste automatizado protege o contrato e regressões mecânicas, mas não substitui a inspeção visual humana.
