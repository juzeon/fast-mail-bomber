<?php

use GuzzleHttp\Exception\RequestException;

$providers = [];
if (file_exists(PROVIDERS_JSON)) {
    $providers = json_decode(file_get_contents(PROVIDERS_JSON));
}
$json = null;
$initialProviderCount=count($providers);
if(!empty(SHODAN_API_KEY)){
    println('Getting providers from Shodan...');
    $shodan=new Shodan(SHODAN_API_KEY);
    $gottenProviders=$shodan->get_providers(1);
    $previousProviderCount=count($providers);
    $providers=array_merge($providers,$gottenProviders);
    $providers=array_values(array_unique($providers));
    file_put_contents(PROVIDERS_JSON, pretty_json_encode($providers));
    println('Added '.(count($providers)-$previousProviderCount).' providers from Shodan.');
}
if(!empty(ZOOMEYE_API_KEY)){
    println('Getting providers from ZoomEye...');
    $zoomeye=new ZoomEye(ZOOMEYE_API_KEY);
    for($i=1;$i<=ZOOMEYE_PAGE_LIMIT;$i++){
        println('Processing ZoomEye page '.$i);
        $gottenProviders=$zoomeye->get_providers($i);
        $previousProviderCount=count($providers);
        $providers=array_merge($providers,$gottenProviders);
        $providers=array_values(array_unique($providers));
        file_put_contents(PROVIDERS_JSON, pretty_json_encode($providers));
        println('Added '.(count($providers)-$previousProviderCount).' providers from ZoomEye page '.$i);
    }
}

println('Completed. Total added: ' . (count($providers)-$initialProviderCount));

