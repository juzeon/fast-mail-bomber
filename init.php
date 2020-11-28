<?php
require_once (__DIR__.'/vendor/autoload.php');
require_once (__DIR__.'/libraries/includes.php');
use GuzzleHttp\Client;
$guzzle=new Client([
    'proxy'=>PROXY,
    'timeout'=>TIMEOUT,
    'verify'=>false
]);
define('HELP_TEXT',<<<EOF
Usage:
    php index.php update-providers|update-nodes|start-bombing|import-providers
    Please refer to README.md for usage guidance.
EOF
);