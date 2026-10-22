<?php
declare(strict_types=1);

/**
 * Strict server-side allowlist: no style, event handlers, images, embedded
 * content, tracking pixels, arbitrary URLs or potentially active markup.
 */
function communication_safe_html(string $html): string {
    if(trim($html)==='')return '';
    if(strlen($html)>120000)throw new RuntimeException('A mensagem formatada é muito extensa.');
    $doc=new DOMDocument('1.0','UTF-8');
    $previous=libxml_use_internal_errors(true);
    try{$ok=$doc->loadHTML('<?xml encoding="UTF-8"?><html><body>'.$html.'</body></html>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}
    finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
    if(!$ok)throw new RuntimeException('Não foi possível interpretar o texto formatado.');
    $body=$doc->getElementsByTagName('body')->item(0);
    if(!$body)return '';
    $htmlOut='';
    foreach($body->childNodes as $node)$htmlOut.=communication_clean_node($node);
    if(strlen($htmlOut)>40000)throw new RuntimeException('Reduza o tamanho da mensagem.');
    return trim($htmlOut);
}
function communication_clean_node(DOMNode $node): string {
    if($node instanceof DOMText)return htmlspecialchars($node->nodeValue??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    if(!$node instanceof DOMElement)return '';
    $tag=strtolower($node->tagName);
    if(in_array($tag,['script','style','iframe','object','embed','form','input','svg','math','video','audio','img','template','meta','link'],true))return '';
    $allowed=['p','br','strong','b','em','i','u','ul','ol','li','blockquote','h2','h3','a','div'];
    $inner='';
    foreach($node->childNodes as $child)$inner.=communication_clean_node($child);
    if(!in_array($tag,$allowed,true))return $inner;
    if($tag==='br')return '<br>';
    if($tag==='b')$tag='strong';
    if($tag==='i')$tag='em';
    if($tag==='div')$tag='p';
    $attrs='';
    if($tag==='a'){
        $href=trim($node->getAttribute('href'));
        $protocol=(string)parse_url($href,PHP_URL_SCHEME);
        $valid=($protocol==='https'&&filter_var($href,FILTER_VALIDATE_URL)!==false)
            || ($protocol==='mailto'&&filter_var(substr($href,7),FILTER_VALIDATE_EMAIL)!==false);
        if(!$valid)return $inner;
        $attrs=' href="'.htmlspecialchars($href,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'" rel="noopener noreferrer"';
    }
    return '<'.$tag.$attrs.'>'.$inner.'</'.$tag.'>';
}
function communication_html_text(string $html): string {
    if($html==='')return '';
    $spaced=preg_replace('~<br\s*/?>|</(?:p|div|li|h2|h3|blockquote)>~i',"\n",$html);
    $text=html_entity_decode(strip_tags((string)$spaced),ENT_QUOTES|ENT_HTML5,'UTF-8');
    return trim(preg_replace("/\n{3,}/","\n\n",str_replace("\r",'',$text))??'');
}
function communication_email_html(string $bodyHtml,string $bodyText): string {
    $inner=$bodyHtml!==''?communication_safe_html($bodyHtml):nl2br(htmlspecialchars($bodyText,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'));
    return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"></head><body>'
        .'<main style="max-width:680px;margin:0 auto;padding:24px;font:16px/1.65 Arial,sans-serif;color:#202020">'
        .$inner.'</main></body></html>';
}
