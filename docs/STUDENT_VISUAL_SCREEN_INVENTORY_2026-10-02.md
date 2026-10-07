# Área do aluno — inventário obrigatório de telas — revisão de 05/10/2026

Este inventário operacionaliza a regra de que nenhuma tela da área do aluno pode ser presumida correta. Toda tranche deve renderizar e inspecionar as superfícies visuais abaixo em desktop e mobile.

A revisão de 05/10/2026 incorpora o contrato não linear do Caderno. Modos visuais separados de “ao vivo”, “já realizado”, “retrospectivo” ou “próximas etapas” deixam de ser superfícies de produto: o Caderno não precisa saber quando a operação física aconteceu.

## Superfícies visuais

| Rota / domínio | Estados mínimos no audit |
|---|---|
| `aluno/login.php` | entrada e ativação inicial |
| `aluno/index.php` | Caderno em andamento; retorno do professor pendente; uma matrícula sem pendência no Caderno e com material utilizável; múltiplas matrículas |
| `aluno/cursos.php` | múltiplas matrículas; curso/turma com material disponível, parcial e agendado; curso com acompanhamento pedagógico pendente; curso sem material |
| material do curso (CMS autenticado) | leitura parcial com seções de aulas diferentes filtradas no servidor; contexto curso/turma; anterior/próximo preservando `cohort`; anotações; telefone estreito |
| `aluno/duvidas.php` | lista; lista com conversa de avaliação contextual; nova dúvida; conversa |
| `aluno/caderno.php` | lista; novo registro; registro derivado com origem e intenção de pesquisa |
| `aluno/teste.php` | registro vazio; exposição isolada; processamento sem roteiro; roteiro associado com zero checks; conjunto arbitrário de checks; resultado independente; avaliação aguardando retorno, revisão solicitada e avaliação concluída |
| `aluno/registro-roteiro.php` | associação inicial e alteração de roteiro a partir do próprio registro, sem iniciar processamento, impor ordem ou movimentar estoque |
| `aluno/teste-etapa.php` | edição de etapa livre sem afetar outras etapas ou estoque |
| `aluno/teste-compartilhado.php` | registro compartilhado |
| `aluno/excluir-teste.php` | confirmação destrutiva |
| `aluno/comparar-processos.php` | um registro já escolhido + escolha do segundo; comparação analítica com diferenças registradas, resultados e continuidade |
| `aluno/processamentos.php` | biblioteca e editor de roteiros reutilizáveis |
| `aluno/processar.php` | consulta de qualquer etapa; checks reversíveis; timer idle/running/paused/elapsed e reutilização; agitação periódica e contínua; edição de dados; estoque como ação separada |
| `aluno/inventario.php` | estoque, vazio e movimentação |
| `aluno/inventario-item.php` | edição de item |
| `aluno/preparos.php` | lista e editor de predefinição |
| `aluno/calibracao.php` | lista e editor de referência |
| `aluno/ferramentas.php` | bancada e toolbox |
| `aluno/perfil.php` | conta/perfil |
| `aluno/senha.php` | ativação e alteração de senha |

## Arquivos em `aluno/` que não constituem tela

Estes arquivos não recebem screenshot próprio porque são redirecionadores, ações HTTP ou streams; o destino visual correspondente continua coberto acima.

- `aluno/exposicao.php` — redireciona para a ferramenta correspondente;
- `aluno/logout.php` — ação de encerramento de sessão;
- `aluno/material-anotacao.php` — endpoint de gravação/edição de anotação;
- `aluno/material.php` — redirecionamento legado para material canônico;
- `aluno/media.php` — entrega de mídia;
- `aluno/preparo-solucoes.php` — redirecionamento para receitas/preparo;
- `aluno/reciprocidade.php` — redirecionamento para ferramenta;
- `aluno/temporizador.php` — redirecionamento legado para Processamentos;
- `aluno/teste-media.php` — entrega de mídia do Caderno;
- `aluno/testes.php` — redirecionamento legado para Caderno;
- `aluno/visibilidade-teste.php` — ação de alteração de visibilidade;
- `aluno/processamento-realizado.php` — compatibilidade: converge para o Processamento do próprio registro, sem modo retrospectivo separado;
- `aluno/processamentos-trocar.php` — compatibilidade: converge para `registro-roteiro.php`, sem conceito de “próximas etapas”.

## Gate visual atual

`tools/browser-tests/student-complete-area-audit.spec.cjs` é a lista executável de cobertura visual total e declara **46 superfícies por viewport**. Os cinco estados que representavam distinções temporais artificiais — `process-intent`, `recording-start`, `recording-associated`, `recording-partial` e `recording-complete` — foram retirados do produto visual porque contradizem o contrato atual do Caderno.

A cobertura específica do Caderno fica em `tools/browser-tests/student-caderno-product-audit.spec.cjs`, com **dez estados por viewport**: biblioteca; registro vazio; Dados opcionais aberto; etapa livre aberta; roteiro associado com zero checks; roteiro associado com uma etapa em edição; conjunto parcial de checks; seletor contextual de roteiro; resultado com contexto recolhido; resultado com contexto aberto. O audit verifica também a ausência de linguagem de workflow, a disponibilidade de todas as etapas, checks reversíveis, edição direta da cópia do roteiro, acesso ao timer, navegação interna em uma única linha no telefone e área de toque mínima das ações por etapa.

Fixtures dessa cobertura precisam reproduzir o DOM relevante da tela real. Uma fixture que omita disclosure, campo, ação condicional ou estado aberto não pode servir como prova visual daquela superfície.

`tools/browser-tests/student-process-execution-state-audit.spec.cjs` cobre o timer como ferramenta independente: ele pode iniciar, pausar, reiniciar e ser reutilizado depois de chegar a zero. O fim do timer pode marcar o check da própria etapa, mas não libera, bloqueia nem seleciona outra etapa.

O pacote de screenshots usado pelo material da Aula 3 deve ser regenerado a partir dessas superfícies sempre que a apresentação canônica do Caderno mudar. Na revisão não linear, o pacote foi atualizado depois de o audit visual passar.

Os testes não substituem a inspeção humana: depois da geração, o artefato inteiro deve ser aberto e observado. Qualquer problema encontrado bloqueia merge até correção e reinspeção.
