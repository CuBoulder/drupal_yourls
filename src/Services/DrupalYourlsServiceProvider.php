<?php

namespace Drupal\drupal_yourls\Services;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class DrupalYourlsServiceProvider extends ServiceProviderBase{
    /**
     * API base url
     */
    private $yourls_base_url, $yourls_secret;

    public function __construct() {
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_secret = $config->get('yourls_secret');
        $this->yourls_base_url = $config->get('yourls_url');
    }
    /**
     * call the API and return the response
     */
    private function callYourlsAPI($api_endpoint, $method, $params = null){
        $res = null;
        switch( $method ){
            case 'GET':
                try{
                    $res = \Drupal::httpClient()->get($api_endpoint);
                    $res = json_decode($res->getBody(), true);
                }
                catch( RequestException | ClientException $e ){
                    $res = [ 'error' => true, 'message' => $e->getMessage()];
                }
                break;
            case 'POST':
                try{
                    $res = \Drupal::httpClient()->post($api_endpoint, ['form_params' => $params ]);
                    $res = json_decode($res->getBody(), true);
                }
                catch( RequestException | ClientException $e ){
                    $res = [ 'error' => true, 'message' => $e->getMessage()];
                }
                break;
            default:
                $res = ['error' => true, 'message' => 'Invalid option given'];
        }
        return $res;
    }
    /**
     * get stats about n number of short links
     */
    public function stats( $start ){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json&action=stats&limit=10&start={$start}";
        return $this->callYourlsAPI( $yourls_api, 'GET' );
    }
    /**
     * get stats about a specific keyword
     */
    public function urlStats( $keyword ){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json&action=url-stats&shorturl={$keyword}";
        return $this->callYourlsAPI( $yourls_api, 'GET' );
    }
    /**
     * delete a short link by keyword
     */
    public function delete( $keyword ){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json&action=delete&shorturl={$keyword}";
        return $this->callYourlsAPI( $yourls_api, 'GET' );
    }
    /**
     * get stats about a short link by long url
     */
    public function contract($url){
        $params = [
            'action' => 'contract',
            'signature' => $this->yourls_secret,
            'url' => urldecode($url),
            'format' => 'json'
        ];
        return $this->callYourlsAPI($this->yourls_base_url, 'POST', $params );
    }
    /**
     * create a new short url
     */
    public function shorturl($url, $keyword = null, $title = null){
        $params = [
            'action' => 'shorturl',
            'signature' => $this->yourls_secret,
            'url' => urldecode($url),
            'format' => 'json'
        ];
        if($keyword && $title){
            $params['title'] = $title;
            $params['keyword'] = $keyword;
        }
        return $this->callYourlsAPI($this->yourls_base_url, 'POST', $params );
    }
    /**
     * get the long url from a keyword
     */
    public function expand( $keyword ){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json&action=expand&shorturl={$keyword}";
        return $this->callYourlsAPI( $yourls_api, 'GET' );
    }
}