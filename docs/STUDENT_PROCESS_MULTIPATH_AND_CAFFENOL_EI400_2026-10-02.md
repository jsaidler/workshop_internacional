# Caderno — múltiplos caminhos de processamento + Brewed Caffenol EI 400 — 02/10/2026

## Decisão de produto

O Caderno registra a pesquisa; o Modo laboratório auxilia a execução. Uma função não pode ser condição obrigatória da outra.

A etapa **Como revelei** deve aceitar três situações equivalentes:

1. **Executar agora**: aplicar um roteiro e acompanhar o processo no Modo laboratório.
2. **Registrar o que já foi feito**: documentar um processo concluído fora do sistema, sem repetir cronômetros.
3. **Completar um registro parcial**: preservar etapas já acompanhadas no sistema e registrar como realizadas as etapas que aconteceram fora dele.

Um roteiro usado retroativamente é copiado como snapshot do registro. A cópia pode ser corrigida etapa por etapa sem alterar o roteiro da biblioteca.

Registro retroativo não movimenta inventário automaticamente. Qualquer consumo posterior é explícito. Reutilização entre primeira e segunda revelação continua sendo um único banho e não pode produzir dupla baixa.

## Padrões de Brewed Caffenol EI 400

Foram incorporadas duas rotas de positivo direto:

- **Brewed Caffenol EI 400 — FeCl₃ + amônia**;
- **Brewed Caffenol EI 400 — peracética**.

Condições de desenvolvimento adotadas a partir da pesquisa do projeto:

- EI 400;
- Brewed Caffenol preparado fresco;
- 35,7 °C;
- 5 min de revelação;
- o mesmo banho usado na primeira revelação é reutilizado na segunda;
- lavagens: 1 min;
- branqueamento: 1 min 30 s.

A referência de preparo do Brewed Caffenol para aproximadamente 1 L permanece registrada como 37 g de café torrado e moído extra-forte, 54 g de carbonato de sódio, 20 g de ácido ascórbico e água até o volume final.

A tabela de pesquisa registra um esquema de agitação numericamente, mas não fornece no material consultado uma instrução operacional textual suficientemente inequívoca para ser mostrada ao aluno. Portanto, **nenhuma instrução de agitação foi inventada no padrão**. O campo permanece disponível para registro/ajuste quando o aluno tiver uma condição efetivamente utilizada.

## UX do registro posterior

A tela `aluno/processamento-realizado.php` deve explicar antes da escolha do roteiro:

- que ela documenta algo que já aconteceu;
- que não executará novamente os tempos;
- que não haverá baixa automática de inventário;
- que um roteiro é copiado para aquele registro;
- que cada etapa da cópia continua editável.

O registro manual usa divulgação progressiva: campos específicos de revelador só aparecem para etapas de revelação, e volume de solução fresca substitui campos de estoque/água quando o revelador selecionado é preparado fresco.

## Gate visual

Esta correção não pode ser aprovada olhando apenas as telas alteradas. O artefato da tranche deve conter todas as superfícies visuais classificadas em `STUDENT_VISUAL_SCREEN_INVENTORY_2026-10-02.md`, em desktop e mobile, e todo o conjunto deve ser inspecionado antes do merge.
