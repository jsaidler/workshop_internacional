# Correções da Área do Aluno — navegação móvel e expiração de sessão

Data: 2026-10-05

## Navegação móvel

A navegação inferior da Área do Aluno é um elemento persistente de aplicação no celular e deve permanecer ancorada à borda inferior da viewport.

`assets/student-area.css` já definia `.student-mobile-nav` como `position: fixed` e reservava espaço inferior no `.student-shell`. O `student-quality-pass.css` anulava esse contrato em telas de até 820 px, transformando a navegação em `position: static` e reduzindo o espaço reservado pelo shell.

A correção remove essa anulação. O quality pass continua responsável pelos ajustes móveis de densidade, mas não muda o posicionamento estrutural da navegação inferior.

A regressão visual deve confirmar que, em telefone:

- `.student-mobile-nav` tem `position: fixed`;
- a borda inferior da navegação coincide com a borda inferior da viewport;
- o shell reserva ao menos a altura da navegação, impedindo que conteúdo útil fique escondido atrás dela.

## Expiração de sessão em material protegido

`current_student()` continua sendo a autoridade de validade da sessão e remove a sessão expirada por inatividade ou limite absoluto.

Quando uma página CMS pertence a material de curso ou possui acesso não público, a ausência de um aluno autenticado deve ser tratada antes da filtragem de acesso do documento. O request é redirecionado para `/aluno/login.php`, preservando a URL original em `next`.

Isso impede o estado incorreto em que uma sessão expirada fazia o renderer continuar na mesma URL e apenas remover as seções protegidas do HTML.

Páginas públicas que não pertencem a material de curso continuam públicas e não são redirecionadas.
