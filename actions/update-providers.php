<?php
use GuzzleHttp\Exception\RequestException;
if(empty(SHODAN_API_KEY)){
    println('SHODAN_API_KEY cannot be empty. 
    Shodan provides free api access for the first 100 results: https://account.shodan.io/');
    exit;
}
$providers=[];
if(file_exists(PROVIDERS_JSON)){
    $providers=json_decode(file_get_contents(PROVIDERS_JSON));
}
$json=null;
$totalAdded=0;
try{
    $resp=$guzzle->get('https://api.shodan.io/shodan/host/search?key='.SHODAN_API_KEY.'&query=mailman/listinfo');
    $json=json_decode($resp->getBody());
    foreach ($json->matches as $host){
        $importProviders=parse_providers($host->data);
        if(isset($host->http) && isset($host->http->redirects)){
            foreach ($host->http->redirects as $redirectObj){
                if(isset($redirectObj->data)){
                    $importProviders=array_merge($importProviders,parse_providers($redirectObj->data));
                }
                if(isset($redirectObj->html)){
                    $importProviders=array_merge($importProviders,parse_providers($redirectObj->html));
                }
            }
        }
        $importProviders=array_unique($importProviders);
        $newProviders=array_diff($importProviders,$providers);
        $totalAdded+=count($newProviders);
        $providers=array_merge($providers,$newProviders);
    }
    file_put_contents(PROVIDERS_JSON,pretty_json_encode($providers));
    println('Completed. Total added: '.$totalAdded);
}catch (JsonException | RequestException $e){
    println('A network error has occurred. Please examine your api key and network connection.');
}catch(Exception $e){
    println($e->getTraceAsString());
}
