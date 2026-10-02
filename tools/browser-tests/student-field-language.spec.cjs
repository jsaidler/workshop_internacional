const {test,expect}=require('@playwright/test');
const fs=require('fs');
const path=require('path');

const root=path.resolve(__dirname,'../..');
const read=rel=>fs.readFileSync(path.join(root,rel),'utf8');
const compactMarkup=text=>text.replace(/\s+(?=<)/g,'');
const studentUiFiles=[
  'aluno/caderno.php',
  'aluno/calibracao.php',
  'aluno/comparar-processos.php',
  'aluno/duvidas.php',
  'aluno/ferramentas.php',
  'aluno/inventario.php',
  'aluno/inventario-item.php',
  'aluno/perfil.php',
  'aluno/preparos.php',
  'aluno/processamentos.php',
  'aluno/teste.php',
  'aluno/teste-compartilhado.php',
  'aluno/teste-etapa.php',
  'app/student_shell.php',
];

test('student fields use one canonical vocabulary at the source',()=>{
  const source=studentUiFiles.map(read).join('\n');
  const forbidden=[
    'EI / ISO',
    'Diafragma',
    'Tempo medido / calculado',
    '>Com reciprocidade<input',
    'Após reciprocidade',
    'Condição da luz',
    'Faixa tonal / intenção',
    '>Preparo salvo<select',
    '>Usar preparo salvo<select',
    'Revelador / solução estoque',
    'Volume preparado/usado',
    'Agitação / modo',
    'Solução armazenável',
    'Lote / identificação',
    'Entrada / preparo',
    'WhatsApp / telefone',
    'Cidade/UF',
    'Preparo / diluição',
    '>Compartilhar<select',
    '>Responder<textarea',
    '>Tempo calculado<input data-quick-reciprocity-source',
    '>Tempo corrigido<input data-quick-reciprocity-target',
    '>Tempo de partida (s)<input',
    '>Agitação a cada<input type="text"',
  ];
  for(const legacy of forbidden)expect(source,`legacy student field label: ${legacy}`).not.toContain(legacy);

  const required={
    'aluno/teste.php':[
      '>EI<input name="iso_reference"',
      '>Abertura<input name="aperture"',
      '>Tempo sem reciprocidade<input name="calculated_time"',
      '>Tempo com reciprocidade<input name="reciprocity_time"',
      '>Condição de luz<textarea name="light_condition"',
      '>Faixa tonal e intenção<textarea name="tonal_range"',
      '>Predefinição de revelação<select name="saved_preparation_id"',
      '>Outro revelador<input name="developer_name"',
      '>Revelador ou solução estoque (ml)<input',
      '>Volume preparado (ml)<input',
      "student_process_inventory_select_html($inventory,'Item do inventário')",
    ],
    'aluno/teste-compartilhado.php':[
      '<dt>EI</dt>',
      '<dt>Abertura</dt>',
      '<dt>Tempo sem reciprocidade</dt>',
      '<dt>Tempo com reciprocidade</dt>',
    ],
    'aluno/comparar-processos.php':[
      "'EI'=>'iso_reference'",
      "'Abertura'=>'aperture'",
      "'Tempo sem reciprocidade'=>'calculated_time'",
      "'Tempo com reciprocidade'=>'reciprocity_time'",
      "'Faixa tonal e intenção'=>'tonal_range'",
    ],
    'aluno/teste-etapa.php':[
      '>Predefinição de revelação<select name="saved_preparation_id"',
      '>Outro revelador<input name="developer_name"',
      '>Revelador ou solução estoque (ml)<input',
      '>Volume preparado (ml)<input',
      '>Agitação<input name="agitation"',
    ],
    'aluno/ferramentas.php':[
      '>Tempo sem reciprocidade<input data-quick-reciprocity-source',
      '>Tempo com reciprocidade<input data-quick-reciprocity-target',
      '>Tempo inicial<input',
    ],
    'aluno/processamentos.php':[
      '>Predefinição de revelação<select name="saved_preparation_id"',
      '>Outro revelador<input name="developer_name"',
      '>Revelador ou solução estoque (ml)<input',
      '>Aviso de agitação<input name="agitation_interval"',
    ],
    'app/student_shell.php':[
      '>Tempo sem reciprocidade<input data-quick-reciprocity-source',
      '>Tempo com reciprocidade<input data-quick-reciprocity-target',
      '>Tempo inicial<input',
      '<span>Predefinições de revelação</span>',
      '<span>Referências de calibração</span>',
    ],
    'aluno/caderno.php':['>Visibilidade<select name="visibility"'],
    'aluno/duvidas.php':['>Resposta<textarea name="body"'],
    'aluno/calibracao.php':['>Nome da referência<input name="label"','>Preparo ou diluição<input name="preparation"'],
    'aluno/inventario.php':['<span>Solução preparada</span>','>Lote ou identificação<input name="lot_code"','>Data de entrada<input type="date" name="acquired_or_prepared_at"'],
    'aluno/inventario-item.php':['>Lote ou identificação<input name="lot_code"'],
    'aluno/perfil.php':['>Telefone para contato<input name="phone"','>Cidade e UF<input name="city_state"'],
    'aluno/preparos.php':['>Outro revelador<input name="developer_name"','>Revelador ou solução estoque (ml)<input'],
  };
  for(const [file,needles] of Object.entries(required)){
    const text=compactMarkup(read(file));
    for(const needle of needles)expect(text,`${file} missing canonical label: ${needle}`).toContain(needle);
  }
});

test('student field controls never rely on an empty visible label',()=>{
  for(const file of studentUiFiles){
    const text=read(file);
    const emptyFormLabel=/<label\b[^>]*class="[^"]*form-field[^"]*"[^>]*>\s*<(?:input|select|textarea)\b([^>]*)>/g;
    for(const match of text.matchAll(emptyFormLabel)){
      expect(match[1],`${file} has a form control with no visible label and no accessible name`).toMatch(/\baria-label=/);
    }
  }
  expect(read('aluno/calibracao.php')).toContain('aria-label="Anotações da calibração"');
  expect(read('aluno/inventario.php')).toContain('aria-label="Anotações do item"');
  expect(read('aluno/teste.php')).toContain('aria-label="Anotações da etapa"');
});

test('static student labels are server-rendered; JavaScript only relabels state-dependent fields',()=>{
  const processUx=read('assets/student-process-ux.js');
  const mechanics=read('assets/student-mechanics.js');
  const workbench=read('assets/student-workbench.js');
  const experience=read('assets/student-experience.js');
  expect(processUx).not.toContain("replaceLabelText(preset,'Predefinição de revelação')");
  expect(processUx).not.toContain("replaceLabelText(input,'Outro revelador')");
  expect(processUx).toContain("kind==='wash'?'Tempo de lavagem':kind==='dry'?'Tempo de secagem (opcional)':'Tempo'");
  expect(mechanics).not.toContain('intervalLabel');
  expect(mechanics).not.toContain('solutionSpan');
  expect(mechanics).toContain("'Tempo com reciprocidade: '+formatDuration(corrected)");
  expect(workbench).toContain("'Tempo com reciprocidade: '+pretty(window.StudentReciprocity.adjustedSeconds(seconds))+' s'");
  expect(experience).not.toContain("span.textContent='Predefinições de revelação'");
  expect(experience).not.toContain("span.textContent='Referências de calibração'");
});

test('form labels and choice legends share the same visual hierarchy',async({page})=>{
  await page.setContent(`<!doctype html><html><head><base href="http://127.0.0.1:8099/"><link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/student-area.css"><link rel="stylesheet" href="/assets/student-experience.css"><link rel="stylesheet" href="/assets/student-rendered-fixes.css"><link rel="stylesheet" href="/assets/student-field-language.css"></head><body class="student-page"><label class="form-field">Título<input></label><fieldset class="choice-field"><legend>Contexto</legend><div class="choice-row"><label class="choice-option"><input type="radio"><span>Pessoal</span></label></div></fieldset></body></html>`,{waitUntil:'load'});
  await expect(page.locator('.form-field')).toBeVisible();
  const styles=await page.evaluate(()=>{
    const pick=selector=>{const style=getComputedStyle(document.querySelector(selector));return {fontSize:style.fontSize,lineHeight:style.lineHeight,fontFamily:style.fontFamily,fontWeight:style.fontWeight,textTransform:style.textTransform,letterSpacing:style.letterSpacing};};
    return {label:pick('.form-field'),legend:pick('.choice-field>legend')};
  });
  expect(styles.label.fontSize).toBe('13px');
  expect(styles.legend.fontSize).toBe(styles.label.fontSize);
  expect(styles.legend.lineHeight).toBe(styles.label.lineHeight);
  expect(styles.legend.fontFamily).toBe(styles.label.fontFamily);
  expect(styles.legend.fontWeight).toBe(styles.label.fontWeight);
  expect(styles.legend.textTransform).toBe(styles.label.textTransform);
  expect(styles.legend.letterSpacing).toBe(styles.label.letterSpacing);
});
