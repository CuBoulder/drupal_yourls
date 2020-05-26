<?php
	
namespace Drupal\custom_urls\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class CustomURLController{	
    private $username;

    // get the configuration settings for the YOURLs install
    public function __construct(){
        $user = User::load(\Drupal::currentUser()->id());
        $this->username = $user->getAccountName(); // Assumes that the username is also the identikey
    }

    // check if a URL goes to a 404 or not
    private function checkValidURL($url){
        $file_headers = @get_headers($url);
        if(!$file_headers) return false;
        for($i=0; $i< count($file_headers); $i++){
            if($file_headers[$i] == 'HTTP/1.1 404 Not Found'){
                // checks for redirects
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
            $node->status = 0; //Do not publish on creation
			$node->enforceIsNew();
            $node->save();
            return ["message" => "Added a new application", "app_status" => true];
        }
        catch(\Exception $e){
            return ["message" => $e->getMessage(), "app_status" => false];
        }
    }

    // Add the request into the Application content type
    // POST request with application data in the body
    public function addNewURLRequest(Request $req){
        $body = json_decode($req->getContent(), true);
        $url_exists = $this->checkValidURL($body['url']); // true | false
        $space = strpos($_POST['short_url'], " ");        // test for spaces in the short URL
        if($url_exists && !$space){
            $resp = $this->addNewApplication($body['url'], $body['custom'], $body['title'], $body['desc']);
            if($resp['status'] === true){
                // everything passed!
                return new Response(json_encode($resp), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
            else{
                // Couldn't enter the application in the DB
                return new Response(json_encode([$resp]), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
        }
        else{
            // Error with form validation
            return new Response(json_encode(['app_status' => 'Error in form validation']), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
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