# Importação histórica de alunos por CSV — 2026-09-25

## Decisão

A interface administrativa de importação histórica de alunos passa a apresentar **CSV como formato canônico**. O objetivo é reduzir ambiguidade de formato, eliminar dependência visual de planilhas proprietárias e oferecer um modelo simples que possa ser aberto e editado em qualquer editor de planilhas ou texto.

A importação continua pertencendo a `Admin → Inscrições → Área do aluno → Alunos` e exige associação explícita a uma turma existente.

## Modelo

A própria tela oferece o download de `/assets/modelo-importacao-alunos.csv`.

O arquivo usa ponto e vírgula como separador e contém somente o cabeçalho, evitando que uma linha fictícia seja importada acidentalmente. A tela apresenta separadamente um exemplo de preenchimento.

Cabeçalho canônico:

```text
Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP
```

Campos obrigatórios:

- `Nome`
- `E-mail`
- `CPF`

Campos opcionais:

- `Telefone`
- `Instagram`
- `Endereço`
- `Cidade/UF`
- `CEP`

## Fluxo do admin

A tela continua sendo renderizada dentro de `Área do aluno → Alunos`. Quando o formulário de importação está aberto, o comportamento do admin:

- apresenta `Importar CSV` em vez de `Importar planilha`;
- limita o seletor de arquivo a `.csv`;
- oferece `Baixar modelo CSV`;
- mostra uma linha de exemplo separada do arquivo modelo;
- envia o formulário para `/admin/student-import-csv.php`, que rejeita extensões diferentes de `.csv` antes de chamar o importador histórico.

## Compatibilidade

O parser existente continua tolerante a formatos históricos que já eram aceitos, mas essa compatibilidade fica fora do fluxo canônico. A interface e o endpoint administrativo atuais trabalham somente com CSV.

## Regressão

`tools/test-student-area-information-architecture.php` verifica que:

- a interface restringe o seletor a CSV;
- o formulário usa o endpoint CSV-only;
- o endpoint rejeita extensões diferentes de CSV;
- o modelo para download continua presente;
- a tela continua mostrando uma linha de exemplo;
- o cabeçalho do arquivo modelo não é alterado silenciosamente.
