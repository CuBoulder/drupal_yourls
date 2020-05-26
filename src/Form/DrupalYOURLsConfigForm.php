<?php
namespace Drupal\drupal_yourls\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DrupalYOURLsConfigForm extends ConfigFormBase {

		public function getFormId(){
			return 'drupal_yourls_admin_settings';
		}

		protected function getEditableConfigNames(){
			return [ 'drupal_yourls.settings' ];
		}

		public function buildForm(array $form, FormStateInterface $form_state) {
			$config = $this->config('drupal_yourls.settings');
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
				return parent::buildForm($form, $form_state);
		 }


		 public function submitForm(array &$form, FormStateInterface $form_state) {
			 // Retrieve the configuration
		 	$this->configFactory->getEditable('drupal_yourls.settings')
			// Set the submitted configuration setting
			->set('yourls_url', $form_state->getValue('YOURLS_URL'))
			->set('yourls_secret', $form_state->getValue('YOURLS_secret'))
			->save();

			parent::submitForm($form, $form_state);
		}


}