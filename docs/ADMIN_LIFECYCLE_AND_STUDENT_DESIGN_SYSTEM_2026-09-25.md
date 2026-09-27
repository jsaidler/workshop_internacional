# Administração, área do aluno e ciclos de vida — 25/09/2026

## Problemas encontrados

A revisão visual e funcional de 25/09 mostrou quatro falhas de produto que não devem ser tratadas como exceções locais:

1. a Área do aluno havia criado uma camada visual paralela, com carregamento próprio de fontes, variáveis próprias de tipografia/cor e um segundo stylesheet para o dashboard;
2. o título público do curso aparecia em várias superfícies, mas não havia caminho administrativo explícito para alterá-lo depois da criação do site;
3. o aluno podia criar testes e remover imagens isoladas, mas não conseguia encerrar o ciclo de vida do próprio teste;
4. uma inscrição podia ser cancelada/arquivada, mas não excluída definitivamente pelo administrador.

## Autoridade visual

`template/page.css` é a autoridade dos tokens e das primitivas visuais já existentes também para a Área do aluno.

A regra estrutural vigente é: **procurar → consumir → identificar lacuna → criar/ampliar globalmente somente se necessário → consumir**. Uma nova superfície não recebe uma implementação local apenas porque precisa de um componente.

As lacunas reutilizáveis que não possuíam antes uma autoridade neutra ficam em `assets/ui-core.css`: campos, select normalizado, choices, checkbox, alertas e modificadores genéricos. O comportamento reutilizável de tema e validação progressiva fica em `assets/ui-core.js`. Essas duas unidades são globais; não pertencem à Área do aluno.

A Área do aluno pode manter CSS próprio **somente para layout e comportamento específicos do aplicativo**, como navegação móvel, fluxo em etapas, captura de imagens, cartões do histórico e barra de ação. Esse CSS não pode:

- carregar fontes próprias;
- declarar stacks tipográficos próprios;
- redefinir paleta paralela;
- criar tokens concorrentes para `title`, `body`, `mono`, `bg`, `surface`, `text`, `muted` ou `line`;
- recriar botão, campo, select, choice, checkbox, alert, validação ou tema com prefixo `student-*`;
- duplicar estilos em um segundo stylesheet apenas para uma tela.

A tipografia e a paleta devem vir dos tokens globais `--title`, `--body`, `--mono`, `--bg`, `--surface`, `--text`, `--muted`, `--line`, `--line-strong`, `--inverse`, `--inverse-bg` e `--focus`.

O antigo `assets/student-dashboard.css` foi removido. `assets/student-area.css` permanece apenas como composição do aplicativo e consome as primitivas globais. `assets/student-area.js` também foi eliminado: não restou comportamento de interface que justificasse uma autoridade JavaScript exclusiva da Área do aluno.

A regra completa está registrada em `GLOBAL_UI_CONSUMPTION_RULE_2026-09-27.md`.

## Nome do curso

O nome exibido ao aluno vem de `activities.public_title`.

O caminho canônico para alterá-lo é:

`Admin → Configurações → Sites → Nome público do curso`

A tela inicial do Admin também oferece `Editar nome do curso` junto ao título mostrado no painel.

`admin_name` continua sendo o nome administrativo e pode ser alterado no mesmo lugar. O slug/endereço não é alterado por essa operação para evitar mudança acidental de URL.

## Exclusão de testes pelo aluno

Um teste pertence ao aluno que o criou. O aluno pode excluí-lo independentemente de estar em rascunho, enviado, com ajustes solicitados ou revisado.

A exclusão é uma ação explícita em duas etapas: o histórico leva a uma tela de confirmação que descreve exatamente o que será apagado.

Excluir um teste remove definitivamente:

- a ficha `student_tests`;
- todas as linhas de `student_test_media` associadas;
- todos os arquivos físicos correspondentes;
- todo o histórico de `student_test_messages` associado.

A implementação remove as linhas filhas explicitamente em transação e, depois do commit, remove os arquivos físicos. Ela não depende de `ON DELETE CASCADE` estar habilitado no runtime SQLite para manter integridade.

## Exclusão de inscrições pelo administrador

Cancelar/arquivar e excluir são operações diferentes.

- **Cancelar inscrição** preserva o registro administrativo e muda seu estado.
- **Excluir inscrição definitivamente** remove o registro `cms_form_submissions`.

Quando a inscrição excluída originou uma matrícula, a matrícula cujo `source_submission_id` aponta para ela também é removida. A exclusão não apaga silenciosamente:

- a conta do aluno;
- o perfil do aluno;
- testes históricos do aluno.

Essas entidades têm ciclos de vida próprios e exigem ações próprias. Isso evita uma cascata destrutiva inesperada quando a intenção administrativa era apenas excluir a inscrição.

## Mídias editoriais privadas

A decisão arquitetural permanece: mídia editorial privada não deve ter biblioteca/upload paralelo. O destino canônico é o gerenciador de mídia único, com privacidade como propriedade do asset e os slots protegidos armazenando apenas referência ao asset. Fotos de testes são exceção porque são anexos privados pertencentes a um registro do aluno, não conteúdo editorial reutilizável.

A migração de `student_private_media` para a biblioteca única é tratada como mudança de armazenamento e autorização separada; até sua conclusão não devem ser adicionadas novas funcionalidades ao uploader paralelo.

## Regressão

`tools/test-admin-lifecycle-and-student-design.php`, `tools/test-student-premium-ui.php` e `tools/test-global-ui-consumption.php` garantem que:

- a Área do aluno herda `template/page.css` e consome `ui-core`;
- Google Fonts e `student-dashboard.css` não reaparecem no shell;
- o CSS da Área do aluno não volta a declarar stacks/paleta paralelos nem primitivas globais locais;
- `assets/student-area.js` não reaparece como motor paralelo de tema/validação;
- as superfícies de aluno continuam consumindo campos, choices, alerts e botões globais;
- o nome público do curso continua editável no Admin;
- o aluno continua tendo acesso à exclusão integral do próprio teste;
- o administrador continua tendo acesso à exclusão definitiva de inscrições;
- a exclusão de teste remove banco, mensagens, mídia e arquivo físico;
- a exclusão de inscrição remove a matrícula originada por ela sem misturar o ciclo de vida da conta e dos testes.
