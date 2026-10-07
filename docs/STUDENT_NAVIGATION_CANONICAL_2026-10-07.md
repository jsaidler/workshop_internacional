# Área do aluno — contrato canônico de navegação

Data: 2026-10-07  
Status: **canônico, obrigatório e prioritário para qualquer trabalho de UI/UX na Área do aluno.**

Este documento prevalece sobre regras de navegação conflitantes em documentos anteriores, inclusive trechos de `STUDENT_MOBILE_EXPERIENCE_2026-09-25.md`, `STUDENT_AREA_EXPERIENCE_CANONICAL_2026-09-30.md` e `STUDENT_PRODUCT_UX_CANONICAL_RULES_2026-10-02.md`.

## 0. Regra de preflight — ler antes de tocar na Área do aluno

Antes de alterar qualquer tela, shell, fluxo ou componente da Área do aluno, é obrigatório ler este documento integralmente.

Isso vale para alterações em:

- `aluno/**`;
- `app/student_*.php` e o shell da Área do aluno;
- `assets/student-*.css` e `assets/student-*.js`;
- fixtures, screenshots e testes da Área do aluno;
- qualquer mudança no CMS autenticado que altere a experiência de navegação do aluno.

Nenhuma implementação deve começar apenas pela tela solicitada. Antes de modificar a interface, deve ser identificado:

1. qual destino global está ativo;
2. qual é o pai lógico da tela;
3. como a tela aparece no telefone;
4. se a barra inferior continua presente;
5. para onde a logo leva;
6. qual ação explícita substitui qualquer dependência de breadcrumb;
7. como a mesma arquitetura aparece no desktop.

Se uma mudança contradiz este contrato, o contrato deve ser discutido e alterado primeiro. Não é permitido criar exceção local silenciosa.

---

## 1. Mobile-first é regra de produto, não breakpoint

A Área do aluno é projetada **primeiro para o telefone**.

O desktop é uma adaptação posterior da mesma arquitetura de informação. Não se desenha uma interface de desktop para depois comprimi-la até caber em 390 px.

A avaliação de uma nova superfície começa no telefone e responde primeiro:

- a pessoa sabe onde está?;
- consegue ir para os quatro destinos principais sem procurar menus?;
- consegue voltar ao contexto anterior sem breadcrumb?;
- as ações principais estão próximas da tarefa?;
- os controles são utilizáveis com toque?;
- a navegação não encobre conteúdo?;
- existe continuidade entre uma tela e a seguinte?

Somente depois a mesma hierarquia é expandida para desktop.

---

## 2. Uma única arquitetura global

A navegação global da Área do aluno possui quatro destinos primários:

1. **Início**
2. **Cursos**
3. **Caderno**
4. **Laboratório**

A ordem, os nomes, os ícones e os destinos desses quatro itens são globais. Uma página não pode inventar uma versão própria.

URLs canônicas atuais:

- Início → `/aluno/`
- Cursos → `/aluno/cursos.php`
- Caderno → `/aluno/caderno.php`
- Laboratório → `/aluno/ferramentas.php` enquanto essa rota permanecer como URL técnica de compatibilidade

`Conta` não concorre com os quatro destinos principais. É configuração pessoal e permanece como ação consistente do cabeçalho/perfil.

---

## 3. Telefone: barra inferior persistente é obrigatória

Em viewport mobile, toda rota autenticada normal da Área do aluno deve manter a mesma barra inferior fixa.

A barra inferior:

- contém sempre os quatro destinos globais;
- mantém a mesma ordem em todas as telas;
- mantém rótulo visível; não depende apenas de ícone;
- possui área de toque adequada;
- respeita safe area;
- nunca cobre conteúdo porque o shell reserva espaço inferior equivalente;
- destaca o destino global correspondente ao contexto atual;
- não desaparece em páginas filhas, editores, material, dúvidas, registro, inventário ou ferramentas;
- não é substituída por breadcrumb, lista de links, botão “voltar” ou cabeçalho local.

Uma rota de página não pode decidir unilateralmente esconder a barra inferior.

Exceções só existem para uma superfície realmente transitória que não seja uma página de navegação — por exemplo, um modal/fullscreen técnico deliberado — e precisam ser documentadas neste arquivo antes da implementação.

### Estado ativo

O item ativo representa o **contexto do produto**, não simplesmente o nome do arquivo PHP.

Exemplos:

- material, aula e dúvidas de um curso → **Cursos**;
- lista do Caderno, registro, resultado e edição de etapa de um registro → **Caderno**;
- inventário, preparos, calculadoras, calibração e biblioteca de processamentos → **Laboratório**;
- uma ferramenta de timer aberta a partir de um registro pode continuar no contexto **Caderno**; o mesmo instrumento aberto de forma autônoma pelo Laboratório permanece em **Laboratório**.

A interface não deve mentir sobre o contexto apenas porque duas rotas reutilizam o mesmo componente.

---

## 4. Desktop: mesma arquitetura, outra apresentação

Desktop não possui uma IA diferente.

Pode usar cabeçalho horizontal, maior largura e navegação global no topo, mas deve preservar:

- os mesmos quatro destinos;
- os mesmos nomes;
- a mesma ordem conceitual;
- o mesmo destino da logo;
- a mesma separação entre navegação global e contextual.

Uma funcionalidade não pode existir apenas porque cabe no desktop nem depender de hover para ser descoberta.

---

## 5. Logo dentro da Área do aluno tem um único destino

Dentro de qualquer página autenticada da Área do aluno, clicar na marca/logo leva sempre para:

`/aluno/`

Nunca para a home pública.

A logo não muda de significado conforme a rota.

Se for necessário oferecer retorno ao site público, isso deve existir como ação explícita e nomeada — por exemplo, **Ver site** — e não por sobrecarga semântica da marca.

No site público, a logo continua pertencendo ao site público. O contexto autenticado e o contexto público não compartilham silenciosamente o mesmo destino.

---

## 6. Breadcrumb não é navegação primária

Breadcrumb é, no máximo, metadado de localização para hierarquias profundas no desktop.

Ele **nunca** pode ser:

- a única maneira de voltar;
- o principal mecanismo para trocar de área;
- necessário para concluir uma tarefa;
- a solução para ausência de navegação global;
- a solução para uma página filha sem ação de retorno;
- a principal navegação no telefone.

No telefone, breadcrumb deve ser omitido sempre que um título/contexto compacto e uma ação de voltar resolvam a orientação.

Se aparecer no desktop, é informação terciária. A pessoa deve conseguir ignorá-lo completamente e continuar navegando sem perda funcional.

---

## 7. Voltar é uma ação explícita de contexto

Páginas filhas usam uma ação clara de retorno, normalmente uma seta de voltar na app bar/cabeçalho contextual.

Essa ação:

- volta ao pai lógico da tarefa;
- preserva contexto relevante, como curso/turma/registro;
- não depende de o usuário ter chegado pela página anterior;
- funciona também quando a tela é aberta por deep link;
- nunca usa a home pública como fallback;
- não substitui a barra inferior.

Exemplos:

- material → curso correspondente;
- dúvida individual → contexto de dúvidas/curso;
- edição de etapa → registro do Caderno;
- edição de item de estoque → inventário;
- edição de roteiro → biblioteca ou registro de origem, conforme o contexto real.

`history.back()` isolado não é arquitetura de navegação.

---

## 8. Histórico do navegador e navegação gestual

A navegação da Área do aluno deve respeitar o histórico nativo do navegador e os gestos do sistema operacional.

Em telefone, **voltar por gesto do Android/iOS ou pelo botão Back do navegador é parte da navegação principal da plataforma**. A aplicação não pode depender apenas dos controles visuais próprios.

### Regra de registro no histórico

Sempre que uma ação do usuário representar deslocamento real para outra tela, objeto ou contexto navegável, esse deslocamento deve produzir uma entrada coerente no histórico do navegador.

Exemplos:

- Início → Cursos;
- Cursos → curso;
- curso → material;
- material → dúvida;
- Caderno → registro;
- registro → edição de etapa;
- Laboratório → inventário → item.

Links e navegação HTTP normal são preferidos porque já produzem histórico nativo corretamente.

Quando uma navegação acontecer por JavaScript/AJAX sem reload, a aplicação deve:

- atualizar a URL canônica correspondente;
- usar `history.pushState()` para movimentos iniciados pelo usuário;
- restaurar o estado correto em `popstate`;
- permitir `Forward` depois de um `Back`;
- evitar criar nova entrada durante o tratamento de `popstate`.

`history.replaceState()` só é apropriado para normalização da entrada atual, correção de URL, preenchimento de estado inicial ou substituição que **não represente um novo movimento do usuário**. Não pode ser usado para esconder etapas reais da navegação.

### Gesto de voltar

O gesto nativo deve percorrer o mesmo caminho conceitual que a pessoa percorreu na interface.

Exemplo:

`Início → Cursos → Curso A → Material 2 → Dúvida`

Back/gesto deve produzir:

`Dúvida → Material 2 → Curso A → Cursos → Início`

Não é aceitável:

- saltar diretamente para a home pública;
- sair da Área do aluno porque uma transição intermediária não entrou no histórico;
- voltar para uma tela de outro contexto;
- criar loops entre duas URLs;
- permanecer na mesma tela porque a aplicação interceptou Back sem atualizar estado;
- adicionar entradas artificiais apenas para impedir que o usuário saia.

### Botão Voltar da interface

A ação visual de voltar continua tendo um **destino lógico canônico** e não depende cegamente de `history.back()`.

O controle deve possuir um `href` ou destino explícito que funcione em deep link.

Quando a aplicação souber que a entrada anterior do histórico pertence à mesma Área do aluno e corresponde ao retorno lógico esperado, ela pode usar o histórico nativo para preservar a pilha real. Caso contrário, usa o destino canônico.

Portanto:

- histórico é o mecanismo de percurso;
- destino lógico é o fallback seguro;
- um não substitui o outro.

### Deep link e recarga

Toda tela navegável precisa continuar válida quando aberta diretamente ou recarregada.

A URL deve conter contexto suficiente para reconstruir a superfície: curso/turma/registro/item/estado navegável quando necessário.

Depois de recarregar:

- a barra global continua correta;
- o item ativo continua correto;
- o botão de voltar mantém destino lógico;
- o gesto Back volta para a entrada anterior real do navegador, se existir;
- nenhum estado crítico depende exclusivamente de um objeto JavaScript perdido no reload.

### O que NÃO entra no histórico

Não criar entradas para microestados efêmeros que não representam deslocamento de navegação, salvo quando houver necessidade explícita de deep link.

Por padrão, não viram nova entrada:

- abrir/fechar disclosure;
- expandir “Dados opcionais”;
- abrir um editor inline dentro do mesmo objeto, quando ele não representa uma rota própria;
- mudar foco;
- abrir tooltip;
- alterar um campo;
- mostrar mensagem de sucesso;
- abrir modal estritamente transitório.

A regra é semântica: **movimento entre superfícies/contextos entra no histórico; estado local de uma mesma superfície não polui a pilha.**

### Scroll e restauração

Quando a navegação é feita por AJAX/History API, o retorno deve preservar ou restaurar posição de leitura quando isso fizer sentido, especialmente em Material e listas longas.

A aplicação não deve fazer o usuário voltar ao topo e reencontrar manualmente o trecho de onde saiu se o navegador conseguir restaurar esse contexto.

### Regressão obrigatória

Os testes de navegação mobile devem validar pelo menos:

1. sequência de navegação por links/bottom bar;
2. Back do navegador em múltiplos níveis;
3. Forward depois de Back;
4. deep link direto em tela filha;
5. fallback do botão Voltar quando não há histórico interno útil;
6. preservação de `cohort`, registro e demais parâmetros;
7. item ativo correto após Back/Forward;
8. ausência de loop ou history trapping.

---

## 9. Navegação contextual é secundária

Tabs, segmented controls, anterior/próximo, filtros e links internos pertencem à superfície atual e não competem visualmente com a navegação global.

Exemplos legítimos:

- Visão geral / Material / Dúvidas dentro de Cursos;
- Exposição / Processamento / Resultado dentro de um registro;
- anterior / próximo entre materiais do mesmo curso.

Eles não podem substituir a barra inferior nem parecer uma segunda navegação global.

Ações correlatas devem permanecer juntas. Não se separa uma tarefa em áreas distantes apenas porque o banco ou o código as modela como entidades diferentes.

---

## 10. Cabeçalho mobile

A app bar mobile deve ser compacta e previsível.

Ela pode conter, conforme a superfície:

- ação de voltar;
- identificação curta da tela/contexto;
- ação de conta ou overflow secundário quando necessário.

Ela não deve conter uma cópia da navegação global já presente na barra inferior.

Títulos gigantes de landing page, menus horizontais comprimidos e blocos de links no topo não são padrão de aplicação mobile.

---

## 11. Profundidade e orientação

A pessoa não deve precisar memorizar a árvore do produto.

Em qualquer tela devem existir respostas visuais claras para três perguntas:

1. **Em qual área global estou?** → barra inferior / navegação global.
2. **O que estou vendo ou fazendo?** → título e contexto local.
3. **Como volto um nível?** → ação explícita de retorno quando houver pai lógico.

Breadcrumb não é necessário para responder a nenhuma delas.

---

## 12. Continuidade entre telas

Ao navegar dentro de uma tarefa:

- parâmetros de contexto devem ser preservados;
- voltar não deve apagar trabalho não relacionado;
- o aluno não deve reencontrar manualmente curso, turma, registro ou item que já estavam definidos;
- ações correlatas devem ocorrer no mesmo contexto sempre que tecnicamente possível;
- abrir uma tela filha não deve fazer a navegação global desaparecer.

Mudança de tela só é justificada quando há mudança real de objeto ou de espaço de trabalho. Não se cria uma página nova para cada decisão pequena.

---

## 13. Critério de consistência

Antes de aprovar qualquer superfície da Área do aluno, comparar com pelo menos uma tela irmã.

A revisão deve verificar:

- mesma barra inferior no telefone;
- mesmo destino da logo;
- mesmo comportamento do item ativo;
- mesma posição/semântica de Conta;
- mesmo padrão de voltar;
- ausência de dependência de breadcrumb;
- mesma hierarquia global/contextual;
- ausência de navegação duplicada;
- ausência de desaparecimento arbitrário do shell;
- ausência de variação de nomes para o mesmo destino.

Uma página isoladamente bonita pode ser reprovada se quebrar a continuidade do produto.

---

## 14. Gate obrigatório para qualquer alteração de UI/UX do aluno

Nenhuma mudança de UI/UX da Área do aluno é concluída sem revisar a navegação global.

Checklist mínimo:

- [ ] contrato de navegação lido antes da implementação;
- [ ] mobile foi a primeira viewport de decisão;
- [ ] barra inferior presente em todas as rotas mobile afetadas e vizinhas;
- [ ] item ativo correto por contexto;
- [ ] logo autenticada aponta para `/aluno/`;
- [ ] retorno contextual explícito existe onde necessário;
- [ ] nenhum fluxo depende de breadcrumb;
- [ ] desktop preserva a mesma IA;
- [ ] deep link não deixa a pessoa sem navegação;
- [ ] Back/gesto do sistema percorre o histórico real da tarefa;
- [ ] Forward funciona depois de Back;
- [ ] transições AJAX navegáveis usam `pushState`/URL canônica e `popstate` restaura o contexto;
- [ ] microestados locais não poluem o histórico;
- [ ] screenshots mobile e desktop foram efetivamente inspecionados;
- [ ] qualquer exceção foi documentada neste arquivo antes do merge.

---

## 15. Proibição de correção local de navegação

Navegação global é responsabilidade do shell compartilhado.

Não corrigir inconsistência de barra inferior, logo, Conta ou destinos globais dentro de uma página individual.

Se uma rota normal não recebe o shell correto, corrige-se o mecanismo compartilhado que a classifica/renderiza.

O mesmo vale para estado ativo: não espalhar regras de pathname por páginas individuais. Deve existir uma fonte central de mapeamento entre contexto de produto e destino global.

---

## 16. Prioridade deste contrato

Em caso de dúvida entre:

- uma implementação existente;
- screenshot antigo;
- fixture;
- teste legado;
- documento anterior;
- este contrato;

a implementação deve ser auditada contra **este contrato**.

Teste ou fixture que imponha navegação conflitante deve ser atualizado. Não se preserva UX ruim para satisfazer regressão antiga.

Este arquivo deve ser consultado novamente antes de qualquer nova tranche de UI/UX da Área do aluno.
