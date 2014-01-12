<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\FirstPartyEditFormController.
 * This configuration entity is used for generating transaction forms.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityManager;

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
    		'exists' => 'mcapi_currency_load',
    		'source' => array('title'),
    	),
    	'#maxlength' => 12,
    	'#disabled' => !$configEntity->isNew(),
    );

    $form['access'] = array(
    	'#title' => t('Access control'),
      '#description' => t("In addition to currency access control, and block access control, access to this form can be restricted."),
    	'#type' => 'select',
    	'#default_value' => isset($settings['architecture']['access']) ? $settings['architecture']['access'] : 'user_chooser_segment_perms:transact',
    	'#options' => module_invoke_all('uchoo_segments'),
    	'#element_validate' => array(),
    	'#weight' => 3
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
    $params = explode(':', $configEntity->partner['user_chooser_config']);
    if (!(array_filter($params))) $params = array('user_chooser_segment_perms', 'transact');

    $form['partner'] = array(
    	'#title' => t('@fieldname preset', array('@fieldname' => t('Partner'))),
    	'#descriptions' => t('In complex sites, it may be possible to choose a user who cannot use the currency'),
    	'#type' => 'details',
    	'#group' => 'steps',
    	'user_chooser_config' => array(
    		'#title' => t('Users to choose from'),
    		'#type' => 'select',
    		'#options' => module_invoke_all('uchoo_segments'),
    		'#default_value' => $configEntity->partner['user_chooser_config'],
    		'#required' => TRUE,
      ),
    	//this really needs to be ajax...
    	'preset' => array(
    		'#title' => t('Preset field to'),
    		'#description' => t('Submit the form to respond to changes above.') .' '. t('Configure this widget more at !link',
    			array('!link' => l('admin/config/people/user_chooser', 'admin/config/people/user_chooser', array('attributes'=>array('target'=> '_blank')))
    		)),
    		'#type' => 'user_chooser_few',
    		'#callback' => array_shift($params),
    		'#args' => $params,
    		'#default_value' => $configEntity->partner['preset'],
    		'#multiple' => FALSE,
    		'#required' => FALSE
    	),
   		'#weight' => 1
    );
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
    $form['worths']= array(
   		'#title' => t('@fieldname preset', array('@fieldname' => t('Worths'))),
   		'#type' => 'details',
   		'#group' => 'steps',
   		'preset' => array(
   			'#title' => t('Preset field to'),
   			'#type' => 'worths',
   			'#default_value' => $configEntity->worths['preset'],
   		),
    	'#weight' => 4
    );
    if (count($currencies) > 1) {
    	$form['worths']['#description'] = implode(' ', array(
    		t('Put a number or zero to include a currency as an option on the form.'),
    		t('Leave blank to exclude the currency.'),
    	));
    }
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

    $fields = $this->moduleHandler->invokeAll('entity_field_info', array('mcapi_transaction'));
    foreach($fields['definitions'] as $def) {
      //get the form widget
      //print_r($def);
    }
    echo("TODO add the extra fields when field_attach_form is deprecated in EntityFormController::init");//dsm doesnt work here

    module_load_include ('tokens.inc', 'mcapi');
    $tokens = mcapi_transaction_list_tokens (FALSE);
    //remove payer and payee and replace with partner and direction
    $tokens[array_search('payer', $tokens)] = 'partner';
    $tokens[array_search('payee', $tokens)] = 'direction';
    $help = l(t('What is twig?'), 'http://twig.sensiolabs.org/doc/templates.html', array('external' => TRUE));

    //TODO workout what the tokens are and write them in template1['#description']
    $form['experience'] = array(
    	'#title' => t('User experience'),
    	'#type' => 'details',
    	'#group' => 'steps',
    	'twig' => array(
    		'#title' => t('Main form'),
    		'#description' => t(
    		  'Use the following twig tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically. @tokens',
    		  array('@tokens' => implode(', ', $tokens))
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
    		  'ajax' => t('replace just the form'),
    			'page' => t('replace whole page')
    	  ),
    		'#default_value' => $configEntity->experience['preview'],
    		'#weight' => 3,
    		'#required' => TRUE
    	),
    	'#weight' => 20
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

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t("Form '%label' has been updated.", array('%label' => $configEntity->label())));
    }
    else {
      drupal_set_message(t("Form '%label' has been added.", array('%label' => $configEntity->label())));
    }

    $form_state['redirect'] = 'admin/accounting/workflow/forms';
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/accounting/workflow/forms/' . $this->entity->id() . '/delete';
  }

}

