# PROJECT STATE — Direct Positive Workshop CMS

Este é o documento canônico de estado operacional do projeto. Leia antes de alterar código, conteúdo, deploy ou fluxo administrativo. Atualize este arquivo sempre que uma decisão estrutural, operacional ou editorial mudar.

## Estado em 13/09/2026

- Branch de produção: `wip/form-response-refinement-2026-07-16`.
- O CI gera e valida o pacote de produção e publica o resultado na branch `production-dist`.
- A hospedagem NÃO é atualizada automaticamente pelo simples fato de `production-dist` ter sido publicada.
- O fluxo normal de atualização da aplicação hospedada é feito pelo próprio usuário em `Admin → Sistema e atualizações → Instalar atualização`.
- FTP/manual deploy é apenas bootstrap inicial ou contingência. Não é o fluxo operacional normal depois que o self-updater está instalado.
- O updater preserva `storage/database.sqlite`, `uploads/`, `config/local.php`, `config/install.php`, `storage/logs/` e `storage/updates/`, cria backup antes da troca e verifica checksums SHA-256.
- Migrações pendentes são executadas pelo bootstrap na primeira requisição após a atualização.

## Regra operacional obrigatória

Para uma alteração de código:

1. ler este documento e os documentos relacionados antes de modificar o projeto;
2. trabalhar em branch/PR quando a mudança for estrutural ou de comportamento;
3. manter CI verde;
4. integrar na branch de produção;
5. confirmar que `production-dist` foi gerada com o commit correto;
6. informar ao usuário que a nova versão está disponível no painel;
7. o usuário aplica a atualização em `Admin → Sistema e atualizações`;
8. só considerar a hospedagem atualizada depois dessa aplicação e da verificação da versão instalada.

Nunca confundir `production-dist` atualizada com hospedagem atualizada.

### Detecção do canal de produção

A tela `Sistema e atualizações` não pode depender de uma resposta possivelmente antiga do cache de `raw.githubusercontent.com` para decidir se existe versão nova.

- Toda consulta ao canal `production-dist` usa URL com token de cache (`?v=...`) e cabeçalhos `Cache-Control: no-cache` / `Pragma: no-cache`.
- A consulta de status usa token novo a cada verificação, portanto uma publicação recém-gerada deve ficar visível sem aguardar expiração do cache intermediário.
- Durante a instalação, o manifesto é lido uma única vez e o `sourceSha` desse manifesto passa a ser o token dos arquivos da mesma atualização.
- Antes da substituição final, `deploy-info.json` deve declarar o mesmo `sourceSha` do manifesto. Se o canal mudar durante o download, a instalação é abortada com `update_channel_changed` em vez de misturar versões.
- `tools/test-update-service.php` e `tools/test-update-restore.php` fazem parte da validação de PR e do deploy de produção.

## Regra de escopo das correções

Defeitos que aparecem em várias páginas, idiomas ou instâncias de um mesmo componente são defeitos sistêmicos e devem ser corrigidos na camada compartilhada responsável pelo comportamento.

- Não corrigir um problema sistêmico com CSS, HTML, JavaScript ou conteúdo específico de uma página, locale, formulário ou bloco individual.
- Preferir regras globais de componente ou da aplicação que cubram todas as páginas que usam aquele elemento.
- Adicionar teste/regressão no mesmo nível de escopo da correção para impedir que o problema reapareça em outra página.
- Uma exceção só é aceitável quando o comportamento diferente daquela página for deliberado e documentado como tal.

Checkbox e radio são um único componente nativo em três contextos: site público, preview/editor e administração. A geometria deve permanecer 18 × 18 px em todos eles. Regras genéricas de `input` não podem lhes impor largura total, altura de campo de texto, padding ou flex expansivo. A área administrativa aplica essa regra globalmente em `body.admin-page`, com limites mínimos e máximos e uma garantia estrutural no shell, não apenas no editor de formulários.

A captura de 13/09 mostrou um detalhe importante: o círculo/quadrado nativo podia parecer pequeno, mas o elemento `input` continuava ocupando a largura inteira da linha por causa do CSS legado de campos de texto. O sintoma visual era o marcador centralizado e o texto empurrado para a direita. Portanto o critério de correção não é apenas o diâmetro visível; a caixa do próprio `input` precisa estar efetivamente limitada a 18 × 18 px.

Como uma atualização de CSS pode ficar mascarada por cache antigo do navegador, os renderers público, editor e admin versionam seus assets com a versão instalada (`deploy-info.json`). Invariantes visuais críticos podem ter uma regra estrutural inline no shell correspondente, sempre em escopo global da aplicação e nunca por página específica.

## Regra de edição de mídia no editor de páginas

Imagem e vídeo são tipos de mídia diferentes e não devem compartilhar o mesmo controle editorial como se fossem equivalentes.

- Imagens continuam usando o inspector de imagem: texto alternativo, ajuste, ponto focal e biblioteca de imagens.
- Qualquer elemento `<video>` dentro da página editável deve ser selecionável como vídeo, independentemente de ser um componente novo, o vídeo do processo, um vídeo legado ou possuir atributos editoriais antigos.
- A seleção de vídeo deve acontecer sobre o próprio elemento `<video>`. O editor intercepta `pointerdown`/`click` em fase de captura, cancela a ação padrão dos controles nativos e interrompe a propagação antes que a seção ou o player processem a interação.
- Não inserir botão, overlay ou outro elemento editável sobre o vídeo para simular seleção.
- O inspector de vídeo é próprio e controla origem, biblioteca de vídeos, upload, URL externa e comportamento de reprodução.
- A capa/poster é propriedade separada do asset de vídeo; editar capa não equivale a trocar o vídeo.
- O controlador dedicado de vídeo deve ser carregado antes dos hooks legados do `cms-pro-editor.js`.

## Regra de integração dos formulários com o editor da página

O editor completo de formulários continua existindo para operações estruturais, mas ajustes editoriais cotidianos não devem obrigar o usuário a abandonar o editor da página.

- Ao selecionar um formulário renderizado na página, o inspector oferece uma edição rápida do formulário correspondente.
- Quando o clique parte de um campo real renderizado, o editor tenta identificar esse campo pelo `name` e abre diretamente suas propriedades rápidas.
- A edição rápida permite alterar o rótulo do campo, o texto do botão e, para listas/rádio/múltipla escolha, o rótulo e o valor interno de cada opção.
- Alterar valor interno deve mostrar aviso porque condições existentes podem depender dele.
- A edição rápida salva pelo mesmo `cms-form-save.php` usado pelo editor completo e pode publicar explicitamente pelo `cms-form-publish.php`; não deve criar uma segunda estrutura de armazenamento.
- Tipo, identificador, ordem, criação/remoção de campos, obrigatoriedade, lógica condicional e fluxo após envio permanecem no editor completo. O inspector deve oferecer link direto para ele.
- O preview do editor da página usa o rascunho do formulário; após salvar o formulário, o preview deve ser recarregado preservando, quando possível, o formulário/campo que estava sendo editado.

## Regra de documentação obrigatória

Depois de cada mudança relevante:

- atualizar este arquivo quando o estado canônico mudar;
- atualizar `docs/CMS_PROFESSIONAL.md` quando mudar arquitetura ou comportamento do admin/CMS;
- atualizar `docs/CMS_V3_DEPLOYMENT.md` e `DEPLOY.md` quando mudar build, atualização ou deploy;
- atualizar `README.md` quando mudar a visão geral, arquivos canônicos ou fluxo principal;
- não criar documentos paralelos para substituir decisões vigentes sem necessidade; corrigir o documento canônico existente.

## Estado editorial público

Português e inglês são documentos editoriais independentes.

### PT-BR

Objetivo atual: converter uma nova turma em português.

Hierarquia vigente da home:

1. workshop;
2. processo/revelação, com o vídeo do processo;
3. prova da primeira turma;
4. equipamento/NINA como ferramenta da pesquisa, não como tema central;
5. suporte incluído;
6. formato;
7. trajetória de João Saidler;
8. inscrição.

### EN

Objetivo atual: formar interesse para a primeira turma em inglês.

Hierarquia vigente:

1. workshop;
2. processo/revelação;
3. apparatus;
4. format;
5. research and photographer;
6. prova da turma em português;
7. interest list.

A página inglesa não deve ser mera tradução da brasileira.

## Estado do CMS/admin

- `/admin/` é dashboard. Não deve redirecionar diretamente para o editor.
- O editor da home é uma ação do dashboard, não a própria página inicial administrativa.
- Dashboard atual: publicação, alterações pendentes, novas inscrições, páginas, formulários, mídia, armazenamento/saúde e atividade recente.
- Checkbox e radio devem manter 18 × 18 px no site público, preview e em toda a administração.
- O shell administrativo carrega CSS/JS locais com `?v=<versão instalada>` e mantém a geometria crítica de checkbox/radio independentemente de cache de stylesheet.
- O editor completo de formulários não deve comprimir configuração, lista de campos, propriedades e preview em colunas concorrentes. Configuração fica em faixa superior; lista de campos e propriedades recebem o espaço principal; preview fica em bloco separado abaixo.
- O editor da página possui edição rápida do formulário para rótulos, texto do botão e rótulo/valor de opções, usando as APIs canônicas do formulário.
- Todos os CSS/JS públicos carregados pelo renderer recebem `?v=<versão instalada>`; a mesma política vale para editor e admin.
- A configuração Apache continua usando `Cache-Control: no-cache, must-revalidate` para `.css` e `.js` como defesa adicional.
- Alterações editoriais, visuais, estruturais e comerciais normais devem ser possíveis pelo CMS. Código deve ser necessário para novas capacidades, não para operação editorial cotidiana.

## Conteúdo e pesquisa que não devem regredir

- O processo/revelação tem precedência editorial sobre a NINA.
- O vídeo do processo deve permanecer associado à seção de processo; o CMS tenta preservar/reconciliar a mídia gerenciada existente.
- A apresentação de João deve explicitar quase oito anos de pesquisa em fotografia química e que ele projeta/constrói câmeras de grande formato, obturadores, tanques e sistemas de processamento.
- A comunicação pública não deve expor fórmulas, receitas internas ou detalhes químicos que foram definidos como conteúdo reservado ao workshop.

## Documentos que devem ser lidos antes de trabalhar

1. `docs/PROJECT_STATE.md` — estado canônico e fluxo operacional;
2. `docs/CMS_PROFESSIONAL.md` — capacidades e princípios do CMS;
3. `docs/CMS_V3_DEPLOYMENT.md` — self-update e persistência;
4. `README.md` — arquitetura geral;
5. `DEPLOY.md` — bootstrap, fallback e atualização;
6. `AGENTS.md` — regras de operação no repositório.
