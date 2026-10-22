# Editor CMS — execução da auditoria transversal (21/10/2026)

## Limites e controle de entrega

Os achados de auditoria são tratados por unidades reversíveis, preservando o documento existente, o editor de formulários e seus leitores. Nenhuma etapa implica instalação automática na HostGator.

- PR #274: persistência, histórico, estados do documento, publicação por revisão, despublicação reversível, hierarchy Save/Publish e revisão responsiva.
- PR #275: edição de links inline, substituição de imagens dentro da moldura existente, uso imediato de uploads prontos, escolha explícita de itens de lista/galeria/carrossel, exclusividade do controlador de vídeo, ciência de alterações de audiência. Dependência técnica da #274.
- Não confundir suíte verde com aceite visual. Conferir desktop 1440, tablet 768, celular 390 e 320: barra, inspetor longo, página, seção, link, imagem, estados erro/salvando/rascunho/publicado e modal de mídia.

## Invariantes

1. `save()` retorna uma Promise que resolve somente após o ACK real da API e grava qualquer geração mais recente do documento, mantendo a revisão recebida.
2. Falha de rede/conflito não apaga a cópia editada em memória nem aciona reload. As saídas exibem uma advertência; o estado de erro não se confunde com sucesso.
3. Toda publicação precisa nomear e validar a revisão exata; retirada da publicação é reversível e preserva rascunho, curso, turma, aula e referências.
4. Link em texto recebe exatamente os mesmos controles de destino/target/remoção que link-botão. URL inválida não é aplicada. A remoção preserva o texto visível.
5. Inserção/substituição de imagem não cria figures aninhados nem descarta legenda ou styling da moldura preexistente.
6. Lista, galeria e carrossel permitem escolher qual item será removido ou reordenado, sem depender da posição final.
7. Vídeos têm um único inspetor efetivo (biblioteca de vídeos).
8. Alteração de audiência em página já publicada precisa informar que o efeito é imediato, mesmo se houver rascunho não publicado.

## Matriz de aceite automatizado

| Fluxo | Teste ou evidência |
|---|---|
| backend revisão stale e unpublish/republish | `tools/test-cms.php` |
| contrato canônico de integridade | `tools/test-editor-integrity-contract.php` |
| latência artificial entre duas edições | `tools/browser-tests/editor-editorial-integrity.spec.cjs` |
| seleção de imagem sem dirty | `tools/browser-tests/editor-editorial-integrity.spec.cjs` |
| publicação e retirada reversível | `tools/browser-tests/editor-editorial-integrity.spec.cjs` |
| edição de link inline e URL inválida | `tools/browser-tests/editor-editorial-integrity.spec.cjs` |
| inserção em figura com legenda | `tools/browser-tests/editor-editorial-integrity.spec.cjs` |
| remoção de item do meio | `tools/browser-tests/editor-component-items.spec.cjs` |
| screenshot mobile link e status | `tools/browser-tests/editor-editorial-integrity.spec.cjs` |
| screenshot administrativo 320/390/768/1440 e seleção | `admin-visual-audit` e inspeção humana do artefato |

## Pendências explícitas fora dessas duas PRs

- Migração da formatação `document.execCommand` para um modelo de rich text de seleção/cursor inteiramente próprio.
- Consolidação definitiva de todas as camadas de seleção/DnD/estrutura em um controlador central, com eliminação dos adaptadores históricos.
- Testes em duas sessões autenticadas e com uploads reais em armazenamento isolado (não apenas mocks de API).
- Validação contextual de links de âncora e navegação interna, acessibilidade por teclado e alcance dos controles em resoluções menores.
- Refinamento de UX do inspetor como drawer redimensionável, densidade tipográfica, estados de falha e carregamento reais.
- Verificação da publicação e revogação em instalação real, **apenas após instalação manual e autorização editorial**.

A entrega é considerada concluída somente após aprovação do commit exato por `validate`, `admin-visual-audit` e `deploy`, inspeção efetiva das capturas, merge sequencial das PRs e verificação dos manifestos da distribuição no SHA do merge. Não reportar itens pendentes como concluídos.
