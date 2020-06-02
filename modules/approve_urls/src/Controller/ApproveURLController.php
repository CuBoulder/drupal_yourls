<?php
	
namespace Drupal\approve_urls\Controller;

// use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \GuzzleHttp\Exception\RequestException;
use Drupal\node\NodeInterface;
// use Drupal\user\Entity\User;

class ApproveURLController{
    private $yourls_base_url, $yourls_secret, $email_recipient;
    // get the configuration settings for the YOURLs install
    public function __construct(){
        $config = \Drupal::config('drupal_yourls.settings');
        $this->yourls_base_url = $config->get('yourls_url');
        $this->yourls_secret = $config->get('yourls_secret');
        $this->email_recipient = \Drupal::currentUser()->getEmail();
    }

    // $nid is upcasted from the dynamic route in the routing.yml file
    // https://api.drupal.org/api/drupal/core%21modules%21node%21src%21NodeInterface.php/interface/NodeInterface/8.2.x
    public function deleteApp(Request $req, NodeInterface $nid = null){
        try{
            // delete the node from YOURLS (if exists) and from Drupal
            $keyword = urldecode($nid->get('field_ucb_short_url')->value);
            $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&action=delete&shorturl={$keyword}&format=json";
            $res = \Drupal::httpClient()->get($yourls_api);
            $res = json_decode($res->getBody(), true);
            \Drupal::logger('approve_urls')->notice("Sucessfully deleted short URL: {$keyword}");
            $nid->delete();
        }
        catch(RequestException $e){
            // If it gets here, the short URL to delete doesn't exist - returns a 404
            \Drupal::logger('approve_urls')->error($e);
        }
        finally{
            // $viewRoute = $req->query->get('destination');
            return new Response(json_encode(['message' => 'Application Deleted.', 'action' => 'deleted']), Response::HTTP_OK, ['content-type' => 'application/json']);
            // return new RedirectResponse($viewRoute, 302); // redirect back to the view
        }
    }
    // send an email with the application status
    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Mail%21MailInterface.php/function/MailInterface%3A%3Amail/8.2.x
    // https://www.zyxware.com/articles/5504/drupal-8-how-to-send-a-mail-programmatically-in-drupal-8
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
        $reply = \Drupal::config('smtp.settings')->get('smtp_from'); //reply-to address

        // mail(module, key, to, lang, params, reply, true)
        $result = $mailManager->mail('approve_urls', 'app_update', $this->email_recipient, 'en', $params, $reply, true);
        if($result['result'] === true){
            // sent successfully
            \Drupal::messenger()->addMessage("Sent update email to {$this->email_recipient}", "status");
            return;
        }
        else{
            // Error sending a message
            \Drupal::messenger()->addMessage("Error sending update email to {$this->email_recipient}", "error");
            return;
        }
    }
    public function approveApp(Request $req, NodeInterface $nid = null){
        try{
            // change the node's status to published and approved
            // 0 -> pending, 1 -> approved, 2 -> rejected
            $nid->set('field_ucb_url_status', 1);
            $nid->save();
            // generate a new custom short URL
            $long_url = urldecode($nid->get('field_ucb_long_url')->uri);
            $keyword = urldecode($nid->get('field_ucb_short_url')->value);
            $title = urldecode($nid->get('field_ucb_site_title')->value); 
            \Drupal::logger('approve_urls')->notice("keyword: {$keyword}, long url: {$long_url}, title: {$title}");
            // $yourls_api = "{$this->yourls_base_url}?signature={$this->yourls_secret}&action=shorturl&format=json&url={$long_url}&keyword={$keyword}&title={$title}";
            // \Drupal::logger('approve_urls')->notice("Making request to : {$yourls_api}");
            $client = \Drupal::httpClient();
            $query_params = "signature={$this->yourls_secret}&action=shorturl&format=json&url={$long_url}&keyword={$keyword}&title={$title}";
            $res = $client->get("$this->yourls_base_url", ['query' => $query_params]);
            $res = json_decode($res->getBody(), true);
            \Drupal::logger('approve_urls')->notice("Created new short URL: {$res['shorturl']}");
            // Email the user about their application status
            $this->sendEmail("Your application has been approved. Here is your short URL: {$res['shorturl']}");
        }
        catch(\Exception $e){
            \Drupal::logger('approve_urls')->error($e);
        }
        finally{
            // $viewRoute = $req->query->get('destination');
            return new Response(json_encode(['message' => 'Application accepted.', 'action' => 'approved']), Response::HTTP_OK, ['content-type' => 'application/json']);
            // return new RedirectResponse($viewRoute, 302);
        }
    }

    public function rejectApp(Request $req, NodeInterface $nid = null){
        try{
            //Reject the application
            $nid->set('field_ucb_url_status', 2);
            $nid->save();
            \Drupal::logger('approve_urls')->notice("Rejecting Application with ID: {$nid->id()}");
            // Send an update to the user about their rejection  :(
            $this->sendEmail("Your application has been denied.");
        }
        catch(\Exception $e){
            \Drupal::logger('approve_urls')->error($e);
        }
        finally{
            // $viewRoute = $req->query->get('destination');
            return new Response(json_encode(['message' => 'Application rejected.', 'action' => 'denied']), Response::HTTP_OK, ['content-type' => 'application/json']);
            // return new RedirectResponse($viewRoute, 302); // return to view

        }
    }
}