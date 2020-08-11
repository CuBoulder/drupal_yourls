<?php
    
namespace Drupal\random_urls\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;


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
        $url = $form_state->getValue('url');
        $flag = false;
        
        // validate that the domin comes from an apporved domain
        for($i =0; $i < count($this->domains); $i++){
            $d = ($this->domains)["url_{$i}"];
            $p = strpos($url, $d);
//             $this->messenger()->addStatus( "$url, $d, $p" );
            if(strpos($url, ($this->domains)["url_{$i}"] ) !== false){
                $flag = true;
            }
        }
        
        if($flag === false){
            $form_state->setErrorByName('url', $this->t('Cannot create short link. Please make sure that the url comes from an approved domain.'));
        }
        // validate that the URL exists
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
        if($flag === false){
            $form_state->setErrorByName('url', $this->t('Cannot create short link. Please make sure that this URL exists.'));
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
            $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&action=shorturl&format=json&url={$url}";
            // \Drupal::logger('random_urls')->notice("url : $yourls_api");
            $res = \Drupal::httpClient()->get($yourls_api); //call the API
            \Drupal::logger('random_urls')->notice("Adding a new random URL. View it on your YOURLs installation");
            $res = json_decode($res->getBody());
            $form['random_url']['#markup'] = "<div role='alert' class='alert alert-success mt-2'> Your new short link is: {$res->shorturl} </div>";
        }
        catch(RequestException | ClientException $e){
            \Drupal::logger('random_urls')->error('Malformed URL or request resulted in a 404');
            $form['random_url']['#markup'] = "<div role='alert' class='alert alert-danger mt-2'> Something went wrong trying to create this short link. Please try again. </div>";
        }
    }
}