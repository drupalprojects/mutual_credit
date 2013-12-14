<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Undo
 *
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Undo operation
 *
 * @Operation(
 *   id = "undo",
 *   label = @Translation("Undo"),
 *   description = @Translation("Undo, according to global undo mode"),
 *   settings = {
 *     "weight" = "3",
 *     "sure" = "Are you sure you want to undo?"
 *   }
 * )
 */
class Undo extends OperationBase {


	public function execute(TransactionInterface $transaction, array $values) {
	  $mail_settings = $this->config->get('special');

		if ($operation['mail']) {
		  //here we are just sending one mail, in one language
		  global $language;
		  $to = implode(user_load($transaction->payer)->mail, user_load($transaction->payee)->mail);
		  $params['transaction'] = $transaction;
		  $params['config'] = $this->configFactory->get('mcapi.operation.'.$operation['op']);
		  drupal_mail('mcapi', 'operation', $to, $language->language, $params);
		}

		$transaction->undo();

		$message = t('The transaction is undone.') .' ';
		return array('#markup' => $message);
	}

  public function settingsForm(array &$form, ConfigFactory $config) {
    $conf = $config->get('special');
    $form['special'] = array(
    	'#type' => 'fieldset',
      '#title' => t('Mail the transactees'),
      '#description' => t('TODO: This should be replaced by rules.'),
      '#weight' => 0
    );
    $form['special']['send'] = array(
     	'#title' => t('Notify both transactees'),
      '#type' => 'checkbox',
     	'#default_value' => $conf['send'],
     	'#weight' =>  0
    );
  	$form['special']['subject'] = array(
 			'#title' => t('Mail subject'),
 			'#description' => '',
			'#type' => 'textfield',
 			'#default_value' => $conf['subject'],
 			'#weight' =>  1,
  	  '#states' => array(
  		  'visible' => array(
  	  	  ':input[name="special[send]"]' => array('checked' => TRUE)
  	    )
  	  )
  	);
  	$form['special']['body'] = array(
 			'#title' => t('Mail body'),
 			'#description' => '',
 			'#type' => 'textarea',
 			'#default_value' => $conf['body'],
 			'#weight' => 2,
  	  '#states' => array(
  		  'visible' => array(
  	  	  ':input[name="special[send]"]' => array('checked' => TRUE)
  	    )
  	  )
  	);
  	$form['special']['cc'] = array(
 			'#title' => t('Carbon copy to'),
 			'#description' => 'A valid email address',
 			'#type' => 'email',
 			'#default_value' => $conf['cc'],
 			'#weight' => 3,
  	  '#states' => array(
  		  'visible' => array(
  	  	  ':input[name="special[send]"]' => array('checked' => TRUE)
  	    )
  	  )
  	);
  	parent::settingsForm($form, $config);
  	return $form;
  }

}
