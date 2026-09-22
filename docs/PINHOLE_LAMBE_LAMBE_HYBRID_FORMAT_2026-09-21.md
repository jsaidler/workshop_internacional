# Pinhole Lambe-Lambe — formato híbrido e oferta em duas versões

Data: 21/09/2026

## Decisão vigente

A Oficina Pinhole Lambe-Lambe combina conteúdo gravado com encontro ao vivo e passa a existir em **duas versões comerciais independentes**.

Cada versão entrega:

- projeto completo da câmera em PDF;
- demonstração da construção correspondente em vídeo, para o participante montar no próprio ritmo;
- conteúdo introdutório necessário para compreender a pinhole, a imagem e a operação do equipamento;
- um encontro ao vivo de 60–90 minutos com João Saidler, dedicado à operação, dúvidas de montagem e conversa sobre o projeto;
- o encontro ao vivo não é gravado;
- fotografia e revelação não fazem parte da entrega da oficina.

## Duas versões

### Materiais alternativos — R$ 98

A construção usa container plástico, pasta de escritório e outros materiais simples de encontrar. O valor comercial está nas soluções construtivas usadas para transformar objetos cotidianos em uma câmera-laboratório funcional, sem depender de marcenaria.

### Madeira — R$ 98

A construção usa estrutura em madeira. O participante pode cortar as próprias peças se tiver ferramentas e espaço adequados ou encomendar as peças já cortadas nas medidas indicadas no projeto e fazer apenas a montagem.

### Combo — R$ 168

O combo inclui os dois projetos e os dois conteúdos de construção. A economia é de R$ 28 em relação à compra separada.

Cada versão deve continuar sendo percebida como produto completo. O combo é um upsell para quem quer explorar as duas soluções, não requisito para obter uma experiência suficiente.

## Crédito para o Workshop Positivo Direto

Quem adquirir uma das versões pode usar **R$ 98 como crédito** na inscrição do Workshop Positivo Direto em Filme de Raio-X, caso decida continuar.

Quem adquirir o combo também tem crédito máximo de **R$ 98**. A compra das duas versões não duplica o crédito.

Na comunicação pública, usar “crédito” e não “desconto”. O objetivo é preservar o valor próprio da Pinhole Lambe-Lambe e apresentar a continuidade como progressão, não como cupom promocional.

## Função comercial

A divisão em duas versões surgiu após pesquisa no Instagram em que o interesse por câmera de madeira e câmera feita com materiais alternativos ficou praticamente empatado. Em vez de aumentar a carga de produção e suporte dentro de um único ticket, cada caminho construtivo vira uma oferta autônoma.

A estrutura resolve quatro problemas:

- atende os dois grupos de interesse sem obrigar todos a receber dois projetos;
- mantém o ticket de entrada baixo;
- evita duplicar o trabalho dentro de uma única oficina de R$ 98;
- cria um upsell simples e compreensível com o combo de R$ 168.

O formato híbrido continua preservando contato direto com João sem exigir repetição integral da demonstração construtiva ao vivo a cada turma.

## Arquitetura pública canônica

A página passa a comunicar:

### Hero

- oficina on-line;
- duas versões;
- construção em vídeo + encontro ao vivo;
- materiais alternativos ou madeira;
- R$ 98 por versão;
- R$ 168 pelas duas.

### Escolha do projeto

A página apresenta três opções no mesmo nível:

1. Materiais alternativos — R$ 98;
2. Madeira — R$ 98;
3. As duas — R$ 168.

A versão de materiais alternativos deve citar container plástico, pasta de escritório e materiais simples de encontrar. A versão em madeira deve explicar que o participante pode cortar as peças ou encomendar o corte nas medidas do projeto.

### O que cada versão entrega

- projeto em PDF;
- construção em vídeo;
- encontro ao vivo de 60–90 minutos.

No combo, o comprador recebe os dois projetos e os dois conteúdos construtivos.

### Crédito

A página deve informar de forma clara, mas secundária, que R$ 98 podem virar crédito no Workshop Positivo Direto. No combo, o crédito máximo continua sendo R$ 98.

### Formulário de interesse

O formulário deve pedir a opção de interesse por radio obrigatório:

- Materiais alternativos — R$ 98;
- Madeira — R$ 98;
- As duas — R$ 168.

O CTA deixa de prometer “data e valor”, porque o valor agora está definido. A função passa a ser receber a data de abertura da primeira turma.

## Integridade estrutural da página

As correções anteriores mostraram que regex e `str_replace` sobre HTML persistido podem deixar a página em estado parcial ou truncado. A atualização estrutural vigente para esta nova oferta é `migrations/045_pinhole_two_versions_offer.php`, que reconstrói a superfície canônica da página e atualiza o schema do formulário de interesse na mesma migração.

A regressão `tools/test-pinhole-two-versions-offer.php` exige:

- presença das três opções e preços;
- materiais alternativos e madeira descritos corretamente;
- combo a R$ 168 e economia de R$ 28;
- crédito máximo de R$ 98;
- preservação de autoridade, NINA, STRKNG #88/#89 e três slots de mídia;
- escolha obrigatória da versão no formulário;
- remoção da promessa antiga baseada apenas em papel paraná/lata/fita isolante.

## Pontos ainda não definidos

- período de acesso ao conteúdo em vídeo;
- plataforma de hospedagem dos vídeos;
- frequência/calendário dos encontros ao vivo;
- política para quem não puder comparecer ao encontro ao vivo;
- meios de pagamento;
- momento em que a lista de interesse será convertida em inscrição direta.

Esses pontos não devem ser inventados na página até serem decididos.
