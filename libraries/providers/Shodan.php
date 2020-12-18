<?php
class Shodan extends Api {
    public function get_providers($page) {
        global $guzzle;
        $totalProviders=[];
        try{
            $resp=$guzzle->get('https://api.shodan.io/shodan/host/search?key='.$this->apiKey.'&query=mailman/listinfo');
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
                $totalProviders=array_merge($totalProviders,$importProviders);
            }
            $totalProviders = array_values(array_unique($totalProviders));
        }catch (JsonException | RequestException $e){
            println('A network error has occurred. Please examine your api key and network connection.');
        }catch(Exception $e){
            println($e->getTraceAsString());
        } finally {
            return $totalProviders;
        }
    }
}