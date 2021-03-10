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
 *   id = "approve_urls_delete_short_urls",
 *   label = @Translation("Delete short links when application is deleted"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Delete existing short urls when an application is deleted."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DeleteApplicationHandler extends WebformHandlerBase {
    use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
   public function preDelete(WebformSubmissionInterface $webform_submission) {
       // delete the URL from YOURLs if it exists
       $this->deleteYOURLsLink($webform_submission);
   }
   
  /**
   * Delete keyword
   */
   private function deleteYOURLsLink(WebformSubmissionInterface $webform_submission){
        $config = \Drupal::config('drupal_yourls.settings');
        $yourls_base_url = $config->get('yourls_url');
        $yourls_secret = $config->get('yourls_secret');
        $keyword = $webform_submission->getElementData('short_url');
        try{
            // delete the node from YOURLS (if exists)
            $yourls_api = "{$yourls_base_url}?signature={$yourls_secret}&action=delete&shorturl={$keyword}&format=json";
            $res = \Drupal::httpClient()->get($yourls_api);
            $res = json_decode($res->getBody(), true);
            \Drupal::logger('approve_urls')->notice("Successfully deleted short URL: {$keyword} from YOURLs.");
        }
        catch(\Exception $e){
            // If it gets here, the short URL to delete doesn't exist in YOURLS - returns a 404
            \Drupal::logger('approve_urls')->notice($e->getMessage());
        }
    }
}