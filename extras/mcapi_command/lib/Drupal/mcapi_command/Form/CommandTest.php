<?php

namespace Drupal\mcapi_command\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommandTest extends ConfigFormBase {

	public function title() {
		print_r(func_get_args());
	}
	/**
	 * {@inheritdoc}
	 */
	public function getFormID() {
		return 'mcapi_operation_settings_form';
	}

	public function buildform(array $form, array &$form_state) {
		$form['command'] = array(
	    '#title' => t('Command line interface'),
	    '#type' => 'fieldset',
	    '#collapsible' => TRUE,
	  );
	  $form['command']['sender'] = array(
	    '#title' => t('Sender'),
	    '#description' => t('Any user of currency @currname', array('@currname' => variable_get('mcapi_commands_currcode', 'credunit'))),
	    '#type' => 'user_chooser_few',
	    '#callback' => 'user_chooser_segment_perms',//because I haven't built a per-currency callback yet
	    '#args' => array('transact'),
	    '#required' => FALSE,
	    '#default_value' => \Drupal::currentUser()->id()
	  );
	  $form['command']['input'] = array(
	    '#title' => t('Command'),
	    '#description' => t('Test your commands here, based on the configured expressions. Max 160 characters.') .'<br />'. t('E.g. pay admin 14 for cleaning'),
	    '#type' => 'textfield',
	    '#maxlength' => 160,
	    '#required' => TRUE
	  );
	  $form['command']['submit'] = array(
	    '#type' => 'submit',
	    '#value' => t('Send'),
	    '#weight' => 1
	  );
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, array &$form_state) {
		parent::submitForm($form, $form_state);
		try {
			drupal_set_message('TESTING ONLY: '. $form_state['values']['input'], 'status', FALSE);
			$response = process_mcapi_command(
				$form_state['values']['input'],
				user_load($form_state['values']['sender']),
				FALSE
			);
		}
		catch (Exception $e) {
			drupal_set_message($e->getMessage(), 'warning');
			return;
		}
		drupal_set_message($response);

	}
}
