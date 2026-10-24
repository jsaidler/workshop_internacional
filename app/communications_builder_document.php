<?php
declare(strict_types=1);

/**
 * Preflight for the future block-email draft format. This does NOT authorize
 * storing or sending client-compiled HTML: the server rendering pipeline
 * must be separately approved before enabling delivery.
 */
function communication_builder_validate_document(PDO $db,string $source,array $mediaRefs=[]):array {
    if($source===''||strlen($source)>90000)throw new RuntimeException('Documento de e-mail vazio ou muito extenso.');
    if(preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i',$source))
        throw new RuntimeException('Declarações e entidades XML não são permitidas.');
    $old=libxml_use_internal_errors(true);
    try {
        $xml=new DOMDocument('1.0','UTF-8');
        $ok=$xml->loadXML($source,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
    }finally{libxml_clear_errors();libxml_use_internal_errors($old);}
    if(!$ok||$xml->doctype!==null||!$xml->documentElement||$xml->documentElement->tagName!=='mjml')
        throw new RuntimeException('O documento MJML não é válido.');
    if(count($mediaRefs)>12)throw new RuntimeException('O e-mail pode conter no máximo 12 imagens.');

    $allowed=[
      'mjml'=>['mj-head','mj-body'],
      'mj-head'=>['mj-attributes'],
      'mj-attributes'=>['mj-all'],
      'mj-all'=>[],
      'mj-body'=>['mj-section'],
      'mj-section'=>['mj-column'],
      'mj-column'=>['mj-text','mj-button','mj-image','mj-divider','mj-spacer'],
      'mj-text'=>['strong','b','em','i','u','br','a','span','p'],
      'mj-button'=>['strong','b','em','i','u','br','span'],
      'strong'=>['strong','b','em','i','u','br','a','span'],
      'b'=>['strong','b','em','i','u','br','a','span'],
      'em'=>['strong','b','em','i','u','br','a','span'],
      'i'=>['strong','b','em','i','u','br','a','span'],
      'u'=>['strong','b','em','i','u','br','a','span'],
      'span'=>['strong','b','em','i','u','br','a'],
      'p'=>['strong','b','em','i','u','br','a','span'],
      'a'=>['strong','b','em','i','u','br','span'],
      'br'=>[], 'mj-image'=>[], 'mj-divider'=>[], 'mj-spacer'=>[],
    ];
    $styleAttrs=[
      'mjml'=>[], 'mj-head'=>[], 'mj-attributes'=>[], 'mj-all'=>['font-family'],
      'mj-body'=>['background-color'],
      'mj-section'=>['background-color','padding'],
      'mj-column'=>['width','padding'],
      'mj-text'=>['font-size','line-height','font-weight','color','align','padding','font-family'],
      'mj-button'=>['font-size','font-weight','color','background-color','border-radius','padding','href','align'],
      'mj-image'=>['src','alt','padding','width','align'],
      'mj-divider'=>['border-color','border-width','padding'],
      'mj-spacer'=>['height','padding'],
      'a'=>['href'],'span'=>[],'strong'=>[],'b'=>[],'em'=>[],'i'=>[],'u'=>[],'p'=>[],'br'=>[],
    ];
    $counts=['nodes'=>0,'sections'=>0,'columns'=>0,'images'=>0,'characters'=>0,'buttons'=>0];
    $seenIds=[];
    $walk=function(DOMNode $node,?string $parent,int $depth)use(&$walk,&$counts,&$seenIds,$allowed,$styleAttrs,$db,$mediaRefs):void {
        if(++$counts['nodes']>350||$depth>14)throw new RuntimeException('O e-mail tem muitos elementos ou níveis.');
        if($node instanceof DOMText||$node instanceof DOMCdataSection){
            $text=$node->nodeValue??'';
            if(trim($text)!==''&&!in_array($parent,['mj-text','mj-button','strong','b','em','i','u','span','p','a'],true))
                throw new RuntimeException('Texto fora de um bloco editável.');
            $counts['characters']+=mb_strlen($text,'UTF-8');
            if($counts['characters']>20000)throw new RuntimeException('Mensagem com mais de 20 mil caracteres.');
            return;
        }
        if(!$node instanceof DOMElement)throw new RuntimeException('Conteúdo XML não permitido.');
        $tag=$node->tagName;
        if(!isset($allowed[$tag])||($parent!==null&&!in_array($tag,$allowed[$parent],true)))
            throw new RuntimeException('Elemento não autorizado no e-mail: '.$tag);
        if($tag==='mj-section'&&++$counts['sections']>30)throw new RuntimeException('Use no máximo 30 seções.');
        if($tag==='mj-column'&&++$counts['columns']>60)throw new RuntimeException('Use no máximo 60 colunas.');
        if($tag==='mj-button')$counts['buttons']++;
        foreach($node->attributes as $attribute){
            $key=$attribute->name;$v=$attribute->value;
            if(!in_array($key,$styleAttrs[$tag],true)||strlen($v)>400||preg_match('/[\x00-\x1f\x7f]/',$v))
                throw new RuntimeException('Atributo não permitido em '.$tag.': '.$key);
            $valid=match($key){
                'href'=>(str_starts_with($v,'https://')&&filter_var($v,FILTER_VALIDATE_URL)!==false)
                  ||(str_starts_with($v,'mailto:')&&filter_var(substr($v,7),FILTER_VALIDATE_EMAIL)!==false),
                'src'=>filter_var($v,FILTER_VALIDATE_URL)!==false&&str_starts_with($v,'https://'),
                'alt'=>trim($v)!==''&&mb_strlen($v,'UTF-8')<=250,
                'font-family'=>in_array($v,['Arial,Helvetica,sans-serif','Arial','Helvetica,sans-serif'],true),
                'color','background-color','border-color'=>preg_match('/^#[A-Fa-f0-9]{6}$/D',$v)===1,
                'font-size','height','border-width'=>preg_match('/^(?:[1-9]|[1-9][0-9]|1[0-9][0-9])px$/D',$v)===1,
                'padding'=>preg_match('/^\d{1,2}px(?: \d{1,2}px){0,3}$/D',$v)===1,
                'border-radius'=>preg_match('/^\d{1,2}px$/D',$v)===1,
                'width'=>preg_match('/^(?:[1-9][0-9]{0,2}px|100%)$/D',$v)===1,
                'line-height'=>preg_match('/^(?:1(?:\.\d)?|2(?:\.\d)?|[1-9][0-9]px)$/D',$v)===1,
                'font-weight'=>in_array($v,['normal','bold','400','500','600','700'],true),
                'align'=>in_array($v,['left','center','right'],true),
                default=>false
            };
            if(!$valid)throw new RuntimeException('Valor inválido para '.$key.'.');
        }
        if($tag==='mj-image'){
            if(++$counts['images']>12)throw new RuntimeException('O e-mail pode conter no máximo 12 imagens.');
            $src=$node->getAttribute('src');$alt=$node->getAttribute('alt');
            if($src===''||$alt==='')throw new RuntimeException('Imagem sem endereço ou descrição.');
            $id=(int)($mediaRefs[$src]??0);
            if($id<1||!ctype_digit((string)$id))throw new RuntimeException('Escolha a imagem na Biblioteca pública.');
            $asset=communication_media_asset($db,$id);
            $origin=communication_site_url($db);
            if($origin===''||$src!==$origin.media_url((string)$asset['email_path']))
                throw new RuntimeException('A imagem não corresponde à versão pública aprovada.');
            $seenIds[]=$id;
        }
        foreach($node->childNodes as $child)$walk($child,$tag,$depth+1);
    };
    $walk($xml->documentElement,null,0);
    if($counts['sections']<1||$counts['columns']<1)
        throw new RuntimeException('O e-mail precisa de ao menos uma seção com coluna.');
    return ['version'=>1,'mjml'=>$xml->saveXML($xml->documentElement),'counts'=>$counts,'media_ids'=>array_values(array_unique($seenIds))];
}
