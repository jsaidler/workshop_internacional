# CMS profissional

Estado atual da área administrativa e do fluxo comercial do workshop em 13/09/2026.

## Administração editorial

- páginas independentes PT/EN com rascunho, publicação e histórico restaurável;
- editor visual com biblioteca de componentes, grades livres e controles de layout;
- blocos reutilizáveis salvos a partir de seções do próprio editor;
- gerenciamento separado de navegação, header, footer e identidade;
- sistema visual global com cores, tipografia, medidas, espaçamento e botões;
- prévia ao vivo do design em desktop, tablet e celular;
- CSS adicional como recurso avançado, sem ser necessário para a operação normal.

A composição editorial aprovada deve continuar com a linguagem visual já existente. Títulos multilinha não devem usar `line-height` abaixo de `1`; o valor de referência do sistema é `1.02`. Compressão tipográfica deve ser feita por largura, tamanho, tracking ou layout, e não por sobreposição das linhas.

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

### Inscrição brasileira: fonte canônica

O formulário brasileiro deve reproduzir o conteúdo necessário do Google Form já usado para a inscrição. Não deve coletar perguntas de marketing, perfil pedagógico ou equipamento que não existam nesse formulário. Foram removidos campos inventados como experiência analógica, formato/equipamento, observações gerais e preferência genérica de pagamento.

Campos atuais da inscrição:

1. Nome Completo;
2. CPF — para emissão de NF;
3. Whatsapp com DDD;
4. E-mail;
5. Instagram;
6. tamanho do suporte: `4x5"` ou `5x7"`;
7. Endereço completo;
8. Cidade/UF;
9. CEP;
10. disponibilidade para a turma;
11. Forma de pagamento;
12. aceite das condições de inscrição e participação.

Disponibilidades atuais:

- Terças, 19h — 6, 13 e 20 de outubro;
- Quintas, 19h — 8, 15 e 22 de outubro;
- Sábados, 9h — 3, 10 e 24 de outubro;
- Sábados, 14h — 3, 10 e 24 de outubro.

A pessoa pode marcar mais de uma opção. A turma é formada pelo maior cruzamento de disponibilidade entre participantes com pagamento confirmado. Uma turma adicional só deve ser aberta com pelo menos 3 participantes com pagamento confirmado e disponibilidade comum.

### Pagamento

Valor: **R$ 698,00**.

Opções apresentadas no próprio fluxo de inscrição:

- PIX — R$ 698,00;
- Cartão de Crédito à vista — R$ 698 + taxas do Mercado Pago;
- Cartão de Crédito Parcelado — R$ 698 + taxas do Mercado Pago.

Pix:

- chave: `20.179.548/0001-58`;
- copia e cola: `00020101021126690014br.gov.bcb.pix0114201795480001580229INSCRICAO MINI CURSO SETEMBRO5204000053039865406698.005802BR592020 1 5 J V T SAIDLER6010PETROPOLIS62070503***6304314B`.

Cartão de crédito usa exclusivamente o link do Mercado Pago:

`https://mpago.la/1xvBsPV`

O envio do formulário não reserva vaga. A inscrição só é considerada confirmada quando o pagamento é confirmado.

## Operação administrativa das inscrições

Para inscrições brasileiras, a área administrativa deve ser deliberadamente simples. O trabalho necessário depois da submissão é:

- consultar os dados enviados;
- ver o método de pagamento escolhido;
- registrar uma informação curta sobre o pagamento, quando necessário;
- usar **Confirmar inscrição e pagamento** para mudar a inscrição de `Aguardando pagamento` para `Confirmada`;
- poder voltar para `Aguardando pagamento` ou cancelar a inscrição.

Não há necessidade de transformar esse fluxo em CRM. A migração 020 adiciona `payment_status`, `payment_confirmed_at` e `payment_note` a `cms_form_submissions` para sustentar apenas esse controle.

## Conteúdo do workshop que afeta a inscrição

A página de inscrição deve ser curta e funcional, mas precisa manter estas informações essenciais:

- workshop on-line e ao vivo, em 3 encontros;
- primeiro encontro: filme de raio-X, exposição e decisões anteriores à revelação;
- segundo encontro: fotografia e processamento ao vivo, produzindo o maior número de imagens possível e variando deliberadamente exposição e parâmetros para comparar resultados — não reduzir esse encontro a uma simples “demonstração”;
- entre o segundo e o terceiro encontro, os participantes fazem seus próprios testes;
- terceiro encontro: análise dos resultados produzidos pelos participantes e das decisões para os testes seguintes;
- cada participante da turma brasileira recebe o suporte dobrável desenvolvido especificamente para reduzir o contato da dupla emulsão do filme de raio-X com a bandeja durante o processamento.

## Estado conhecido do editor

Foi observado no uso real o erro JavaScript `Cannot set properties of null (setting 'textContent')` ao tentar editar uma página. Mudanças no editor devem continuar evitando pressupor que alvos de UI existem antes de escrever em `textContent` ou propriedades equivalentes. Não considerar esse tipo de regressão encerrado sem teste real de abrir página, selecionar conteúdo, editar, salvar, recarregar e publicar.

## Princípio de operação

Alterações editoriais, visuais, estruturais e comerciais devem ser possíveis pela área administrativa. Código deve ser necessário apenas para criar novas capacidades do CMS. No fluxo de inscrição, o sistema deve pedir somente o que é necessário para inscrição, envio do suporte, formação da turma e pagamento.

## Atualizações

A branch de produção continua sendo `wip/form-response-refinement-2026-07-16`. O workflow valida PHP/JS, testes do CMS e o build, publica o pacote aprovado em `production-dist` e o painel `Sistema e atualizações` pode instalar o pacote preservando banco, uploads e configuração local.

A atualização de paridade com o Google Form está sendo preparada na branch `fix/registration-google-form-parity-2026-09-13`, com a migração 020 e cobertura de regressão específica do fluxo de inscrição/pagamento.
