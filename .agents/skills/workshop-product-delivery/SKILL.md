---
name: workshop-product-delivery
description: "Transforme expectativas de produto do Workshop CMS em alterações completas, testadas e revisáveis. Use quando o usuário pedir para criar, melhorar, corrigir ou evoluir funcionalidades no site público, CMS, administração ou área dos alunos."
---

# Execução autônoma de produto

O usuário descreve o resultado; você responde pela engenharia ponta a ponta. **Não transforme uma solicitação de implementação em mera auditoria ou entrega de arquivos Markdown.** Nunca afirme sucesso sem evidência dos comportamentos solicitados.

## Entrada e descoberta

1. Consulte `AGENTS.md`, `docs/GLOBAL_DOCUMENTATION_CONTRACT_2026-10-07.md`, `docs/PROJECT_STATE.md` e os demais contratos aplicáveis na ordem canônica. Consulte o código e os testes existentes, além de PRs/CI quando a conexão estiver disponível. Distingua fonte normativa atual de documento histórico.
2. Inspecione `git status`, branch, HEAD, remotes e modificações pendentes. Preserve trabalho preexistente. Continue uma implementação já em andamento na sua branch, quando pertinente, em vez de reiniciá-la. Em caso de worktree suja, crie outra worktree isolada somente se isso preservar integralmente as alterações.
3. Traduza a expectativa do usuário em jornadas, invariantes, estados e critérios de aceite verificáveis. Formule as hipóteses necessárias com base no produto; pergunte apenas por decisões de produto insubstituíveis. Não peça prompts técnicos.
4. Localize as autoridades canônicas de PHP/SQLite/CMS, editor, turma/curso/matrícula, laboratório e comunicações. Considere bibliotecas ou ferramentas maduras quando forem realmente superiores; evite novas implementações paralelas e refatorações sem relação com a necessidade.
5. Antes de codificar, escolha a menor solução sistêmica que atende ao pedido. Segurança, migrações, permissões, concorrência, semântica dos dados, acessibilidade e reversibilidade entram no desenho quando afetados.

## Realização e verificação

6. Implemente a causa raiz e as rotas envolvidas. Atualize contratos canônicos no mesmo diff; evite documentos de status redundantes. Preserve credenciais, dados e conteúdo existentes.
7. Acrescente testes que **reproduzam a falha anterior** e protejam o resultado esperado: sucesso, erro, conflitos, dados legados, rollback, autorização, idempotência e efeitos colaterais conforme o caso. Use exclusivamente SQLite/usuários/arquivos sintéticos.
8. Rode testes focados, regressões adjacentes e gates relevantes do CI. Investigue falhas e corrija as de sua responsabilidade. Falha preexistente exige evidência verificável de que também ocorre no baseline, não mera afirmação.
9. Para UI/UX, use a skill `workshop-browser-qa`: renderização real, fluxos operacionais, estados, teclado, viewport móvel/desktop, screenshots abertos e comparação visual. Teste em fixture local e navegador real; ausência de navegador ou permissão é bloqueio a resolver, não aprovação.
10. Para mudanças distribuíveis, use `workshop-release-readiness`: exercite build, manifestos, integridade e updater. Não inclua dados ou credenciais de instalação no pacote.
11. Revise o próprio diff contra cada critério e o estado do Git. Quando útil, convoque um revisor independente com escopo delimitado. Não multiplique agentes ou releia todo o repositório sem necessidade.

## Entrega sem intermediários

12. Crie commit(s) claros na branch isolada. Faça push **somente dessa branch**, sem force-push destrutivo. Abra ou atualize PR em rascunho contra `wip/form-response-refinement-2026-07-16`, usando `gh` se autenticado; associe critérios de aceite, testes, evidências de UI, riscos residuais e link do artefato CI.
13. Monitore os checks aplicáveis de GitHub Actions, corrija falhas atribuíveis ao trabalho e atualize o PR. Se autenticação ou rede impedir GitHub, investigue e tente recuperar acesso seguro; exponha bloqueio preciso se persistir, sem declarar a entrega sincronizada.
14. Conclua com mensagem curta **em linguagem de produto**: o que mudou, como foi comprovado, estado do PR, estado da atualização instalável e única ação de autorização necessária. Registros técnicos ficam no PR, não em anexos que o usuário precise transportar.

## Limites de autoridade

- Sem merge, alteração de `production-dist`, execução contra HostGator, banco real, envio de e-mails ou instalação em produção sem permissão expressa.
- Nunca confunda pacote gerado no PR com publicação no canal de produção nem publicação no canal com instalação efetiva.
- Quando o usuário pedir somente avaliação, planejamento ou revisão, entregue apenas isso, sem implementar silenciosamente.
- Não invente resultados, testes, screenshots, capacidade de ferramentas ou aprovação. Bloqueios externos reais precisam ser descritos com precisão.
