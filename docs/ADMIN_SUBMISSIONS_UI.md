# Administração de inscrições — regra visual e operacional

Este documento define a superfície canônica de `Admin → Inscrições → Respostas`.

## Estrutura

A tela usa master-detail:

- a lista de inscrições ocupa uma coluna limitada, com aproximadamente 300–340 px em desktop;
- a ficha selecionada recebe todo o espaço restante;
- a área de respostas não herda o limite genérico estreito do restante do admin: ela se alinha aos 28 px usados pelo toolbar/context nav e aproveita a largura disponível;
- abaixo de 900 px, lista e ficha deixam de disputar a mesma linha e passam para uma coluna.

Não comprimir a ficha para preservar uma proporção percentual da lista. A prioridade da tela é ler e operar os dados da inscrição.

## Campos da inscrição

Cada dado é uma unidade composta por rótulo e valor. O rótulo fica acima do valor.

Em desktop largo, as unidades usam duas colunas. Antes de a largura começar a quebrar e-mails, CPF, CEP ou telefone em fragmentos, o grid cai para uma coluna. Valores não usam `overflow-wrap:anywhere` nem `word-break` arbitrário.

O endereço ocupa a linha inteira. CPF, CEP e telefone são formatados apenas para apresentação administrativa; o payload original permanece inalterado.

As linhas visuais existem entre grandes grupos (`Contato`, `Presente e envio`, `Disponibilidade`, `Pagamento`), não sob cada dado individual.

## Estado e seleção

Seleção e estado têm semânticas diferentes:

- a inscrição selecionada usa fundo neutro e marcador escuro;
- `Aguardando pagamento` usa uma família âmbar/ocre;
- `Confirmada` usa verde;
- cancelada/arquivada usa neutro cinza.

Verde não deve ser usado para indicar apenas seleção.

O resumo da ficha se chama `Status`, não repete `Inscrição`. A ação principal do estado fica próxima desse resumo: confirmar pagamento quando pendente ou voltar para aguardando quando confirmado. A seção detalhada de pagamento continua existindo para notas e ações completas.

Ações rápidas de estado não podem apagar `payment_note` existente quando o formulário rápido não enviar esse campo.

## Hierarquia e toolbar

O título `Inscrições` já existe no shell administrativo e não é repetido no conteúdo. A faixa abaixo das abas reúne:

- contagem de aguardando/confirmadas;
- filtro de formulário quando houver mais de um;
- ações `Editar formulário` e `Exportar CSV`.

O cabeçalho da ficha permanece visível durante o scroll interno do detalhe.

## Sidebar

`Direct Positive Workshop` e `Administração` são duas linhas distintas da marca administrativa. Não concatenar os dois textos visualmente.

## Cache e regressão

`admin-inbox.css` e `admin-registration.css` são carregados com `admin_asset_url()` e portanto recebem a versão instalada no URL.

A regressão é coberta por:

- `tools/test-admin-submissions-layout.php`, para invariantes estruturais;
- `tools/browser-tests/admin-submissions-layout.spec.cjs`, que valida largura real, grid dos dados, colapso responsivo e distinção visual de estado em Chromium.
