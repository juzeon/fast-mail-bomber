<?php
require_once (__DIR__.'/config.php');
require_once (__DIR__.'/init.php');
if($argc==1){
    println(HELP_TEXT);
    exit;
}
if(!file_exists(__DIR__.'/data')){
    mkdir(__DIR__.'/data');
}
switch ($argv[1]){
    case 'update-providers':
        require_once (__DIR__ . '/actions/update-providers.php');
        break;
    case 'update-nodes':
        require_once (__DIR__ . '/actions/update-nodes.php');
        break;
    case 'refine-nodes':
        require_once (__DIR__ . '/actions/refine-nodes.php');
        break;
    case 'start-bombing':
        require_once (__DIR__ . '/actions/start-bombing.php');
        break;
    case 'import-providers':
        require_once (__DIR__ . '/actions/import-providers.php');
        break;
    default:
        println(HELP_TEXT);
        break;
}