<?php
use GuzzleHttp\Exception\RequestException;
$deadProviders=[];
if(file_exists(DEAD_PROVIDERS_JSON)){
    $deadProviders=json_decode(file_get_contents(DEAD_PROVIDERS_JSON));
}
if(!file_exists(PROVIDERS_JSON)){
    println('Please run update-providers first.');
    exit;
}
$providers=json_decode(file_get_contents(PROVIDERS_JSON));
$usingProviders=array_diff($providers,$deadProviders);
$usingProviders=array_reverse($usingProviders);
$nodes=[];
if(file_exists(NODES_JSON)){
    $nodes=json_decode(file_get_contents(NODES_JSON));
}
$totalAdded=0;
foreach ($usingProviders as $listinfoUrl){
    println('Processing provider '.$listinfoUrl);
    $html=null;
    try{
        $html=$guzzle->get($listinfoUrl)->getBody();
    }catch (Exception $e){
        $deadProviders[]=$listinfoUrl;
        file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProviders));
        println('Provider '.$listinfoUrl.' cannot be accessed. Skipped & Added to dead list.');
        continue;
    }
    preg_match_all('/href="listinfo\/(.*?)"/',$html,$m);
    if(empty($m[1])){
        if(PRESERVE_EMPTY_PROVIDERS){
            println('Provider '.$listinfoUrl.' returned an empty list. Preserved according to config.');
        }else{
            $deadProviders[]=$listinfoUrl;
            file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProviders));
            println('Provider '.$listinfoUrl.' returned an empty list. Added to dead list according to config.');
        }
        continue;
    }
    try{
        $testUrl=substr($listinfoUrl,0,strlen($listinfoUrl)-8).'subscribe/'.$m[1][0].'?language=en';
        $testResp=$guzzle->get($testUrl);
        $testHtml=$testResp->getBody();
        if(strpos($testHtml,'no hidden token')){
            $deadProviders[]=$listinfoUrl;
            file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProviders));
            println('Provider '.$listinfoUrl.' forces a CSRF check. Skipped & Added to dead list.');
            continue;
        }else if(strpos($testHtml,'captcha')){
            $deadProviders[]=$listinfoUrl;
            file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProviders));
            println('Provider '.$listinfoUrl.' forces a captcha. Skipped & Added to dead list.');
            continue;
        }
    }catch(Exception $e){
        $deadProviders[]=$listinfoUrl;
        file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProviders));
        println('Provider '.$listinfoUrl.' cannot be accessed. Skipped & Added to dead list.');
        continue;
    }
    $singleAdded=0;
    foreach ($m[1] as $item){
        $url=substr($listinfoUrl,0,strlen($listinfoUrl)-8).'subscribe/'.$item;
        if(!in_array($url,$nodes)){
            //println('Added node '.$url);
            $nodes[]=$url;
            $totalAdded++;
            $singleAdded++;
        }
    }
    file_put_contents(NODES_JSON,pretty_json_encode($nodes));
    println('Added nodes from the aforementioned provider: '.$singleAdded);
}
println('Completed. Total added: '.$totalAdded);
println('Start refining nodes...');
include __DIR__.'/refine-nodes.php';