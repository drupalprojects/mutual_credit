<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\FirstPartyTransactionForm.
 * Generate a Transaction form using the FirstParty_editform entity.
 */

namespace Drupal\firstparty_forms\Form;

use Drupal\mcapi\Form\TransactionForm;


class FirstPartyTransactionForm extends TransactionForm {

	var $config;

	function __construct() {
		//TODO
		//this is the only way I know how to get the args. Could it be more elegant?
		$form_id = \Drupal::request()->attributes->get('_raw_variables')->get('form_id');
		$this->config = \Drupal::config('mcapi.1stparty.'.$form_id)->get();
	}

  /**
   * Get the original transaction form and alter it according whichever
   * 1stparty form has been summoned.
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    if (empty($form_state['mcapi_submitted'])) {
    	$this->form_step_1_alter($form, $form_state);
    }
    else {
    	$this->form_step_2_alter($form, $form_state);
    }
    $form['#theme'] = '1stpartyform';
    return $form;
  }

  //$config->get('id');

  private function form_step_1_alter(&$form, &$form_state) {

  	drupal_set_title($this->config['title']);
  	//sort out the payer and payee, for the secondparty and direction
  	//the #title and #description will get stripped later
  	$form['partner'] = $form['payer'];
  	unset($form['payer'], $form['payee']);
    //this bit could be abstracted somewhere, it is also done in FirstPartyEditFormController
  	$params = explode(':', $this->config['partner']['user_chooser_config']);
  	$form['partner']['#callback'] = array_shift($params);
  	$form['partner']['#args'] = $params;

  	$form['direction'] = array(
  		'#type' => $this->config['direction']['widget'],
  		'#default_value' => $this->config['direction']['preset'],
  		'#options' => array(
  		  'incoming' => $this->config['direction']['incoming'],
  		  'outgoing' => $this->config['direction']['outgoing'],
  	  )
  	);

  	//handle the worths field
  	unset($form['worths']); //because it is wrong in the TransactionForm.php

  	//handle the description
  	$form['description']['#default_value'] = $this->config['description']['preset'];
  	$form['description']['#placeholder'] = $this->config['description']['placeholder'];

  	//hide the state, type
  	$form['state']['#type'] = 'value';
  	//TODO get the first state of this workflow
  	$form['state']['#value'] = TRANSACTION_STATE_FINISHED;
  	$form['type']['#type'] = 'value';

  	//handle the field API

  	$form['#twig'] = $this->config['step1']['twig1'];
  	//$form['#twig'] = $this->config['step1']['template1'];

    //make hidden any fields that do not occur in the template
    //this should probably happen in validate phase
    $form['#twig_tokens'] = array('partner', 'direction', 'description', 'worths', 'actions');
    foreach ($form['#twig_tokens'] as $token) {
     	if (strpos($this->config['step1']['twig1'], $token) === FALSE) {
  	    $form[$token]['#type'] = 'value';
     	}
  	}
  }

  private function form_step_2_alter(&$form, &$form_state, $config) {

  }



  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, array &$form_state) {
  	$actions = parent::actions($form, $form_state);

  	if (empty($form_state['mcapi_submitted'])) {//step 1
  		if ($this->config['step1']['next1'] == 'ajax') {
  			//this isn't working at all...
  			$actions['submit']['#attributes']['class'][] = 'use-ajax';
  			$actions['submit']['#attached']['library'][] = array('views_ui', 'drupal.ajax');
  		}
  		$actions['submit']['#value'] = $this->config['step1']['button1'];
  	}
  	else {//setp 2
  		$actions['submit']['#value'] = $this->config['step2']['button2'];
  	}
  	return $actions;
  }


}


