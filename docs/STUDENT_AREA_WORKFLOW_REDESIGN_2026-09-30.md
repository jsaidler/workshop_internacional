# Área do aluno — fluxo de trabalho canônico — 2026-09-30

Este documento substitui, para decisões de experiência e navegação da Área do aluno, a interpretação anterior que tratava Curso, Caderno e Laboratório como silos equivalentes. A revisão de 01/10/2026 substitui também a interpretação do Processamento como assistente linear obrigatório e das anotações como controles anexados a cada seção do material.

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
4. **Processamento é um caderno de laboratório editável, não um wizard.** As etapas já registradas ficam visíveis como linha do tempo. Cada uma pode ser aberta e corrigida sem apagar as etapas posteriores.
5. O sistema pode sugerir uma etapa coerente para continuar, mas a sugestão não bloqueia outras etapas válidas. Pesquisa experimental não pode ser obrigada a seguir uma árvore rígida desenhada pelo software.
6. **Secagem encerra o processamento.** Depois de registrar Secagem, o próximo destino normal é Resultado.
7. Alterar dados de uma etapa e alterar a sequência são operações diferentes. A edição normal preserva todo o restante do processo. Se a sequência estiver errada, a correção destrutiva é explícita: remover aquela etapa e as posteriores para refazer a partir dali.
8. Receitas devem reunir no mesmo lugar quantidades, redimensionamento, modo de preparo, origem e avisos necessários. O aluno não deve consultar uma página para quantidades e outra para descobrir como preparar.
9. Fórmulas externas perigosas ou adaptações de reagentes não são inferidas pela interface. Referências clássicas podem ser indicadas com a fonte original, mas substituições precisam ter suporte técnico específico antes de virarem receita operacional.
10. Estados vazios não exibem seções sem conteúdo apenas para preservar layout.
11. Textos de interface usam português natural e pluralização correta; não usar `item(ns)`, `registro(s)` ou linguagem de implementação.

## Anotações no material

Anotação é uma camada pessoal do estudo, não parte estrutural de cada seção editorial. O material não deve receber um formulário repetido no fim de toda seção.

A página possui um único **Caderno de anotações** acessível de forma persistente. Nesse painel o aluno vê, cria, edita e remove suas anotações. Quando uma anotação se refere a um trecho específico, o vínculo com a seção é preservado como contexto e escolhido dentro do próprio caderno; o controle de edição não fica fisicamente grudado ao conteúdo da seção.

Isso mantém duas hierarquias distintas: o texto do curso continua sendo texto do curso; as notas do aluno continuam sendo notas do aluno.

## Hierarquia visual

A interface não é um painel administrativo. Linhas, microtexto monoespaçado e títulos condensados são recursos de hierarquia, não decoração repetida. Campos e rótulos precisam permanecer legíveis em celular e em condições de laboratório. Espaço vazio só é útil quando aproxima o olhar da próxima ação; não deve separar conteúdo relacionado.

## Fluxo do registro

**Exposição** reúne identificação da fotografia, filme, EI, diafragma, tempo e correção de reciprocidade. Dados menos frequentes permanecem opcionais.

**Processamento** mostra primeiro o processo já registrado, com cada etapa editável. Abaixo dele, o sistema oferece uma sugestão para continuar e permite escolher outra etapa. O temporizador acompanha a etapa que está sendo registrada. Corrigir os dados de uma etapa não muda a sequência; corrigir a sequência é uma ação separada e explicitamente destrutiva.

**Resultado** concentra imagem da chapa, anotações e, quando o registro pertence ao curso, envio para avaliação. Conversa fica subordinada à avaliação, não compete com o registro do resultado.

## Compatibilidade

Rotas antigas de utilidades simples podem continuar existindo para links salvos, mas devem convergir para a seção correspondente da bancada integrada. Regras de matrícula, privacidade, propriedade dos dados e persistência não mudam por causa deste redesenho.
