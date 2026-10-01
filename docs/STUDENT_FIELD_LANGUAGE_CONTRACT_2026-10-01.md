# Contrato de linguagem dos campos — Área do aluno

Data: 2026-10-01

## Objetivo

Eliminar divergências de nomenclatura, hierarquia visual e nome acessível entre formulários da Área do aluno. O contrato vale para Caderno, Exposição, Processamento, Resultado, Dúvidas, Inventário, Predefinições de revelação, Referências de calibração, Ferramentas, Perfil e toolbox contextual.

## Regras

1. O mesmo conceito usa o mesmo nome em todas as telas.
2. Labels de campos e `legend` de grupos de escolha ocupam o mesmo nível visual.
3. A unidade não substitui o nome do conceito; quando necessária, permanece como complemento do label.
4. Barras (`/`) não são usadas para fundir conceitos diferentes ou alternativas de vocabulário.
5. Campos dentro de `details` que usam o `summary` como título visual recebem nome acessível explícito.
6. Copy estática é entregue pelo HTML do servidor. JavaScript só altera labels quando o significado muda de acordo com o estado do formulário.
7. Nomes internos de banco de dados não determinam a terminologia mostrada ao aluno.

## Vocabulário canônico

| Conceito | Label canônico |
| --- | --- |
| Sensibilidade de trabalho | EI |
| Abertura da objetiva | Abertura |
| Exposição antes da correção | Tempo sem reciprocidade |
| Exposição após a correção | Tempo com reciprocidade |
| Condição observada | Condição de luz |
| Registro de faixa tonal + intenção | Faixa tonal e intenção |
| Configuração de revelação salva | Predefinição de revelação |
| Revelador fora do catálogo | Outro revelador |
| Quantidade de concentrado/estoque | Revelador ou solução estoque (ml) |
| Revelador preparado no momento | Volume preparado (ml) |
| Associação de consumo | Item do inventário |
| Intervalo usado para o alerta do timer | Aviso de agitação |
| Acesso privado/turma/curso | Visibilidade |
| Texto enviado em uma discussão | Resposta |
| Referência de calibração | Nome da referência |
| Identificação de lote ou outro código | Lote ou identificação |
| Data padrão de um insumo | Data de entrada |
| Data de uma solução preparada | Data de preparo |
| Telefone do perfil | Telefone para contato |
| Localidade do perfil | Cidade e UF |

## Labels dependentes de estado

Somente labels cujo significado realmente muda podem ser atualizados pelo runtime:

- `Tempo` → `Tempo de lavagem` para etapas de lavagem;
- `Tempo` → `Tempo de secagem (opcional)` para secagem;
- `Data de entrada` → `Data de preparo` quando o item do inventário é uma solução preparada.

`Predefinição de revelação`, `Outro revelador`, `Aviso de agitação` e `Solução preparada` não devem depender de JavaScript para aparecer corretamente.

## Acessibilidade

Um `summary` de `<details>` não é o nome acessível de um `<textarea>` interno. Os campos de anotações que não repetem visualmente o título recebem `aria-label` específico:

- Anotações da calibração;
- Anotações do item;
- Anotações da etapa.

## Regressão

`tools/browser-tests/student-field-language.spec.cjs` bloqueia:

- retorno de labels legados;
- divergência entre labels e `legend` no mesmo nível semântico;
- controles `.form-field` sem nome visível ou acessível;
- retorno de relabeling JavaScript para copy estática;
- divergência do vocabulário principal de exposição, processamento, inventário, perfil e ferramentas.
