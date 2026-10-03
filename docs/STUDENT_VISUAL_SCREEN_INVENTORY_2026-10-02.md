# Área do aluno — inventário obrigatório de telas — 02/10/2026

Este inventário operacionaliza a regra de que nenhuma tela da área do aluno pode ser presumida correta. Toda tranche deve renderizar e inspecionar as superfícies visuais abaixo em desktop e mobile.

## Superfícies visuais

| Rota / domínio | Estados mínimos no audit |
|---|---|
| `aluno/login.php` | entrada e ativação inicial |
| `aluno/index.php` | dashboard |
| `aluno/cursos.php` | lista de cursos e curso/turma |
| material do curso (CMS autenticado) | leitura + anotações |
| `aluno/duvidas.php` | lista, nova dúvida e conversa |
| `aluno/caderno.php` | lista e novo registro |
| `aluno/teste.php` | exposição, escolha de processamento, roteiro aplicado e resultado |
| `aluno/processamento-realizado.php` | registro retroativo, retomada parcial e registro concluído |
| `aluno/processamentos-trocar.php` | troca antes de fatos consolidados, troca depois de fatos registrados e registro já realizado; nunca inferir interrupção a partir do estado do app |
| `aluno/teste-etapa.php` | edição de etapa real |
| `aluno/teste-compartilhado.php` | registro compartilhado |
| `aluno/excluir-teste.php` | confirmação destrutiva |
| `aluno/comparar-processos.php` | comparação |
| `aluno/processamentos.php` | biblioteca e editor |
| `aluno/processar.php` | modo laboratório; navegação entre etapas, consulta sem progresso, timer idle/running/paused/elapsed, agitação periódica e contínua, ajustes de tempo/agitação abertos |
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
- `aluno/visibilidade-teste.php` — ação de alteração de visibilidade.

## Gate

`tools/browser-tests/student-complete-area-audit.spec.cjs` é a lista executável de cobertura visual total. Estados transversais de mudança de rota são complementados por `tools/browser-tests/student-process-replanning-audit.spec.cjs`, incluindo a troca depois de fatos registrados e a troca enquanto o app possuía estado operacional mas nenhum fato havia sido consolidado. O segundo caso deve provar visualmente que o sistema não fabrica uma “interrupção”.

Os testes não substituem a inspeção humana: depois da geração, o artefato inteiro deve ser aberto e observado. Qualquer problema encontrado bloqueia merge até correção e reinspeção.
