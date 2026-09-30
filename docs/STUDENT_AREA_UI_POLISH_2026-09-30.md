# Área do aluno — passe de polimento de UI/UX

Data: 2026-09-30
Status: implementação em andamento nesta branch
Base: `wip/form-response-refinement-2026-07-16` após o PR #156

## Objetivo

A nova arquitetura de produto da Área do aluno está preservada. Este passe não altera domínio, permissões, dados ou a separação canônica entre:

- Meus cursos;
- Caderno de Processos;
- Ferramentas;
- Conta;
- Visão geral / Material / Dúvidas dentro do contexto de cada curso.

O objetivo é transformar a primeira implementação funcional dessa arquitetura em uma interface de uso recorrente mais clara, menos administrativa e mais adequada a estudo e trabalho de laboratório.

## Diagnóstico

A implementação inicial do PR #156 usa repetidamente a mesma construção visual — borda, superfície, card, label monoespaçada e título em caixa alta — para objetos de importância muito diferente. A consequência é uma interface organizada, porém visualmente plana e excessivamente próxima de um painel administrativo.

Os problemas prioritários são:

1. hierarquia insuficiente entre página, seção, objeto, metadado e ação;
2. excesso de caixas e sombras para separar conteúdo;
3. Caderno com comparação, visibilidade, metadados e ações competindo com o registro em si;
4. Ferramentas apresentadas como grade genérica de cards de produto;
5. Visão geral do curso usando “Progresso” para uma informação que representa disponibilidade de aulas;
6. uso excessivo de caixa alta e texto monoespaçado em níveis estruturais;
7. mobile tratado em vários pontos como simples compressão da composição desktop;
8. navegação móvel com quatro destinos globais em uma grade declarada anteriormente com três colunas.

## Direção visual

A Área do aluno continua herdando tipografia, cores e primitivas do sistema global. Não foi criada uma nova camada visual nem uma paleta paralela.

A direção deste passe é:

- menos containers ornamentais e mais separação por ritmo, linhas e espaço;
- títulos de aplicação em escala menor que landing pages e sem caixa alta obrigatória;
- monoespaçada reservada principalmente a rótulos, valores, estados e dados técnicos;
- ação principal evidente sem transformar todas as ações em botões equivalentes;
- objetos recorrentes apresentados como listas de trabalho, não como cards promocionais;
- curso tratado como contexto; Caderno tratado como histórico de trabalho; Ferramentas tratadas como instrumentos;
- mobile priorizando leitura, alvo de toque e continuidade do fluxo.

## Mudanças executadas

### Shell global

- navegação desktop deixa de usar blocos invertidos como seleção e passa a indicar contexto com linha ativa;
- números da navegação ficam ocultos no desktop, reduzindo ruído;
- títulos e subtítulos da aplicação deixam de forçar caixa alta;
- ritmo vertical passa a consumir a escala global `--ux-space-*`;
- navegação mobile passa a usar quatro colunas, uma para cada destino global real.

### Curso

- cabeçalho contextual continua subordinado à topbar global e fica visualmente mais leve;
- navegação Visão geral / Material / Dúvidas passa a usar estado ativo por linha, sem simular uma segunda barra principal;
- lista de aulas continua mostrando disponibilidade por turma;
- o antigo rótulo “Progresso” foi removido: a interface agora descreve explicitamente quantas aulas estão disponíveis;
- Material e Dúvidas passam a formar o bloco de acesso ao curso, sem card ornamental e sombra.

### Caderno de Processos

- registros deixam de parecer cards independentes e passam a compor uma lista contínua;
- seleção para comparação ocupa uma coluna funcional estreita;
- título e caminho do processo ganham precedência sobre compartilhamento e ações secundárias;
- barra de ações do registro fica subordinada ao conteúdo principal;
- resumos e etapas usam linhas e espaço em vez de sucessão de caixas.

### Ferramentas

- a grade deixa de usar cards promocionais elevados;
- cada ferramenta recebe índice sequencial e estrutura de instrumento dentro de uma bancada comum;
- desktop usa duas colunas e mobile uma coluna;
- áreas internas de cálculo, temporizador, preparo e resultados reduzem caixas aninhadas e usam divisores estruturais;
- Inventário passa a priorizar identificação e saldo antes de histórico e ações.

### Mobile

- navegação global inferior corrigida para quatro destinos;
- registros, ferramentas e curso removem padding lateral duplicado e caixas que desperdiçavam largura;
- ações críticas continuam grandes e utilizáveis;
- comparação e visibilidade se reorganizam verticalmente sem ocultar informação.

## Autoridade de CSS

Permanece a regra vigente:

`template/page.css` → tokens e linguagem visual pública

`assets/ui-core.css` → primitivas reutilizáveis globais

`assets/student-area.css` → shell, contexto de curso e composição transversal da aplicação do aluno

`assets/student-workbench.css` → composições específicas do Caderno, Ferramentas, Inventário, comparação e dúvidas introduzidas pelo workbench

Nenhum botão, campo, select, choice, checkbox, validação ou tema foi recriado localmente.

## Critérios de regressão

Este passe deve preservar:

- quatro destinos globais coerentes em desktop e mobile;
- nenhuma nova fonte, paleta ou primitiva global local;
- Caderno global e registros existentes;
- fluxo guiado de exposição/processamento/revisão;
- comparação, repetição, compartilhamento e exclusão de registros;
- ferramentas e suas regras de acesso;
- contexto do curso em Material e Dúvidas;
- responsividade sem overflow horizontal do documento;
- todas as permissões e persistência existentes.

## Fora do escopo

Este passe não adiciona novas funcionalidades ao produto, não altera schema e não reabre as decisões de arquitetura do PR #156. Melhorias futuras de favoritos, agenda, glossário, fornecedores, exportação ou estatísticas continuam adiadas conforme o documento canônico da Área do aluno.
