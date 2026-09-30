# Área do aluno — experiência canônica

Data: 2026-09-30
Status: canônico e posterior ao passe visual do PR #157.

Este documento prevalece sobre decisões anteriores de navegação e apresentação da Área do aluno quando houver conflito. O domínio e os dados definidos em `STUDENT_AREA_PRODUCT_AND_IMPLEMENTATION_2026-09-30.md` continuam válidos; o que muda aqui é a forma como o aluno encontra e usa essas capacidades.

## 1. Princípio

A interface não deve exigir que o aluno compreenda a arquitetura interna do sistema, a diferença entre escopo global e escopo de matrícula ou a classificação técnica de uma funcionalidade.

A navegação responde ao que a pessoa veio fazer:

- **Início** — orientação e acesso rápido.
- **Cursos** — estudar: aulas, material e dúvidas.
- **Caderno** — registrar: exposição, processamento e resultado.
- **Laboratório** — fazer: exposição, temporização, calibração, preparo químico, inventário e preparos salvos.

**Conta** é configuração e não possui o mesmo peso das quatro tarefas principais. Permanece acessível no cabeçalho.

## 2. Início

`/aluno/` é uma página de orientação, não o curso selecionado e não um dashboard de métricas.

Ela deve permitir entender a área sem aprender sua estrutura previamente. Os três eixos são apresentados em linguagem de ação:

- Estudar → Cursos;
- Registrar → Caderno;
- Fazer → Laboratório.

A página pode mostrar contexto útil já existente — matrícula disponível, último registro e quantidade de instrumentos — mas não inventa progresso, última atividade ou recomendação que o sistema não possua.

## 3. Cursos

`/aluno/cursos.php` concentra seleção e contexto de curso. Ao abrir um curso, o aluno encontra disponibilidade de aulas, material e dúvidas.

A navegação contextual `Visão geral / Material / Dúvidas` é secundária e pertence ao conteúdo do curso; não deve parecer uma segunda barra global.

Disponibilidade de aula não é progresso do aluno. A interface usa termos como `Disponível`, `Agendada`, `Aguardando` e `aulas disponíveis`.

## 4. Caderno

O Caderno continua global e pessoal. O objeto principal é o registro.

A hierarquia visual de uma linha é:

1. título do registro;
2. caminho do processo ou indicação de ausência de processamento;
3. contexto e compartilhamento;
4. data e atualização;
5. ações secundárias.

Ações de operação não podem ser texto solto. Repetir, comparar, salvar acesso, excluir e demais operações aparecem como controles com affordance de botão. Ações destrutivas usam o modificador visual destrutivo global.

## 5. Laboratório

A antiga apresentação `Ferramentas` deixa de ser um catálogo de pequenos aplicativos e passa a ser **Laboratório**.

Os instrumentos são organizados pela situação de uso:

- **Exposição** — exposição equivalente e reciprocidade;
- **Processamento** — temporizador e calibração;
- **Química e estoque** — preparo de soluções e inventário;
- **Preparos salvos** — configurações pessoais reutilizáveis no Caderno.

Os URLs técnicos anteriores podem permanecer por compatibilidade. A linguagem da interface usa Laboratório.

## 6. Contrato visual

A Área do aluno herda tokens e primitivas globais. Não cria uma segunda paleta, tipografia, botão, campo ou sistema de tema.

Regras de composição:

- hierarquia deve ser percebida antes da leitura detalhada;
- títulos de aplicação não usam escala de landing page;
- uma superfície não vira card apenas para existir;
- divisores e espaço substituem caixas aninhadas sempre que não houver necessidade semântica de contenção;
- botões têm aparência de botão; links de texto ficam reservados a navegação contextual, breadcrumbs e links dentro de conteúdo;
- ações do mesmo grupo compartilham altura, alinhamento e espaçamento;
- formulário termina com uma faixa de ações clara, nunca com botão solto entre campos;
- estados e metadados são secundários ao objeto e à ação principal;
- no mobile, a navegação inferior contém somente Início, Cursos, Caderno e Laboratório;
- Conta permanece no cabeçalho e não ocupa um quinto destino primário.

## 7. Mobile e uso em laboratório

A versão mobile não é apenas o desktop estreitado.

- ações principais ocupam largura útil quando isso reduz erro de toque;
- controles de processo têm alvos grandes;
- ações persistentes podem ficar acima da navegação inferior durante fluxos longos;
- tabelas comparativas podem rolar, mas o restante da página não cria overflow horizontal;
- informação secundária quebra para a linha seguinte em vez de comprimir títulos e controles.

## 8. Compatibilidade

Nenhuma mudança desta etapa altera schema, propriedade de registros, regras de acesso, persistência ou contratos de matrícula.

`/aluno/ferramentas.php` permanece como URL do Laboratório por compatibilidade. Rotas existentes dos instrumentos também permanecem.

## 9. Critérios de regressão

A regressão deve impedir:

- retorno de `Meus cursos / Caderno / Ferramentas / Conta` como quatro destinos equivalentes;
- retorno da raiz `/aluno/` como workspace de curso;
- uso de `Progresso` para simples disponibilidade de aulas;
- ações operacionais renderizadas como texto sem affordance;
- cards promocionais na listagem de instrumentos;
- Conta ocupando a navegação inferior;
- quebra da autoridade visual global;
- perda de contexto de curso, Caderno, inventário, preparos, dúvidas ou material.
