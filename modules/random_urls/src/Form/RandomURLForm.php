<?php
    
namespace Drupal\random_urls\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RedirectMiddleware;


class RandomURLForm extends FormBase {
    private $yourls_base_url, $yourls_secret, $domains;
    function __construct(){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret'); 
        $this->domains = $config->get('yourls_allowed_domains'); 
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
  
    /*
    * {@inheritdoc}
    */
    public function validateForm(array &$form, FormStateInterface $form_state){
        $url = $form_state->getValue('url'); $from_approved_domain = true;
        // validate that the URL exists and comes from an approved domain
        // If the URL is a redirect, then verify that the start and end URLs are from an approved domain
        try{
            $res = \Drupal::httpClient()->get($url, ['allow_redirects' => ['track_redirects' => true, 'max' => 5] ]);
            $res = $res->getHeader( RedirectMiddleware::HISTORY_HEADER );
            $end_url = end($res); // has the redirected URL, if it's an empty string then the URL wasn't redirected
            \Drupal::logger('random_urls')->notice($end_url);
            for($i =0; $i < count($this->domains); $i++){
                if(strpos($url, ($this->domains)["url_{$i}"] ) === false ){
                    $from_approved_domain = false;
                    break;
                }
                if( !empty($end_url) && strpos($end_url, ($this->domains)["url_{$i}"] ) === false ){
                    $from_approved_domain = false;
                    break;
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
    }


    public function handleNewRandomURL(array &$form, FormStateInterface $form_state) {
        return $form['random_url'];
    }

    /*
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $url = $form_state->getValue('url');
        try{
            // remove a trailing slash if any
            if(substr($url, -1) === '/'){
                $url = mb_substr($url, 0, -1);
            }
            $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&action=shorturl&format=json&url={$url}";
            $res = \Drupal::httpClient()->get($yourls_api);
            $res = json_decode($res->getBody());
            $form['random_url']['#markup'] = "<div role='alert' class='alert alert-success mt-2'> Your new short link is: {$res->shorturl} </div>";
        }
        catch(RequestException | ClientException $e){
            \Drupal::logger('random_urls')->error( $e->getMessage() );
            $form['random_url']['#markup'] = "<div role='alert' class='alert alert-danger mt-2'> Something went wrong trying to create this short link. Please try again. </div>";
        }
    }
}