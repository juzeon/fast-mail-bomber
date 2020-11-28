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
        preg_match('/[lL]ocation: (.*)/',$host->data,$m);
        $url=trim(isset($m[1])?$m[1]:'');
        if(startsWith($url,'https://')){
            $url='http://'.substr($url,8,strlen($url));
        }
        if(endsWith($url,'/')){
            $url=substr($url,0,strlen($url)-1);
        }
        if(!in_array($url,$providers) && !empty($url)){
            $providers[]=$url;
            $totalAdded++;
        }
    }
    file_put_contents(PROVIDERS_JSON,pretty_json_encode($providers));
    println('Completed. Total added: '.$totalAdded);
}catch (JsonException | RequestException $e){
    println('A network error has occurred. Please examine your api key and network connection.');
}catch(Exception $e){
    println($e->getTraceAsString());
}
