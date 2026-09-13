# CMS profissional

Estado atual da área administrativa do workshop. O estado operacional canônico do projeto está em `docs/PROJECT_STATE.md` e deve ser lido antes de qualquer alteração.

## Administração editorial

- `/admin/` é o dashboard administrativo; não redireciona diretamente para o editor;
- o dashboard concentra estado de publicação, alterações pendentes, novas inscrições, páginas, formulários, mídia, armazenamento/saúde, atividade recente e atalhos;
- editar a home é uma ação do painel, não o destino automático da administração;
- páginas independentes PT/EN com rascunho, publicação e histórico restaurável;
- editor visual com biblioteca de componentes, grades livres e controles de layout;
- blocos reutilizáveis salvos a partir de seções do próprio editor;
- gerenciamento separado de navegação, header, footer e identidade;
- sistema visual global com cores, tipografia, medidas, espaçamento e botões;
- prévia ao vivo do design em desktop, tablet e celular;
- CSS adicional como recurso avançado, sem ser necessário para a operação normal;
- CSS e JavaScript locais da administração usam a versão instalada no URL, evitando que uma atualização correta continue escondida por cache antigo.

## Mídia

- imagens e vídeos em biblioteca própria;
- metadata, texto alternativo, legenda, descrição e tags;
- ponto focal visual para imagens;
- versões substituíveis e restauráveis sem quebrar referências estruturadas;
- derivados responsivos para imagens;
- poster de vídeo a partir de frame escolhido;
- rastreamento de uso nas páginas;
- seleção e ações em lote;
- arquivamento protegido quando o asset ainda está em uso;
- o vídeo do processo/revelação é parte da hierarquia editorial pública e deve preservar a mídia gerenciada já associada sempre que possível.

### Controles de imagem no editor de páginas

Imagem usa um inspector próprio com texto alternativo, ajuste (`cover`/`contain`), ponto focal e biblioteca de imagens. Substituir uma imagem abre somente a coleção de imagens.

### Controles de vídeo no editor de páginas

Vídeo não reutiliza o inspector nem o seletor de imagem.

- qualquer elemento `<video>` da página é editável, inclusive vídeos antigos e o vídeo do processo, sem depender de um atributo editorial específico;
- o próprio `<video>` é a superfície de seleção: o editor intercepta `pointerdown` e `click` em captura, executa `preventDefault()` e `stopImmediatePropagation()` e abre o inspector antes que os controles nativos ou a seção processem a interação;
- não existe botão/overlay `Editar vídeo` inserido por cima do player;
- o inspector de vídeo controla origem, biblioteca, upload, URL externa e reprodução;
- capa/poster aparece como propriedade separada do vídeo;
- o mesmo controle atende todas as páginas, idiomas e componentes;
- o controlador dedicado de vídeo é carregado antes dos hooks legados de `cms-pro-editor.js`;
- CSS e JavaScript do editor recebem `?v=<versão instalada>` na entrada autenticada `editor/index.php`.

## Formulários

- formulários próprios armazenados no site;
- editor completo de campos com drag-and-drop, duplicação, largura, obrigatoriedade, ajuda e opções;
- prévia no painel;
- rascunho/publicação;
- respostas associadas ao formulário/página, status de acompanhamento, notas e CSV;
- checkbox e radio têm geometria global de 18 × 18 px em site público, preview e administração; nenhum stylesheet administrativo pode tratá-los como campos de texto;
- a administração reforça largura, altura, mínimos e máximos de checkbox/radio no shell, além do CSS compartilhado, para não depender de cache antigo;
- o editor completo de formulários prioriza a tarefa de edição: configurações gerais ficam acima, lista de campos e propriedades recebem a área principal e a prévia fica em bloco separado, em vez de quatro áreas comprimidas lado a lado;
- no editor visual da página, o formulário não é tratado como uma entidade textual única nem exige escolher um campo em um seletor lateral;
- rótulos de campo, rótulos de opções, textos de ajuda e texto do botão são alvos editoriais independentes na própria prévia: o usuário clica exatamente no texto exibido e edita `contenteditable` no lugar, como nos demais textos da página;
- inputs, selects, textareas, checkboxes e radios também são subalvos selecionáveis como contexto do campo; clicar neles não deve transformar o interior do formulário em área morta;
- todos os subalvos são preparados quando o documento do iframe é instalado, e o controlador também tenta instalar-se imediatamente quando o documento já existe, evitando depender exclusivamente de um próximo evento `load`;
- a interação de um subalvo é capturada antes do clique do bloco do formulário, para que o wrapper não roube a seleção daquele elemento específico;
- o wrapper do formulário não pode cancelar genericamente todo `pointerdown`/`click` de seus descendentes. Somente um subalvo reconhecido recebe interceptação; os demais eventos continuam para o editor normal;
- o cursor é posicionado no ponto clicado; `Enter` conclui a edição e `Esc` cancela a alteração em curso;
- alterações visuais são gravadas como rascunho do formulário pela API canônica `cms-form-save.php`; a publicação continua explícita por `cms-form-publish.php`;
- propriedades que não aparecem na página continuam contextuais: ao clicar uma opção, por exemplo, o inspector pode mostrar o valor interno daquela opção, sem duplicar o rótulo visível em um segundo campo de texto;
- o inspector não deve oferecer um dropdown para navegar pelos campos do formulário nem reproduzir os textos visíveis em caixas de edição paralelas;
- alterações estruturais — tipo, identificador, ordem, criação/remoção, obrigatoriedade, lógica condicional e fluxo após envio — permanecem no editor completo, acessível por link direto no inspector;
- alterar o valor interno de uma opção exibe aviso porque regras condicionais podem depender desse valor;
- a edição visual usa o mesmo schema e as mesmas APIs do editor completo; não existe uma segunda estrutura ou banco de formulário.

## Princípio de correção sistêmica

Quando um defeito aparece em várias páginas, idiomas ou instâncias de um componente, a correção deve ser feita na camada compartilhada correspondente. Não usar exceções pontuais por página, ID, locale ou formulário para mascarar um problema de base. A regressão também deve ser testada no mesmo nível global da correção.

## Independência editorial PT/EN

Português e inglês são documentos editoriais independentes.

- PT-BR: página orientada à inscrição de uma nova turma em português, com prova da primeira turma e suporte incluído.
- EN: página orientada à formação de interesse para a primeira turma em inglês, com maior peso para processo e pesquisa e sem reproduzir automaticamente a estrutura comercial brasileira.

O processo/revelação deve ter precedência editorial sobre a NINA. A câmera é apresentada como ferramenta desenvolvida dentro da pesquisa, não como assunto central do workshop.

## Princípio de operação

Alterações editoriais, visuais, estruturais e comerciais normais devem ser possíveis pela área administrativa. Código deve ser necessário apenas para criar novas capacidades do CMS.

## Atualizações

A branch de produção é `wip/form-response-refinement-2026-07-16`. O workflow valida PHP/JS, testes do CMS e o build e publica o pacote aprovado em `production-dist`.

O fluxo operacional normal depois do bootstrap é:

1. integrar a alteração na branch de produção;
2. confirmar que `production-dist` foi gerada corretamente;
3. o usuário abre `Admin → Sistema e atualizações`;
4. o painel identifica a versão disponível;
5. o usuário executa `Instalar atualização`;
6. o updater preserva banco, uploads, configuração local e logs e cria backup antes da substituição;
7. a próxima requisição executa migrações pendentes.

A detecção de versão remota não pode depender de cache do `raw.githubusercontent.com`. O painel usa um token novo no URL de `deploy-info.json` e cabeçalhos `no-cache` a cada consulta. Durante uma instalação, o `sourceSha` do manifesto identifica os downloads daquela release e `deploy-info.json` precisa confirmar o mesmo SHA antes da troca final. Se o canal mudar durante o processo, a atualização é abortada em vez de misturar versões.

`production-dist` atualizada não significa hospedagem atualizada. Só considerar a instalação remota atual depois que a atualização for aplicada pelo painel e a versão instalada for verificada.
