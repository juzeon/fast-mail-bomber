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
$promises=[];
foreach ($usingProviders as $listinfoUrl){
    if (filter_var($listinfoUrl,FILTER_VALIDATE_URL) === FALSE) {
        $deadProviders[]=$listinfoUrl;
        file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProviders));
        println('Provider '.$listinfoUrl.' is not a valid url. Skipped & Added to dead list.');
        continue;
    }
    $promises[]=function () use ($guzzle, &$nodes, $listinfoUrl, &$deadProviders, &$totalAdded){
        $promise=$guzzle->getAsync($listinfoUrl);
        $promise->then(function ($resp) use ($guzzle, &$nodes, $listinfoUrl, &$deadProviders, &$totalAdded){
            $html = $resp->getBody();
            println('Hit ' . $listinfoUrl);
            preg_match_all('/href="(\.\.\/)*listinfo\/(.*?)"/', $html, $m);
            if (empty($m[2])) {
                if (PRESERVE_EMPTY_PROVIDERS) {
                    println('Provider ' . $listinfoUrl . ' returned an empty list. Preserved according to config.');
                } else {
                    $deadProviders[] = $listinfoUrl;
                    file_put_contents(DEAD_PROVIDERS_JSON, pretty_json_encode($deadProviders));
                    println('Provider ' . $listinfoUrl . ' returned an empty list. Added to dead list according to config.');
                }
                return;
            }
            $testUrl = substr($listinfoUrl, 0, strlen($listinfoUrl) - 8) . 'subscribe/' . $m[2][0] . '?language=en';
            $promise2=$guzzle->getAsync($testUrl);
            $promise2->then(function ($resp2) use ($guzzle, &$nodes, $listinfoUrl, &$deadProviders, $m, &$totalAdded, $testUrl) {
                $testHtml = $resp2->getBody();
                println('Hit ' . $testUrl);
                if (strpos($testHtml, 'no hidden token')) {
                    $deadProviders[] = $listinfoUrl;
                    file_put_contents(DEAD_PROVIDERS_JSON, pretty_json_encode($deadProviders));
                    println('Provider ' . $listinfoUrl . ' forces a CSRF check. Skipped & Added to dead list.');
                    return;
                } else if (strpos($testHtml, 'captcha')) {
                    $deadProviders[] = $listinfoUrl;
                    file_put_contents(DEAD_PROVIDERS_JSON, pretty_json_encode($deadProviders));
                    println('Provider ' . $listinfoUrl . ' forces a captcha. Skipped & Added to dead list.');
                    return;
                }else if(intval($resp2->getStatusCode()/100)!=2){
                    $deadProviders[] = $listinfoUrl;
                    file_put_contents(DEAD_PROVIDERS_JSON, pretty_json_encode($deadProviders));
                    println('Provider ' . $listinfoUrl . ' test failed. Skipped & Added to dead list.');
                    return;
                }
                $singleAdded = 0;
                foreach ($m[2] as $item) {
                    $url = substr($listinfoUrl, 0, strlen($listinfoUrl) - 8) . 'subscribe/' . $item;
                    if (!in_array($url, $nodes)) {
                        //println('Added node '.$url);
                        $nodes[] = $url;
                        $totalAdded++;
                        $singleAdded++;
                    }
                }
                file_put_contents(NODES_JSON, pretty_json_encode($nodes));
                println('Added ' . $singleAdded . ' nodes from provider ' . $listinfoUrl);
                return;
            });
            return $promise2;
        });
        return $promise;
    };
}
$pool=new \GuzzleHttp\Pool($guzzle,$promises,[
    'concurrency'=>THREAD_POOL_SIZE,
    'rejected'=>function($reason,$index) use ($usingProviders,&$deadProviders){
        $deadProviders[]=$usingProviders[$index];
        file_put_contents(DEAD_PROVIDERS_JSON,pretty_json_encode($deadProviders));
        println('Provider '.$usingProviders[$index].' cannot be accessed. Skipped & Added to dead list.');
    }
]);
$pool->promise()->wait();

println('Completed. Total added: '.$totalAdded);
println('Start refining nodes...');
include __DIR__.'/refine-nodes.php';