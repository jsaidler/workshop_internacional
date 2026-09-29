# Passada de UI/UX — Ensino

Data: 2026-09-29

## Escopo

Esta etapa aplica o contrato administrativo às coleções de Ensino:

- Cursos
- Aulas
- Material

O princípio continua sendo o mesmo da reorganização anterior: Curso é catálogo e dimensão de contexto; não volta a ser uma árvore paralela da administração.

## Cursos

O catálogo passa a priorizar relações e carga operacional, não uma sequência de contadores soltos. Cada linha mostra:

- identidade do curso e estado;
- página pública e formulário vinculados;
- inscrições, turmas e alunos como leitura operacional compacta;
- aulas e material como leitura pedagógica compacta;
- uma única ação principal para abrir o curso.

Busca e página são preservadas ao abrir o detalhe e ao voltar ao catálogo. O detalhe deixa de repetir uma grade de cards métricos: usa um resumo compacto e uma tabela de relações que leva às coleções globais já filtradas.

Configuração continua responsável apenas pela relação Curso ↔ Página pública ↔ Formulário de inscrição. Página e formulário permanecem autoridades do CMS.

## Aulas

A matriz “uma coluna por turma” foi removida porque não escala. Com muitas turmas, a largura cresceria indefinidamente e tornaria a tabela inutilizável.

O fluxo passa a ser:

1. filtrar o curso;
2. escolher uma turma;
3. consultar a lista de aulas e o estado de liberação daquela turma;
4. abrir uma aula para administrar sua liberação.

O detalhe da liberação expõe as três operações já suportadas pelo domínio:

- agendar data e hora;
- liberar agora;
- bloquear.

Estados técnicos `released`, `scheduled` e `blocked` não aparecem como vocabulário principal da interface; são apresentados como `Liberada`, `Agendada` e `Bloqueada`.

## Material

A lista continua sendo uma coleção global filtrável por curso e busca. Ao abrir um item, o detalhe assume a leitura da página em vez de ficar empilhado depois da coleção.

O detalhe separa:

- identidade editorial da página;
- mapeamento Seção → Aula;
- edição no CMS;
- desassociação do curso em uma zona separada das ações cotidianas.

A associação de novas páginas deixa de materializar silenciosamente uma lista ilimitada de opções. O servidor consulta apenas páginas ainda disponíveis para o curso, oferece busca por título/slug e apresenta no máximo 50 resultados por busca, informando explicitamente quando há mais resultados e pedindo refinamento.

## Primitivas compartilhadas

Filtros ativos, chips removíveis, tons de badges e foco de tabelas com overflow deixam de pertencer apenas ao domínio Operação. Foram extraídos para `assets/admin-collection-ux.css`, carregado pelo shell administrativo e reutilizado por qualquer coleção.

`assets/admin-teaching.css` fica restrito aos componentes específicos do domínio Ensino: resumo de curso, detalhe de aula/material, editor de liberação, criação e zona de desassociação.

## Invariantes adicionadas ao CI

`tools/test-admin-teaching-ux.php` protege as decisões desta etapa:

- curso preserva busca/página ao abrir;
- detalhe não reintroduz grade redundante de cards;
- aula não cria uma coluna para cada turma;
- agendamento/liberação/bloqueio continuam disponíveis;
- estados técnicos são traduzidos;
- associação de material usa busca limitada e explícita;
- desassociação fica separada;
- tabelas de coleção permanecem focáveis em overflow;
- nenhuma rota de Ensino adiciona CSS visual inline.

## Próxima superfície

Depois desta etapa, a auditoria transversal pode seguir para o domínio Site — Páginas, Formulários, Outras respostas, Navegação, Visual e SEO — mantendo a mesma disciplina: uma aplicação, poucos componentes canônicos, ações previsíveis e comportamento pensado para volume real.
