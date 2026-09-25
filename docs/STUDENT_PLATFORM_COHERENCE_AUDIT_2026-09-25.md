# Auditoria de coerência — plataforma do aluno — 25/09/2026

## Contexto

A revisão funcional posterior ao PR #89 mostrou que corrigir ações isoladas não é suficiente. O problema restante é arquitetural: algumas responsabilidades ainda estão duplicadas, algumas permissões existem sem uma forma confiável de pré-visualização e alguns objetos administrativos aparecem longe do lugar onde o usuário naturalmente procura por eles.

Esta auditoria é o contrato da próxima correção sistêmica.

## 1. Identidade visual

A documentação existente determina que a tipografia e o tema são propriedades do sistema visual global. A Área do aluno não pode possuir fonte, paleta ou tema paralelos.

`template/page.css` continua sendo a base visual. `assets/student-area.css` pode definir apenas layout e comportamento específicos do aplicativo.

A Área do aluno deve consumir também os tokens dinâmicos definidos em `Design → Tipografia/cores` para a atividade correspondente. Herança apenas dos defaults do template não é suficiente quando o site já permite personalização pelo CMS.

## 2. Nome público do curso

O nome exibido ao aluno é `activities.public_title`.

A edição desse nome não pode ficar escondida apenas em `Configurações → Sites`. Ela deve aparecer no contexto normal do site atual, em `Site → Identidade`, ao lado de `Nome do site` e `Wordmark`, com distinção explícita entre:

- **Nome público do curso** — nome da entidade curso, usado na Área do aluno;
- **Nome do site** — identidade editorial/SEO da versão localizada;
- **Wordmark** — texto mostrado no cabeçalho público.

`Configurações → Sites` continua sendo a tela de gestão multi-site e pode editar os mesmos nomes administrativos.

## 3. Ciclo de vida dos testes

O aluno é proprietário dos testes que cria.

Além da exclusão integral já implementada, cada teste deve possuir uma visibilidade explícita:

- `private` — somente autor e administração;
- `cohort` — autor, administração e alunos com matrícula ativa na mesma turma;
- `course` — autor, administração e qualquer aluno com matrícula ativa no mesmo curso/site.

Nunca existe publicação anônima na web.

A visibilidade controla somente ficha técnica e imagens. A conversa de avaliação entre aluno e professor continua privada mesmo quando o teste é compartilhado.

O próprio aluno pode alterar a visibilidade e excluir o teste independentemente do estado de avaliação. A exclusão continua removendo ficha, mensagens, registros de mídia e arquivos físicos.

## 4. Bloqueio progressivo do material por aula

O documento `caderno-positivo-direto` já contém seções reais identificadas por `data-cms-section` e o banco possui o mapa seção → aula. O erro de produto é a falta de uma verificação administrativa inequívoca do resultado filtrado.

Contrato:

- a renderização normal do administrador pode continuar mostrando o documento completo;
- a área `Páginas protegidas` deve oferecer **Visualizar como turma**;
- essa visualização usa exatamente o mesmo filtro server-side aplicado ao aluno e nunca uma simulação apenas visual;
- a lista de segmentação deve agrupar seções pelas aulas e mostrar a contagem de seções de cada aula;
- testes de regressão devem provar que, para uma turma com apenas Aula 1 liberada, nós das Aulas 2 e 3 não chegam ao HTML entregue.

A existência de opções de segmentação sem uma pré-visualização real do conteúdo filtrado é considerada incompleta.

## 5. Mídia editorial privada

Existe hoje uma implementação paralela `student_private_media`. Ela viola a regra de fonte única de mídia do CMS.

Contrato final:

- `media_assets` é a única biblioteca para mídia editorial reutilizável;
- cada asset possui `visibility = public|private`;
- asset privado não pode continuar acessível por URL direta em `/uploads`;
- ao tornar um asset privado, seus arquivos são armazenados em diretório protegido e servidos somente por endpoint autorizado;
- slots do material armazenam apenas referência ao asset da biblioteca;
- `Área do aluno → Páginas protegidas` não faz upload: apenas escolhe/troca a mídia privada da biblioteca;
- o gerenciador de mídia é o único lugar para upload, metadados e mudança de visibilidade;
- dados legados de `student_private_media` devem ser migrados sem perda antes de a interface paralela deixar de ser usada.

Fotos de testes dos alunos continuam fora da biblioteca editorial, pois são anexos de um registro do aluno e possuem ciclo de vida próprio.

## 6. Inscrições

Cancelar/arquivar e excluir definitivamente continuam sendo ações distintas.

A exclusão permanente já possui backend e deve ser claramente acessível no detalhe da inscrição, em uma área destrutiva explícita. Quando houver matrícula criada pela inscrição, ela é removida; conta do aluno, perfil e testes não são apagados silenciosamente.

## 7. Importação histórica

CSV é o formato canônico. A interface PHP deve expressar isso diretamente; não deve depender de JavaScript que renomeia `Importar planilha`, troca `accept` ou reescreve o `action` do formulário depois que a página carregou.

O modelo e a linha de exemplo devem fazer parte da interface renderizada pelo servidor.

## Critério de conclusão

A correção só está pronta quando:

1. não há fonte/tema paralelo na Área do aluno;
2. o nome público do curso é editável no contexto do site atual;
3. testes possuem exclusão integral e visibilidade `private|cohort|course`;
4. compartilhamento nunca expõe conversa privada de avaliação;
5. o bloqueio de aulas é verificável com `Visualizar como turma` e testado contra HTML realmente filtrado;
6. a biblioteca de mídia é a única autoridade para mídia editorial privada;
7. o uploader paralelo de `student_private_media` desaparece da interface;
8. a exclusão definitiva de inscrição permanece clara e testada;
9. o fluxo de importação é CSV nativo no HTML/PHP, sem correção posterior por JavaScript;
10. CI, browser regression, build e dry-run ficam verdes antes do merge.
