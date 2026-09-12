# CMS profissional

Estado atual da área administrativa do workshop.

## Administração editorial

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
- ponto focal visual;
- versões substituíveis e restauráveis sem quebrar referências estruturadas;
- derivados responsivos para imagens;
- poster de vídeo a partir de frame escolhido;
- rastreamento de uso nas páginas;
- seleção e ações em lote;
- arquivamento protegido quando o asset ainda está em uso.

## Formulários

- formulários próprios armazenados no site;
- editor de campos com drag-and-drop, duplicação, largura, obrigatoriedade, ajuda e opções;
- prévia no painel;
- rascunho/publicação;
- respostas associadas ao formulário/página, status de acompanhamento, notas e CSV.

## Princípio de operação

Alterações editoriais, visuais, estruturais e comerciais devem ser possíveis pela área administrativa. Código deve ser necessário apenas para criar novas capacidades do CMS.

## Atualizações

A branch de produção é `wip/form-response-refinement-2026-07-16`. O workflow valida PHP/JS, testes do CMS e o build, publica o pacote aprovado em `production-dist` e o painel `Sistema e atualizações` pode instalar o pacote preservando banco, uploads e configuração local.
