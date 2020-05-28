<?php
	
namespace Drupal\approve_urls\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use \GuzzleHttp\Exception\RequestException;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

class ApproveURLController{
    private $yourls_base_url, $yourls_secret;
    // get the configuration settings for the YOURLs install
    public function __construct(){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret'); 
    }

    // $nid is upcasted from the dynamic route in the routing.yml file
    // https://api.drupal.org/api/drupal/core%21modules%21node%21src%21NodeInterface.php/interface/NodeInterface/8.2.x
    public function deleteApp(Request $req, NodeInterface $nid = null){
        try{
            // delete the node from YOURLS (if exists) and from Drupal
            $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&action=delete&shorturl={}&format=json";
            \Drupal::httpClient()->get($yourls_api);
            //$res = json_decode($res->getBody(), true);
            $nid->delete();
        }
        catch(RequestException $e){
            // If it gets here, the short URL to delete doesn't exist - returns a 404
            \Drupal::logger('approve_urls')->error($e);
        }
        finally{
            $viewRoute = $req->query->get('destination');
            return new RedirectResponse($viewRoute, 302); // redirect back to the view
        }
    }

    public function approveApp(Request $req, NodeInterface $nid = null){
        try{
            // change the node's status to published and approved
            // 0 -> pending, 1 -> approved, 2 -> rejected
            $nid->set('field_ucb_url_status', 1);
            $nid->set('status', 1);
            $nid->save();
            // generate a new custom short URL
            $long_url = urldecode($nid->get('field_ucb_long_url')->uri);
            $keyword = urldecode($nid->get('field_ucb_short_url')->value);
            $title = urldecode($nid->get('field_ucb_site_title')->value); 
            // \Drupal::logger('approve_urls')->notice("keyword: {$keyword}, long url: {$long_url}, title: {$title}");
            $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&action=shorturl&format=json&url={$long_url}&title={$title}&keyword={$keyword}";
            \Drupal::httpClient()->get($yourls_api);
            $res = json_decode($res->getBody(), true);
            \Drupal::logger('approve_urls')->notice("Created new short URL: {$res['shorturl']}");
            // TODO: notify the user about their new short url
        }
        catch(\Exception $e){
            \Drupal::logger('approve_urls')->error($e);
        }
        finally{
            $viewRoute = $req->query->get('destination');
            return new RedirectResponse($viewRoute, 302); //return to the view
        }
    }

    public function rejectApp(Request $req, NodeInterface $nid = null){
        try{
            //Reject the application
            $nid->set('field_ucb_url_status', 2);
            $nid->save();
            \Drupal::logger('approve_urls')->notice("Rejecting Application with ID: {$nid->id()}");
            // TODO: Figure out what to do about rejected applications 
        }
        catch(\Exception $e){
            \Drupal::logger('approve_urls')->error($e);
        }
        finally{
            $viewRoute = $req->query->get('destination');
            return new RedirectResponse($viewRoute, 302);
        }
    }
}