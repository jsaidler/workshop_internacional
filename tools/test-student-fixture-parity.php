<?php
declare(strict_types=1);

/**
 * Minimum parity guard for audit fixtures and the real student templates.
 *
 * Fixtures must not replace actual app controls with simplified/invented markup.
 * This deliberately checks the regressions that made two visually defective
 * surfaces pass their first audits; it does not certify all UI states.
 */
$root=dirname(__DIR__);
$files=[
  'notes_php'=>$root.'/app/student_notes_experience.php',
  'notes_fixture'=>$root.'/tools/browser-fixture/student-inline-annotations.html',
  'notebook_php'=>$root.'/aluno/caderno.php',
  'notebook_fixture'=>$root.'/tools/browser-fixture/student-notebook-compare.html',
];
$source=[];
foreach($files as $key=>$path){
  $source[$key]=@file_get_contents($path);
  if(!is_string($source[$key])){fwrite(STDERR,"fixture-parity: cannot read $path\n");exit(1);}
}
$checks=[
  'real note question composer uses secondary button class'=>str_contains($source['notes_php'],'class="button button-secondary"')&&str_contains($source['notes_php'],'data-note-question-publish'),
  'real note question composer uses contextual text'=>str_contains($source['notes_php'],"'Salvar e publicar dúvida'"),
  'general-question fixture has the real submitter, copy and class'=>str_contains($source['notes_fixture'],'<button class="button button-secondary" type="submit" name="create_question" value="1" data-note-question-publish>Salvar e publicar dúvida</button>'),
  'both real and fixture note composer offer course visibility'=>str_contains($source['notes_php'],'value="course"')&&str_contains($source['notes_fixture'],'value="course"'),
  'legacy editor has the same trigger and note article structure'=>str_contains($source['notes_php'],'data-legacy-note')&&str_contains($source['notes_php'],'data-legacy-edit')&&str_contains($source['notes_fixture'],'data-legacy-note')&&str_contains($source['notes_fixture'],'data-legacy-edit'),
  'notebook fixture uses the real three-stage content summary'=>str_contains($source['notebook_php'],'student-record-summary')&&str_contains($source['notebook_fixture'],'class="student-record-summary" aria-label="Conteúdo do registro"'),
  'notebook fixture does not contain superseded stage-strip markup'=>!str_contains($source['notebook_fixture'],'student-record-progress'),
];
foreach($checks as $label=>$valid)if(!$valid){fwrite(STDERR,"fixture-parity: FAIL: $label\n");exit(1);}
echo "fixture-parity: ok (".count($checks)." real-template assertions)\n";
