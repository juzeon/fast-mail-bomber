<?php
if(!file_exists(NODES_JSON)){
    println('Please run update-nodes first.');
}
$rawNodes=json_decode(file_get_contents(NODES_JSON));
$usedProviders=[];
$refinedNodes=[];
foreach ($rawNodes as $node){
    $provider=parse_providers($node);
    if(in_array($provider,$usedProviders)){
        continue;
    }
    $usedProviders[]=$provider;
    $refinedNodes[]=$node;
}
file_put_contents(REFINED_NODES_JSON,pretty_json_encode($refinedNodes));
println('Completed. Total refined: '.count($refinedNodes).' nodes.');