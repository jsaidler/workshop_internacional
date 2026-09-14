# SEO — Direct Positive Workshop

Este documento registra a estratégia editorial de SEO do site do workshop. Os campos continuam editáveis em `Admin → SEO e compartilhamento`; a migração `031_fill_page_seo.php` apenas estabelece uma base profissional para o estado atual.

## Princípios

- Cada página atende uma intenção distinta. PT e EN não são traduções automáticas e não devem receber metadados idênticos.
- O tema primário é **positivo direto em filme de raio X / direct-positive X-ray film**. Termos relacionados — exposição, revelação por reversão, química, controle tonal e análise do resultado — entram somente quando descrevem de fato a página.
- Não repetir palavras-chave artificialmente. Título e descrição devem primeiro explicar com precisão o conteúdo e a ação disponível.
- A URL canônica fica vazia no registro editorial de SEO. O renderer já gera a canonical absoluta a partir do host público, da página e do locale corretos; hardcodar o domínio no banco tornaria staging/mudança de domínio mais frágeis.
- As páginas públicas atuais permanecem `index,follow`.
- A imagem social padrão dessas páginas é `/assets/media/hero-medusa.webp`, porque é uma obra produzida pelo processo ensinado e já funciona como principal prova visual do workshop.
- Páginas adicionais criadas no editor recebem fallback a partir do título e da descrição editorial da própria página. Se já possuírem SEO deliberadamente preenchido, a migração preserva esses valores.

## PT-BR — Home

**Intenção:** descoberta do workshop e compreensão da proposta. O usuário deve entender imediatamente que se trata de workshop online e ao vivo, de positivo direto em filme de raio X, conduzido por João Saidler.

**Título de busca**  
`Workshop: Positivo Direto em Filme de Raio X | João Saidler`

**Descrição de busca**  
`Workshop online e ao vivo de João Saidler sobre positivo direto em filme de raio X: exposição, revelação por reversão, química e análise de resultados.`

**Título social**  
`Positivo Direto em Filme de Raio X — Workshop ao vivo`

**Descrição social**  
`Três encontros online e ao vivo para compreender e controlar o positivo direto em filme de raio X, da exposição à revelação e à leitura do resultado.`

## PT-BR — Inscrição

**Intenção:** transacional. Esta página não deve competir com a home repetindo exatamente a mesma promessa; ela deve responder à busca de quem já decidiu avaliar ou realizar a inscrição.

**Título de busca**  
`Inscrição — Workshop de Positivo Direto em Filme de Raio X`

**Descrição de busca**  
`Inscreva-se na próxima turma do workshop online e ao vivo de positivo direto em filme de raio X com João Saidler. Três encontros, turma reduzida.`

**Título social**  
`Inscrição — Workshop de Positivo Direto em Filme de Raio X`

**Descrição social**  
`Próxima turma em português: três encontros online e ao vivo sobre exposição, revelação por reversão, química e análise dos resultados.`

## EN — Home / Interest survey

**Intent:** discovery plus qualified international interest. The page must not imply that enrollment is already open; its current conversion is the interest list for the first English-language group.

**Search title**  
`Direct Positive X-Ray Film Workshop | João Saidler`

**Search description**  
`Live online workshop with João Saidler on direct-positive X-ray film: exposure, reversal processing, tonal control and analysis of participants’ results.`

**Social title**  
`Direct Positive X-Ray Film — Live Online Workshop`

**Social description**  
`Join the interest list for three live online sessions on direct-positive X-ray film: exposure, reversal processing, tonal control and result analysis.`

## Reavaliação

Os metadados precisam ser revistos quando mudar a intenção da página, não a cada pequena edição de texto. Em particular, a home em inglês deve ser revista quando deixar de ser uma pesquisa de interesse e passar a aceitar inscrições/pagamento. A página brasileira de inscrição deve ser revista se o formato, número de encontros ou natureza da oferta mudar de forma material.
