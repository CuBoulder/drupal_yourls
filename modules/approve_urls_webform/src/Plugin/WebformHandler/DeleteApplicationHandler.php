<?php

namespace Drupal\approve_urls_webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

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
    /**
     * {@inheritdoc}
     */
    public function preDelete(WebformSubmissionInterface $webform_submission) {
        // delete the URL from YOURLs if it exists
        $keyword = $webform_submission->getElementData('short_url');
        $yourls_connector = \Drupal::service('drupal_yourls.yourls_connector');
        $res = $yourls_connector->delete( $keyword );
        if( !isset($res['error']) ){
            \Drupal::logger('approve_urls')->notice("Successfully deleted short URL: {$keyword} from YOURLs.");
            \Drupal::messenger()->addMessage("Successfully deleted short URL: {$keyword} from YOURLs.", "status");
        }
    }
}
