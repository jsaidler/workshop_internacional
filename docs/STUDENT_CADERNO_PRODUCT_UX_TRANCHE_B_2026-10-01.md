# Caderno — Tranche B do redesign da área do aluno — 01/10/2026

## Objetivo

O Caderno deixa de ser apenas uma coleção de formulários e passa a explicitar a lógica de pesquisa ensinada ao aluno: **exposição → processamento → resultado**. A interface deve ajudar o aluno a registrar o que fez, entender em que ponto está e saber qual é a próxima ação sem depender de explicação externa.

## Contrato pedagógico

Em qualquer registro o aluno deve conseguir responder rapidamente:

1. **Como expus?** — filme, EI, abertura, tempo e contexto da cena.
2. **Como revelei?** — roteiro ou sequência efetivamente executada.
3. **O que obtive?** — imagem do resultado, observações e, quando aplicável, avaliação do curso.

A interface orienta no ponto de uso, sem transformar o Caderno em apostila. Cada etapa recebe uma explicação curta sobre a função daquele registro na pesquisa.

## Biblioteca do Caderno

`/aluno/caderno.php` deve:

- explicar em uma frase para que serve o Caderno;
- manter **Novo registro** como ação primária inequívoca;
- usar estados vazios instrutivos;
- mostrar em cada registro o contexto, estado atual e próxima ação;
- mostrar visualmente a progressão **Exposição / Processamento / Resultado**;
- manter ações administrativas secundárias separadas da ação de continuidade.

## Registro

`/aluno/teste.php` continua organizado em três partes, mas a navegação passa a explicar o significado de cada uma:

- **Exposição — Como expus**;
- **Processamento — Como revelei**;
- **Resultado — O que obtive**.

### Exposição

A tela deve explicar que os dados de exposição serão confrontados depois com o processamento e o resultado. A fotografia da cena é uma referência opcional, não o resultado final.

### Processamento

O fluxo recomendado é **usar um processamento salvo**. Quando ainda não há processamento no registro, o aluno recebe duas escolhas explícitas:

1. escolher um roteiro salvo e executá-lo no **Modo laboratório**;
2. registrar etapas manualmente, como alternativa para experimentação fora de um roteiro previamente montado.

O modo manual é deliberadamente secundário e não possui o temporizador legado embutido. A temporização operacional pertence ao Modo laboratório.

Quando existe um plano aplicado ao registro, o Caderno mostra nome do roteiro, progresso, próxima etapa e ação para continuar no laboratório. Se o plano ainda não começou, pode ser substituído por outro.

### Resultado

Antes do preenchimento do resultado, a interface reapresenta de forma compacta os dois lados anteriores do experimento — exposição e processamento — para reforçar a relação entre causa, procedimento e resultado. As anotações devem estimular observação útil à repetição ou ajuste do teste, sem impor interpretação.

## Mobile

O Caderno deve continuar plenamente operacional no telefone. A navegação de três partes não pode gerar overflow horizontal; ações primárias devem ocupar largura útil suficiente; resumos devem colapsar para uma coluna; nenhum controle essencial pode ficar atrás da navegação fixa inferior.

## Inspeção visual

Esta tranche só pode ser concluída após renderização e inspeção visual humana, em desktop e mobile, de pelo menos:

- biblioteca do Caderno;
- exposição;
- escolha de processamento;
- processamento com plano aplicado;
- resultado.

O audit automatizado protege contra regressões, mas não substitui a inspeção visual humana.
