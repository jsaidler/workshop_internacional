# Inventário e predefinições — Tranche C do redesign da área do aluno — 01/10/2026

## Retificação de produto — 15/10/2026 (PR #245 draft)

A auditoria integral de 13/10 e a crítica visual de 15/10 revelaram que a formulação anterior mantinha uma tela de controle administrativo: três painéis numéricos antes dos materiais físicos, botão de cadastro dominante mesmo com estoque existente e arquivamento ao lado de movimentação. **Esta decisão posterior prevalece especificamente sobre os três requisitos contraditórios do contrato de interface de 01/10** (Novo item sempre primário, métricas sempre acima da lista, Editar/Arquivar permanentemente expostos).

A tarefa habitual na lista com itens é **consultar o que existe, verificar quantidade/localização e registrar entrada/saída**. Por isso a lista física precede o resumo de contagens; os três indicadores continuam disponíveis num `details` nativo “Resumo do estoque”, depois dos itens, sem desaparecer ou alterar cálculos. “Movimentar estoque” permanece visível como ação principal em cada item. “Novo item” continua acessível no cabeçalho e é a ação principal no estado vazio. “Editar item” e “Arquivar” ficam num `details` nomeado “Mais opções” de cada item; acesso por toque/teclado, foco e POST/CSRF originais são preservados. A operação destrutiva não compete com a movimentação cotidiana.

O contrato físico continua inalterado: nenhum consumo é inferido do uso de um roteiro, o saldo só muda em movimentação válida, e arquivamento preserva o histórico. O inventário não é o catálogo de predefinições. Deve haver testes e inspeção da composição mobile/desktop, estados de erro/modal/edição e navegação antes do aceite. **PR draft, sem merge nem instalação.**

## Objetivo

Inventário e Predefinições de revelação deixam de parecer dois cadastros desconectados. A interface deve ensinar claramente a diferença entre **o que existe fisicamente no laboratório** e **uma combinação de parâmetros que o aluno quer reutilizar**.

## Modelo mental canônico

### Inventário — o que eu tenho

O Inventário representa materiais e soluções físicas disponíveis no laboratório.

- **Insumo**: produto ou material armazenado, por exemplo Parodinal, cloreto férrico ou carbonato de sódio.
- **Solução preparada**: banho ou solução já preparada e fisicamente disponível.
- O saldo muda por entradas, saídas e consumo registrado em processos.
- Arquivar remove o item do estoque ativo sem reescrever o histórico.

A tela precisa responder rapidamente: **o que tenho, quanto tenho, onde está e se preciso repor**.

### Predefinição de revelação — como eu costumo preparar e usar

Uma predefinição não é estoque. Ela salva uma configuração reutilizável de revelação: revelador, proporções/volume, temperatura, tempo e agitação.

Criar uma predefinição **não adiciona solução ao inventário** e usá-la em um roteiro **não significa que exista fisicamente aquele volume**. Quando houver consumo real, o Inventário continua sendo a autoridade do saldo.

A tela precisa responder: **quais condições eu repito e quais parâmetros serão preenchidos quando eu escolher esta predefinição**.

## Relação com Processamentos

Processamentos podem usar uma predefinição como origem de parâmetros. O roteiro copia os valores necessários para preservar o que foi planejado.

O inventário representa consumo físico. Em particular, a segunda revelação dos padrões de positivo direto reutiliza o mesmo banho da primeira e **não pode gerar uma segunda baixa do revelador**.

## Contrato de interface — Inventário

`/aluno/inventario.php` deve:

- explicar a diferença entre insumo e solução preparada;
- oferecer **Novo item** como ação primária;
- mostrar uma leitura resumida do estoque antes da lista: itens ativos, itens com estoque baixo e soluções preparadas;
- apresentar saldo como informação dominante de cada item;
- deixar **Editar**, **Registrar entrada/saída** e **Arquivar** descobríveis sem depender de um menu oculto;
- explicar o efeito de entrada, saída e arquivamento;
- usar estado vazio instrutivo;
- manter histórico de movimentações como prova do que alterou o saldo.

## Contrato de interface — Predefinições

`/aluno/preparos.php` deve:

- explicar de forma explícita que predefinição não é estoque;
- oferecer **Nova predefinição** como ação primária;
- mostrar revelador, preparo/diluição, temperatura, tempo e agitação na leitura de cada item;
- deixar **Editar** e **Excluir** sempre visíveis;
- usar estado vazio que ensine quando vale a pena criar a primeira;
- oferecer caminho direto ao Inventário quando o aluno quiser registrar uma solução realmente preparada.

## Linguagem pedagógica

A orientação aparece no ponto de uso e deve ser curta. A interface não reensina química fotográfica; ela explica o significado operacional do que está sendo cadastrado e a consequência das ações.

## Mobile

No telefone:

- saldo e identidade do item continuam legíveis sem abrir detalhes;
- ações essenciais não dependem de hover ou menus minúsculos;
- formulários colapsam para uma coluna;
- nenhuma ação primária pode ficar coberta pela navegação inferior;
- não pode haver overflow horizontal.

## Inspeção visual

A tranche só pode ser concluída após renderização e inspeção visual humana, em desktop e mobile, de pelo menos:

- inventário com conteúdo;
- inventário vazio;
- criação/movimentação de item;
- lista de predefinições;
- editor de predefinição.

`student-visual-audit` é proteção automatizada, não substituto da inspeção visual humana.
