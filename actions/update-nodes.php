<?php
use GuzzleHttp\Exception\RequestException;
$deadProvivers=[];
if(file_exists(DEAD_PROVIDERS_JSON)){
    $deadProvivers=json_decode(file_get_contents(DEAD_PROVIDERS_JSON));
}
if(!file_exists(PROVIDERS_JSON)){
    println('Please run update-providers first.');
    exit;
}
$providers=json_decode(file_get_contents(PROVIDERS_JSON));
$usingProviders=array_diff($providers,$deadProvivers);
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
    }catch (RequestException $e){
        $deadProvivers[]=$listinfoUrl;
        file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProvivers));
        println('Provider '.$listinfoUrl.' cannot be accessed. Skipped & Added to dead list.');
        continue;
    }
    preg_match_all('/href="listinfo\/(.*?)"/',$html,$m);
    if(empty($m[1])){
        if(PRESERVE_EMPTY_PROVIDERS){
            println('Provider '.$listinfoUrl.' returned an empty list. Preserved according to config.');
        }else{
            $deadProvivers[]=$listinfoUrl;
            file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProvivers));
            println('Provider '.$listinfoUrl.' returned an empty list. Added to dead list according to config.');
        }
        continue;
    }
    $singleAdded=0;
    foreach ($m[1] as $item){
        $url=substr($listinfoUrl,0,strlen($listinfoUrl)-8).'subscribe/'.$item;
        if(!in_array($url,$nodes)){
            println('Added node '.$url);
            $nodes[]=$url;
            $totalAdded++;
            $singleAdded++;
        }
    }
    file_put_contents(NODES_JSON,pretty_json_encode($nodes));
    println('Added from the aforementioned provider: '.$singleAdded);
}
println('Completed. Total added: '.$totalAdded);