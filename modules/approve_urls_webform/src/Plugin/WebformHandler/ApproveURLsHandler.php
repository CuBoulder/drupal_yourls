<?php

namespace Drupal\approve_urls_webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "approve_urls_webform_custom_validator",
 *   label = @Translation("Alter Form Validation"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Form alter to validate url and keyword."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class ApproveURLsHandler extends WebformHandlerBase {
    use StringTranslationTrait;

    /**
    * {@inheritdoc}
    */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $this->validateURL($form_state);
        $this->validateKeyword($form_state);
    }

    /**
    * Validate url exists.
    */
    private function validateURL(FormStateInterface $formState) {
        $value = !empty($formState->getValue('long_url')) ? $formState->getValue('long_url') : NULL; //long_url has 2 values, title and url
        // Skip empty unique fields
        if (empty($value)) {
          return;
        }
        // check if the URL goes to a 404
        $flag = true;
        $file_headers = @get_headers($value['url']);
        if(!$file_headers) return false;
        for($i=0; $i< count($file_headers); $i++){
            if($file_headers[$i] == 'HTTP/1.1 404 Not Found'){
                // checks for redirects to a 404
                $flag = false;
            }
        }
        // TODO: check if the URL has already been shortened
        
        // Strip trailing slash from URL if any
        if(substr($value['url'], -1) === '/'){
            $value['url'] = mb_substr($value['url'], 0, -1);
        }
        $flag ? $formState->setValue('long_url', $value) : $formState->setErrorByName('long_url', $this->t('URL does not exist. Please enter a valid URL'));
    }
  
  /**
   * Validate keyword
   */
  private function validateKeyword(FormStateInterface $formState) {
    $value = !empty($formState->getValue('short_url')) ? Html::escape($formState->getValue('short_url')) : NULL;

    // Skip empty unique fields or arrays (aka #multiple).
    if (empty($value) || is_array($value)) {
      return;
    }
    // check for spaces and formatting
    $regex = preg_match('/[!@#$%^&*():.<>?[\]\{\}\|\/[:blank:]]/', $value);
    if($regex){
        $formState->setErrorByName('short_url', $this->t('Please make sure the short url has no spaces, special chars, or is a link'));
        return;
    }
    // check if the keyword already exists
    $config = \Drupal::config('drupal_yourls.settings');
    $yourls_base_url = $config->get('yourls_url');
    $yourls_secret = $config->get('yourls_secret'); 
    $yourls_api = "{$yourls_base_url}?signature={$yourls_secret}&format=json&action=expand&shorturl={$value}";
    
    try{
        $res = \Drupal::httpClient()->get($yourls_api);
        $res = json_decode($res->getBody(), true);
        if($res['statusCode'] == 200){
            $formState->setErrorByName('short_url', $this->t('Keyword already exists. Please choose another one.')); // short url exists or is a reserved word
        }
    }
    catch(\Exception $e){
        // recieved a 404 - this means that the short URL doesnt exist yet
        \Drupal::logger('approve_urls_webform')->notice($e->getMessage());
        $formState->setValue('short_url', $value);
    }
  }
}