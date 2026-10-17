<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasSeo=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_page_seo'")->fetchColumn();
    $hasActivities=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activities'")->fetchColumn();
    if(!$hasPages||!$hasSeo||!$hasActivities)return;

    $profiles=[
        'pt-BR|home'=>[
            'title'=>'Workshop: Positivo Direto em Filme de Raio X | João Saidler',
            'description'=>'Workshop online e ao vivo de João Saidler sobre positivo direto em filme de raio X: exposição, revelação por reversão, química e análise de resultados.',
            'social_title'=>'Positivo Direto em Filme de Raio X — Workshop ao vivo',
            'social_description'=>'Três encontros online e ao vivo para compreender e controlar o positivo direto em filme de raio X, da exposição à revelação e à leitura do resultado.',
            'social_image'=>'/assets/media/hero-medusa.webp',
            'canonical_url'=>'',
            'robots'=>'index,follow',
        ],
        'en|home'=>[
            'title'=>'Direct Positive X-Ray Film Workshop | João Saidler',
            'description'=>'Live online workshop with João Saidler on direct-positive X-ray film: exposure, reversal processing, tonal control and analysis of participants’ results.',
            'social_title'=>'Direct Positive X-Ray Film — Live Online Workshop',
            'social_description'=>'Join the interest list for three live online sessions on direct-positive X-ray film: exposure, reversal processing, tonal control and result analysis.',
            'social_image'=>'/assets/media/hero-medusa.webp',
            'canonical_url'=>'',
            'robots'=>'index,follow',
        ],
        'pt-BR|inscricao'=>[
            'title'=>'Inscrição — Workshop de Positivo Direto em Filme de Raio X',
            'description'=>'Inscreva-se na próxima turma do workshop online e ao vivo de positivo direto em filme de raio X com João Saidler. Três encontros, turma reduzida.',
            'social_title'=>'Inscrição — Workshop de Positivo Direto em Filme de Raio X',
            'social_description'=>'Próxima turma em português: três encontros online e ao vivo sobre exposição, revelação por reversão, química e análise dos resultados.',
            'social_image'=>'/assets/media/hero-medusa.webp',
            'canonical_url'=>'',
            'robots'=>'index,follow',
        ],
    ];

    $pages=$db->query("SELECT p.* FROM cms_pages p INNER JOIN activities a ON a.id=p.activity_id WHERE a.is_root=1 AND p.status!='archived' ORDER BY p.locale,p.is_home DESC,p.sort_order,p.id")->fetchAll();
    $read=$db->prepare('SELECT title,description,social_title,social_description,social_image,canonical_url,robots FROM cms_page_seo WHERE page_id=?');
    $save=$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?) ON CONFLICT(page_id) DO UPDATE SET title=excluded.title,description=excluded.description,social_title=excluded.social_title,social_description=excluded.social_description,social_image=excluded.social_image,canonical_url=excluded.canonical_url,robots=excluded.robots,updated_at=excluded.updated_at');

    foreach($pages as $page){
        $locale=(string)$page['locale'];
        $slug=(string)$page['slug'];
        $key=$locale.'|'.((int)$page['is_home']===1?'home':$slug);
        $curated=$profiles[$key]??null;

        if(is_array($curated)){
            $seo=$curated;
        }else{
            $json=(string)($page['published_document_json']?:$page['draft_document_json']);
            $document=json_decode($json,true);
            $meta=is_array($document)&&is_array($document['meta']??null)?$document['meta']:[];
            $pageTitle=trim((string)$page['title']);
            $metaTitle=trim((string)($meta['title']??''));
            $metaDescription=trim((string)($meta['description']??''));
            $title=$metaTitle!==''?$metaTitle:$pageTitle;
            if(!str_contains(mb_strtolower($title,'UTF-8'),'joão saidler')&&!str_contains(strtolower($title),'joao saidler'))$title.=' | João Saidler';
            $description=$metaDescription;
            if($description==='')$description=$locale==='en'
                ?$pageTitle.' — information from João Saidler’s Direct Positive X-Ray Film workshop.'
                :$pageTitle.' — informações do workshop de positivo direto em filme de raio X de João Saidler.';
            $seo=[
                'title'=>$title,
                'description'=>$description,
                'social_title'=>$title,
                'social_description'=>$description,
                'social_image'=>'/assets/media/hero-medusa.webp',
                'canonical_url'=>'',
                'robots'=>'index,follow',
            ];

            // Unknown/editor-created pages may already have deliberate SEO. Fill
            // only the empty parts instead of overwriting an editorial decision.
            $read->execute([(int)$page['id']]);
            $existing=$read->fetch();
            if(is_array($existing)){
                foreach(['title','description','social_title','social_description','social_image','canonical_url'] as $field){
                    if(trim((string)($existing[$field]??''))!=='')$seo[$field]=(string)$existing[$field];
                }
                $robots=(string)($existing['robots']??'');
                if(in_array($robots,['index,follow','noindex,follow','index,nofollow','noindex,nofollow'],true))$seo['robots']=$robots;
            }
        }

        $save->execute([
            (int)$page['id'],
            (string)$seo['title'],
            (string)$seo['description'],
            (string)$seo['social_title'],
            (string)$seo['social_description'],
            (string)$seo['social_image'],
            (string)$seo['canonical_url'],
            (string)$seo['robots'],
            gmdate('c'),
        ]);
    }
};
