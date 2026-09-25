# Experiência da Área do aluno e registro móvel de testes — 25/09/2026

## Problemas observados

A validação visual em produção expôs quatro falhas de produto que não podem ser tratadas como detalhes cosméticos:

1. o site público não oferecia um caminho evidente para a Área do aluno;
2. a Área do aluno parecia uma página administrativa estreita dentro de uma tela vazia, em vez de uma extensão coerente do site;
3. o registro de testes era um formulário longo de desktop, embora o uso real aconteça junto da câmera e do laboratório, frequentemente no telefone;
4. controles de upload de mídia privada no admin podiam extrapolar horizontalmente o card.

A mesma captura mostrou que páginas legais, especialmente `privacidade`, estavam herdando uma composição promocional em duas colunas e criando uma área vazia desproporcional para um texto de leitura contínua.

## Contrato de navegação

`Área do aluno` é uma ação estrutural do site e não depende da configuração editorial do menu. Toda página pública renderizada pelo CMS exibe acesso direto para `/aluno/`. Se o visitante ainda não estiver autenticado, o fluxo existente o leva ao login e depois ao destino protegido.

Dentro da Área do aluno:

- desktop: `Cursos · Testes · Conta` permanecem na barra superior;
- celular: a navegação principal fica fixa na base da tela, com alvos amplos para uso por toque;
- `Sair` continua disponível no desktop; ações de conta ficam agrupadas em `Conta` em vez de competir com a navegação primária.

## Contrato do registro de teste

O registro não é uma ficha burocrática. Ele acompanha a ordem real do trabalho e funciona como um pequeno aplicativo de campo/laboratório.

### Etapa 01 — Cena e exposição

O aluno:

1. registra ou anexa uma fotografia da cena usando a câmera traseira do telefone quando disponível;
2. informa identificação do teste, filme/lote, EI ou ISO de referência, diafragma, tempo inicialmente calculado, tempo após correção de reciprocidade, condição da luz e relação entre regiões claras e sombras que pretende preservar;
3. salva a exposição e segue para a revelação.

### Etapa 02 — Revelação e resultado

O aluno:

1. registra revelador, diluição/quantidade, temperatura, tempo, movimentação/agitação e observações do processo;
2. fotografa ou anexa o resultado já processado;
3. segue para a revisão.

### Etapa 03 — Revisar e enviar

A tela reúne os parâmetros essenciais e coloca, de forma visualmente comparável, a fotografia da cena e a fotografia do resultado. Depois disso o aluno envia o teste para avaliação. A conversa com o professor permanece vinculada ao mesmo teste.

## Mídia

`student_test_media` passa a diferenciar `scene` e `result`. Arquivos continuam privados, servidos somente pelos endpoints autenticados e sujeitos aos mesmos limites de formato, tamanho e quantidade. Registros de mídia existentes recebem `result` como compatibilidade histórica.

No celular, os controles usam `accept="image/*"` compatível com os formatos aceitos e `capture="environment"` para oferecer a câmera traseira quando o navegador suporta essa capacidade.

## Direção visual

A Área do aluno continua usando a identidade do projeto:

- fundo neutro claro;
- preto como cor estrutural;
- verde existente como acento funcional;
- `Saira Extra Condensed` para títulos;
- IBM Plex Sans para leitura;
- IBM Plex Mono para rótulos, estados e parâmetros.

A mudança não introduz estética de dashboard SaaS. O objetivo é uma ferramenta editorial/técnica ligada ao workshop, com hierarquia clara, superfícies contidas e controles dimensionados para toque.

## Páginas legais

`privacidade` passa a ser tratada como texto longo: uma coluna editorial central, título e rótulo no mesmo eixo, largura de leitura controlada e entrelinha apropriada. Não usa a divisão visual de uma seção promocional.

## Admin de mídia privada

Formulários dentro de `Área do aluno → Páginas protegidas → Imagens privadas` têm `min-width: 0`, controles limitados a 100% da célula e `input[type=file]` contido pelo card. Em telas pequenas, o formulário cai para uma coluna.

## Regressão

`tools/test-student-mobile-experience.php` impede regressões estruturais verificando:

- presença do acesso público à Área do aluno;
- navegação responsiva da Área do aluno;
- três etapas do registro;
- controles de captura para cena e resultado;
- persistência separada das fases de mídia;
- estilos de navegação e ação móvel;
- tratamento editorial da página de privacidade;
- proteção contra overflow no upload de mídia privada.
