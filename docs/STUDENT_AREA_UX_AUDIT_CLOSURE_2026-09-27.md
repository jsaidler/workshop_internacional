# Fechamento da auditoria factual da Área do aluno — 27/09/2026

Este documento fecha a sequência A1–A7 e a revisão transversal registrada em `STUDENT_AREA_UX_AUDIT_2026-09-27.md`. Ele não substitui aquele documento; registra o estado final depois do merge da revisão transversal.

## Estado final

A1, A2, A3, A4, A5a, A6 e A7 estão concluídos. A revisão transversal final também está concluída.

A5b — recuperação de senha — permanece deliberadamente separada e bloqueada até existir uma autoridade canônica de e-mail transacional e um fluxo de token de uso único com validade curta. A ausência desse subsistema não invalida nem reabre os demais blocos.

### Revisão transversal final

O fechamento foi concluído no PR #133, merge `b8f968670e92a822fd4bf55cefc8c550bad9755e`.

O PR corrigiu dois defeitos encontrados apenas na leitura cruzada das rotas:

1. helpers de apresentação usados por `teste.php` e `teste-compartilhado.php` passaram para módulo compartilhado carregado pelo bootstrap;
2. ações de upload/remoção de mídia em Exposição e Revelação passaram a salvar os campos atuais da etapa antes da mutação da mídia, eliminando a janela de perda de dados digitados ainda não salvos.

A regressão adicionada trava a ordem `salvar etapa → mutar mídia` e o Playwright cobre o caso crítico de preencher Revelação, anexar a imagem do resultado e confirmar a persistência dos valores.

O deploy de produção do merge foi o run `36300681265` e concluiu com sucesso.

## Estado de instalação confirmado visualmente

O último estado efetivamente confirmado por captura do painel `Admin → Sistema e atualizações` continua sendo:

- **Instalado:** `67fe31d5f189`
- **Produção disponível:** `67fe31d5f189`
- timestamp exibido: `2026-09-27T03:40:41+00:00`

Os merges posteriores foram publicados no canal de atualização, mas publicação no canal não é tratada como prova de que a hospedagem os instalou.

## Próxima frente desbloqueada

Com a auditoria da Área do aluno encerrada, o trabalho volta para o editor/CMS no ponto em que havia sido pausado: **J4 — redução de autoridades concorrentes no front-end**.

A regra permanece a mesma: mapear as sobreposições reais, escolher uma autoridade por responsabilidade e retirar uma concorrência por PR, com regressão do runtime efetivo antes do merge.