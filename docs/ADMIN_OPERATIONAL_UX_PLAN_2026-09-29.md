# Administração — polimento operacional

Data: 2026-09-29

Esta etapa segue a fundação de UI/UX estabelecida no PR #150 e concentra-se nas coleções Operação: Inscrições, Turmas, Alunos e Pessoas.

## Critérios

- filtros devem ser compactos, previsíveis e manter o contexto após abrir/alterar registros;
- filtros ativos devem ser legíveis sem depender de interpretar campos espalhados;
- status internos devem ser traduzidos para vocabulário de interface;
- datas devem usar apresentação humana consistente;
- ações de linha devem ter prioridade clara e não competir visualmente com dados;
- ações destrutivas devem ser separadas da ação primária;
- detalhe deve aparecer como uma continuação clara da lista e possuir retorno explícito ao mesmo contexto;
- tabelas devem preservar densidade administrativa e degradar de forma controlada em telas pequenas;
- nenhuma tela operacional deve criar uma nova família visual quando uma primitiva global existente resolve a função.

## Ordem

1. Inscrições
2. Turmas
3. Alunos
4. Pessoas

A implementação deve manter paginação e filtragem no servidor e preservar a distinção de domínio: Pessoa é identidade global; Aluno é participação educacional; inscrição, pagamento e atribuição de turma continuam independentes.
