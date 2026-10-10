# Auditoria visual real da área do aluno — 2026-10-02

## Motivo

A auditoria visual anterior não era suficiente para aprovar a interface. Ela verificava overflow, presença de elementos e screenshots, mas alguns fixtures abreviavam justamente os estados mais densos. Com isso, uma tela podia passar tecnicamente e ainda representar mal o produto real.

Esta correção passa a tratar a inspeção visual como gate de produto, não como simples regressão técnica.

## Falhas encontradas na auditoria anterior

- O editor declarava um roteiro de 9 etapas, mas o fixture mostrava apenas 4 etapas.
- O runner declarava 9 etapas, mas a linha do tempo mostrava apenas 5.
- O registro retroativo parcial declarava 5 etapas, mas mostrava apenas 1.
- O registro retroativo completo declarava 9 etapas, mas mostrava apenas 2.
- A seleção de padrões mostrava apenas 2 padrões, embora o catálogo real tenha 6.
- A página de material usa um fixture mínimo e não representa uma página real de conteúdo; não pode ser considerada aprovada visualmente por esse screenshot.

## Leitura crítica da interface atual

### Caderno

O registro repete contexto em camadas sucessivas: cabeçalho do registro, navegação Exposição/Processamento/Resultado, título interno da etapa e, em seguida, outra pergunta ou card de estado. No mobile isso cria mais leitura de interface do que leitura do próprio registro.

Decisão: a navegação por três partes já informa onde o aluno está. O título interno “01 · Exposição / Como expus”, “02 · Processamento / Como revelei” e “03 · Resultado / O que obtive” não precisa permanecer visível no mobile.

### Escolha do caminho de processamento

“O processamento já aconteceu?” é a decisão necessária. Os rótulos adicionais “Sim/Não”, explicações e microcabeçalhos “Agora/Já realizado” repetem a mesma informação.

Decisão: manter a pergunta e duas ações inequívocas; reduzir os rótulos intermediários.

### Roteiro associado

Nome do roteiro, progresso e próxima etapa são informação útil. A repetição de “Agora”, “Já realizado”, “Revelar no laboratório”, “Registrar o que foi feito” cria uma árvore visual desnecessária.

Decisão: preservar as duas ações reais e reduzir microhierarquias no mobile.

### Registro retroativo

A tela começa com título, explicação, ajuda, data e observações antes de chegar à escolha do roteiro. Depois, a lista real pode conter 2 roteiros pessoais + 6 padrões. A versão anterior do fixture escondia esse peso.

Decisão: reduzir texto permanente, manter ajuda em disclosure e tornar opções linhas compactas. O histórico de etapas deve permanecer completo no audit, mas visualmente compacto.

### Biblioteca de processamentos

Com seis padrões, cartões grandes geram uma página excessivamente longa. O nome do padrão já contém revelador, EI e rota de branqueamento; repetir tudo em chips e descrição gera ruído.

Decisão: no mobile, padrão deve funcionar como linha de escolha compacta. Descrição longa é secundária. Metadados repetidos devem ser reduzidos.

### Editor de roteiro

A tela real de 9 etapas é longa por natureza. O problema não é o número de etapas, e sim fazer cada etapa ocupar mais espaço do que precisa. O estado de edição inline é legitimamente maior, mas as etapas fechadas devem ser compactas.

Decisão: renderizar sempre todas as etapas no audit e comprimir ações/metadados das etapas fechadas no mobile.

## Roteiros do workshop

O catálogo atual contém 6 padrões:

1. Parodinal EI 200 — FeCl₃ + amônia — 9 etapas.
2. Parodinal EI 400 — FeCl₃ + amônia — 9 etapas.
3. Parodinal EI 200 — peracética — 7 etapas.
4. Parodinal EI 400 — peracética — 7 etapas.
5. Brewed Caffenol EI 400 — FeCl₃ + amônia — 9 etapas.
6. Brewed Caffenol EI 400 — peracética — 7 etapas.

Nos roteiros FeCl₃ + amônia, o audit deve mostrar a sequência completa, inclusive branqueamento, lavagem, limpeza com amônia, nova lavagem, segunda revelação, lavagem final e secagem. A segunda revelação reutiliza o banho da primeira quando o padrão assim define.

Foi encontrado ainda um erro de apresentação na implementação atual: a área de padrões possui texto e metadado escritos como se todos os padrões fossem Parodinal. Isso é incompatível com os dois padrões de Brewed Caffenol. Até a correção estrutural do markup, esses metadados redundantes e incorretos não devem ser exibidos.

A documentação histórica da pesquisa não deve ser usada para substituir silenciosamente os parâmetros didáticos atuais do workshop: o próprio índice mestre alerta que testes antigos não devem ser transformados em receita atual.

## Gates adicionados

A auditoria automatizada passa a falhar se:

- a biblioteca não renderizar 6 padrões;
- o editor de um roteiro de 9 etapas não renderizar 9 etapas;
- o runner de um roteiro de 9 etapas não renderizar 9 etapas;
- o registro parcial de 5 etapas não renderizar 5 etapas;
- o registro completo de 9 etapas não renderizar 9 etapas.

Passar nesses gates não aprova o design. Após cada renderização, os screenshots mobile e desktop devem ser inspecionados e as telas problemáticas corrigidas antes do merge.

## Material — fronteira de evidência M-01 (10/10/2026)

A renderização `student-aula3-material-audit.php` usa banco temporário e a cadeia de migrations 085, 087, 089, 090 e 091, sobre um fim **sintético** da Aula 2. Ela não consulta a versão editorial publicada na hospedagem. A última migration preserva sete `data-study-image-placeholder` por decisão explícita; a aprovação de altura/largura dessas caixas comprova apenas a estrutura provisória, não as imagens finais. Não substituir conteúdo autoral ou receitas com inferências, nem converter placeholders em imagens fictícias.

A revisão M-01 passa a distinguir evidência de **geometria do leitor autenticado** (scroll interno mobile, cabeçalho, barra global, rodapé e notas) de evidência de **conteúdo editorial final**. Capturas de início/meio/fim/notas abertas, em desktop e telefone, entram no gate; a fixture recebe marcador de origem, e a transição artificial da Aula 2 fica explicitamente marcada. A inspeção da publicação real, acessos, imagens prontas e estados persistidos de anotação depende de evidência autenticada adicional. Não atribuir status de auditoria integral.
