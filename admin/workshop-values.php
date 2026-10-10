<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];
if(!$activity){header('Location: /admin/',true,303);exit;}
$activityId=(int)$activity['id'];$notice=$_SESSION['workshop_values_notice']??null;unset($_SESSION['workshop_values_notice']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf('workshop-values',$_POST['_csrf']??null))$_SESSION['workshop_values_notice']=['error','Não foi possível validar a solicitação.'];
    else try{workshop_prices_save($db,$activityId,[PUBLIC_LOCALE_EN=>$_POST['price_en']??'',PUBLIC_LOCALE_PT_BR=>$_POST['price_pt_br']??'']);$_SESSION['workshop_values_notice']=['success','Valores atualizados. As duas versões públicas já usam a nova configuração.'];}
    catch(InvalidArgumentException){$_SESSION['workshop_values_notice']=['error','Informe um valor válido para cada idioma, com até 80 caracteres.'];}
    catch(Throwable){$_SESSION['workshop_values_notice']=['error','Não foi possível salvar os valores.'];}
    header('Location: /admin/workshop-values.php?activity='.$activityId,true,303);exit;
}
$prices=workshop_prices($db,$activityId);
admin_shell_start('values','Valores do workshop',$state);
?>
<?php if($notice):?><p class="admin-notice <?=h($notice[0])?>" role="status"><?=h($notice[1])?></p><?php endif;?>
<section class="section-intro"><p>Configure o preço exibido em cada versão. O valor é aplicado automaticamente em todos os pontos da página e na pergunta comercial do formulário.</p><div class="admin-actions"><a href="/<?=h($activity['slug'])?>/?lang=en" target="_blank" rel="noopener">Ver EN</a><a href="/<?=h($activity['slug'])?>/?lang=pt-br" target="_blank" rel="noopener">Ver PT-BR</a></div></section>
<section class="admin-panel workshop-values-panel"><p class="admin-kicker">Configuração por idioma</p><h2>Preço previsto</h2><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('workshop-values'))?>"><div class="locale-values-grid"><label class="admin-field"><span>Workshop em inglês <small>EN</small></span><input name="price_en" value="<?=h($prices[PUBLIC_LOCALE_EN])?>" maxlength="80" required aria-describedby="price-en-help"><small id="price-en-help">Exemplo: US$195</small></label><label class="admin-field"><span>Workshop em português <small>PT-BR</small></span><input name="price_pt_br" value="<?=h($prices[PUBLIC_LOCALE_PT_BR])?>" maxlength="80" required aria-describedby="price-pt-help"><small id="price-pt-help">Exemplo: R$ 950</small></label></div><p class="settings-contract">Use o valor completo como deve aparecer, incluindo moeda e separadores. Esta configuração controla o destaque, o resumo, o bloco de formato e a pergunta sobre interesse comercial.</p><button class="admin-button" type="submit">Salvar valores</button></form></section>
<?php admin_shell_end();
