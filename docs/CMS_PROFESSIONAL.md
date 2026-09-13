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
- CSS adicional como recurso avançado, sem ser necessário para a operação normal.

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
- o inspector de vídeo controla origem, asset da biblioteca, upload, URL externa e reprodução;
- a biblioteca de troca mostra exclusivamente `MediaLibrary.videos`;
- capa/poster aparece como propriedade separada do vídeo;
- o mesmo controle atende todas as páginas, idiomas e componentes.

## Formulários

- formulários próprios armazenados no site;
- editor de campos com drag-and-drop, duplicação, largura, obrigatoriedade, ajuda e opções;
- prévia no painel;
- rascunho/publicação;
- respostas associadas ao formulário/página, status de acompanhamento, notas e CSV.

### Edição rápida no editor da página

Selecionar um formulário inserido numa página abre, no próprio inspector do editor WYSIWYG, uma versão simplificada do editor de formulário.

Essa edição rápida permite alterar sem sair da página:

- texto do botão de envio;
- rótulo de cada campo;
- obrigatoriedade;
- placeholder dos campos simples;
- para lista, rádio e múltipla escolha: rótulo visível e valor interno de cada opção;
- salvar como rascunho ou salvar e publicar.

A edição rápida usa as mesmas APIs e o mesmo schema do editor completo. Não existe cópia local do formulário nem segunda fonte de verdade. Alterações estruturais — tipo, ordem, criação/remoção de campo, lógica condicional e fluxo após envio — continuam no editor completo, acessível a partir do inspector.

### Editor completo de formulários

O editor completo existe para trabalho estrutural, não para obrigar o usuário a sair da página quando precisa apenas corrigir texto ou valor de opção.

- configuração geral fica no topo;
- lista de campos e propriedades ocupam a área principal;
- a prévia fica separada, sem esmagar lista e inspector em três colunas estreitas;
- lógica condicional e fluxo após envio ficam recolhidos por padrão e são abertos somente quando necessários.

### Controles nativos

Checkbox e radio são tratados como controles nativos, não como inputs de texto.

- no site público, largura, altura, mínimos, máximos e `flex-basis` ficam travados em 18 × 18 px na camada compartilhada `.cms-public`;
- no admin, uma camada final compartilhada (`assets/admin-controls.css`) trava checkbox/radio em 16 × 16 px depois de todos os estilos administrativos normais;
- isso vale para todas as páginas administrativas, inclusive a prévia do editor de formulários;
- o critério visual inclui a caixa efetiva do elemento, não apenas o círculo/quadrado desenhado pelo navegador.

## Cache e assets administrativos

O shell administrativo versiona CSS/JS locais com `?v=<versão instalada>`, derivado de `deploy-info.json`. Assim, uma atualização de aplicação troca também os URLs dos assets administrativos e não depende de o navegador perceber sozinho que o CSS mudou.

A mesma política já vale para o site público e para o editor de páginas. `Cache-Control: no-cache, must-revalidate` permanece como defesa adicional.

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

`production-dist` atualizada não significa hospedagem atualizada. Só considerar a instalação remota atual depois que a atualização for aplicada pelo painel e a versão instalada for verificada.
