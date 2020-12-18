<?php


class ZoomEye extends Api {
    public function get_providers($page) {
        global $guzzle;
        $totalProviders = [];
        try {
            $resp = $guzzle->get('https://api.zoomeye.org/host/search?query=mailman/listinfo&sub_type=v4&page=' . $page, [
                'headers' => [
                    'API-KEY' => $this->apiKey
                ]
            ]);
            $json = json_decode($resp->getBody());
            foreach ($json->matches as $host) {
                $totalProviders = array_merge($totalProviders, parse_providers($host->portinfo->banner));
            }
            $totalProviders = array_values(array_unique($totalProviders));
        } catch (JsonException | RequestException $e) {
            println('A network error has occurred. Please examine your api key and network connection. Also check if the api limit of your ZoomEye account is reached.');
        } catch (Exception $e) {
            println($e->getTraceAsString());
        } finally {
            return $totalProviders;
        }
    }
}