# Histórico transacional do editor

Este documento define o contrato de Desfazer/Refazer do editor visual.

## Princípio

Qualquer alteração da estrutura da página precisa entrar no mesmo histórico do editor antes de ser salva ou antes de qualquer recarga do preview. O histórico não é reiniciado quando somente o iframe de preview recarrega.

Isso vale para inserção, remoção, duplicação, reordenação, movimentação entre colunas e renomeação editorial de componentes.

## Contrato

- O editor central é a única autoridade do histórico da página.
- Extensões do editor não mantêm pilhas próprias. Elas registram a alteração pela API `window.CmsEditorHistory.commit()`.
- Um `commit()` representa o estado posterior a uma ação concluída. O estado anterior já precisa existir na pilha.
- `undo()` e `redo()` restauram HTML e configurações da página sem publicar automaticamente.
- Restaurar histórico marca o rascunho como alterado, permitindo que o autosave grave o estado restaurado.
- Recarregar somente o iframe após um salvamento não apaga a pilha de histórico.
- Uma navegação para outra página ou recarga completa do editor cria uma nova sessão de histórico.
- O HTML armazenado no histórico não carrega classes transitórias de seleção/edição nem `contenteditable`; contexto de seleção é guardado separadamente quando houver identidade estrutural.
- Após restauração, o editor emite `cms:history-restored` para que controladores estruturais possam reconstruir seleção e controles.

## Seleção

Quando um componente estrutural possui `data-cms-node-id`, seu identificador acompanha o snapshot. Desfazer/Refazer tenta recuperar esse elemento depois da restauração. Se o elemento não existir naquele estado — por exemplo, ao desfazer sua criação — o editor cai para a seção correspondente ou deixa a seleção vazia, sem inventar um alvo.

## Salvamento

Salvar não cria uma entrada de histórico por si só. O histórico descreve alterações editoriais, não operações de persistência. Extensões que alteram o DOM devem chamar `commit()` e só depois disparar o salvamento.

## Regressão obrigatória

A suíte de navegador deve comprovar pelo menos:

1. inserir componente → Desfazer remove → Refazer restaura;
2. mover componente → Desfazer devolve à posição/coluna anterior;
3. duplicar e remover participam do mesmo histórico;
4. renomear `Nome no editor` pode ser desfeito/refeito;
5. uma recarga do iframe entre a alteração e o Desfazer não destrói a pilha;
6. uma nova alteração depois de Desfazer invalida o ramo de Refazer.
