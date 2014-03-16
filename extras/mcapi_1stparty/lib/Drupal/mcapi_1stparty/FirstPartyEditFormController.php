<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\FirstPartyEditFormController.
 * This configuration entity is used for generating transaction forms.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityManager;
use Drupal\field\Field;

class FirstPartyEditFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $configEntity = $this->entity;
    $form['#tree'] = TRUE;
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title of the form'),
      '#default_value' => $configEntity->title,
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
    	'#weight' => 0,
    );


    $form['id'] = array(
    	'#type' => 'machine_name',
    	'#default_value' => $configEntity->id(),
    	'#machine_name' => array(
    		'exists' => 'mcapi_editform_load',
    		'source' => array('title'),
    	),
    	'#maxlength' => 12,
    	'#disabled' => !$configEntity->isNew(),
    );

    $exchange = $configEntity->exchange ?
    entity_load('mcapi_exchange', $configEntity->exchange) :
    NULL;
    /* this could be enabled, but then the rest of the form would have to be ajax-reloaded */
    foreach (entity_load_multiple('mcapi_exchange') as $id => $entity) {
      $options[$id] = $entity->label();
    }
    $form['exchange'] = array(
      '#title' => t('Restricted to exchange:'),
      '#type' => 'select',
      '#empty_option' => t('- All -'),
      '#empty_value' => '',
      '#options' => $options,
      '#default_value' => $exchange ? $exchange->id() : '',
      '#disabled' => TRUE,
      '#weight' => 1,
    );
    $form['path'] = array(
    	'#title' => t('Path'),
      '#description' => t('The url path at which this form will appear. Must be unique. E.g. myexchange/payme'),
      '#type' => 'textfield',
      '#weight' => 3,
      '#element_validate' => array(array($this, 'unique_path')),
      '#default_value' => $configEntity->path,
      '#required' => TRUE
    );

    $form['type'] =  array(
      '#title' => t('Transaction type'),
    	'#type' => 'mcapi_types',
    	'#default_value' => $configEntity->type,
    	'#weight' => 5,
    );

    //following section of the form allows the admin to handle the individual fields of the transaction form.
    //the fields are handled here one in each tab, each field having some shared settings and some specific ones.
    $form['steps'] = array(
    	'#title' => t('Payment experience'),
    	'#type' => 'vertical_tabs',
    	'#weight' => 6,
    );

    //get all the wallets, determined by context
    $form['partner'] = array(
    	'#title' => t('@fieldname preset', array('@fieldname' => t('Partner'))),
    	'#descriptions' => t('In complex sites, it may be possible to choose a user who cannot use the currency'),
    	'#type' => 'details',
    	'#group' => 'steps',
   		'#weight' => 1
    );
    //if this form is being edited by a member of the exchange, we can use the local_wallets autocomplete widget
    //otherwise the preset is just a wallet id.
    $user = user_load(\Drupal::currentUser()->id());
      $form['partner']['preset'] = array('#markup' => 'Partner preset not available yet');
      //@todo when entity reference widget is behaving
      /*
    if ($exchange && $exchange->member($user)) {
    	$form['partner']['preset'] = array(
    		'#title' => t('Preset field to'),
        '#type' => 'local_wallets',
    		'#default_value' => $configEntity->partner['preset'],
    		'#multiple' => FALSE,
    		'#required' => FALSE
    	);
    }
    elseif ($exchange) {
      $form['partner']['preset'] = array(
      	'#title' => t('Wallet number'),
      	'#description' => t("Wallet owner must be, or be in, exchange '!name'.", array('!name' => $exchange->label())),
        '#type' => 'number',
        '#min' => 1,
    		'#default_value' => $configEntity->partner['preset'],
      );
    }
    else {
      $form['partner']['preset'] = array(
      	'#markup' => t('This field can only be preset in forms with a designated exchange.')
      );
    }

    	*/

    $form['direction']= array(
    	'#title' => t('@fieldname preset', array('@fieldname' => t('Direction'))),
    	'#description' => t('Direction relative to the current user'),
      '#type' => 'details',
      '#group' => 'steps',
    	'preset' => array(

      	'#title' => t('Preset field to '),
				'#description' => t("Either 'incoming' or 'outgoing' relative to the logged in user"),
  			'#type' => $configEntity->direction['widget'],
  			'#options' => array(
  				'none' => t('Neither'),
  				'incoming' => empty($configEntity->direction['incoming']) ? t('Incoming') : $configEntity->direction['incoming'],
  				'outgoing' => empty($configEntity->direction['outgoing']) ? t('Outgoing') : $configEntity->direction['outgoing'],
  			),
  			'#default_value' => $configEntity->direction['preset'],
  			'#required' => TRUE
    	),
	    'widget' => array(
	      '#title' => t('Widget'),
	      '#type' => 'radios',
	      '#options' => array(
	        'select' => t('Dropdown select box'),
	        'radios' => t('Radio buttons')
	      ),
	      '#default_value' => $configEntity->direction['widget'],
    		'#required' => TRUE,
	      '#weight' => 1,
	    ),
	    'incoming' => array(
	      '#title' => t("@label option label", array('@label' => t('Incoming'))),
	      '#type' => 'textfield',
	      '#default_value' => $configEntity->direction['incoming'],
	    	'#placeholder' => t('Pay'),
    		'#required' => TRUE,
	      '#weight' => 2
	    ),
	    'outgoing' => array(
	      '#title' => t("@label option label",  array('@label' => t('Outgoing'))),
	      '#type' => 'textfield',
	      '#default_value' => $configEntity->direction['outgoing'],
	    	'#placeholder' => t('Request'),
    		'#required' => TRUE,
	      '#weight' => 3
	    ),
    	'#weight' => 3
  	);
    $form['worths'] = array(
      '#title' => t('@fieldname preset', array('@fieldname' => t('Worths'))),
      '#type' => 'details',
      '#group' => 'steps',
      '#weight' => 4
    );
    /*
     * This won't work until I know how to prepopulate a worths field using form API
    //worths can be preset for any or all of the currencies available in this exchange
    //@todo = get the currencies elegantly out of entity_reference $exchange->field_currencies
    if ($exchange) {
      $currcodes = db_select('mcapi_exchange__field_currencies', 'c')
        ->fields('c', array('field_currencies_target_id'))
        ->condition('entity_id', $exchange->id())
        ->execute()->fetchCol();
      $form['worths']['preset'] = array(
   			'#title' => t('Preset field to'),
   			'#description' => t('Choose one currency and optionally a value'),
   			'#type' => 'worths',
   		  '#currencies' => $currcodes,
   			'#default_value' => $configEntity->worths['preset'],
     );
    }
    else {
      $form['worths']['preset'] = array(
        '#markup' => t('This field can only be preset in forms with a designated exchange.')
      );
    }
    */
    $form['description']= array(
    	'#title' => t('@fieldname preset', array('@fieldname' => t('Description'))),
    	'#description' => t('Direction relative to the current user'),
    	'#type' => 'details',
    	'#group' => 'steps',
    	'preset' => array(
    		'#title' => t('Preset field to'),
    		'#type' => 'textfield',
	      '#default_value' => $configEntity->description['preset'],
	      '#required' => FALSE,
      ),
    	'placeholder' => array(
    		'#title' => t('Placeholder text'),
    		'#type' => 'textfield',
	      '#default_value' => $configEntity->description['placeholder'],
	      '#required' => FALSE,
    		'#attributes' => array('style' => 'color:#999')
      ),
    	'#weight' => 5
    );

    //@todo GORDON allow the defaults to be set for each field API instance

    //this gets all the fields for this entity, not sure if it is relevant
    $form_display = entity_get_form_display('mcapi_transaction', 'mcapi_transaction', 'default');
    //the default_value for each widget is $configEntity->{$fieldname}['preset'];
    /*
    foreach(Field::FieldInfo()->getInstances('mcapi_transaction') as $instances) {
      foreach($instances as $fieldname => $instance) {//I'm not sure what happened to bundles
        foreach ($configEntity->{$fieldname}['preset'] as $val) {
          $instance[] = $val;
        }
        $element = array();
        $delta = 0;//@todo iterate through these
        $items = (array)$configEntity->{$fieldname}['preset'];
        //this should produce the right widget for defaults, if only we knew how to populate it
        $form_display->getRenderer($instance->getName())->formElement(
          $instance->getValue(),
          $delta,
          $element,
          $form,
          $form_state
        );
      }
    }*/

    $form['fieldapi_1']= array(
      '#title' => 'FieldAPI 1 preset',
      '#type' => 'details',
      '#group' => 'steps',
      'preset' => array(
        'container' => array(
          '#markup' => 'There should be one configurable field for each FieldAPI field on the transaction. This is tricky'
        )
      ),
      /*
       *
      'preset' => array(
        '#title' => t('Preset field to'),
        '#description' => t('Choose one currency and optionally a value'),
        '#type' => 'worths',
        '#default_value' => $configEntity->worths['preset'],
       ),
       */
      '#weight' => 7
    );

/*
    $form['other']= array(
      '#title' => 'Other fields',
      '#type' => 'details',
      '#group' => 'steps',
      '#weight' => 10,
    );
*/

    $help = l(t('What is twig?'), 'http://twig.sensiolabs.org/doc/templates.html', array('external' => TRUE));
    //TODO workout what the tokens are and write them in template1['#description']
    $form['experience'] = array(
    	'#title' => t('User experience'),
    	'#type' => 'details',
    	'twig' => array(
    		'#title' => t('Main form'),
    		'#description' => t(
    		  'Use the following twig tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically. @tokens',
    		  array('@tokens' => '{{ '.implode(' }}, {{ ',  mcapi_1stparty_transaction_tokens()) .' }}')
    	  ) .' '. $help,
    		'#type' => 'textarea',
    		'#rows' => 6,
    		'#default_value' => $configEntity->experience['twig'],
    		'#weight' => 1,
    		'#required' => TRUE
    	),
    	'button' => array(
    		'#title' => t('Button label'),
    		'#description' => t("The text to appear on the 'save' button, or the absolute url of an image."),
    		'#type' => 'textfield',
    		'#default_value' => $configEntity->experience['button'],
    		'#required' => TRUE,
    		'#weight' => 2,
    	),
    	'preview' => array(
    		'#title' => t('Preview mode'),
    		'#type' => 'radios',
    		'#options' => array(
    			'page' => t('Basic - Go to a fresh page'),
    		  'ajax' => t('Ajax - Replace the transaction form'),
    			'modal' => t('Modal - Confirm in a dialogue box')
    	  ),
    		'#default_value' => $configEntity->experience['preview'],
    		'#weight' => 3,
    		'#required' => TRUE
    	),
    	'#weight' => 20,
    );
    $form['#suffix'] = t(
  	  "The user will then proceed to the 'create' operation page to confirm the transaction, which is configured at !link",
      array('!link' => l('admin/accounting/transactions/workflow/create', 'admin/accounting/transactions/workflow/create'))
    );
    return $form;
  }

  public function multistepSubmit($form, &$form_state) {
    $trigger = $form_state['triggering_element'];
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        $form_state['widget_settings_edit'] = TRUE;
        break;

      case 'update':
        $form_state['widget_settings'][$form_state['input']['widget']] = $form_state['values']['widget_settings'];
        $form_state['widget_settings_edit'] = NULL;
        break;

      case 'cancel':
        $form_state['widget_settings_edit'] = NULL;
        break;

      case 'refresh_display':
        break;
    }

    $form_state['rebuild'] = TRUE;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    //not sure what to do here
    parent::submit($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
  	$configEntity = $this->entity;
    $status = $configEntity->save();
    //rebuild the menu
    //@todo, this is only necessary if the path has changed
    \Drupal::service('router.builder')->rebuild();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t("Form '%label' has been updated.", array('%label' => $configEntity->label())));
    }
    else {
      drupal_set_message(t("Form '%label' has been added.", array('%label' => $configEntity->label())));
    }

    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_1stparty_editform_list'
    );
  }


  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   * Borrowed from NodeFormController
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $query = \Drupal::request()->query;
    if ($query->has('destination')) {
      $destination = drupal_get_destination();
      $query->remove('destination');
    }
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin.1stparty_editform.delete_confirm',
      'route_parameters' => array(
          '1stparty_editform' => $this->entity->id(),
      ),
      'options' => array(
          'query' => $destination,
      ),
    );
  }

  public function unique_path(&$element, &$form_state) {
    $dupe = db_select('router', 'r')
      ->fields('r', array('name'))
      ->condition('name', 'mcapi.1stparty.'.$this->entity->id(), '<>')
      ->condition('path', $form_state['values']['path'])
      ->execute()->fetchField();
    if ($dupe) \Drupal::formBuilder()->setError('path', $form_state, t('Path is already used.'));
  }
}