# Contrato de interação do editor — 2026-09-26

## Critério de pronto

Uma função editorial só é considerada implementada quando a operação pode ser concluída pela interface, com feedback previsível e sem depender de recarregamentos com temporização fixa.

## Inserção de conteúdo

- Os controles `+ Antes`, `+ Depois`, `+ Adicionar` e `+ Conteúdo` pertencem exclusivamente à interface editorial e nunca entram no HTML canônico da página.
- A paleta aberta deve ficar acima das demais camadas editoriais e seus botões devem permanecer acionáveis por mouse, toque e teclado.
- A escolha de um componente deve produzir a alteração imediatamente no documento em edição.
- O editor deve salvar a alteração antes de qualquer recarregamento usado para reconstituir bindings; não é permitido recarregar a prévia após um atraso fixo sem confirmação do save.
- Falha de save não pode apagar silenciosamente a alteração local.

## Slots de mídia privada

- `[data-private-media-slot]` é um alvo editorial de mídia, não um bloco genérico e não deve ser promovido automaticamente para `data-cms-component="image"`.
- No editor, um slot vazio deve ser apresentado como `Infográfico pendente`/imagem privada pendente e ser selecionável.
- Ao selecionar o slot, o administrador deve poder escolher uma imagem privada da Biblioteca de mídia e vinculá-la ao slot sem substituir o markup canônico do slot por uma imagem pública.
- O vínculo deve persistir em `course_page_media_slots`.
- O HTML canônico conserva o slot; a resolução para `<img>` ocorre apenas na renderização autorizada.
- Slot vazio continua sem markup para o aluno. O placeholder é exclusivamente editorial.

## Regressão obrigatória

Testes de navegador devem executar o clique real na paleta, inserir componentes e comprovar persistência após o ciclo de save/reload. Devem também cobrir a seleção e o vínculo de um slot privado sem converter o slot em imagem pública.