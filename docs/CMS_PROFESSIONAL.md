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
- ponto focal visual;
- versões substituíveis e restauráveis sem quebrar referências estruturadas;
- derivados responsivos para imagens;
- poster de vídeo a partir de frame escolhido;
- rastreamento de uso nas páginas;
- seleção e ações em lote;
- arquivamento protegido quando o asset ainda está em uso;
- o vídeo do processo/revelação é parte da hierarquia editorial pública e deve preservar a mídia gerenciada já associada sempre que possível.

## Formulários

- formulários próprios armazenados no site;
- editor de campos com drag-and-drop, duplicação, largura, obrigatoriedade, ajuda e opções;
- prévia no painel;
- rascunho/publicação;
- respostas associadas ao formulário/página, status de acompanhamento, notas e CSV;
- checkbox e radio têm dimensão visual normalizada em 18 × 18 px no site público, preview e admin;
- no site público, largura, altura, mínimos, máximos e `flex-basis` desses controles são travados em 18 px para impedir que regras genéricas de `input` os façam ocupar a largura do grupo ou herdar a altura de campos de texto;
- essa normalização pertence à camada compartilhada `.cms-public`, portanto vale para todas as páginas públicas e para o preview do editor, não para uma página, locale ou formulário específico;
- assets CSS/JS são revalidados após atualização da aplicação para evitar que cache antigo masque correções visuais recém-instaladas.

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
