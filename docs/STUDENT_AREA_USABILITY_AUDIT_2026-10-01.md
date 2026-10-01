# Área do aluno — auditoria de mecânicas e usabilidade — 2026-10-01

Esta auditoria complementa `STUDENT_AREA_WORKFLOW_REDESIGN_2026-09-30.md`. O critério aqui não é acabamento visual: cada função precisa justificar sua existência no fluxo real do aluno e não apenas porque existe uma tabela, rota ou cálculo disponível no sistema.

## Critério de produto

A Área do aluno deve operar como três ambientes coerentes: estudar no Curso, registrar a prática no Caderno e recorrer a utilidades de apoio quando necessário. Uma função isolada não deve virar destino de navegação por padrão. Dados salvos só merecem interface própria quando voltam a ser usados em outra tarefa. Qualquer dado persistente que o aluno possa criar precisa ter uma forma coerente de correção posterior; “salvar e nunca mais editar” não é uma mecânica aceitável para um caderno de pesquisa.

## Processamento — corrigido nesta revisão

Problema anterior: o registro de processamento se comportava como um wizard. O backend impunha uma árvore rígida de próximas etapas, o histórico ficava recolhido e a única correção disponível era desfazer a última etapa. Isso contrariava a natureza experimental do Caderno e tornava impossível corrigir temperatura, tempo, diluição, agitação ou anotação de uma etapa já executada.

Contrato atual:
- as etapas registradas formam um log visível;
- cada etapa pode ser editada individualmente;
- edição comum preserva todas as etapas posteriores;
- o sistema sugere uma continuação, mas não a transforma em obrigação;
- corrigir a sequência é uma operação separada, claramente destrutiva, que remove a etapa escolhida e as seguintes;
- consumo de inventário é reconciliado quando uma etapa é editada;
- registros já revisados permanecem bloqueados para alteração.

## Anotações do material — corrigido nesta revisão

Problema anterior: cada seção CMS recebia fisicamente um formulário de anotação no final. Isso misturava duas hierarquias diferentes — conteúdo didático e caderno pessoal —, criava repetição visual e fazia a localização da ferramenta depender da estrutura editorial da página.

Contrato atual:
- existe um único caderno de anotações da página;
- o painel é persistente e independente do layout editorial;
- uma anotação pode ser geral para a página ou vinculada a um trecho;
- notas existentes continuam editáveis e removíveis;
- o vínculo com a seção serve como contexto, não como posição obrigatória do editor.

## Exposição e reciprocidade — manter, mas contextuais

A correção de reciprocidade pertence ao registro de Exposição e já é calculada no próprio Caderno. A calculadora independente continua útil como consulta rápida, mas não deve competir com o fluxo do registro.

Exposição equivalente é uma utilidade operacional válida para recalcular tempo quando diafragma, EI ou compensação mudam. Deve continuar disponível na bancada e no acesso rápido, não como etapa obrigatória do Caderno.

## Temporizador — manter como utilidade contextual

No processamento, o temporizador acompanha a etapa em execução. A versão independente é uma conveniência de laboratório. Não deve manter valores arbitrários que pareçam fazer parte de uma receita; o tempo da etapa é a referência quando existe contexto de processo.

## Receitas — manter como referência operacional

Receita precisa reunir fórmula, redimensionamento, modo de preparo e segurança. Preparos armazenáveis podem alimentar o Inventário sem redigitação. Metadados internos de origem não pertencem à interface do aluno.

## Inventário — manter, mas secundário

O Inventário tem função real porque é consumido pelo registro de processamento e recebe preparos armazenáveis. Não é uma planilha administrativa: cadastro expõe primeiro tipo, nome, quantidade e unidade; lote, validade, local e limites são detalhes opcionais.

A correção de um item não deve ser falsificada como uma movimentação de estoque. Nome, lote, validade, local, categoria e anotações agora podem ser editados separadamente; quantidade continua sendo alterada apenas por entradas e saídas para que o histórico de saldo permaneça íntegro.

## “Preparos salvos” — redefinido como Predefinições de revelação

O objeto salvo não representa necessariamente um frasco ou lote físico. Ele guarda uma configuração reutilizável de revelação — revelador, diluição/volume, temperatura, tempo e agitação — e é reaplicado ao preencher uma etapa. Portanto, a interface passa a chamá-lo de **Predefinição de revelação**. Soluções físicas continuam pertencendo ao Inventário.

Predefinições existentes agora podem ser editadas sem criar outra entrada e sem quebrar a identidade do registro salvo. Isso é importante porque etapas de processo podem apontar para uma predefinição já existente.

## Referências de calibração — editáveis, mas ainda isoladas

A calibração salva referências pessoais de filme, revelador, preparo, temperatura, pré-banho e tempo do branco. Esses registros agora podem ser corrigidos depois de criados, eliminando o comportamento anterior de “salvar uma vez e conviver com o erro”.

Ainda assim, a calibração não é consumida automaticamente por Exposição, Processamento ou outra ferramenta. Portanto ela continua sendo uma referência de consulta e não deve ganhar mais destaque até existir uma função clara de reaproveitamento. Integrar a referência ao fluxo ou demotá-la ainda é uma decisão de produto pendente.

## Catálogo de reveladores — manter, organizar melhor

O catálogo ampliado de reveladores é intencional: além de Parodinal e Brewed Caffenol, o aluno pode registrar reveladores analógicos comuns e usar **Outro** quando necessário. O problema não é a existência dessas opções, e sim apresentá-las como uma lista indiferenciada.

Parodinal e Brewed Caffenol devem permanecer primeiro, por serem os processos diretamente trabalhados na pesquisa/curso. Os demais reveladores comuns devem aparecer como grupo secundário e **Outro** como saída explícita. Assim o Caderno continua aceitando pesquisa fora do processo principal sem transformar a interface em catálogo de mercado.

## Regra para novas funções

Antes de adicionar uma nova ferramenta à Área do aluno, responder três perguntas: em que tarefa real ela é usada; de onde vêm seus dados; e para onde o resultado volta. Se a terceira resposta for “lugar nenhum”, a função é uma referência isolada e deve permanecer secundária até existir integração real.
