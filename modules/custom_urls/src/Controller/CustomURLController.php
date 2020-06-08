<?php
	
namespace Drupal\custom_urls\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class CustomURLController{	
    private $username, $yourls_base_url, $yourls_secret;

    // get the configuration settings for the YOURLs install
    public function __construct(){
        $user = User::load(\Drupal::currentUser()->id());
        $config = \Drupal::config('drupal_yourls.settings');

        $this->username = $user->getAccountName(); // Assumes that the username is also the identikey
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret'); 
    }

    // check if a URL goes to a 404 or not
    private function checkValidURL($url){
        $file_headers = @get_headers($url);
        if(!$file_headers) return false;
        for($i=0; $i< count($file_headers); $i++){
            if($file_headers[$i] == 'HTTP/1.1 404 Not Found'){
                // checks for redirects to a 404
                return false;
            }
        }
        return true;
    }

    // Add the form data to the DB
    private function addNewApplication($url, $shortURL, $title, $desc){
        try{
            $node = Node::create([
				'type' => 'short_url_application', //MUST CREATE A CONTENT TYPE THAT MATCHES THIS!
				'title' => "New URL Request from {$this->username}",
                'body' => $desc,
                'field_ucb_long_url' => $url,
                'field_ucb_short_url' => $shortURL,
                'field_ucb_site_title' => $title,
				'field_ucb_url_status' => 0 // pending
			]);
            $node->status = 1; // publish on creation
			$node->enforceIsNew();
            $node->save();
            return true;
        }
        catch(\Exception $e){
            \Drupal::logger('custom_urls')->error('Error entering new short URL application into DB');
            return false;
        }
    }
    // check if the short url already exists
    private function checkKeywordExists($keyword){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json&action=url-stats&shorturl={$keyword}";
        try{
            $res = \Drupal::httpClient()->get($yourls_api);
            $res = json_decode($res->getBody(), true);
            if($res['statusCode'] == 200){
                return true; // short url exists
            }
            else{
                return false;
            }
        }
        catch(\Exception $e){
            // recieved a 404 or other error
            return false;
        }
    }

    // Add the request into the Application content type
    // POST request with application data in the body
    public function addNewURLRequest(Request $req){
        $body = json_decode($req->getContent(), true);
        if($this->checkKeywordExists($body['custom'])){
            // This key word is taken
            return new Response(json_encode(['app_status' => false, 'message' => 'This short URL already exists. Please choose one that hasn\'t been taken.']), Response::HTTP_OK, ['content-type' => 'application/json']);
        }
        $url_exists = $this->checkValidURL($body['url']); // true | false
        $space = strpos($body['custom'], " ");        // test for spaces in the short URL
        if($url_exists && !$space){
            $resp = $this->addNewApplication($body['url'], $body['custom'], $body['title'], $body['desc']);
            if($resp){
                // Added the application to DB successfully
                return new Response(json_encode(['app_status' => true, 'message' => 'Added a new application to the DB']), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
            else{
                // Couldn't enter the application in the DB
                return new Response(json_encode(['app_status' => false, 'message' => 'Error entering the application. Please try again.']), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
        }
        else{
            // Error with form validation
            return new Response(json_encode(['app_status' => false, 'message' => 'Error in form validation. Please make sure that the URL exists and that your keyword is one word.']), Response::HTTP_OK, ['content-type' => 'application/json']);
        }
    }

    //render the page to get a random URL
    public function render(){
        return(array(
            '#theme' => 'custom-urls-template',
            '#yourlsBase' => 'asdf',
            '#username' => $this->username
        ));
    }
}