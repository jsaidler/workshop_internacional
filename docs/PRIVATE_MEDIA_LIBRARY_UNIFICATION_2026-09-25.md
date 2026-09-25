# Biblioteca única de mídia privada — 25/09/2026

## Problema

O material protegido criou historicamente uma segunda cadeia de mídia (`student_private_media`) com upload, armazenamento, títulos e vínculo próprios. Isso duplicou responsabilidades já existentes na Biblioteca de mídia e produziu a interface “Imagens privadas” dentro de `Área do aluno → Páginas protegidas`.

A duplicação é incorreta. Privacidade é uma propriedade da mídia; não é motivo para criar uma segunda biblioteca.

## Decisão canônica

`media_assets` é a fonte única de mídia editorial do site.

Cada asset possui `visibility`:

- `public` — pode ser usado em conteúdo público;
- `private` — arquivo protegido, disponível ao administrador e, quando vinculado a material didático, a alunos autorizados.

Uma página protegida não faz upload. Seus slots armazenam somente a relação:

`page_id + slot_key → media_asset_id`

em `course_page_media_slots`.

## Fluxo administrativo

1. Abra **Mídia → Biblioteca**.
2. Envie ou abra a imagem desejada.
3. Em **Acesso**, marque **Privada**.
4. Em **Inscrições → Área do aluno → Páginas protegidas**, abra a página.
5. Na seção **Mídia protegida**, selecione o asset privado para cada slot.

Não existe segundo seletor de arquivo nessa área. Se ainda não há mídia adequada, o botão **Abrir Biblioteca** leva ao único lugar responsável por upload e metadata.

Um asset vinculado a um slot protegido não pode ser tornado público nem arquivado antes de ser removido desses slots.

## Proteção do arquivo

Marcar um asset como privado precisa proteger o arquivo real, não apenas esconder sua URL na interface.

Por isso, requisições para `/uploads/media/...` passam por `media-file.php`. O endpoint resolve o caminho para o asset correspondente:

- asset público: entrega normal com cache público;
- asset privado: somente administrador autenticado pode usar a URL direta;
- aluno: recebe uma URL assinada e temporária em `/aluno/media.php`, vinculada ao asset, página, turma e sessão de aluno.

A URL assinada só é aceita se o asset privado estiver efetivamente vinculado àquela página protegida e o aluno possuir matrícula ativa no contexto informado.

Vídeos servidos por `media-stream.php` também respeitam `visibility`; um vídeo privado não pode ser lido pela rota pública sem autenticação administrativa.

## Compatibilidade com mídia privada antiga

`student_private_media` deixa de ser caminho de escrita. O material ainda possui fallback somente de leitura para arquivos antigos já vinculados, para que uma atualização não quebre material existente.

Novos vínculos usam exclusivamente `media_assets` e `course_page_media_slots`. A interface antiga de upload é substituída pela seleção da Biblioteca. A remoção física da tabela/storage legado pode ocorrer somente depois de verificar que nenhum registro antigo continua em uso.

## Exceção: fotos dos testes dos alunos

`student_test_media` permanece separado. Essas imagens não são mídia editorial reutilizável do CMS: são anexos privados pertencentes ao registro experimental de um aluno, com ciclo de vida ligado ao teste. Excluir o teste exclui também esses anexos.

## Regressão

`tools/test-private-media-library.php` protege os seguintes contratos:

- `media_assets.visibility` existe;
- `course_page_media_slots` existe;
- a Área do aluno substitui o uploader antigo por vínculo a assets privados;
- a Biblioteca oferece `Pública` / `Privada`;
- `student_material.php` não volta a possuir um writer de upload editorial;
- slots protegidos resolvem `media_assets`;
- `/uploads/media/...` passa pelo gate de visibilidade;
- mídia pública não pode ser vinculada a slot privado.
