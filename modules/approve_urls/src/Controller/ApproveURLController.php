<?php
	
namespace Drupal\approve_urls\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class ApproveURLController{	
    public function deleteApp(Request $req, NodeInterface $nid = null){
        // delete the node from YOURLS and from Drupal
        // $nid is from the dynamic route from the routing.yml
        \Drupal::logger('approve_urls')->notice("Deleting this entry: {$nid->getTitle()}");
        // then redirect back to the View
        // $req is from the view and has a param that has the url to go back to
        $viewRoute = $req->query->get('destination');
        // redirect back to the view page once the node's been deleted
        return new RedirectResponse($viewRoute, 302);
    }

    public function approveApp(Request $req, NodeInterface $nid = null){
        $viewRoute = $req->query->get('destination');
        // redirect back to the view page once the node's been deleted
        return new RedirectResponse($viewRoute, 302);
    }

    public function rejectApp(Request $req, NodeInterface $nid = null){
        $viewRoute = $req->query->get('destination');
        // redirect back to the view page once the node's been deleted
        return new RedirectResponse($viewRoute, 302);
    }

}