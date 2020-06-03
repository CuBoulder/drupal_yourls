<?php

namespace Drupal\random_urls\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use \GuzzleHttp\Exception\RequestException;

class RandomURLController{	
    private $yourls_base_url, $yourls_secret;
    // get the configuration settings for the YOURLs install
    public function __construct(){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret'); 
    }
    // check for a 404 status code
    // returns true if the URL exists, otherwise return false
    private function checkURLExists($url){
        $flag = true;
        $file_headers = @get_headers($url);
        if(!$file_headers) $flag = false;
        else{
            for($i=0; $i< count($file_headers); $i++){
                if($file_headers[$i] == 'HTTP/1.1 404 Not Found'){
                    $flag = false;
                    break;
                }
            }
        }
        return $flag;
    }
    //get a random URL from the API
    public function getRandomURL(Request $req){
        $url = $req->query->get('url');  //long url from user
        // check if the domain comes from a colroado.edu site
        preg_match('/.\.*colorado\.edu/', $url, $matches, PREG_UNMATCHED_AS_NULL); //make sure the URL comes from a colorado.edu domain
        if(!$matches){
            $message = "URL isn't from a colorado.edu domain.";
            return new Response(json_encode(['message' => $message]), Response::HTTP_OK, ['content-type' => 'application/json']);
        }
        if($this->checkURLExists($url)){
            // URL exists and comes from a colorado.edu domain
            try{
                $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&action=shorturl&format=json&url={$url}";
                // \Drupal::logger('random_urls')->notice("url : $yourls_api");
                $res = \Drupal::httpClient()->get($yourls_api); //call the API
                \Drupal::logger('random_urls')->notice("Adding a new random URL. View it on your YOURLs installation");
                return new Response($res->getBody(), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
            catch(RequestException $e){
                \Drupal::logger('random_urls')->error($e);
                return new Response(json_encode(['message' => $e]), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
            }
        }
        else{
            // URL doesn't exist
            $message = "This URL can't be found. Please make sure that your URL goes somewhere.";
            return new Response(json_encode(['message' => $message]), Response::HTTP_OK, ['content-type' => 'application/json']);
        }
    }

    //render the page to get a random URL - Page cache is disabled for this page
    public function render(){
        return(array(
            '#theme' => 'random-urls-template',
            '#yourlsBase' => $this->yourls_base_url,
            '#yourlsSecret' => $this->yourls_secret,
            '#results' => []
        ));
    }
}