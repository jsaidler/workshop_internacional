# Calculadora de reciprocidade no gerenciador de testes — 2026-09-29

## Objetivo

Na etapa **Exposição** da ficha de teste do aluno, `Tempo após reciprocidade` passa a ser calculado automaticamente no navegador a partir de `Tempo calculado`.

A operação é deliberadamente client-side. O backend continua armazenando os dois valores textuais enviados pela ficha e não recalcula nem normaliza o tempo.

## Autoridade da pesquisa

A referência é a planilha **Cálculos Pinhole**, em especial a folha `PINHOLE ~0,35`, que registra o **expoente de correção de reciprocidade 1,38542662**. A própria tabela confirma a progressão usada pelo sistema, por exemplo:

- 1 s → 1 s;
- 2 s → aproximadamente 2,612 s;
- 4 s → aproximadamente 6,825 s;
- 8 s → aproximadamente 17,831 s;
- 32 s → aproximadamente 121,696 s.

O contrato implementado é:

```text
se t <= 1 s: t_corrigido = t
se t > 1 s:  t_corrigido = t ^ 1,38542662
```

O cálculo usa segundos internamente.

## Formatos aceitos

O aluno não precisa converter o tempo para um formato único. O parser reconhece e preserva a notação sempre que ela puder representar o resultado:

- segundos simples: `4` → `6.825`;
- segundos com unidade: `4 s` → `6.825 s`;
- decimal brasileiro: `4,0 s` → `6,825 s`;
- `mm:ss`: `00:32` → `02:01.696`;
- `hh:mm:ss`: `00:00:04` → `00:00:06.825`;
- minutos ou horas com unidade: `2 min` → `12.659 min`;
- frações de segundo, como `1/30s`, permanecem inalteradas por estarem abaixo de 1 s.

Entradas não reconhecidas limpam o resultado automático em vez de manter um valor antigo incoerente.

## Implementação

- `assets/student-reciprocity.js`: parser, cálculo, formatação e binding dos campos;
- `app/student_shell.php`: carrega o asset com versão própria de cache;
- `tools/browser-fixture/student-reciprocity.html`: fixture isolada;
- `tools/browser-tests/student-reciprocity.spec.cjs`: regressão real no navegador;
- `tools/test-student-test-workflow.php`: guarda a integração e o expoente canônico.

O campo `reciprocity_time` fica somente leitura quando o JavaScript está ativo, mas continua sendo enviado pelo formulário e persistido como antes.
