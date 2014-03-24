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

	function __construct(EntityManagerInterface $entity_manager, $form_name = NULL) {
	  parent::__construct($entity_manager);
	  //in alpha7 this prop is declared in Drupal\Core\Form\FormBuilder but never populated
	  $this->moduleHandler = \Drupal::moduleHandler();

		if (!$form_name) {
      $options = \Drupal::request()->attributes->get('_route_object')->getOptions();
    	//this is the only way I know how to get the args. Could it be more elegant?
      $form_name = $options['parameters']['editform_id'];
		}
		//Not sure when it is appropriate to use entity_load and when to use config
		//I guess it depends on whether you need all the methods
		//don't know which uses least resources.
		//$this->config = \Drupal::config('mcapi.1stparty.'.$form_id);
		$this->config = entity_load('1stparty_editform', $form_name);
    //makes $this->entity;
		$this->prepareTransaction();

	}

	/**
	 * Symfony routing callback
	 */
	public function title() {
	  return $this->config->title;
	}

  /**
   * Get the original transaction form and alter it according to
   * the 1stparty form saved in $this->config.
   */
  public function form(array $form, array &$form_state) {
    //@todo we need to be able to pass in an entity here from the context
    //and generate $this->entity from it before building the base transaction form.
    //have to wait and see how panels works in d8

    $form = parent::form($form, $form_state);
    $config = $this->config;

  	//sort out the payer and payee, for the secondparty and direction
  	//the #title and #description will get stripped later
    if ($config->get('direction.preset') == 'incoming') {
      $form['partner'] = $form['payer'];
      $form['mywallet'] = $form['payee'];
    }
    else {
      $form['partner'] = $form['payee'];
      $form['mywallet'] = $form['payer'];
    }
  	unset($form['payer'], $form['payee']);


  	$account = user_load(\Drupal::currentuser()->id());
  	//use this method because i still don't know how to iterate
  	//through the $account->field_exchanges entity_reference field.
  	foreach (mcapi_get_wallet_ids($account) as $wid) {
  	  $my_wallets[$wid] = entity_load('mcapi_wallet', $wid)->label();
  	}
  	//because you can't render a form element as #markup while it still carries a value, AFAIK
  	//we create another element mywallet_value which takes precedence in the validate function, below
  	if (\Drupal::config('mcapi.wallets')->get('entity_types.user:user') > 1) {//show a widget
  	  if (count($my_wallets) > 1) {
    	  $form['mywallet']['#type'] = 'select';
    	  $form['mywallet']['#options'] = $my_wallets;
  	  }
  	  else {
  	    $form['mywallet']['#type'] = 'markup';
  	    $form['mywallet']['#markup'] = current($my_wallets);
  	    $form['mywallet']['#default_value'] = key($my_wallets);
  	  }
  	}
  	if (count($my_wallets) < 2) {
  	  $form['mywallet_value'] = array(
  	  	'#type' => 'value',
  	    '#value' => key($my_wallets)
  	  );
  	  unset($form['mywallet']);
  	}
  	$form['partner']['#element_validate'] = array(
  	  array($this, 'firstparty_convert_direction'),
  	  'local_wallet_validate_id'
  	);

  	if ($config->partner['preset']) {
    	$form['partner']['#default_value'] = $config->partner['preset'];
  	}
  	$form['direction'] = array(
  		'#type' => $config->direction['widget'],
  		'#default_value' => $config->direction['preset'],
  		'#options' => array(
  		  'incoming' => $config->direction['incoming'],
  		  'outgoing' => $config->direction['outgoing'],
  	  ),
  	);

  	//handle the description
  	$form['description']['#placeholder'] = $config->description['placeholder'];

  	//TODO put this in the base transaction form,
  	//where the one checkbox can enable both payer and payee to be selected from any exchange
    if (strpos($config->experience['twig'], '{{ intertrade }}') && can_intertrade($account)) {
    	//this checkbox flips between partner_choosers
    	$form['intertrade'] = array(
    		'#title' => t('Intertrade'),
    	  '#description' => t('Trade with someone outside your exchange'),
    	  '#type' => 'checkbox',
    	  '#default_value' => 0,
    	);
    	//make a second partner widget and switch between them
    	$form['partner']['#states'] = array(
    	  'visible' => array(
          ':input[name="intertrade"]' => array('checked' => FALSE)
        )
    	);
    	$form['partner_all'] = $form['partner'];
    	$form['partner_all']['#local'] = FALSE;
    	$form['partner_all']['#states']['visible'][':input[name="intertrade"]']['checked'] = TRUE;
    }

  	//hide the state, type
  	$form['state']['#type'] = 'value';
  	//TODO get the first state of this workflow
  	$form['state']['#value'] = TRANSACTION_STATE_FINISHED;
  	$form['type']['#type'] = 'value';

  	//handle the field API
  	$form['#twig'] = $config->experience['twig'];

    //make hidden any fields that do not occur in the template
    $form['#twig_tokens'] = mcapi_1stparty_transaction_tokens();

    foreach ($form['#twig_tokens'] as $token) {
     	if (strpos($config->experience['twig'], $token) === FALSE) {
  	    $form[$token]['#type'] = 'value';
     	}
  	}
  	$form['#twig_tokens'][] = 'actions';
    $form['#theme'] = '1stpartyform';

/*
    $form['#attributes']['class'][] = 'contextual-region';
    //@todo contextual links
    //pretty hard because it is designed to work only with templated themes, not theme functions
    //instead we'll probably just put a link in the menu
    $form['#title_suffix']['links'] = array(
      '#type' => 'contextual_links',
      '#contextual_links' => array(
        '1stpartyform' => array(
          'route_parameters' => array('1stpartyform' => $config->id())
        )
      )
    );
    $form['contextual_links'] = array(
      '#type' => 'contextual_links_placeholder',
      '#id' => array('firstparty:1stpartyform='.$config->id().': ')
    );
    */

    $form['suffix'] = array(
      '#markup' => '<br />'.l('edit', 'admin/accounting/transactions/forms/'.$config->id),
    );
    return $form;
  }

  /*
   * element validation callback
   * convert the firstparty, 3rdparty and direction fields into payer and payee.
   */
  public function validate(array $form, array &$form_state) {
    $my_wallet_id = array_key_exists('mywallet_value', $form_state['values']) ?
      $form_state['values']['mywallet_value'] :
      $form_state['values']['mywallet'];
    $partner_wallet_id = $form_state['values']['intertrade'] ?
      $form_state['values']['partner_all'] :
      $form_state['values']['partner'];

    if ($form_state['values']['direction'] == 'outgoing') {
      //$element is only needed for the parents
      \Drupal::formBuilder()->setValue($form['payee'], $partner_wallet_id, $form_state);
      \Drupal::formBuilder()->setValue($form['payer'], $my_wallet_id, $form_state);
    }
    else {
      \Drupal::formBuilder()->setValue($form['payer'], $partner_wallet_id, $form_state);
      \Drupal::formBuilder()->setValue($form['payee'], $my_wallet_id, $form_state);
    }

    parent::validate($form, $form_state);
  }

  /**
   * Returns an array of supported actions for the current entity form.
   * //TODO Might be ok to delete this now
   */
  protected function actions(array $form, array &$form_state) {
  	$actions = parent::actions($form, $form_state);
		if ($this->config->experience['preview'] == 'ajax') {
			//this isn't working at all...
			$actions['save']['#attributes']['class'][] = 'use-ajax';
			$actions['save']['#attached']['library'][] = array('views_ui', 'drupal.ajax');
		}
		$actions['save']['#value'] = $this->config->experience['button'];

  	return $actions;
  }

  /**
   * work out the default values, if any
   */
  function prepareTransaction() {
    //the partner is either the owner of the current page, under certain circumstances
    //or is taken from the form preset.
    //or is yet to be determined.
    if (0) {//no notion of context has been introduced yet
      //infer the partner wallet from the the node ower or something like that
    }
    elseif($this->config->partner['preset']) {
      $partner = $this->config->partner['preset'];
    }
    else $partner = '';

    //prepare a transaction using the defaults here
    $vars = array('type' => $this->config->type);
    foreach (mcapi_1stparty_transaction_tokens() as $prop) {
      if (property_exists($this->config, $prop)) {
        if (is_array($this->config->$prop)) {
          if (array_key_exists('preset', $this->config->{$prop})) {
            if (!is_null($this->config->{$prop}['preset'])){
              $vars[$prop] = $this->config->{$prop}['preset'];
            }
          }
        }
      }
    }
    //now handle the payer and payee, based on partner and direction
    if ($this->config->direction['preset'] == 'incoming') {
      $vars['payee'] = \Drupal::currentUser()->id();
      $vars['payer'] = $partner;
    }
    elseif($this->config->direction['preset'] == 'outgoing') {
      $vars['payer'] = \Drupal::currentUser()->id();
      $vars['payee'] = $partner;
    }
    //at this point we might want to override some values based on input from the url
    //this means the form can be populated using fields shared with another entity.

    $this->entity = \Drupal::entityManager()->getStorageController('mcapi_transaction')->create($vars);
  }
}
