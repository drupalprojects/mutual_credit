<?php

/**
 * @file
 * Definition of Drupal\mcapi\McapiFormFormController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityFormController;

class McapiFormFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $mcapiform = $this->entity;
    $form['#tree'] = TRUE;
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title of the form'),
      '#default_value' => $mcapiform->title,
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
    	'#weight' => 0,
    );

    $form['id'] = array(
    	'#type' => 'machine_name',
    	'#default_value' => $mcapiform->id(),
    	'#machine_name' => array(
    		'exists' => 'mcapi_currency_load',
    		'source' => array('title'),
    	),
//    	'#maxlength' => 12,
    	'#disabled' => !$mcapiform->isNew(),
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
    	'#default_value' => $mcapiform->type,
    	'#weight' => 5,
    );

    //following section of the form allows the admin to handle the individual fields of the transaction form.
    //the fields are handled here one in each tab, each field having some shared settings and some specific ones.
    $form['steps'] = array(
    	'#title' => t('Payment experience'),
    	'#type' => 'vertical_tabs',
    	'#weight' => 6,
    );
    $params = explode(':', $mcapiform->partner['user_chooser_config']);
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
    		'#default_value' => $mcapiform->partner['user_chooser_config'],
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
    		'#default_value' => $mcapiform->partner['preset'],
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
  			'#type' => $mcapiform->direction['widget'],
  			'#options' => array(
  				'none' => t('Neither'),
  				'incoming' => empty($mcapiform->direction['incoming']) ? t('Incoming') : $mcapiform->direction['incoming'],
  				'outgoing' => empty($mcapiform->direction['outgoing']) ? t('Outgoing') : $mcapiform->direction['outgoing'],
  			),
  			'#default_value' => $mcapiform->direction['preset'],
  			'#required' => TRUE
    	),
	    'widget' => array(
	      '#title' => t('Widget'),
	      '#type' => 'radios',
	      '#options' => array(
	        'select' => t('Dropdown select box'),
	        'radios' => t('Radio buttons')
	      ),
	      '#default_value' => $mcapiform->direction['widget'],
	      '#weight' => 1,
    		'#required' => TRUE
	    ),
	    'incoming' => array(
	      '#title' => t("@label option label", array('@label' => t('Incoming'))),
	      '#type' => 'textfield',
	      '#default_value' => $mcapiform->direction['incoming'],
	    	'#placeholder' => t('Pay'),
	      '#weight' => 2
	    ),
	    'outgoing' => array(
	      '#title' => t("@label option label",  array('@label' => t('Outgoing'))),
	      '#type' => 'textfield',
	      '#default_value' => $mcapiform->direction['outgoing'],
	    	'#placeholder' => t('Request'),
	      '#weight' => 3
	    ),
    	'#weight' => 3
  	);
    /*
    $form['worths']= array(
   		'#title' => t('@fieldname preset', array('@fieldname' => t('Worths'))),
   		'#type' => 'details',
   		'#group' => 'steps',
   		'preset' => array(
   			'#title' => t('Preset field to'),
   			'#description' => t('Preset'),
   			'#type' => 'worths',
   			'#default_value' => $this->worths['preset'],
   		),
    	'#weight' => 4
    );
    if (count($currencies) > 1) {
    	$form['worths']['#description'] = implode(' ', array(
    		t('Put a number or zero to include a currency as an option on the form.'),
    		t('Leave blank to exclude the currency.'),
    	));
    }*/
    $form['description']= array(
    	'#title' => t('@fieldname preset', array('@fieldname' => t('Description'))),
    	'#description' => t('Direction relative to the current user'),
    	'#type' => 'details',
    	'#group' => 'steps',
    	'preset' => array(
    		'#title' => t('Preset field to'),
    		'#type' => 'textfield',
	      '#default_value' => $mcapiform->description['preset'],
	      '#required' => FALSE,
      ),
    	'#weight' => 5
    );
    //TODO this might only work if the calendar widget is available
    $form['created']= array(
    	'#title' => t('Date field', array('@fieldname' => t('Date'))),
    	'#description' => t('Backdating transactions'),
    	'#type' => 'details',
    	'#group' => 'steps',
    	'show' => array(
    		'#title' => t('Show date widget'),
    		'#type' => 'checkbox',
	      '#default_value' => $mcapiform->created['show'],
	      '#required' => FALSE,
      ),
    	'#weight' => 5
    );
    //TODO workout what the tokens are and write them in template1['#description']
    $form['step1'] = array(
    	'#title' => t('Step 1'),
    	'#type' => 'details',
    	'#group' => 'steps',
    	'template1' => array(
    		'#title' => t('Main form'),
    		'#description' => t('Use the tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically.'),
    		'#type' => 'textarea',
    		'#rows' => 6,
    		'#default_value' => $mcapiform->step1['template1'],
    		'#weight' => 1,
    		'#required' => TRUE
    	),
    	'button1' => array(
    		'#title' => t('Button label'),
    		'#description' => t("The text to appear on the 'save' button, or the absolute url of an image."),
    		'#type' => 'textfield',
    		'#default_value' => $mcapiform->step1['button1'],
    		'#required' => TRUE,
    		'#weight' => 2,
    	),
    	'next1' => array(
    		'#title' => t('Submission'),
    		'#type' => 'radios',
    		'#options' => array(
    		  'ajax' => t('replace just the form'),
    			'page' => t('replace whole page')
    	  ),
    		'#default_value' => $mcapiform->step1['next1'],
    		'#weight' => 3,
    		'#required' => TRUE
    	),
    	'#weight' => 20
    );
    //get the display modes
    $modes = array('certificate');
    $form['step2'] = array(
    	'#title' => t('Step 2'),
    	'#type' => 'details',
    	'#group' => 'steps',
   		'format2' => array(
 				'#title' => t('Display format'),
 				'#type' => 'radios',
 				'#options' => $modes + array('custom' => t('Custom')),
 				'#default_value' => $mcapiform->step2['format2'],
 				'#weight' => 0,
   		),
    	'template2' => array(
    		'#title' => t('Main form'),
    		'#description' => t('Use the tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically.'),
    		'#type' => 'textarea',
    		'#rows' => 6,
    		'#default_value' => $mcapiform->step2['template2'],
        '#states' => array(
    			'visible' => array(
    				':input[name="format2"]' => array('value' => 'custom'),
    			)
    		),
    		'#weight' => 1,
    	),
    	'button2' => array(
    		'#title' => t('Button label'),
    		'#description' => t("The text to appear on the 'save' button, or the absolute url of an image."),
    		'#type' => 'textfield',
    		'#default_value' => $mcapiform->step2['button2'],
    		'#required' => TRUE,
    		'#weight' => 2,
    	),
    	'next2' => array(
    		'#title' => t('Outcome'),
    		'#type' => 'radios',
    		'#options' => array(
    		  'modal' => t('transaction in modal window'),
    			'ajax' => t('transaction in place'),
    			'page' => t('redirect to path'),
    	  ),
    		'#default_value' => $mcapiform->step2['next2'],
    		'#required' => TRUE,
    		'#weight' => 3,
    	),
    	'redirect' => array(
    		'#title' => t('Redirect path'),
    		'#type' => 'textfield',
    		'#default_value' => $mcapiform->redirect['format'],
    		'#weight' => 5,
    		'#placeholder' => 'transaction/%serial',
    		'#states' => array(
    			'visible' => array(
    				':input[name="next2"]' => array('value' => 'page'),
    			)
    		)
    	),
   	  '#weight' => 21
    );

    $form['message'] = array(
    	'#title' => t('Confirmation message'),
    	'#description' => t('Contents of the message box.'),
    	'#type' => 'textfield',
    	'#default_value' => $mcapiform->message,
    	'#weight' => 6,
    	'#states' => array(
    		'visible' => array(
    			':input[name="next2"]' => array('value' => 'page'),
    		)
    	)
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
  	$mcapiform = $this->entity;
    $status = $mcapiform->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t("Form '%label' has been updated.", array('%label' => $mcapiform->label())));
    }
    else {
      drupal_set_message(t("Form '%label' has been added.", array('%label' => $mcapiform->label())));
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


