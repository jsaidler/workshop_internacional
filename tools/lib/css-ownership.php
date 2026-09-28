<?php
declare(strict_types=1);

/**
 * Small dependency-free CSS scanner used by architecture tooling.
 * It deliberately does not rewrite selectors or values; it only identifies
 * style-rule context and declaration source ranges.
 */
function css_ownership_authored_files(string $root): array {
    $files=[];
    foreach(['assets','editor','template','admin'] as $relativeRoot){
        $dir=$root.'/'.$relativeRoot;
        if(!is_dir($dir))continue;
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
        foreach($iterator as $file){
            if(!$file->isFile()||strtolower($file->getExtension())!=='css')continue;
            $relative=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
            if(str_contains($relative,'/vendor/')||str_contains($relative,'/node_modules/'))continue;
            $files[]=$relative;
        }
    }
    sort($files,SORT_STRING);
    return $files;
}

function css_ownership_normalize(string $value): string {
    $value=preg_replace('/\s+/',' ',trim($value))??trim($value);
    return preg_replace('/\s*([>+~,:])\s*/','$1',$value)??$value;
}

function css_ownership_contextual_at_rule(string $prelude): bool {
    if(!str_starts_with(ltrim($prelude),'@'))return false;
    $name=strtolower((string)strtok(substr(ltrim($prelude),1),' '));
    return in_array($name,['media','supports','container','layer','scope'],true);
}

/** @return array{0:string,1:int}|null */
function css_ownership_next_boundary(string $css,int $start,int $end): ?array {
    $quote=null;$escaped=false;$paren=0;$bracket=0;$comment=false;
    for($i=$start;$i<$end;$i++){
        $ch=$css[$i];$next=$i+1<$end?$css[$i+1]:'';
        if($comment){if($ch==='*'&&$next==='/'){$comment=false;$i++;}continue;}
        if($quote!==null){
            if($escaped){$escaped=false;continue;}
            if($ch==='\\'){$escaped=true;continue;}
            if($ch===$quote)$quote=null;
            continue;
        }
        if($ch==='/'&&$next==='*'){$comment=true;$i++;continue;}
        if($ch==='"'||$ch==="'"){$quote=$ch;continue;}
        if($ch==='('){$paren++;continue;}
        if($ch===')'){$paren=max(0,$paren-1);continue;}
        if($ch==='['){$bracket++;continue;}
        if($ch===']'){$bracket=max(0,$bracket-1);continue;}
        if($paren===0&&$bracket===0&&($ch==='{'||$ch===';'||$ch==='}'))return [$ch,$i];
    }
    return null;
}

function css_ownership_matching_close(string $css,int $open,int $end): int {
    $depth=1;$quote=null;$escaped=false;$comment=false;$paren=0;$bracket=0;
    for($i=$open+1;$i<$end;$i++){
        $ch=$css[$i];$next=$i+1<$end?$css[$i+1]:'';
        if($comment){if($ch==='*'&&$next==='/'){$comment=false;$i++;}continue;}
        if($quote!==null){
            if($escaped){$escaped=false;continue;}
            if($ch==='\\'){$escaped=true;continue;}
            if($ch===$quote)$quote=null;
            continue;
        }
        if($ch==='/'&&$next==='*'){$comment=true;$i++;continue;}
        if($ch==='"'||$ch==="'"){$quote=$ch;continue;}
        if($ch==='('){$paren++;continue;}
        if($ch===')'){$paren=max(0,$paren-1);continue;}
        if($ch==='['){$bracket++;continue;}
        if($ch===']'){$bracket=max(0,$bracket-1);continue;}
        if($paren||$bracket)continue;
        if($ch==='{'){$depth++;continue;}
        if($ch==='}'&&--$depth===0)return $i;
    }
    return $end-1;
}

function css_ownership_skip_leading(string $css,int $start,int $end): int {
    $i=$start;
    while($i<$end){
        while($i<$end&&ctype_space($css[$i]))$i++;
        if($i+1<$end&&$css[$i]==='/'&&$css[$i+1]==='*'){
            $close=strpos($css,'*/',$i+2);
            if($close===false||$close>=$end)return $end;
            $i=$close+2;
            continue;
        }
        break;
    }
    return $i;
}

/** @return list<array{property:string,start:int,end:int}> */
function css_ownership_declarations(string $css,int $bodyStart,int $bodyEnd): array {
    $declarations=[];$segmentStart=$bodyStart;
    $quote=null;$escaped=false;$comment=false;$paren=0;$bracket=0;$brace=0;
    for($i=$bodyStart;$i<=$bodyEnd;$i++){
        $atEnd=$i===$bodyEnd;
        $ch=$atEnd?';':$css[$i];$next=(!$atEnd&&$i+1<$bodyEnd)?$css[$i+1]:'';
        if($comment){if(!$atEnd&&$ch==='*'&&$next==='/'){$comment=false;$i++;}continue;}
        if($quote!==null){
            if($escaped){$escaped=false;continue;}
            if($ch==='\\'){$escaped=true;continue;}
            if($ch===$quote)$quote=null;
            continue;
        }
        if(!$atEnd&&$ch==='/'&&$next==='*'){$comment=true;$i++;continue;}
        if($ch==='"'||$ch==="'"){$quote=$ch;continue;}
        if($ch==='('){$paren++;continue;}
        if($ch===')'){$paren=max(0,$paren-1);continue;}
        if($ch==='['){$bracket++;continue;}
        if($ch===']'){$bracket=max(0,$bracket-1);continue;}
        if($ch==='{'){$brace++;continue;}
        if($ch==='}'){$brace=max(0,$brace-1);continue;}
        if(($ch!==';'&&!$atEnd)||$paren||$bracket||$brace)continue;

        $segmentEnd=$atEnd?$bodyEnd:$i;
        $propertyStart=css_ownership_skip_leading($css,$segmentStart,$segmentEnd);
        if($propertyStart<$segmentEnd&&preg_match('/\G([A-Za-z_-][A-Za-z0-9_-]*)\s*:/A',substr($css,$propertyStart,$segmentEnd-$propertyStart),$match)){
            $property=$match[1];
            if(!str_starts_with($property,'--'))$property=strtolower($property);
            $declarations[]=[
                'property'=>$property,
                'start'=>$propertyStart,
                'end'=>$atEnd?$segmentEnd:$i+1,
            ];
        }
        $segmentStart=$i+1;
    }
    return $declarations;
}

/**
 * @param list<string> $contexts
 * @param list<array{context:string,selector:string,declarations:list<array{property:string,start:int,end:int}>}> $rules
 */
function css_ownership_parse_range(string $css,int $start,int $end,array $contexts,array &$rules): void {
    $cursor=$start;$segmentStart=$start;
    while($cursor<$end){
        $boundary=css_ownership_next_boundary($css,$cursor,$end);
        if($boundary===null)break;
        [$kind,$index]=$boundary;
        if($kind===';'){$cursor=$index+1;$segmentStart=$cursor;continue;}
        if($kind==='}')break;
        $prelude=css_ownership_normalize(substr($css,$segmentStart,$index-$segmentStart));
        $close=css_ownership_matching_close($css,$index,$end);
        if($prelude!==''){
            if(str_starts_with($prelude,'@')){
                if(css_ownership_contextual_at_rule($prelude)){
                    $nested=$contexts;$nested[]=$prelude;
                    css_ownership_parse_range($css,$index+1,$close,$nested,$rules);
                }
            }else{
                $rules[]=[
                    'context'=>implode(' > ',$contexts),
                    'selector'=>$prelude,
                    'declarations'=>css_ownership_declarations($css,$index+1,$close),
                ];
            }
        }
        $cursor=$close+1;$segmentStart=$cursor;
    }
}

/** @return list<array{context:string,selector:string,declarations:list<array{property:string,start:int,end:int}>}> */
function css_ownership_scan(string $css): array {
    $rules=[];
    css_ownership_parse_range($css,0,strlen($css),[],$rules);
    return $rules;
}

/** @return array{rules:array<string,int>,properties:array<string,int>} */
function css_ownership_counts(string $css): array {
    $ruleCounts=[];$propertyCounts=[];
    foreach(css_ownership_scan($css) as $rule){
        $ruleKey=$rule['context'].' || '.$rule['selector'];
        $ruleCounts[$ruleKey]=($ruleCounts[$ruleKey]??0)+1;
        foreach($rule['declarations'] as $declaration){
            $propertyKey=$ruleKey.' || '.$declaration['property'];
            $propertyCounts[$propertyKey]=($propertyCounts[$propertyKey]??0)+1;
        }
    }
    return ['rules'=>$ruleCounts,'properties'=>$propertyCounts];
}
