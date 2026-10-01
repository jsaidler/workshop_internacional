# Área do aluno — auditoria integral de mecânicas — 2026-10-01

Esta revisão considera a Área do aluno como produto, não como conjunto de rotas. O critério é: a ação é compreensível, aparece no momento correto, pode ser corrigida quando necessário e o dado produzido volta a ser usado em algum lugar útil.

## Mapa de produto

### Acesso e conta
- `login.php` / `senha.php`: fluxo de autenticação separado do ambiente de estudo. Mantido.
- `perfil.php`: dados pessoais, senha e encerramento de sessão. Mantido como menu secundário; não deve competir com Curso/Caderno.

### Início
- `index.php`: retoma o registro atualizado mais recentemente e oferece Curso/Caderno como destinos secundários. Mecânica coerente.
- Não deve virar painel de atalhos de todas as ferramentas.

### Curso e material
- `cursos.php`: uma matrícula abre direto; múltiplas matrículas exigem escolha. Mecânica coerente.
- O material continua sendo conteúdo, não formulário.
- **Correção desta revisão:** anotações passam a ter entrada explícita no topo da página do material, além da gaveta flutuante. O editor continua fora das seções CMS.
- Uma anotação pode ser geral da página ou associada a um trecho; a associação é contexto, não posição física do formulário.

### Dúvidas
- `duvidas.php`: discussão pertence à turma e pode opcionalmente apontar para um registro do Caderno. Mantido.
- Privada = aluno/professor; Turma = discussão coletiva. A visibilidade é escolhida na criação.

### Caderno
- `caderno.php`: registro é a unidade principal do trabalho experimental.
- Criar, continuar, duplicar, compartilhar e excluir permanecem ações do registro.
- **Problema encontrado:** `comparar-processos.php` existia, mas a seleção de dois registros havia desaparecido da interface. Era funcionalidade órfã.
- **Correção desta revisão:** “Comparar registros” ativa um modo temporário de seleção dentro do próprio Caderno. Checkboxes só aparecem nesse modo; exatamente dois registros seguem para a comparação.

### Exposição
- Dados de cena/exposição pertencem ao registro.
- Reciprocidade calculada dentro da Exposição continua contextual; a ferramenta independente é apenas consulta rápida.
- Exposição equivalente continua como utilidade, não como etapa obrigatória.

### Processamento
- O processo é um log experimental editável, não um wizard irreversível.
- Cada etapa registrada pode ser corrigida sem apagar as seguintes.
- Alterar a sequência é uma ação destrutiva separada.
- O sistema sugere a continuação mais provável, mas “Registrar outra etapa” permanece disponível como exceção deliberada.
- **Problema encontrado:** todos os tipos não-revelação reutilizavam o mesmo bloco “Tempo + inventário + quantidade”. Isso gerava, por exemplo, “Quantidade utilizada” em Secagem e Lavagens.
- **Correção desta revisão:** campos passam a depender da natureza da etapa:
  - revelação: revelador, preparo, inventário quando aplicável, temperatura, tempo e agitação;
  - banho químico: tempo e inventário/quantidade somente quando houver item escolhido;
  - lavagem: tempo de lavagem, sem consumo de inventário;
  - secagem: apenas tempo de secagem opcional e anotações; sem temperatura/agitação/inventário;
  - outra etapa: nome e dados opcionais adequados ao registro livre.
- O backend também ignora inventário em lavagem/secagem e dados físicos incompatíveis com secagem, evitando que campos ocultos gerem dados absurdos.
- **Problema encontrado:** “Nome do revelador” aparecia sem explicar que era o complemento da opção “Outro”.
- **Correção desta revisão:** o campo passa a ser “Outro revelador”, com explicação curta e só é pertinente quando “Outro” estiver selecionado.

### Temporizador
- No processamento, acompanha uma etapa com duração.
- Não é exibido para Secagem nesta revisão.
- Fora do registro, permanece como utilidade de laboratório.

### Resultado e avaliação
- Resultado pertence ao mesmo registro e recebe imagem + anotação do resultado.
- Em registro de curso, envio para avaliação e conversa continuam vinculados ao registro.
- Registro revisado permanece bloqueado contra alterações posteriores.

### Ferramentas
- Reciprocidade, exposição equivalente, temporizador e receitas são utilidades contextuais.
- Toolbox é acesso rápido; `ferramentas.php` é a bancada completa. A duplicação é intencional apenas entre “rápido” e “completo”, não entre rotas independentes equivalentes.
- **Correção desta revisão:** metadado interno de procedência das receitas deixa de ser renderizado no HTML do aluno; a fonte permanece no código/pesquisa, não como copy de interface.

### Inventário
- Inventário representa matéria física disponível no laboratório.
- Saldo muda por movimentações; metadados do item são editados sem inventar entrada/saída fictícia.
- Lavagens e secagem não consomem inventário.
- Quantidade utilizada só deve aparecer quando existe um item de inventário efetivamente selecionado.

### Predefinições de revelação
- São configurações reutilizáveis de revelação, não frascos ou lotes físicos.
- Podem ser criadas e editadas.
- O catálogo amplo de reveladores é mantido, com Parodinal/Brewed Caffenol primeiro, outros reveladores em grupo secundário e “Outro” como caso personalizado.

### Referências de calibração
- São referências pessoais editáveis de tempo do branco.
- Continuam secundárias porque ainda não alimentam automaticamente outra ferramenta.
- Não devem receber mais destaque antes de existir integração real com o fluxo.

### Rotas legadas
- Rotas antigas de reciprocidade/exposição/temporizador podem redirecionar para a bancada; não devem reaparecer como destinos paralelos na navegação.

## Regras de mecânica daqui para frente

1. Um dado que o aluno cria precisa poder ser corrigido depois, salvo quando o bloqueio for deliberado (ex.: registro revisado).
2. Campo só aparece quando faz sentido para o objeto atual; formulário genérico não pode vazar campos de outro tipo de etapa.
3. Ação destrutiva nunca é misturada com edição comum.
4. Utilidade pequena deve ser contextual; página própria só quando a tarefa realmente exige espaço próprio.
5. Anotações pessoais não alteram a hierarquia editorial do material.
6. Metadados internos, nomes de arquivos, decisões de projeto e instruções de bastidor nunca são copy para o aluno.
7. Funcionalidade sem caminho de entrada ou sem uso posterior é dívida de produto: integrar, demotar ou remover — nunca apenas deixá-la escondida no código.
