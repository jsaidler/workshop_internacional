# Editor — nomes internos dos elementos

Elementos estruturais podem receber um **Nome no editor** independente do conteúdo público.

## Objetivo

Em páginas simples, `Imagem`, `Contêiner` ou as primeiras palavras de um parágrafo são suficientes para reconhecer um elemento. Em páginas maiores isso deixa de funcionar: várias imagens, colunas e contêineres passam a ter o mesmo nome e a árvore perde valor como instrumento de navegação.

Por isso, qualquer componente, contêiner ou coluna pode receber `data-cms-editor-label`.

## Contrato

- O campo público chama-se **Nome no editor**.
- É opcional.
- O valor serve somente para organização e localização no editor.
- O nome não é inserido como texto visível no site.
- A árvore `Estrutura`, a busca estrutural e o breadcrumb usam o nome personalizado quando ele existe.
- Apagar o campo restaura automaticamente o nome derivado do tipo ou do conteúdo.
- A alteração usa a mesma página e o mesmo HTML canônico; não existe cadastro paralelo de nomes.
- Duplicar um elemento preserva seu nome interno até que o editor o altere.

## Prioridade de rótulo

Para elementos estruturais, a ordem é:

1. `data-cms-editor-label`, quando preenchido;
2. nome semântico do tipo (`Imagem`, `Galeria`, `Grupo de colunas`, `Coluna`, etc.);
3. trecho do conteúdo textual, quando esse conteúdo é mais informativo que o nome genérico.

Para parágrafos, títulos e outros blocos textuais sem nome personalizado, o texto continua sendo usado para facilitar reconhecimento imediato.

## Atualização da interface

Alterar o nome deve atualizar imediatamente:

- a árvore `Estrutura`;
- resultados da busca estrutural;
- breadcrumb do elemento selecionado.

A gravação acontece pela API normal da página. Nenhuma alteração de nome exige publicação imediata; continua valendo a distinção entre rascunho e versão publicada.

## Regressão

Os testes de navegador devem garantir que:

- o campo aparece para um elemento estrutural selecionado;
- o nome é persistido no elemento;
- o nome não vira conteúdo visível da página;
- a árvore passa a exibi-lo;
- a busca encontra o elemento pelo nome;
- o breadcrumb usa o mesmo nome.
