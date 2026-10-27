---
name: workshop-release-readiness
description: "Prepare e valide o artefato de atualização do Workshop CMS, seu manifesto e os testes do self-updater, respeitando o fluxo GitHub Actions production-dist e a instalação administrativa na HostGator."
---

# Entrega instalável no canal existente

Leia `DEPLOY.md`, `docs/CMS_V3_DEPLOYMENT.md`, `.github/workflows/deploy.yml`, `tools/build-dist.php`, `tools/write-deploy-manifest.php` e o contrato de estado persistente antes de empacotar.

## Pré-release em branch de trabalho

1. Verifique que a implementação, regressões afetadas e documentação canônica estejam completos. Rode `php tools/test-build-dist.php`, `php tools/test-update-service.php`, `php tools/test-update-restore.php` e testes adjacentes; aplique `build-dist.cmd` em Windows ou `php tools/build-dist.php` seguido de `SOURCE_SHA=<sha> php tools/write-deploy-manifest.php dist` onde a variável puder ser definida de forma equivalente.
2. Valide `dist/`, `deploy-manifest.json`, `deploy-info.json`, tamanhos e checksums, de acordo com o contrato do build. Teste que não há `config/local.php`, `storage/database.sqlite`, `storage/installed.lock`, uploads privados, tokens, logs ou outras informações persistentes. Não reimplemente empacotamento já canônico.
3. Quando houver mudanças de migração, teste caminho limpo e atualização de banco sintético antigo, incluindo rollback e invariantes de schema/dados. Migrações com efeitos de filesystem ou transações próprias exigem análise específica; não presumir que um savepoint universal as torna seguras.
4. Comite e sincronize a branch isolada; abra PR em rascunho. O workflow `.github/workflows/deploy.yml` roda em PR para produção e publica artefato `workshop-dist-<sha>` (retenção limitada). Confirme o run, o `sourceSha`, a disponibilidade do artefato e seus checks, ou declare precisamente o que falta. Revise alterações de distribuição e scripts críticos antes de declarar pronto.

## Portas de liberação

- **Pronto para revisão:** código, testes, PR em rascunho, build validado e artefato CI identificado; nenhuma publicação no canal de produção.
- **Pronto para instalar no painel:** PR aprovado e integrado com autorização; workflow na branch `wip/form-response-refinement-2026-07-16` passou e publicou `production-dist` com o commit pretendido. Confira manifesto/sourceSha no canal. Não confunda aprovação do PR com versão distribuída.
- **Instalado em produção:** somente após operação do responsável em `Admin → Sistema e atualizações → Instalar atualização` e confirmação da versão instalada/saúde da aplicação. Nunca declare instalado com base apenas em GitHub ou CI.

## Proteção

- Nunca execute merge, modifique `production-dist` diretamente, acesse HostGator, altere banco real ou instale update sem autorização expressa.
- Não peça FTP ou ZIP manual quando o self-updater já está disponível. Se ele estiver indisponível, diagnostique e apresente a menor contingência segura para aprovação.
- Não sacrifique integridade ou invente teste/artefato por pressa; reporte o bloqueio exato com o trabalho preservado para retomada.
