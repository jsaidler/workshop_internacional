<div class="student-home-live student-home-notifications">
  <header class="student-home-live-header"><h1 class="student-title">Notificações</h1><a href="/aluno/">← Início</a></header>
  <?php if(!$notices):?><p class="student-home-live-empty">Nenhuma atualização disponível.</p><?php else:?>
  <div class="student-home-notice-list">
  <?php foreach($notices as $notice):?>
    <a href="<?=h((string)$notice['href'])?>" class="student-home-notice<?=$notice['unread']?' is-unread':''?>">
      <span class="student-home-notice-symbol" aria-hidden="true"><?=($notice['kind']==='answer')?'↗':(($notice['kind']==='opportunity')?'◷':(($notice['kind']==='academic')?'!':'✓'))?></span>
      <span><small><?=h(($notice['kind']==='answer'?'Resposta do professor':($notice['kind']==='opportunity'?'Encontro de outra turma':($notice['kind']==='academic'?'Aviso acadêmico':'Retorno da avaliação'))).' · '.$notice['cohort'])?></small>
        <strong><?=h((string)$notice['title'])?></strong>
        <small><?=h((string)$notice['course'])?></small>
      </span><span aria-hidden="true">›</span>
    </a>
  <?php endforeach;?></div><?php endif;?>
</div>
