# Experiência da Área do aluno e registro móvel de testes — 25/09/2026

## Problemas observados

A validação visual em produção expôs falhas de produto, não detalhes cosméticos:

1. o site público não oferecia caminho evidente para a Área do aluno;
2. a Área do aluno parecia uma aplicação visualmente separada do site;
3. o registro de testes era um formulário longo de desktop, embora o uso real aconteça junto da câmera e do laboratório;
4. mídia editorial privada havia ganhado um uploader paralelo em vez de reutilizar a Biblioteca de mídia;
5. páginas legais, especialmente `privacidade`, herdavam composição promocional inadequada para leitura contínua.

## Autoridade visual

A Área do aluno **não possui tipografia, paleta ou tema próprios**. Ela consome:

- `template/page.css` como base visual;
- tokens e fontes dinâmicos configurados em `Design` para a atividade atual;
- `assets/student-area.css` somente para layout, estados e comportamento próprios do aplicativo.

Nomes de fontes ou cores não são hardcoded como decisão local da Área do aluno. Se o Design do site mudar, a Área do aluno acompanha a mesma configuração.

## Navegação

`Área do aluno` é uma ação estrutural do site e não depende da configuração editorial do menu. Toda página pública renderizada pelo CMS possui acesso para `/aluno/`.

Dentro da Área do aluno:

- desktop: `Cursos · Testes · Conta` na barra superior;
- celular: navegação principal fixa na base, com alvos adequados para toque;
- `Sair` continua disponível no desktop; ações de conta ficam agrupadas em `Conta`.

## Registro de teste

O registro acompanha a ordem real do trabalho e funciona como ferramenta de campo/laboratório.

### 01 — Exposição

O aluno fotografa/anexa a cena e registra identificação, filme/lote, EI/ISO, diafragma, tempo calculado, tempo corrigido pela reciprocidade, condição da luz e relação entre regiões claras e sombras.

### 02 — Revelação

O aluno registra revelador, diluição, temperatura, tempo, movimentação/agitação e observações; depois fotografa/anexa o resultado.

### 03 — Revisar e enviar

A tela reúne parâmetros essenciais e coloca cena e resultado de forma comparável antes do envio para avaliação.

`student_test_media.phase` diferencia `scene` e `result`. Arquivos continuam privados. No celular, os controles usam `accept="image/*"` e `capture="environment"` quando apropriado.

## Propriedade e compartilhamento

O aluno pode excluir definitivamente seus testes e todas as mídias/mensagens associadas. Cada teste pode ser:

- privado;
- compartilhado com a turma;
- compartilhado com os alunos do curso.

A conversa professor/aluno nunca faz parte do compartilhamento.

## Páginas legais

`privacidade` é texto longo: uma coluna editorial central, largura de leitura controlada e hierarquia do mesmo sistema visual público. Não usa composição promocional em duas colunas.

## Mídia editorial privada

Não existe upload em `Área do aluno → Páginas protegidas`. Upload e alteração de visibilidade acontecem em `Admin → Mídia`. A tela de página protegida somente vincula um slot semântico a um `media_asset` privado existente.

As fotografias dos testes dos alunos continuam fora da Biblioteca de mídia porque são anexos de registros individuais, não assets editoriais reutilizáveis.

## Regressão

Os testes devem verificar:

- acesso público à Área do aluno;
- navegação responsiva;
- herança real do Design global;
- três etapas do registro e captura de cena/resultado;
- exclusão integral do teste;
- visibilidade privado/turma/curso sem vazamento da conversa;
- ausência do uploader editorial paralelo;
- tratamento editorial da página de privacidade.
