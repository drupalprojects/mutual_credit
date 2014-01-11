<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\Form\FirstPartyTransactionForm.
 * Generate a Transaction form using the FirstParty_editform entity.
 */

namespace Drupal\mcapi_1stparty\Form;

use Drupal\mcapi\Form\TransactionForm;


class FirstPartyTransactionForm extends TransactionForm {

	var $config;//the settings as a configuration object

	function __construct() {//no args passed
		//TODO
		//this is the only way I know how to get the args. Could it be more elegant?
		$form_id = \Drupal::request()->attributes->get('_raw_variables')->get('1stparty_editform');
//		$this->config = \Drupal::config('mcapi.1stparty.'.$form_id);
    //we could load the entity, but its less demanding to load it as a config
		$this->config = \Drupal::entityManager()->getStorageController('1stparty_editform')->load($form_id);
	}

  /**
   * Get the original transaction form and alter it according to
   * the 1stparty form saved in $this->config.
   */
  public function form(array $form, array &$form_state) {
    //neet to get the variable from the param converter, which for some reason wasn't put into $form->entity
    global $temp_mcapi_transaction;
    $this->entity = $temp_mcapi_transaction;
    $configuration = $this->config;

    $form = parent::form($form, $form_state);

  	drupal_set_title($this->config->get('title'));

  	//sort out the payer and payee, for the secondparty and direction
  	//the #title and #description will get stripped later
  	$form['partner'] = $form['payer'];
  	unset($form['payer'], $form['payee']);
  	$partner = $this->config->get('partner');
    //this bit could be abstracted somewhere, it is also done in FirstPartyEditFormController
  	$params = explode(':', $partner['user_chooser_config']);
  	$form['partner']['#callback'] = array_shift($params);
  	$form['partner']['#args'] = $params;
  	$form['partner']['#exclude'] = array(\Drupal::currentUser()->id());

  	if ($partner['preset']) {
    	$form['partner']['#default_value'] = array(\Drupal::currentUser()->id());
  	}
  	$direction = $this->config->get('direction');
  	$form['direction'] = array(
  		'#type' => $direction['widget'],
  		'#default_value' => $direction['preset'],
  		'#options' => array(
  		  'incoming' => $direction['incoming'],
  		  'outgoing' => $direction['outgoing'],
  	  )
  	);

  	//handle the worths field
  	//unset($form['worths']); //because it is wrong in the TransactionForm.php

  	//handle the description
  	$description = $this->config->get('description');
  	$form['description']['#placeholder'] = $description['placeholder'];

  	//hide the state, type
  	$form['state']['#type'] = 'value';
  	//TODO get the first state of this workflow
  	$form['state']['#value'] = TRANSACTION_STATE_FINISHED;
  	$form['type']['#type'] = 'value';

  	//handle the field API
    $experience = $this->config->get('experience');
  	$form['#twig'] = $experience['twig'];

    //make hidden any fields that do not occur in the template
    $form['#twig_tokens'] = array('partner', 'direction', 'description', 'worths');
    foreach ($form['#twig_tokens'] as $token) {
     	if (strpos($experience['twig'], $token) === FALSE) {
  	    $form[$token]['#type'] = 'value';
     	}
  	}
  	$form['#twig_tokens'][] = 'actions';

    $form['#theme'] = '1stpartyform';
    return $form;
  }


  /**
   * Returns an array of supported actions for the current entity form.
   * //TODO Might be ok to delete this now
   */
  protected function actions(array $form, array &$form_state) {
  	$actions = parent::actions($form, $form_state);

  	$experience = $this->config->get('experience');
		if ($experience['preview'] == 'ajax') {
			//this isn't working at all...
			$actions['submit']['#attributes']['class'][] = 'use-ajax';
			$actions['submit']['#attached']['library'][] = array('views_ui', 'drupal.ajax');
		}
		$actions['save']['#value'] = $experience['button'];

  	return $actions;
  }

}


