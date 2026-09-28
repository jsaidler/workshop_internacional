<?php
declare(strict_types=1);

require_once __DIR__.'/lib/css-ownership.php';

$root=dirname(__DIR__);
$check=in_array('--check',$argv,true);
$files=css_ownership_authored_files($root);

function css_selector_property_family(string $property): string {
    if(str_starts_with($property,'--'))return $property;
    $property=strtolower($property);
    if($property==='all')return '*';
    foreach([
        'margin','padding','border','background','font','flex','grid','animation','transition',
        'list-style','columns','column-rule','inset','overflow','overscroll-behavior',
        'place-content','place-items','place-self','text-decoration','outline','scroll-margin',
        'scroll-padding','mask'
    ] as $family){
        if($property===$family||str_starts_with($property,$family.'-'))return $family;
    }
    return $property;
}

function css_selector_properties_conflict(string $left,string $right): bool {
    if($left===$right)return true;
    $a=css_selector_property_family($left);$b=css_selector_property_family($right);
    return $a==='*'||$b==='*'||$a===$b;
}

/** @return array{css:string,moves:int} */
function css_selector_consolidate_pass(string $css): array {
    $rules=css_ownership_scan($css);
    if(!$rules)return ['css'=>$css,'moves'=>0];

    $groups=[];
    foreach($rules as $index=>$rule){
        $key=$rule['context'].' || '.$rule['selector'];
        $groups[$key][]=$index;
    }

    $removals=[];$insertions=[];$moves=0;
    foreach($groups as $indices){
        if(count($indices)<2)continue;
        $targetIndex=$indices[count($indices)-1];
        $target=$rules[$targetIndex];
        foreach(array_slice($indices,0,-1) as $sourceIndex){
            $source=$rules[$sourceIndex];
            foreach($source['declarations'] as $declaration){
                $property=$declaration['property'];
                $blocked=false;

                // Moving a declaration to the later duplicate selector is neutral only when
                // it crosses no declaration capable of competing with that property family.
                foreach($rules as $candidateIndex=>$candidate){
                    if($candidateIndex===$sourceIndex)continue;
                    if($candidate['start']<=$source['end']||$candidate['start']>=$target['start'])continue;
                    foreach($candidate['declarations'] as $candidateDeclaration){
                        if(css_selector_properties_conflict($property,$candidateDeclaration['property'])){
                            $blocked=true;break 2;
                        }
                    }
                }
                if($blocked)continue;

                // A related shorthand/longhand already owned by the target is order-sensitive.
                foreach($target['declarations'] as $targetDeclaration){
                    if(css_selector_properties_conflict($property,$targetDeclaration['property'])){
                        $blocked=true;break;
                    }
                }
                if($blocked)continue;

                $text=substr($css,$declaration['start'],$declaration['end']-$declaration['start']);
                if($text==='')continue;
                $removals[]=['start'=>$declaration['start'],'end'=>$declaration['end']];
                $insertions[$target['bodyEnd']][]=['source'=>$declaration['start'],'text'=>$text];
                $moves++;
            }
        }
    }
    if($moves===0)return ['css'=>$css,'moves'=>0];

    $edits=[];
    foreach($removals as $removal)$edits[]=['position'=>$removal['start'],'kind'=>'remove','end'=>$removal['end'],'text'=>''];
    foreach($insertions as $position=>$items){
        usort($items,static fn(array $a,array $b): int=>$a['source']<=>$b['source']);
        $text='';
        foreach($items as $item)$text.=$item['text'];
        $edits[]=['position'=>(int)$position,'kind'=>'insert','end'=>(int)$position,'text'=>$text];
    }
    usort($edits,static function(array $a,array $b): int {
        if($a['position']===$b['position'])return $a['kind']==='insert'?-1:1;
        return $b['position']<=>$a['position'];
    });
    foreach($edits as $edit){
        if($edit['kind']==='insert')$css=substr($css,0,$edit['position']).$edit['text'].substr($css,$edit['position']);
        else $css=substr($css,0,$edit['position']).substr($css,$edit['end']);
    }
    return ['css'=>$css,'moves'=>$moves];
}

$totalMoves=0;$changedFiles=[];
foreach($files as $relative){
    $path=$root.'/'.$relative;
    $css=(string)file_get_contents($path);
    $movesForFile=0;$passes=0;
    do{
        $result=css_selector_consolidate_pass($css);
        $css=$result['css'];
        $moves=$result['moves'];
        $movesForFile+=$moves;
        $passes++;
        if($passes>100)throw new RuntimeException('Selector consolidation did not converge for '.$relative);
    }while($moves>0);

    if($movesForFile===0)continue;
    $changedFiles[]=$relative;
    $totalMoves+=$movesForFile;
    echo $relative.': order-safe declaration moves '.$movesForFile.' in '.($passes-1)." pass(es)\n";
    if(!$check)file_put_contents($path,$css);
}

echo "Selector consolidation fixed point; order-safe moves: {$totalMoves}; files: ".count($changedFiles)." of ".count($files).".\n";
if($check&&$changedFiles)exit(1);
