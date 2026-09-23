# Área do aluno — autenticação e materiais protegidos

## Objetivo

Disponibilizar materiais do workshop dentro do próprio site sem publicar o conteúdo no repositório GitHub nem em uma URL pública de mídia.

A área do aluno fica em `/aluno/` e exige conta individual.

## Modelo de segurança

- Cada aluno possui usuário próprio e senha armazenada apenas como `password_hash()`.
- A senha provisória criada pelo administrador deve ser trocada no primeiro acesso.
- Uma redefinição de senha ou desativação incrementa `session_version` e invalida imediatamente sessões antigas.
- O login aplica limite de 5 tentativas em uma janela de 15 minutos por combinação de identificador + IP, usando HMAC com `ip_hash_secret`; o IP não é armazenado em claro.
- Login, logout, troca de senha e ações administrativas usam CSRF.
- Páginas da área do aluno enviam `Cache-Control: private, no-store` e `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`.
- O HTML do material é entregue somente após autenticação e autorização por atividade/curso.
- O material recebe CSP própria sem JavaScript, iframes, objetos, formulários ou conexões externas.

## Onde o material fica

O conteúdo protegido **não deve ser commitado no repositório público**.

O administrador envia um arquivo HTML pela tela `Admin → Alunos → Materiais`. O conteúdo é validado e gravado em `student_materials.html_content`, dentro do `storage/database.sqlite` persistente. Assim:

- atualizações do código não removem o material;
- o HTML não aparece no GitHub;
- o servidor só devolve o conteúdo a um aluno matriculado na atividade correspondente ou a um administrador autenticado.

Para proteção completa das imagens privadas, o HTML deve ser autocontido: imagens incorporadas como `data:`. Referenciar imagens em `/uploads` ou qualquer URL pública continua expondo esses arquivos fora da autenticação.

## Administração

`/admin/students.php`

- cria contas;
- associa uma conta a uma ou mais atividades;
- altera nome/e-mail;
- redefine senha provisória;
- ativa/desativa acesso.

`/admin/student-materials.php`

- seleciona a atividade pelo seletor normal do CMS;
- envia ou substitui um HTML protegido;
- arquiva materiais;
- permite pré-visualização para administradores.

## Fluxo do aluno

1. O aluno entra em `/aluno/`.
2. Se não estiver autenticado, é redirecionado para `/aluno/login.php`.
3. No primeiro acesso, a senha provisória precisa ser trocada.
4. O painel lista apenas materiais pertencentes às atividades às quais aquela conta está vinculada.
5. O material é aberto por UUID, mas a posse do UUID não concede acesso: a autorização é conferida novamente no servidor.

## Limite inevitável

A autenticação impede acesso não autorizado ao arquivo original e às páginas do servidor. Depois que um aluno autenticado visualiza um conteúdo, nenhum sistema web consegue impedir de forma absoluta captura de tela, fotografia da tela ou cópia manual. Portanto a proteção é de acesso e distribuição, não DRM.

## Deploy

A migration `046_student_area.php` é aplicada automaticamente no bootstrap depois da atualização.

O diretório `aluno/` foi incluído no `tools/build-dist.php`, portanto entra no pacote de produção. Como nas demais mudanças estruturais, atualizar `production-dist` não altera o HostGator sozinho: após merge/deploy, instalar a atualização em `Admin → Sistema e atualizações`.
