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
preg_match_all('/http[s]{0,1}:\/\/(.*?)\/mailman/',$text,$m);
foreach ($m[0] as $item){
    $url=$item.'/listinfo';
    if(startsWith($url,'https://')){
        $url='http://'.substr($url,8,strlen($url));
    }
    if(!in_array($url,$providers)){
        $providers[]=$url;
        $totalAdded++;
    }
}
file_put_contents(PROVIDERS_JSON,pretty_json_encode($providers));
println('Completed. Total added: '.$totalAdded);