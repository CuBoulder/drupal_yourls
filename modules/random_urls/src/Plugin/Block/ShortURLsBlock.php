<?php

namespace Drupal\random_urls\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'Short URLs' Block.
 *
 * @Block(
 *   id = "drupal_yourls_results_block",
 *   admin_label = @Translation("All Short URLs Block"),
 *   category = @Translation("Drupal YOURLs"),
 * )
 */
class ShortURLsBlock extends BlockBase {
    private $yourls_base_url, $yourls_secret;
    public function __construct(){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret'); 
    }
    //get the first 10 short URLs to initally populate table
    private function getResults(){
        $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json&action=stats&limit=10";
        try{
            $res = \Drupal::httpClient()->get($yourls_api);
            $res = json_decode($res->getBody(), true);
            return ['links' => $res['links'], 'max_pages' => $res['stats']['total_links']];
        }
        catch(RequestException | ClientException $e){
            \Drupal::logger('random_urls')->error($e->getMessage() );
            return ['links' => [], 'max_pages' => 0 ];
        }
    }
    /**
    * {@inheritdoc}
    */
    public function defaultConfiguration() {
      return array('label' => 'YOURLs Results Block',);
    }
    /**
    * {@inheritdoc}
    */
    public function build() {
        $results = $this->getResults();
        return [
            '#theme' => 'short-urls-results-template',
            '#results' => $results['links'],
            '#maxPages' => $results['max_pages'],
            '#cache' => ['max-age' => 0]
        ];
    }

}