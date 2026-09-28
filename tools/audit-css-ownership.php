<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$files=[
    'assets/admin-system.css',
    'assets/admin-media.css',
    'assets/student-area.css',
    'assets/cms-core.css',
    'assets/cms-editorial.css',
    'editor/editor-system.css',
];

function css_audit_normalize(string $value): string {
    $value=preg_replace('/\s+/',' ',trim($value))??trim($value);
    return preg_replace('/\s*([>+~,:])\s*/','$1',$value)??$value;
}

function css_audit_entries(string $css): array {
    $css=preg_replace('~/\*.*?\*/~s','',$css)??$css;
    $entries=[];$stack=[];$buffer='';$quote=null;$escaped=false;$paren=0;$bracket=0;
    $len=strlen($css);
    for($i=0;$i<$len;$i++){
        $ch=$css[$i];
        if($quote!==null){
            $buffer.=$ch;
            if($escaped){$escaped=false;continue;}
            if($ch==='\\'){$escaped=true;continue;}
            if($ch===$quote)$quote=null;
            continue;
        }
        if($ch==='"'||$ch==="'"){$quote=$ch;$buffer.=$ch;continue;}
        if($ch==='('){$paren++;$buffer.=$ch;continue;}
        if($ch===')'){$paren=max(0,$paren-1);$buffer.=$ch;continue;}
        if($ch==='['){$bracket++;$buffer.=$ch;continue;}
        if($ch===']'){$bracket=max(0,$bracket-1);$buffer.=$ch;continue;}
        if($paren>0||$bracket>0){$buffer.=$ch;continue;}
        if($ch==='{'){
            $prelude=css_audit_normalize($buffer);$buffer='';
            if($prelude===''){$stack[]=['type'=>'block','name'=>''];continue;}
            if(str_starts_with($prelude,'@')){
                $name=strtolower(strtok(substr($prelude,1),' '));
                $contextual=in_array($name,['media','supports','container','layer','scope'],true);
                $stack[]=['type'=>$contextual?'context':'at','name'=>$prelude];
            }else{
                $contexts=[];
                foreach($stack as $level)if($level['type']==='context')$contexts[]=$level['name'];
                $context=implode(' > ',$contexts);
                $key=$context.' || '.$prelude;
                $entries[$key]=($entries[$key]??0)+1;
                $stack[]=['type'=>'rule','name'=>$prelude];
            }
            continue;
        }
        if($ch==='}'){
            $buffer='';
            array_pop($stack);
            continue;
        }
        if($ch===';'&&(!$stack||end($stack)['type']!=='rule')){$buffer='';continue;}
        if(!$stack||end($stack)['type']!=='rule')$buffer.=$ch;
    }
    return $entries;
}

$totalDuplicates=0;
foreach($files as $relative){
    $path=$root.'/'.$relative;
    if(!is_file($path)){echo "MISSING $relative\n";continue;}
    $entries=css_audit_entries((string)file_get_contents($path));
    $duplicates=array_filter($entries,static fn(int $count): bool=>$count>1);
    arsort($duplicates);
    $totalDuplicates+=count($duplicates);
    echo "\n[$relative] repeated selector/context keys: ".count($duplicates)."\n";
    $shown=0;
    foreach($duplicates as $key=>$count){
        echo str_pad((string)$count,3,' ',STR_PAD_LEFT).' × '.$key."\n";
        if(++$shown>=80){echo "... truncated ...\n";break;}
    }
}
echo "\nCSS ownership audit complete; repeated keys: $totalDuplicates\n";
