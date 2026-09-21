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

## Regra técnica para mudanças editoriais estruturais

A migração `042_pinhole_hybrid_format.php` mostrou uma fragilidade: mudanças baseadas em `str_replace` de frases exatas podem atualizar apenas parte de uma página quando o conteúdo já sofreu edições anteriores no CMS.

Para mudanças que alteram a arquitetura de uma oferta, não depender de frases antigas exatas. Normalizar os blocos estruturais pelo identificador semântico da seção (`data-cms-section`) e preservar os componentes de mídia e demais seções que não fazem parte da mudança.

A correção canônica é `migrations/043_pinhole_hybrid_coherence.php`, com regressão específica em `tools/test-pinhole-hybrid-coherence.php`.

## Pontos ainda não definidos

- período de acesso ao conteúdo em vídeo;
- plataforma de hospedagem dos vídeos;
- frequência/calendário dos encontros ao vivo;
- política para quem não puder comparecer ao encontro ao vivo;
- preço final e meios de pagamento;
- momento em que a lista de interesse será convertida em inscrição direta.

Esses pontos não devem ser inventados na página até serem decididos.
