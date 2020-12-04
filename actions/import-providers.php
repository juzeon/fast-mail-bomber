<?php
if($argc!=3){
    println('Please enter the path of your file to import.');
    exit;
}
$providers=[];
if(file_exists(PROVIDERS_JSON)){
    $providers=json_decode(file_get_contents(PROVIDERS_JSON));
}
$totalAdded=0;
$file=$argv[2];
$text=null;
try {
    $text=file_get_contents($file);
}catch(Exception $e){
    println('The file you provided cannot be access');
    exit;
}
$importProviders=parse_providers($text);
$newProviders=array_diff($importProviders,$providers);
$totalAdded=count($newProviders);
$providers=array_merge($providers,$newProviders);
file_put_contents(PROVIDERS_JSON,pretty_json_encode($providers));
println('Completed. Total added: '.$totalAdded);