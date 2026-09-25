# Área do aluno — acesso, coerência visual e registro móvel de testes — 25/09/2026

## Evidência que motivou a correção

A validação visual revelou três problemas de produto, não apenas de CSS:

1. o site público não oferecia caminho visível para a Área do aluno;
2. a Área do aluno parecia um produto paralelo e inacabado, com identidade `Direct Positive Workshop`, pouca densidade de informação, grande vazio de página e linguagem visual diferente do site público;
3. o registro de testes era uma ficha longa de desktop, embora a situação real de uso aconteça principalmente ao lado da câmera e no laboratório, com o telefone na mão.

A mesma validação mostrou ainda um defeito no admin: o formulário de **Imagens privadas** podia ultrapassar a largura do card porque o texto longo do slot e o `input[type=file]` participavam de uma grade de duas colunas sem contenção adequada.

## Decisão de produto

A Área do aluno não é um microsite. Ela é uma extensão autenticada de **João Saidler Fotografia** e deve manter o mesmo vocabulário visual do site: fundo, superfícies, linhas, verde de foco, famílias tipográficas, escala de títulos e tema claro/escuro.

O caderno de testes, por sua vez, é uma ferramenta de campo. Em telas pequenas deve se comportar como uma aplicação móvel de registro, com uma tarefa por etapa e ações grandes o bastante para uso com o telefone.

## Acesso pelo site público

A navegação pública recebe um item persistente:

- `Área do aluno` em português;
- `Student area` em inglês;
- destino `/aluno/`.

A entrada é adicionada aos itens configuráveis do próprio CMS. Não existe um cabeçalho paralelo ou um link escondido apenas em rodapé.

## Identidade e navegação autenticada

O shell autenticado passa a usar:

- wordmark `João Saidler Fotografia`, voltando ao site público;
- navegação desktop `Cursos · Testes · Perfil`;
- alternância `Auto · Light · Dark`, compartilhando a preferência `workshop-theme` usada pelo site público;
- navegação inferior fixa em celular para `Cursos · Testes · Perfil`;
- superfícies e tokens equivalentes aos do site público nos temas claro e escuro.

A tela inicial deixa de ser um único card perdido no canvas. Ela funciona como painel do aluno: cursos, estado de liberação das aulas, acesso ao material, ação **Registrar teste** e registros recentes.

## Caderno móvel de testes

Um teste continua sendo um único registro persistente, mas a interface o divide de acordo com a sequência real de trabalho.

### 01 — Cena e exposição

O aluno registra:

- título e data;
- filme e lote;
- ISO/EI usado como referência;
- diafragma;
- tempo calculado;
- tempo com correção de reciprocidade;
- condição da luz;
- regiões claras e sombras que pretende preservar.

Na mesma etapa existe **Foto da cena**. Em telefone, `Fotografar cena` abre a câmera traseira por meio de `capture="environment"`; `Escolher foto` permite usar uma imagem já existente. A foto é enviada ao armazenamento privado e marcada semanticamente como `scene`.

### 02 — Revelação

O aluno registra a condição efetivamente utilizada:

- revelador;
- diluição/quantidade;
- temperatura;
- tempo de revelação;
- movimentação/agitação.

Salvar esta etapa não regrava os campos da exposição. Cada etapa atualiza somente os campos pelos quais é responsável.

### 03 — Resultado

O aluno:

- fotografa uma ou mais imagens do resultado;
- pode escolher imagens já existentes;
- registra observações sobre o resultado e o próximo ajuste;
- envia o conjunto para avaliação.

Fotos desta etapa recebem `media_kind=result`. As imagens antigas existentes antes da migração assumem `result`, preservando compatibilidade.

A conversa de avaliação permanece vinculada ao teste. O fluxo de estados continua `draft → submitted → needs_revision → reviewed`.

## Mídia e privacidade

A regra anterior permanece: JPEG, PNG e WebP, máximo de 12 MB por arquivo e até seis imagens no total por teste. O arquivo continua em `storage/student-test-media/`, fora do acesso HTTP direto, e só é servido após autorização.

A nova coluna `media_kind` não cria outra biblioteca de mídia; ela apenas distingue a função editorial da imagem no mesmo teste:

- `scene` — referência da cena antes/na exposição;
- `result` — fotografia do resultado processado.

## Responsividade e qualidade

### Celular

- uma coluna de formulário;
- progresso de três etapas sempre legível;
- controles primários com aproximadamente 48 px de altura mínima;
- botões específicos para câmera e galeria;
- navegação inferior fixa com safe-area;
- nenhum overflow horizontal;
- título, campos e imagens ocupam o canvas útil, sem miniaturizar uma interface de desktop.

### Desktop

- o mesmo conteúdo permanece editorial e alinhado ao site;
- navegação de topo substitui a barra inferior;
- largura útil fica limitada para evitar formulários espalhados por toda a tela;
- curso, materiais, estado das aulas e testes recentes usam o espaço sem deixar a página parecer vazia.

## Admin — Imagens privadas

O upload de infográficos passa a usar uma única coluna controlada. `select`, título e seletor de arquivo recebem `min-width:0`, `max-width:100%` e largura contida no card. O texto semântico longo do slot pode ser truncado visualmente pelo controle sem alterar seu valor.

A regra deve funcionar tanto em desktop quanto em 390 px sem criar scroll horizontal na página.

## Regressão

A validação automática cobre:

- existência do item público `/aluno/`;
- remoção da identidade paralela `Direct Positive Workshop` do shell do aluno;
- identidade `João Saidler Fotografia`;
- navegação móvel inferior;
- dashboard em vez da antiga página esparsa;
- três etapas explícitas do teste;
- captura direta pela câmera;
- papéis `scene` e `result`;
- atualizações parciais por etapa;
- ausência de overflow na interface móvel;
- área mínima dos controles de captura;
- formulário de Imagens privadas contido no card em desktop e celular.
