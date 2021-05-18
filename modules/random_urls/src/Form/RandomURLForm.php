<?php

namespace Drupal\random_urls\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RedirectMiddleware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RandomURLForm extends FormBase {
    private $domains;
    private $url_exists;
    protected $yourls_connector;
    function __construct( $yourls_connector ){
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

    /**
    * {@inheritdoc}
    */
    public function getFormId() {
        return 'random_url_form';
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Inline form errors module doesn't show the message properly
        $form['#disable_inline_form_errors'] = TRUE;
        $form['url'] = array(
          '#type' => 'textfield',
          '#placeholder' => $this->t('Shorten my URL'),
          '#title' => $this->t('URL to Shorten'),
          '#required' => TRUE,
        );
        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
          '#button_type' => 'primary',
          '#ajax' => [
              'callback' => '::handleNewRandomURL',
              'wrapper' => 'random-url-wrapper'
          ]
        );
        $form['random_url'] = array(
            '#type' => 'markup',
            '#prefix' => '<div id="random-url-wrapper">',
            '#suffix' => '</div>',
            '#markup' => '',
        );
        return $form;
    }
    /**
    * {@inheritdoc}
    */
    public function validateForm(array &$form, FormStateInterface $form_state){
        $url = $form_state->getValue('url'); $from_approved_domain = false;
       	// remove a trailing slash if any
        if(substr($url, -1) === '/'){
            $url = mb_substr($url, 0, -1);
        }

        // Check if this URL has been shortened before, random links are 1:1 while custom links are many to one
        $res = ($this->yourls_connector)->contract( $url );
        if(!isset($res['error'])){
            if($res['statusCode'] == 200){
              $this->url_exists = $res;
            }
        }

        // validate that the URL exists and comes from an approved domain
        // If the URL is a redirect, then verify that the start and end URLs are from an approved domain
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
        catch(RequestException | ClientException $e){
            \Drupal::logger('random_urls')->error($e->getMessage() );
            $form_state->setErrorByName('url', $this->t('Cannot create short link. Please make sure that this URL exists.'));
        }
        if(!$from_approved_domain){
            $form_state->setErrorByName('url', $this->t('Please make sure this URL comes from an approved domain.'));
        }
        $form_state->setValue('url', $url);
    }


    public function handleNewRandomURL(array &$form, FormStateInterface $form_state) {
        return $form['random_url'];
    }

    /**
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $url = $form_state->getValue('url');
        $shortened = null;
        try{
          if( (bool) $this->url_exists['url_exists'] === true){
             $shortened = $this->url_exists['links']['link_1']['shorturl'];
          }
          else{
            $res = ($this->yourls_connector)->shorturl($url);
            $shortened = $res['shorturl'];
          }
          $form['random_url']['#markup'] = "<div role='alert' class='alert alert-success mt-2'> Your new short link is: {$shortened} </div>";
        }
        catch( \Exception $e){
          $form['random_url']['#markup'] = "<div role='alert' class='alert alert-danger mt-2'> Something went wrong trying to create this short link. Please try again. </div>";
        }
    }
}
