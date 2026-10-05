# Hotfix — padrão pedagógico do material

A Aula 2/Aula 3 deve usar o sistema visual já estabelecido para material de estudo (`study-material`, `study-chapter`, `study-unit`).

As seções acrescentadas em `085_aula3_student_area_research_guide.php` e `087_aula2_practice_bridge.php` foram criadas depois da migração que converteu o material para esse sistema e, por isso, usaram inadvertidamente componentes genéricos de página (`section`/`format`). O resultado foi uma hierarquia de landing page, com títulos grandes e ritmo de comunicação comercial, inadequada ao material da aula.

A correção deve preservar o conteúdo pedagógico e os screenshots, mas:

- converter as novas unidades para `study-unit`;
- manter apenas o divisor da Aula 3 como `study-chapter`;
- usar títulos curtos e funcionais, como no restante do material;
- retirar fundos e espaçamentos promocionais herdados de componentes genéricos;
- manter explicações no corpo do texto em largura de leitura;
- validar o material final montado em desktop e telefone;
- falhar a auditoria se qualquer uma das novas unidades voltar a usar o padrão genérico de landing page.
