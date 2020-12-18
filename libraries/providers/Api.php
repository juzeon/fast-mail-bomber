<?php
abstract class Api {
    protected $apiKey;
    public function __construct($apiKey){
        $this->apiKey=$apiKey;
    }
    public function get_providers($page){

    }
}