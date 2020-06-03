<?php

namespace Drupal\random_urls\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Short URLs' Block.
 *
 * @Block(
 *   id = "short_urls_block",
 *   admin_label = @Translation("All Short URLs Block"),
 *   category = @Translation("Drupal URLs"),
 * )
 */
class ShortURLsBlock extends BlockBase {
    private $yourls_base_url, $yourls_secret;
    public function __construct(){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret'); 
    }
    //get all of the existing short urls from the API or data about a certain url
    private function getAllShortURLs($page = null, $keyword=null){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json";
        // get the results of existing short URLS
        if($page){
            try{
                $start = ($page * 10) - 10; //the start of results to fetch
                $end = ($page * 10) -1; // end of the results to fetch
                $yourls_api = "{$yourls_api}&action=stats&limit={$end}&start={$start}"; //get 10 results at a time
                // \Drupal::logger('random_urls')->notice("url: {$yourls_api}");
                $res = \Drupal::httpClient()->get($yourls_api);
                $res = json_decode($res->getBody(), true);
                return $res['links'];
            }
            catch(RequestException $e){
                \Drupal::logger('random_urls')->error($e);
                return [];
            }
        }
        // get data about a specific short url
        else if($keyword){
            try{
                $yourls_api = "{$yourls_api}&action=url-stats&shorturl={$keyword}";
                $res = \Drupal::httpClient()->get($yourls_api);
                $res = json_decode($res->getBody(), true);
                // format the result
                return ["link_1" => $res['link'] ];
            }
            catch(RequestException $e){
                \Drupal::logger('random_urls')->error($e);
                return []; // 404 or some other error
            }
        }
        else{
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
        $req = \Drupal::request();
        $results = [];
        if($req->query->get('page')){
            $results = $this->getAllShortURLs($req->query->get('page'), null);
        }
        else if($req->query->get('keyword')){
            $results = $this->getAllShortURLs(null, $req->query->get('keyword'));
        }
        // if no query parameters, just return the top 10 results
        else{
            $results = $this->getAllShortURLs(1, null);
        }
        return [
            '#theme' => 'short-urls-results-template',
            '#results' => $results,
            '#cache' => ['max-age' => 0]
        ];
    }

}