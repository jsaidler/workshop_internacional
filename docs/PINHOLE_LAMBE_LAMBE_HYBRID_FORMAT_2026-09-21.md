# Pinhole Lambe-Lambe — formato híbrido

Data: 21/09/2026

## Decisão vigente

A Oficina Pinhole Lambe-Lambe deixa de ser concebida como uma aula integral de 2–3 horas transmitida ao vivo. O formato aprovado combina conteúdo gravado com um encontro ao vivo.

A oferta passa a ser composta por:

- projeto completo da câmera em PDF, com lista de materiais e peças imprimíveis;
- demonstração completa da construção em vídeo, para o participante acompanhar e montar no próprio ritmo;
- conteúdo introdutório sobre princípio da pinhole, características da imagem, falha de reciprocidade e operação do equipamento;
- um encontro ao vivo de 60–90 minutos com João Saidler, separado do conteúdo gravado, dedicado à operação, às dúvidas surgidas durante a montagem e à conversa direta sobre a câmera;
- o encontro ao vivo não é gravado;
- fotografia e revelação não fazem parte da entrega da oficina.

## Função comercial

O formato híbrido preserva o contato direto com João — importante para confiança, relacionamento e continuidade para produtos maiores — sem exigir que toda a demonstração construtiva seja repetida ao vivo a cada turma.

A construção em vídeo melhora a qualidade da demonstração, permite close, cortes e repetição de etapas e reduz a dependência de horário para a parte mais longa do conteúdo. O encontro ao vivo permanece como elemento de diferenciação frente a um tutorial gravado comum.

Publicamente, a oferta não deve ser apresentada como “curso gravado + chamada”. A comunicação deve vender a combinação de três entregas complementares: projeto completo, construção em vídeo e encontro ao vivo com o autor da câmera.

## Arquitetura pública canônica

A página deve comunicar o formato inteiro de maneira coerente em todos os pontos de decisão. Não pode coexistir copy do formato antigo com copy híbrida.

Hero:

- “Oficina on-line · construção em vídeo + encontro ao vivo”;
- formato: “Vídeos + encontro ao vivo”;
- encontro: “60 a 90 minutos”;
- projeto: “PDF completo da câmera”;
- construção: “No seu ritmo”.

Seção da oferta:

- título: “O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.”;
- eixo 1: Projeto em PDF;
- eixo 2: Construção em vídeo;
- eixo 3: Encontro ao vivo.

O conteúdo sobre imagem, exposição e princípio da pinhole continua fazendo parte da oficina, mas não ocupa o mesmo nível visual das três entregas que estruturam a oferta.

FAQ:

- manter “A câmera é funcional?” como objeção comercial importante;
- distinguir explicitamente o conteúdo em vídeo do encontro ao vivo;
- formulação vigente para gravação: a construção da câmera faz parte do conteúdo em vídeo; o encontro com João acontece ao vivo e não é gravado;
- fotografia e revelação continuam fora do escopo da oficina.

## Integridade estrutural da página — correção de 21/09/2026

A atualização `043_pinhole_hybrid_coherence.php` corrigiu parte da copy, mas revelou outro problema: aplicar substituições estruturais por expressões regulares sobre HTML já transformado pelo CMS pode produzir um documento diferente do esperado na hospedagem. O sintoma observado foi inequívoco: a seção da oferta passou a exibir dois parágrafos equivalentes, e as seções posteriores — autoridade, FAQ e formulário — desapareceram da página renderizada, fazendo o rodapé surgir logo após a oferta.

Essa condição invalida a estratégia de continuar aplicando pequenos patches sobre o HTML já deformado.

A correção vigente é `migrations/044_repair_pinhole_page_integrity.php`. Ela executa uma reconstrução canônica única do documento público da Pinhole com as seis seções aprovadas, na ordem:

1. hero;
2. câmera/projeto;
3. oferta;
4. autoridade de João;
5. FAQ;
6. lista de interesse.

A reconstrução preserva deliberadamente os três placeholders de mídia, o retrato da seção de autoridade, a NINA, as premiações/publicações e o formulário existente. Ela elimina a copy duplicada e restaura as seções desaparecidas.

A regressão `tools/test-pinhole-page-integrity-repair.php` parte de um HTML deliberadamente corrompido que reproduz o sintoma observado — página truncada após a oferta e parágrafo híbrido duplicado — e exige a restauração integral da arquitetura antes de o CI passar.

Regra resultante: quando a integridade estrutural do HTML persistido estiver em dúvida, não tentar “consertar” o documento com mais regex ou substituições de frases. Reconstruir a superfície editorial canônica uma vez e voltar a deixar futuras alterações de conteúdo para o CMS.

## Pontos ainda não definidos

- período de acesso ao conteúdo em vídeo;
- plataforma de hospedagem dos vídeos;
- frequência/calendário dos encontros ao vivo;
- política para quem não puder comparecer ao encontro ao vivo;
- preço final e meios de pagamento;
- momento em que a lista de interesse será convertida em inscrição direta.

Esses pontos não devem ser inventados na página até serem decididos.
