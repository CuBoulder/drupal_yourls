<?php

namespace Drupal\approve_urls_webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
    private $yourls_base_url;
    private $yourls_secret;

    /**
    * {@inheritdoc}
    */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret');
        $this->validateURL($form_state);
        $this->validateKeyword($form_state);
        // Last, format the URL before submitting form by removing trailing slash if any
        $url = $form_state->getValue('long_url');
        if(substr($url['url'], -1) === '/'){
          $url['url'] = mb_substr($url['url'], 0, -1);
        }
        $form_state->setValue('long_url', $url);
    }

    /**
    * Validate url exists.
    */
    private function validateURL(FormStateInterface $formState) {
        $value = !empty($formState->getValue('long_url')) ? $formState->getValue('long_url') : NULL; //long_url has 2 values, title and url
        // Skip empty unique fields
        if (empty($value)) return;
        // Check if this URL actually goes somewhere
        try{
            $res = \Drupal::httpClient()->get($value['url'], ['allow_redirects' => ['track_redirects' => true, 'max' => 5] ]);
        }
        catch(\Exception $e){
            \Drupal::logger('approve_urls_webform')->notice($e->getMessage());
            $formState->setErrorByName('long_url][url', $this->t('This URL does not exist. Please make sure the URL is publicly accessible.'));
        }
        // Also check if this long URL has been shortened before
        try{
            $res = \Drupal::httpClient()->post($this->yourls_base_url, ['form_params' => [
                'action' => 'contract',
                'signature' => $this->yourls_secret,
                'url' => $value['url'],
                'format' => 'json'
            ]]);
            $res = json_decode($res->getBody(), true);
            if($res['statusCode'] == 200 && (bool) $res['url_exists']){
                $formState->setErrorByName('long_url][url', $this->t('This URL has already been shortened'));
            }
        }
        catch(\Exception $e){
            // recieved a 404 which means that the URL hasn't been shortened yet
            \Drupal::logger('approve_urls_webform')->notice($e->getMessage());
        }
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
    $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&format=json&action=expand&shorturl={$value}";
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
    }
  }
}