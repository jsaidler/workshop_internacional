# Área do aluno — fluxo de trabalho canônico — 2026-09-30

Este documento substitui, para decisões de experiência e navegação da Área do aluno, a interpretação anterior que tratava Curso, Caderno e Laboratório como silos equivalentes.

## Princípio

A interface deve acompanhar a tarefa do aluno, não expor a topologia interna do sistema. O aluno não deve precisar descobrir em qual subsistema vive uma ação pequena, nem atravessar páginas intermediárias para usar uma ferramenta de poucos campos.

## Navegação primária

A navegação principal tem três destinos de trabalho:

- **Início**: continuidade. Mostra o registro mais recente e oferece acesso direto ao próximo passo real.
- **Curso**: estudo. Com uma única matrícula, abre diretamente o curso. Só existe escolha de matrícula quando há mais de uma.
- **Caderno**: prática. Cada registro acompanha uma fotografia por Exposição → Processamento → Resultado.

**Ferramentas** é acesso contextual/secundário. Abre uma bancada rápida sobre a tela atual e também possui uma bancada completa para uso independente. Conta, tema e saída são configurações e não competem com a navegação de trabalho.

## Regras de interação

1. Uma utilidade simples não ganha subpágina apenas por ser uma função separada no código. Reciprocidade, exposição equivalente e temporizador devem poder ser usados sem abandonar a tarefa atual.
2. Cada tela e cada registro devem apresentar uma ação principal evidente. Duplicar, compartilhar, comparar, corrigir e excluir são ações secundárias.
3. O Caderno não é uma tabela administrativa. O estado do registro e a continuação são mais importantes que metadados e controles.
4. Durante o processamento, o próximo passo domina a tela. O histórico fica recolhido e serve para conferência/correção.
5. **Secagem encerra o processamento.** Depois de registrar Secagem, não existe próxima etapa; o próximo destino é Resultado.
6. Correção destrutiva de processo não aparece repetida em todas as linhas. O fluxo normal oferece apenas desfazer a última etapa; correções anteriores são exceção.
7. Receitas devem reunir no mesmo lugar quantidades, redimensionamento, modo de preparo, origem e avisos necessários. O aluno não deve consultar uma página para quantidades e outra para descobrir como preparar.
8. Fórmulas externas perigosas ou adaptações de reagentes não são inferidas pela interface. Referências clássicas podem ser indicadas com a fonte original, mas substituições precisam ter suporte técnico específico antes de virarem receita operacional.
9. Estados vazios não exibem seções sem conteúdo apenas para preservar layout.
10. Textos de interface usam português natural e pluralização correta; não usar `item(ns)`, `registro(s)` ou linguagem de implementação.

## Hierarquia visual

A interface não é um painel administrativo. Linhas, microtexto monoespaçado e títulos condensados são recursos de hierarquia, não decoração repetida. Campos e rótulos precisam permanecer legíveis em celular e em condições de laboratório. Espaço vazio só é útil quando aproxima o olhar da próxima ação; não deve separar conteúdo relacionado.

## Fluxo do registro

**Exposição** reúne identificação da fotografia, filme, EI, diafragma, tempo e correção de reciprocidade. Dados menos frequentes permanecem opcionais.

**Processamento** mostra a etapa atual/seguinte, seus dados e temporizador. Etapas já concluídas ficam em Histórico. Ao registrar Secagem, o registro passa imediatamente para Resultado.

**Resultado** concentra imagem da chapa, anotações e, quando o registro pertence ao curso, envio para avaliação. Conversa fica subordinada à avaliação, não compete com o registro do resultado.

## Compatibilidade

Rotas antigas de utilidades simples podem continuar existindo para links salvos, mas devem convergir para a seção correspondente da bancada integrada. Regras de matrícula, privacidade, propriedade dos dados e persistência não mudam por causa deste redesenho.