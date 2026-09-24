# Área do aluno — autenticação e materiais protegidos

## Objetivo

A área do aluno protege materiais que não devem ser publicados como arquivos ou páginas abertas. Cada participante recebe uma conta individual; não existe senha compartilhada da turma.

## Princípios de segurança

- contas individuais com senha armazenada apenas como `password_hash()`;
- senha temporária obrigatoriamente trocada no primeiro acesso;
- sessão regenerada no login e no logout;
- limitação de tentativas de login por combinação de e-mail e IP;
- CSRF nos formulários de login administrativo, logout e troca de senha;
- materiais enviados para a área do aluno são armazenados no SQLite persistente da instalação, nunca no repositório público;
- páginas protegidas enviam `Cache-Control: private, no-store` e `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet`;
- o HTML protegido recebe identificação visual do aluno autenticado para desencorajar redistribuição casual;
- desativar a matrícula corta o acesso imediatamente sem apagar a conta ou o material.

## Modelo de dados

`student_users` mantém identidade, hash de senha, estado e último login.

`student_enrollments` vincula um aluno a uma atividade/site. A mesma conta pode ter acesso a mais de uma atividade sem duplicação de credenciais.

`student_materials` armazena título, slug, idioma, estado e o HTML integral do material. O conteúdo fica dentro de `storage/database.sqlite`, que já é persistente e bloqueado para acesso HTTP.

`student_login_attempts` registra apenas uma chave HMAC derivada de e-mail + IP para limitar tentativas; não armazena o IP em claro.

## Operação

No admin, `Inscrições → Área do aluno` permite:

- criar ou reativar acesso individual;
- gerar senha temporária;
- redefinir senha;
- ativar/desativar a matrícula;
- importar um HTML protegido;
- publicar ou recolher o material.

O material deve ser importado como HTML autônomo. Quando houver imagens exclusivas do material, a opção preferida é incorporá-las ao próprio HTML como `data:` para que não exista uma URL pública separada. Isso também evita colocar o conteúdo protegido no repositório público.

## Rotas

- `/aluno/login.php`
- `/aluno/`
- `/aluno/senha.php`
- `/aluno/material.php?slug=...`
- `/aluno/logout.php`

## Limite real da proteção

Autenticação impede acesso público, indexação e URLs diretas abertas. Nenhum sistema web consegue impedir um usuário autorizado de fotografar a tela, fazer captura ou copiar manualmente o que consegue ler. A identificação individual no material funciona como camada de responsabilização, não como DRM absoluto.
