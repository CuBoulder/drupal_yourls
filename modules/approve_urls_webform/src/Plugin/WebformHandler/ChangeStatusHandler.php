<?php

namespace Drupal\approve_urls_webform\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "approve_urls_change_status",
 *   label = @Translation("Connect to YOURLs when applications are reviewed"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Generate a short URL when the application is approved."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class ChangeStatusHandler extends WebformHandlerBase {
    use StringTranslationTrait;

    /**
    * {@inheritdoc}
    */
    public function preSave(WebformSubmissionInterface $webform_submission){
        \Drupal::logger('approve_url_webform')->notice("in the preSave Handler");
        $keyword = $webform_submission->getElementData('short_url');
        $long_url = $webform_submission->getElementData('long_url');
        $status = $webform_submission->getElementData('application_status');
        switch($status){
            case 'Approved':
                $this->approveURL($long_url, $keyword);
                break;
            case 'Rejected':
                $this->rejectURL();
                break;
            default:
                return;
        }
    }
  
    /**
    * approve a submission
    */
    private function approveURL(array $longURL, $keyword) {
        $config = \Drupal::config('drupal_yourls.settings');
        $yourls_base_url = $config->get('yourls_url');
        $yourls_secret = $config->get('yourls_secret'); 
        \Drupal::logger('approve_url_webform')->notice(urldecode($longURL['url']));
    }
    
    /**
    * reject a submission
    */
    private function rejectURL() {
        
        \Drupal::logger('approve_url_webform')->notice('rejecting url... rip');
        // send the email to user notifying them of their rejection... rip
    }
}