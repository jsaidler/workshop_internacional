# Matriz verificável de telas, estados e dispositivos — Área do aluno

Data: 09/10/2026  
Status: **auditoria em andamento; não aprovada integralmente.**  
Escopo: área autenticada do aluno, componentes compartilhados e fluxos que abrem superfícies transitórias.  
Autoridades: `docs/STUDENT_NAVIGATION_CANONICAL_2026-10-07.md`, `docs/STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md`, `docs/STUDENT_REAL_VISUAL_AUDIT_2026-10-02.md` e `docs/PROJECT_STATE.md`.

## Por que esta matriz existe

A tela de edição da anotação existente foi omitida pela revisão do PR #215. O pipeline verde confirmou apenas estados escolhidos pelo autor dos testes, não a qualidade do produto. **Nenhum workflow verde é aceito como certificado de inspeção humana.**

Uma tela é o produto cartesiano de contexto de dados, tarefa, estado operacional e dispositivo, não uma URL. A matriz de 46 screenshots desktop + 46 mobile é apenas o inventário de superfícies básicas; não constitui aprovação integral de seus estados.

## Critério de aprovação por superfície

Para cada estado aplicável registrar **ambas** as evidências:
1. Funcionamento — interação reproduzida no navegador com entrada válida, inválida e as transições relevantes; comportamento server-side e persistência conferidos quando houver gravação.
2. Inspeção — captura de renderização real com DOM e classes fiéis à aplicação, efetivamente observada em telefone e desktop, verificando leitura, contraste, hierarquia, foco, rolagem até o último controle, barra global, navegação, conteúdo extenso, sobreposições e feedback.

Os estados mínimos, quando aplicáveis, são: **vazio**, **listagem com poucos e muitos registros**, **visualização**, **criação**, **edição**, **desdobramento/accordion/modal**, **confirmação**, **cancelamento**, **erro de validação e erro de serviço**, **salvamento**, **exclusão/desvinculação**, **retorno/histórico**, **permissão/indisponibilidade** e **conteúdo extenso**. Cada estado deve existir em **telefone (390×844)** e **desktop (1440×1100)**. Testes de largura intermediária/teclado físico são suplementares. Estados inaplicáveis devem ser justificados, não ignorados.

## Inventário obrigatório (46 superfícies básicas)

| Família | Superfícies indexadas | Estados que precisam ser cobertos explicitamente |
|---|---|---|
| Acesso e conta | `login`, `activation-password`, `profile`, `password-change` | entrada, formulário incompleto, erro, credencial inválida, sucesso, retorno, sessão expirada |
| Início | `home`, `home-feedback`, `home-study`, `home-multiple` | sem matrícula, 1 ou várias, tarefa pendente, feedback e desvio contextual |
| Cursos | `course-list`, `course-detail`, `course-feedback`, `course-empty` | matriz de matrículas, aulas liberadas/parciais, vazios, retornar, seleção do curso |
| Material | `material` + painel de anotações da página | texto extenso, seleção, vínculo, marca deslocada/alterada/removida, notas gerais e antigas, lista, edição, salvamento, erro, exclusão, anotação→dúvida |
| Dúvidas | `questions-list`, `questions-feedback`, `question-new`, `question-thread` | lista vazia/cheia, privacidade aluno/professor, turma/curso, título/corpo inválidos, nova, conversa, resposta, acesso não autorizado |
| Caderno | `notebook`, `new-record`, `shared-record`, `delete-record` | vazio, várias fichas, criação, cancelamento, exclusão/confirmar, registro compartilhado/privado, retroatividade, voltar |
| Exposição e processamento | `exposure`, `process-choice`, `process-plan`, `process-partial`, `step-editor` | sem plano, selecionar/editar/trocar, etapas parciais, marcação/desmarcação, inputs inválidos, rollback, retorno |
| Resultado e avaliação | `result`, `result-waiting`, `result-revision`, `result-reviewed` | sem resultado, enviado, revisão solicitada, revisão submetida, recebido, dados/fotos/observações |
| Comparação e linhagem | `compare-select`, `compare-records`, `research-derived` | seleção, comparação, diferenças, variação derivada, ações condicionais, retorno |
| Processamentos e laboratório | `process-library`, `process-editor`, `lab-runner` | biblioteca, criação, 9 etapas, edição, reordenação, remoção, andamento, timer, retomada, cancelamento |
| Inventário | `inventory`, `inventory-empty`, `inventory-movement`, `inventory-item-editor` | vazio/cheio, item novo/editar, movimento +/−, quantidade insuficiente, confirmação, erro |
| Preparos e referências | `preparations`, `preparation-editor`, `calibration-list`, `calibration-editor` | listas vazias/cheias, criar, editar, excluir, validar campos, retorno |
| Ferramentas | `tools`, `toolbox` | cronômetro parado/ativo/pausado/zerado, reciprocidade, exposição equivalente, painéis abertos/fechados |

O inventário de rotas canônicas está em `tools/browser-tests/student-complete-area-audit.spec.cjs`. Uma fixture que omita controles presentes na interface real é **não conforme**, independentemente de o teste passar.

## Evidência efetivamente disponível em 09/10

- As 46 superfícies acima foram renderizadas em desktop e telefone na auditoria da revisão anterior: **evidência de composição básica apenas**. Os mosaicos foram examinados; estados adicionais não cobertos permanecem pendentes.
- Para **Material/anotações**, PR #216 adicionou captura de lista, editor de nota persistida, transformação em dúvida e criação de nota geral, nos dois dispositivos, além dos estados de anotação nova já existentes. O acesso à anotação geral estava impossível de alcançar no fim de uma lista longa no desktop; o controle foi deslocado para o início.
- Regressões existentes cobrem uma série de processos e transições, mas **não são equivalentes à inspeção visual de cada um dos estados acima**.
- Nenhuma superfície sem avaliação de seus estados aplicáveis pode ser declarada 'aprovada integralmente'. O PR #216 **não** representa por si só a conclusão da revisão de todas as telas.

## Imutabilidade da auditoria visual (09/10/2026)

Foi identificada uma falha transversal no workflow `student-visual-audit`: a geração do pacote de capturas da Aula 3 terminava em um commit automático na **branch de qualquer PR**, alterando a revisão durante a validação. A etapa de commit foi retirada, e o workflow passou a operar com `contents: read`. A saída visual fica disponível como artefato, sem modificar o código-fonte examinado. A atualização persistente de imagens da Aula 3 exige operação explícita, separada e revisada, no seu próprio escopo. Um PR não pode mudar de SHA por efeito colateral de testes.

## Auditoria por domínio — Dúvidas e formulários (09/10/2026)

**Achado D-01 — fixture incompleta:** `student-secondary-screens-audit.html?screen=question-new` só apresentava `private` e `cohort`; o formulário real `aluno/duvidas.php` também permite `course` (todas as turmas do mesmo curso). A fixture tinha ainda controles sem nomes e campos sem os atributos `required` reais. Corrigidos o markup da fixture, a verificação de paridade PHP e os testes de visibilidade em ambas as viewports. Teste e renderização: `tools/browser-tests/student-complete-area-audit.spec.cjs`, capturas em `student-visual-audit/questions/`.

**Achado D-02 — foco em campo invisível:** o controlador compartilhado `assets/student-workbench.js` aplicava `.focus()` no primeiro `input`, inclusive campos `type=hidden` de formulários que o servidor já preenche automaticamente. A abertura podia manter foco no acionador. O seletor agora busca o primeiro controle visível e habilitado; o teste usa o JS real e verifica foco no assunto após reabrir o painel. Esta correção transversal também alcança painéis de inventário/processos que consomem o mesmo controlador.

**Verificações introduzidas:** estado aberto e cancelado, `aria-expanded`, título/corpo obrigatórios, teto de 180 caracteres, exclusividade do grupo de três rádios, navegação por foco, renderização de cada visibilidade e **listagem simultânea de dúvida privada, da turma e do curso** em telefone 390×844 e desktop 1440×1100.

**Ainda não certificados:** sucesso real de POST, erro de servidor, lista vazia/longa de conversas, professor/aluno com matrículas diferentes, deep link e resposta, reconciliação da conversa depois de exclusão e a ausência de oclusão visual causada pela navegação inferior durante o scroll. Os testes de autorização por curso introduzidos no PR #215 continuam necessários, mas não equivalem a essa inspeção.

## Gates que não podem ser flexibilizados

- [ ] Todos os estados do fluxo de anotações, incluindo notas antigas, confirmação de remoção, erro e retorno, renderizados e inspecionados em ambas as viewports.
- [ ] Todas as superfícies da matriz com estados aplicáveis enumerados e evidências de interação + inspeção vinculadas.
- [ ] Nenhuma fixture incompleta usada como prova de fidelidade à superfície real.
- [ ] Telas extensas verificadas no início, meio, fim e com teclado/rolagem do painel, não apenas screenshot de corpo inteiro.
- [ ] Problemas da inspeção resolvidos, telas alteradas reinspecionadas e regressão automatizada verde.
- [ ] Só então considerar revisão completa e autorizar atualização da hospedagem.

**Não utilizar a expressão “auditoria visual completa” enquanto restar qualquer gate sem evidência verificável.**
