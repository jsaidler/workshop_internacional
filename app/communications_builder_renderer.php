<?php
declare(strict_types=1);
require_once __DIR__.'/communications_builder_document.php';

/**
 * Trusted PHP renderer for the bounded MJML grammar. Not a full MJML compiler.
 * Output remains experimental until cross-client layout parity is approved.
 */
function communication_builder_render_email(PDO $db,string $source,array $mediaRefs=[]):array {
    $validated=communication_builder_validate_document($db,$source,$mediaRefs);
    $xml=new DOMDocument('1.0','UTF-8');
    if(!$xml->loadXML($validated['mjml'],LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING))
        throw new RuntimeException('Não foi possível interpretar o MJML validado.');
    $escape=static fn(string $value):string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $style=static function(DOMElement $node,array $props)use($escape):string {
        $out=[];
        foreach($props as $prop=>$css){
            if($node->hasAttribute($prop))$out[]=$css.':'.$node->getAttribute($prop);
        }
        return $escape(implode(';',$out));
    };
    $inline=null;
    $inline=static function(DOMNode $node)use(&$inline,$escape):string {
        if($node instanceof DOMText||$node instanceof DOMCdataSection)return $escape((string)$node->nodeValue);
        if(!$node instanceof DOMElement)return '';
        $tag=$node->tagName;
        $name=match($tag){'b','strong'=>'strong','em','i'=>'em','u'=>'u','a'=>'a','br'=>'br','span'=>'span','p'=>'p',default=>null};
        if($name===null)throw new RuntimeException('Formatação de texto inválida.');
        if($name==='br')return '<br>';
        $children='';
        foreach($node->childNodes as $child)$children.=$inline($child);
        $attrs=$name==='a'?' href="'.$escape($node->getAttribute('href')).'" style="color:inherit;text-decoration:underline"':'';
        return '<'.$name.$attrs.'>'.$children.'</'.$name.'>';
    };
    $contents=static function(DOMElement $node)use($inline):string {
        $html='';
        foreach($node->childNodes as $child)$html.=$inline($child);
        return $html;
    };
    $renderBlock=static function(DOMElement $node)use($escape,$style,$contents):string{
        switch($node->tagName){
        case 'mj-text':
            $css='font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#252525;'
                .$style($node,['font-size'=>'font-size','line-height'=>'line-height','font-weight'=>'font-weight',
                    'color'=>'color','align'=>'text-align','font-family'=>'font-family','padding'=>'padding']);
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="'.$css.'">'
                .$contents($node).'</td></tr></table>';
        case 'mj-button':
            $bg=$node->getAttribute('background-color')?:'#186f4d';
            $color=$node->getAttribute('color')?:'#ffffff';
            $css='display:inline-block;padding:12px 20px;background:'.$escape($bg).';color:'.$escape($color)
                .';text-decoration:none;font-family:Arial,Helvetica,sans-serif;'
                .$style($node,['font-size'=>'font-size','font-weight'=>'font-weight','border-radius'=>'border-radius']);
            $child=$contents($node);
            $url=$node->getAttribute('href');
            $inner=$url!==''?'<a href="'.$escape($url).'" style="'.$css.'">'.$child.'</a>'
                :'<span style="'.$css.'">'.$child.'</span>';
            $align=$node->getAttribute('align')?:'center';
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="'.$escape($align).'" style="padding:8px 0">'.$inner.'</td></tr></table>';
        case 'mj-image':
            $src=$escape($node->getAttribute('src'));
            $alt=$escape($node->getAttribute('alt'));
            $width=$node->getAttribute('width')?:'600px';
            $align=$node->getAttribute('align')?:'center';
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="'.$escape($align)
                .'" style="padding:'.$escape($node->getAttribute('padding')?:'8px 0').'">'
                .'<img src="'.$src.'" alt="'.$alt.'" width="'.(int)$width.'" style="display:block;border:0;width:100%;max-width:'.$escape($width).';height:auto" />'
                .'</td></tr></table>';
        case 'mj-divider':
            $border=$node->getAttribute('border-color')?:'#dddddd';
            $width=$node->getAttribute('border-width')?:'1px';
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:'
                .$escape($node->getAttribute('padding')?:'12px 0').'"><div style="border-top:'.$escape($width).' solid '.$escape($border).';height:0"></div></td></tr></table>';
        case 'mj-spacer':
            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td height="'
                .(int)($node->getAttribute('height')?:20).'" style="height:'.$escape($node->getAttribute('height')?:'20px')
                .';font-size:0;line-height:0">&nbsp;</td></tr></table>';
        }
        throw new RuntimeException('Bloco de e-mail não suportado.');
    };
    $sections='';$plain=[];
    $body=$xml->getElementsByTagName('mj-body')->item(0);
    if(!$body instanceof DOMElement)throw new RuntimeException('O documento não contém corpo MJML.');
    foreach($body->childNodes as $section){
        if(!$section instanceof DOMElement||$section->tagName!=='mj-section')continue;
        $cols=[];
        foreach($section->childNodes as $column)if($column instanceof DOMElement&&$column->tagName==='mj-column')$cols[]=$column;
        if(!$cols)continue;
        $width=(int)floor(100/count($cols));
        $cells='';
        foreach($cols as $column){
            $items='';
            foreach($column->childNodes as $block){
                if(!$block instanceof DOMElement)continue;
                $items.=$renderBlock($block);
                if($block->tagName==='mj-text')$plain[]=trim($block->textContent);
                elseif($block->tagName==='mj-button'){
                    $plain[]=trim($block->textContent).($block->getAttribute('href')!==''?' — '.$block->getAttribute('href'):'');
                }
            }
            $cells.='<td class="email-col" width="'.$width.'%" valign="top" style="width:'.'vertical-align:top;padding:'
                .$escape($column->getAttribute('padding')?:'0').'">'.$items.'</td>';
        }
        $bg=$section->getAttribute('background-color')?:'#ffffff';
        $pad=$section->getAttribute('padding')?:'16px 20px';
        $sections.='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:'
            .$escape($bg).'"><tr><td style="padding:'.$escape($pad).'">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'.$cells.'</tr></table>'
            .'</td></tr></table>';
    }
    $background=$body->getAttribute('background-color')?:'#f1f0ed';
    $html='<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<style>@media only screen and (max-width:600px){.email-col{display:block;width:100%;box-sizing:border-box}}</style>'
        .'</head><body style="margin:0;padding:0;background:'.$escape($background).'">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:'.$escape($background)
        .';border-collapse:collapse"><tr><td align="center"><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;border-collapse:collapse">'
        .$sections.'</table></td></tr></table></body></html>';
    return ['version'=>1,'html'=>$html,'text'=>trim(implode("\n\n",array_filter($plain))),'mjml'=>$validated['mjml'],
        'sha256'=>hash('sha256',$html),'counts'=>$validated['counts']];
}
