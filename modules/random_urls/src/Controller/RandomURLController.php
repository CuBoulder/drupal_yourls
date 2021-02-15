<?php

namespace Drupal\random_urls\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;


class RandomURLController{	
    private $yourls_base_url, $yourls_secret, $domains;
    // get the configuration settings for the YOURLs install
    public function __construct(){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret'); 
        $this->domains = $config->get('yourls_allowed_domains'); 
    }

    //get all of the listed URLS - for the table of results
    public function getAllURLs(Request $req){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json";
        $search = preg_match('/http/', $req->query->get('keyword')) ? 'url' : 'keyword'; // search type
        // get the results of existing short URLS
        if($req->query->get('page') !== null){
            try{
                $start = ($req->query->get('page') * 10) - 10; //the start of results to fetch
                $yourls_api = "{$yourls_api}&action=stats&limit=10&start={$start}"; //get 10 results at a time (doesnt work for case senstiive keyword searches)
                $res = \Drupal::httpClient()->get($yourls_api);
                $res = json_decode($res->getBody(), true);
                return new Response(json_encode(['links' => $res ]), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
            catch(RequestException | ClientException $e){
                \Drupal::logger('random_urls')->error($e->getMessage());
                return new Response(json_encode(['message' => $e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
            }
        }
        // get data about a specific short url
        else if($search === 'keyword'){
            try{
                $yourls_api = "{$yourls_api}&action=url-stats&shorturl={$req->query->get('keyword')}";
                $res = \Drupal::httpClient()->get($yourls_api);
                $res = json_decode($res->getBody(), true);
                // format the result
                return new Response(json_encode(['links' => [ 'link_1' => $res['link']] ]), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
            catch(RequestException | ClientException $e){
                \Drupal::logger('random_urls')->error($e->getMessage());
                return new Response(json_encode(['message' => $e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
            }
        }
        // get data about a URL
        else if($search === 'url'){
            $_url = $req->query->get('keyword');
            // remove trailing slash if any
            if(substr($_url, -1) === '/'){
                $_url = mb_substr($_url, 0, -1);
            }
            try{
                // check if this URL has already been shortened
                $res = \Drupal::httpClient()->post($this->yourls_base_url, ['form_params' => [
                    'action' => 'contract',
                    'signature' => $this->yourls_secret,
                    'url' => $_url,
                    'format' => 'json'
                ]]);
                $res = json_decode($res->getBody(), true);
                if((bool) $res['url_exists']){
                    return new Response(json_encode(['links' => $res['links'] ]), Response::HTTP_OK, ['content-type' => 'application/json']);
                }
                else{
                    throw new Exception('URL does not exist');
                }
            }
            catch(Exception $e){
                \Drupal::logger('random_urls')->error($e->getMessage());
                return new Response(json_encode(['message' => $e->getMessage() ]), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
            }
        }
        else{
            return new Response(json_encode(['message' => 'Invalid query parameter']), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
        }
    }

    //render the page to get a random URL - Page cache is disabled for this page
    public function render(){
        $form = \Drupal::formBuilder()->getForm('Drupal\random_urls\Form\RandomURLForm');
        return(array(
            '#theme' => 'random-urls-template',
            '#domains' => $this->domains,
            '#form' => $form
        ));
    }
}