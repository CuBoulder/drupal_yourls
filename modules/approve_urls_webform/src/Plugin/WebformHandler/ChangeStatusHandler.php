<?php

namespace Drupal\approve_urls_webform\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;

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
    private $recipient;

    /**
    * {@inheritdoc}
    */
    public function preSave(WebformSubmissionInterface $webform_submission){
        $keyword = $webform_submission->getElementData('short_url');
        $long_url = $webform_submission->getElementData('long_url');
        $status = $webform_submission->getElementData('application_status');
        
        $uid = $webform_submission->getOwnerId();
        $this->recipient = User::load($uid)->getEmail();
        
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
    
    // Email function
    private function sendEmail($message){
        $mailManager = \Drupal::service('plugin.manager.mail');
        // creating an email template with the key 'approve_urls' and hook_mail()
        $params = [
            "subject" => "CU Short URLs Application Status",
            "body" => $message,
            "headers" => [
                "Content-Type" => "text/plain; charset=utf-8",
                "MIME-Version" => "1.0",
                "Content-Transfer-Encoding" => "8Bit"
            ]
        ];
        $reply = !empty(\Drupal::config('smtp.settings')->get('smtp_from')) ? \Drupal::config('smtp.settings')->get('smtp_from') : \Drupal::config('system.site')->get('mail'); //reply-to address

        // mail(module, key, to, lang, params, reply, true)
        $result = $mailManager->mail('approve_urls_webform', 'app_update', $this->recipient, 'en', $params, $reply, true);
        if($result['result'] === true){
            // sent successfully
            \Drupal::messenger()->addMessage("Sent update email to {$this->recipient}", "status");
            return;
        }
        else{
            // Error sending a message
            \Drupal::messenger()->addMessage("Error sending update email to {$this->recipient}", "error");
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
        $email = $config->get('yourls_send_email');
        
        try{
            $client = \Drupal::httpClient();
            // YOURLs POST body must be form data
            $res = $client->post($yourls_base_url, ['form_params' => [
                'action' => 'shorturl',
                'signature' => $yourls_secret,
                'url' => urldecode($longURL['url']),
                'format' => 'json',
                'title' => $longURL['title'],
                'keyword' => $keyword
            ]]);
            $res = json_decode($res->getBody(), true);
            
            \Drupal::messenger()->addMessage("Created new short URL: {$res['shorturl']}", "status");
            if($this->send_email_flag === 1){
                // Email the user about their application status
                $this->sendEmail("Your application has been approved. Here is your short URL: {$res['shorturl']}");
            }
        }
        catch(Exception $e){
            \Drupal::logger('approve_urls_webform')->error($e->getMessage());
        }
    }
    
    /**
    * reject a submission
    */
    private function rejectURL(){
        $email = \Drupal::config('drupal_yourls.settings')->get('yourls_send_email');
        if($email === 1){
            $this->sendEmail("Your application has been denied.");
        }
        \Drupal::messenger()->addMessage("Rejecting Application. No short URL will be generated.", "status");
    }
}