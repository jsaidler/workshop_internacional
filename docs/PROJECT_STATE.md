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

## Regra de escopo das correções

Defeitos que aparecem em várias páginas, idiomas ou instâncias de um mesmo componente são defeitos sistêmicos e devem ser corrigidos na camada compartilhada responsável pelo comportamento.

- Não corrigir um problema sistêmico com CSS, HTML, JavaScript ou conteúdo específico de uma página, locale, formulário ou bloco individual.
- Preferir regras globais de componente ou da aplicação que cubram todas as páginas que usam aquele elemento.
- Adicionar teste/regressão no mesmo nível de escopo da correção para impedir que o problema reapareça em outra página.
- Uma exceção só é aceitável quando o comportamento diferente daquela página for deliberado e documentado como tal.

Checkbox/radio públicos são normalizados na camada compartilhada `.cms-public`. Checkbox/radio administrativos são normalizados numa camada final compartilhada do admin (`assets/admin-controls.css`), carregada depois dos estilos administrativos normais. Nenhuma tela administrativa deve voltar a depender da geometria genérica de `input` para controles nativos de escolha.

A captura de 13/09 mostrou um detalhe importante: o círculo/quadrado nativo podia parecer pequeno, mas o elemento `input` continuava ocupando a largura inteira da linha por causa do CSS legado de campos de texto. O critério de correção não é apenas o diâmetro visível; a caixa do próprio `input` precisa estar efetivamente limitada.

Como atualizações de CSS/JS podem ficar mascaradas por cache antigo, o renderer público, o editor de páginas e o shell administrativo versionam seus assets locais com a versão instalada lida de `deploy-info.json`.

## Regra de edição de mídia no editor de páginas

Imagem e vídeo são tipos de mídia diferentes e não devem compartilhar o mesmo controle editorial como se fossem equivalentes.

- Imagens continuam usando o inspector de imagem: texto alternativo, ajuste, ponto focal e biblioteca de imagens.
- Qualquer elemento `<video>` dentro da página editável deve ser selecionável como vídeo, independentemente de ser um componente novo, o vídeo do processo, um vídeo legado ou possuir atributos editoriais antigos.
- A seleção de vídeo acontece sobre o próprio elemento `<video>`. O editor intercepta `pointerdown`/`click` em fase de captura, cancela a ação padrão dos controles nativos e interrompe a propagação antes que a seção ou o player processem a interação.
- Não inserir botão, overlay ou outro elemento editável sobre o vídeo para simular seleção.
- O inspector de vídeo é próprio e controla origem, troca por asset de vídeo, upload, URL externa e comportamento de reprodução.
- A biblioteca do controle de vídeo lista apenas vídeos. Capa/poster é propriedade separada do vídeo.
- O vídeo do processo usa exatamente o mesmo mecanismo global das demais páginas e componentes.

## Regra de edição de formulários

O formulário inserido numa página é conteúdo editorial da própria página e deve oferecer edição cotidiana sem obrigar o usuário a abandonar o editor WYSIWYG.

- Ao selecionar um formulário no editor da página, o inspector oferece uma **edição rápida** do formulário associado.
- A edição rápida cobre: texto do botão, rótulo dos campos, obrigatoriedade, placeholder nos tipos simples e, para listas/rádio/múltipla escolha, **rótulo visível e valor interno de cada opção**.
- O inspector salva pelo mesmo `cms-form-save.php` usado pelo editor completo e também pode publicar pelo `cms-form-publish.php`; não existe uma segunda fonte de verdade.
- Alterações estruturais — tipo, ordem, criação/remoção de campos, condições e fluxo após envio — continuam no editor completo, acessível como ação secundária a partir do inspector.
- A integração vale para qualquer formulário inserido em qualquer página/idioma; não existe tratamento específico para o formulário de inscrição.
- O editor completo de formulários deve priorizar a edição de campos. A antiga composição de três colunas apertadas é considerada regressão: configuração geral fica no topo, campos ocupam a área principal e a prévia fica separada.
- Lógica condicional e fluxo após envio são configurações avançadas e ficam recolhidas por padrão no editor completo, podendo ser abertas quando necessário.

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
- Checkbox e radio públicos permanecem normalizados em 18 × 18 px; no admin, a camada compartilhada final usa 16 × 16 px e trava largura, altura, mínimos e máximos para impedir herança de campos de texto.
- Todos os CSS/JS públicos carregados pelo renderer recebem `?v=<versão instalada>`; a mesma política é aplicada aos assets do editor e aos assets carregados pelo shell administrativo.
- A configuração Apache continua usando `Cache-Control: no-cache, must-revalidate` como defesa adicional.
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
