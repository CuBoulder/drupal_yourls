<?php

namespace Drupal\yourls_rest_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\RedirectMiddleware;
use Psr\Log\LoggerInterface;

/**
 * Provides a YOURLs Resource
 *
 * @RestResource(
 *   id = "yourls_shorturl_resource",
 *   label = @Translation("YOURLs Shorten URL"),
 *   uri_paths = {
 *     "create" = "/v1/shorten"
 *   }
 * )
 */
class ShortenURLResource extends ResourceBase {
    protected $user, $yourls_connector, $domains;

    public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      array $serializer_formats,
      LoggerInterface $logger,
      AccountProxyInterface $user,
      $yourls_connector) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->user = $user;
        $this->yourls_connector = $yourls_connector;
        $this->domains = \Drupal::config('drupal_yourls.settings')->get('yourls_allowed_domains');

    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
      return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->getParameter('serializer.formats'),
        $container->get('logger.factory')->get('rest'),
        $container->get('current_user'),
        $container->get('drupal_yourls.yourls_connector')
      );
    }

    /**
     * @param string $url The URL to validate
     * @return boolean
     */
    private function checkValidURL($url){
      $from_approved_domain = false;
      try{
        $res = \Drupal::httpClient()->get($url, ['allow_redirects' => ['track_redirects' => true, 'max' => 5] ]);
        $res = $res->getHeader( RedirectMiddleware::HISTORY_HEADER );
        $end_url = end($res); // has the redirected URL, if it's an empty string then the URL wasn't redirected
        for($i =0; $i < count($this->domains); $i++){
          if(strpos($url, ($this->domains)["url_{$i}"] )){
            $from_approved_domain = true;
          }
          if( !empty($end_url) && strpos($end_url, ($this->domains)["url_{$i}"] )){
            $from_approved_domain = true;
          }
        }
      }
      catch(\Exception $e){
          \Drupal::logger('random_urls')->error($e->getMessage() );
      }
      return $from_approved_domain;
    }

    /**
     * Responds to POST requests.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function post(Request $request) {
      $response = [
        'message' => null,
        'shorturl' => null,
        'status' => null
      ];
      /**
       * Verify user has proper role
       */
      if(($this->user)->hasPermission('create_random_url')){
        $body = json_decode($request->getContent(), true);
        /**
         * Verify that post body contains URL param
         */
        if(isset($body['url'])){
          $url = urldecode($body['url']);
          /**
           * Verify that URL exists and comes from an approved domain
           */
          if($this->checkValidURL($url)){
            $res = ($this->yourls_connector)->shorturl($url);
            $response['message'] = "success";
            $response['shorturl'] = "{$res['shorturl']}";
            $response['status'] = 200;
          }
          else{
            $response['message'] = "URL cannot be shortened. Does this URL come from an approved domain?";
            $response['status'] = 422;
          }
        }
        else{
          $response['message'] = "Missing body parameter [url]";
          $response['status'] = 422;
        }
      }
      else{
        $response['message'] = "You do not have permission to access this endpoint";
        $response['status'] = 401;
      }
      return new Response( json_encode($response, JSON_UNESCAPED_SLASHES ) , $response['status'], ['content-type' => 'application/json']);
    }
}
