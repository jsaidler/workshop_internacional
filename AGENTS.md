## Ambiente Windows e codificação

Quando executar comandos no PowerShell antes de manipular arquivos com acentuação, configurar a sessão com:

```powershell
chcp 65001
[Console]::InputEncoding=[Text.UTF8Encoding]::new($false)
[Console]::OutputEncoding=[Text.UTF8Encoding]::new($false)
$OutputEncoding=[Text.UTF8Encoding]::new($false)
```

Scripts existentes no diretório atual devem ser chamados com caminho explícito. Exemplo:

```powershell
.\build-dist.cmd
```

Não instruir o uso de `build-dist.cmd` isoladamente no PowerShell.

## Documentação canônica

As decisões do projeto devem ser mantidas nos documentos existentes do repositório em vez de criar versões paralelas. Quando uma regra mudar, atualizar o documento canônico correspondente.

Estado comercial/técnico atual a preservar:

- branch de produção: `wip/form-response-refinement-2026-07-16`;
- pacote verificável para o updater: `production-dist`;
- uma execução verde no GitHub não prova que a HostGator foi atualizada;
- `dist/` é artefato gerado e não deve ser editado manualmente;
- banco, configuração local, uploads, logs e `storage/updates/` são persistentes.

## Inscrição brasileira

O Google Form usado na operação real é a referência canônica para os dados que precisam ser coletados. Não inventar perguntas de marketing, perfil, experiência ou equipamento para “melhorar” a inscrição.

Campos canônicos atuais:

- Nome Completo;
- CPF para NF;
- Whatsapp com DDD;
- E-mail;
- Instagram;
- suporte `4x5"` ou `5x7"`;
- Endereço completo;
- Cidade/UF;
- CEP;
- disponibilidade;
- forma de pagamento;
- aceite.

Pagamento:

- Pix: R$ 698,00;
- cartão de crédito à vista ou parcelado: R$ 698 + taxas Mercado Pago;
- link de cartão: `https://mpago.la/1xvBsPV`;
- a vaga só é confirmada após confirmação do pagamento.

No admin, manter o fluxo simples: visualizar inscrição, registrar informação curta de pagamento, confirmar inscrição/pagamento, voltar para aguardando ou cancelar. Não transformar essa operação em CRM sem solicitação explícita.

## Conteúdo do workshop

Não reduzir o segundo encontro a “demonstração”. A descrição correta enfatiza fotografia e processamento ao vivo, o maior número possível de imagens e variação deliberada de exposição/parâmetros para comparar resultados.

A sequência é:

1. filme de raio-X, exposição e decisões anteriores à revelação;
2. produção fotográfica/processamento ao vivo com comparação de variações;
3. análise dos resultados produzidos pelos participantes.

O suporte dobrável anti-riscos é um objeto físico desenvolvido especificamente para manter a dupla emulsão do filme de raio-X afastada da bandeja durante o processamento.

## UI e editor

Não usar `line-height < 1` em títulos multilinha. A referência atual é `1.02`.

Foi observado o erro `Cannot set properties of null (setting 'textContent')` ao tentar editar uma página. Alterações no editor devem testar o fluxo real de abrir página, selecionar, editar, salvar, recarregar e publicar, e devem tratar alvos DOM ausentes antes de escrever em `textContent` ou propriedades equivalentes.
