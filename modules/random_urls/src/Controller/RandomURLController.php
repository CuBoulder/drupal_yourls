<?php

namespace Drupal\random_urls\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RandomURLController extends ControllerBase{
    private $domains;
    protected $yourls_connector;
    // get the configuration settings for the YOURLs install
    public function __construct( $yourls_connector ){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->domains = $config->get('yourls_allowed_domains');
        $this->yourls_connector = $yourls_connector;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static($container->get('drupal_yourls.yourls_connector'));
    }

    //get all of the listed URLS - for the table of results
    public function getAllURLs(Request $req){
        $search = preg_match('/http/', $req->query->get('keyword')) ? 'url' : 'keyword'; // search type
        // get the results of existing short URLS
        if($req->query->get('page') !== null){
            $start = ($req->query->get('page') * 10) - 10; //the start of results to fetch
            $res = ($this->yourls_connector)->stats($start);
            if(isset($res['error'])){
                return new Response(json_encode(['message' => $res['message'] ]), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
            }
            else{
                return new Response(json_encode(['links' => $res ]), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
        }
        // get data about a specific short url
        else if($search === 'keyword'){
            $res = ($this->yourls_connector)->urlStats($req->query->get('keyword'));
            if(isset($res['error'])){
                return new Response(json_encode(['message' => $res['message'] ]), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
            }
            else{
                return new Response(json_encode(['links' => ['links' => [ 'link_1' => $res['link']]] ]), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
        }
        // get data about a URL
        else if($search === 'url'){
            $_url = $req->query->get('keyword');
            // remove trailing slash if any
            if(substr($_url, -1) === '/'){
                $_url = mb_substr($_url, 0, -1);
            }
            $res = ($this->yourls_connector)->contract($_url);
            if(!isset($res['error']) && (bool) $res['url_exists']){
                return new Response(json_encode(['links' => ['links' => $res['links']] ]), Response::HTTP_OK, ['content-type' => 'application/json']);
            }
            else{
                return new Response(json_encode(['message' => 'URL does not exist' ]), Response::HTTP_INTERNAL_SERVER_ERROR, ['content-type' => 'application/json']);
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