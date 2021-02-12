<?php
require_once (__DIR__.'/vendor/autoload.php');
require_once (__DIR__.'/libraries/includes.php');
require_once (__DIR__.'/libraries/providers/Api.php');
require_once (__DIR__.'/libraries/providers/ZoomEye.php');
require_once (__DIR__.'/libraries/providers/Shodan.php');
ini_set('memory_limit', '-1');
use GuzzleHttp\Client;
$guzzle=new Client([
    'proxy'=>PROXY,
    'timeout'=>TIMEOUT,
    'verify'=>false,
    'headers'=>[
        'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0'
    ]
]);
define('HELP_TEXT',<<<EOF
Usage:
    php index.php update-providers|update-nodes|refine-nodes|start-bombing|import-providers
    Please refer to README.md for usage guidance.
EOF
);