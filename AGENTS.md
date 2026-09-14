# Regras de operação no repositório

## Leitura obrigatória antes de trabalhar

Antes de alterar código, conteúdo, CMS, deploy ou fluxo administrativo, ler nesta ordem:

1. `docs/PROJECT_STATE.md`;
2. `docs/CMS_PROFESSIONAL.md`;
3. `docs/CMS_V3_DEPLOYMENT.md`;
4. `README.md`;
5. `DEPLOY.md`.

Não reconstruir decisões pela memória quando os documentos vigentes disserem algo diferente. Quando uma decisão estrutural, editorial ou operacional mudar, atualizar os documentos canônicos no mesmo trabalho; não deixar a documentação para uma etapa futura.

Quando um defeito se reproduzir em várias páginas, idiomas ou instâncias de um componente, tratar como problema sistêmico. Corrigir a camada compartilhada responsável e adicionar regressão nesse mesmo nível. Não usar CSS, HTML, JavaScript ou conteúdo específico de página, locale, formulário, ID ou bloco para mascarar um problema global, salvo quando a exceção for deliberada e documentada.

## Regra obrigatória: correção definitiva

Não encerrar um defeito com uma contenção quando a causa raiz estiver no próprio sistema.

- Um workaround pode ser usado temporariamente apenas para proteger produção enquanto a causa raiz é investigada.
- A correção final deve remover ou tornar desnecessário o workaround, corrigir a camada responsável, reparar o estado legado afetado quando necessário e adicionar regressão que reproduza a classe real da falha.
- Não considerar um problema resolvido apenas porque o sintoma deixou de aparecer em uma página específica.
- Quando existirem dois caminhos diferentes para produzir o mesmo artefato, consolidar o comportamento em uma implementação canônica em vez de manter algoritmos paralelos que possam divergir.
- Migrações corretivas devem ser não destrutivas: preservar originais e dados editoriais, reconstruir somente artefatos derivados e invalidar derivados defeituosos quando a reconstrução falhar.

### Pipeline de imagens

- O arquivo original enviado é a fonte de verdade imutável de uma versão de mídia.
- Derivadas responsivas são artefatos descartáveis e devem ser reconstruíveis a partir do original.
- ImageMagick deve normalizar `page/virtual canvas` antes e depois do redimensionamento; metadados de canvas virtual nunca podem alterar a geometria raster da derivada.
- Toda derivada deve ser verificada após a gravação quanto a dimensões raster, geometria de página virtual e preservação de transparência quando aplicável antes de ser registrada como válida.
- O caminho de upload e o caminho de regeneração manual/automática devem usar o mesmo writer e as mesmas validações.

### CSS adicional de Design

O campo `Design → CSS adicional` é a última camada editorial de CSS do site.

- O CSS gerado pelo sistema e o CSS adicional devem ser emitidos em elementos `<style>` separados.
- `#cms-custom-css` deve ser o último estilo autoral renderizado no `<head>` público e no preview normal.
- A prévia ao vivo de Design deve reaplicar seu estilo adicional no fim do `<head>` em cada atualização.
- Regras estruturais do sistema não devem usar `!important` sem necessidade quando isso impedir uma sobrescrita deliberada pelo CSS adicional.
- Qualquer novo estilo inline ou dinâmico do CMS deve respeitar essa precedência ou ser inserido antes da camada de CSS adicional.

## Segurança de interação no editor visual

O editor WYSIWYG deve ser dirigido pela interação direta do usuário, não por ciclos automáticos de re-renderização do inspector.

- Não usar `MutationObserver` do inspector para reescrever o próprio inspector como mecanismo de seleção ou sincronização.
- Não encadear `Promise.resolve(...).then(...)` ou callbacks de carregamento que chamem novamente a mesma função de renderização sem uma mudança explícita de estado; isso pode criar loop de microtasks e congelar a interface.
- Um clique em texto editável deve ser resolvido diretamente a partir do elemento clicado, interceptando o evento antes do wrapper apenas quando necessário.
- Formulários inseridos na página não devem ser convertidos em uma única entidade textual. Rótulos, opções, ajuda, controles e texto do botão são subalvos independentes.
- Um formulário já renderizado (`data-cms-form-block`) não pode voltar ao inspector legado de formulário inteiro. O editor estrutural completo só pode ser aberto por ação explícita do usuário.
- Nunca instalar um handler que bloqueie genericamente todo `pointerdown` ou `click` apenas porque ocorreu dentro de `[data-cms-form-block]`. Se um subalvo não for reconhecido, a interação deve continuar para os demais mecanismos do editor em vez de se tornar uma área morta.
- O resolvedor de subalvos não pode depender apenas de um `span` específico como `event.target`; deve reconhecer a estrutura real de `label`, `legend`, `.cms-field`, `.cms-consent`, controles e botão.
- Os alvos do formulário devem ser preparados quando o documento do iframe é instalado, e o controlador deve funcionar tanto em `load` futuro quanto quando o documento já estiver disponível.
- Alterações no preview usadas apenas para edição não podem persistir no HTML público da página.

A hospedagem é atualizada normalmente pelo usuário em `Admin → Sistema e atualizações`. O CI publica o pacote em `production-dist`; isso não significa, por si só, que a hospedagem já foi atualizada.

## Ambiente Windows e codificação

Quando executar comandos no PowerShell antes de manipular arquivos com acentuação, configurar a sessão com:

```powershell
chcp 65001
[Console]::InputEncoding=[Text.UTF8Encoding]::new($false)
[Console]::OutputEncoding=[Text.UTF8Encoding]::new($false)
$OutputEncoding=[Text.UTF8Encoding]::new($false)
```
