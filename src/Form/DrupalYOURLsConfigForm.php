<?php
namespace Drupal\drupal_yourls\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DrupalYOURLsConfigForm extends ConfigFormBase {
    private $init_allowed_urls;
    function __construct(){
        $this->init_allowed_urls  = $this->config('drupal_yourls.settings')->get('yourls_allowed_domains');
    }
	public function getFormId(){
		return 'drupal_yourls_admin_settings';
	}

	protected function getEditableConfigNames(){
		return [ 'drupal_yourls.settings' ];
	}
    
	public function buildForm(array $form, FormStateInterface $form_state) {
		$config = $this->config('drupal_yourls.settings');
        $allowed_urls = $config->get('yourls_allowed_domains');
        
		
        $form['YOURLS_URL'] = [
            '#type' => 'textfield',
            '#title' => $this->t('YOURLs API Endpoint'),
            '#description' => $this->t('Enter the API endpoint for your YOURLs installation. This is most likely, https://sho.rt/yourls-api.php'),
            '#default_value' => $config->get('yourls_url'),
        ];
        $form['YOURLS_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('YOURLs API Signature'),
            '#description' => $this->t('Enter your secret token for your YOURLs install. This can be found on the tools page from the Admin menu.'),
            '#default_value' => $config->get('yourls_secret'),
        ];
        // Gather the number of names in the form already.
        $num_names = $form_state->get('num_names');
        // We have to ensure that there is at least one name field.
        if ($num_names === NULL) {
          $num_fields = (count($allowed_urls) > 0) ? count($allowed_urls) : 1;
          $name_field = $form_state->set('num_names', $num_fields);
          $num_names = $num_fields;
        }
        $form['#tree'] = TRUE;
        $form['allowed_domains_fieldset'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Allowed Domains'),
          '#description' => $this->t('This is a list of allowed domains where users can generate random URLs from. Only include the domain name!'),
          '#prefix' => '<div id="domains-fieldset-wrapper">',
          '#suffix' => '</div>',
        ];
        for ($i = 0; $i < $num_names ; $i++) {
          $default = ($this->init_allowed_urls)["url_{$i}"] ? ($this->init_allowed_urls)["url_{$i}"] : 'n/a' ;
          $form['allowed_domains_fieldset']['domain'][$i] = [
            '#type' => 'textfield',
            '#title' => $this->t('Domain'),
            '#description' => $this->t('Subdomains can be used too'),
            '#default_value' => $default
          ];
        }
        $form['allowed_domains_fieldset']['actions'] = [
          '#type' => 'actions',
        ];
        $form['allowed_domains_fieldset']['actions']['add_name'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add'),
          '#submit' => ['::addOne'],
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => 'domains-fieldset-wrapper',
          ],
        ];
        // If there is more than one name, add the remove button.
        if ($num_names > 1) {
          $form['allowed_domains_fieldset']['actions']['remove_name'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#submit' => ['::removeCallback'],
            '#ajax' => [
              'callback' => '::addmoreCallback',
              'wrapper' => 'domains-fieldset-wrapper',
            ],
          ];
        }
        $form['YOURLS_send_email'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Send confirmation emails to applicants'),
            '#default_value' => $config->get('yourls_send_email'),
        ];  
        return parent::buildForm($form, $form_state);
    }		 
    /**
    * Callback for both ajax-enabled buttons.
    * Selects and returns the fieldset with the names in it.
    */
    public function addmoreCallback(array &$form, FormStateInterface $form_state) {
        return $form['allowed_domains_fieldset'];
    }
    
    /**
    * Submit handler for the "add-one-more" button.
    * Increments the max counter and causes a rebuild.
    */
    public function addOne(array &$form, FormStateInterface $form_state) {
        $name_field = $form_state->get('num_names');
        $add_button = $name_field + 1;
        $form_state->set('num_names', $add_button);
        $form_state->setRebuild();
    }
    
    /**
    * Submit handler for the "remove one" button.
    * Decrements the max counter and causes a form rebuild.
    */
    public function removeCallback(array &$form, FormStateInterface $form_state) {
        $name_field = $form_state->get('num_names');
        if ($name_field > 1) {
          $remove_button = $name_field - 1;
          $form_state->set('num_names', $remove_button);
        }
        $form_state->setRebuild();
    }
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->configFactory->getEditable('drupal_yourls.settings');
		$config->set('yourls_url', $form_state->getValue('YOURLS_URL'))
		->set('yourls_secret', $form_state->getValue('YOURLS_secret'))
		->set('yourls_send_email', $form_state->getValue('YOURLS_send_email'))
		->clear('yourls_allowed_domains');
		$domains = $form_state->getValue(['allowed_domains_fieldset', 'domain']);
		for($i = 0; $i < count($domains); $i++){
            $config->set("yourls_allowed_domains.url_{$i}", $domains[$i]);
		}
		$config->save();
		parent::submitForm($form, $form_state);
	}
}