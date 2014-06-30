<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Form\Settings
 */

namespace Drupal\mcapi_signatures\Form;

use Drupal\Core\Form\ConfigFormBase;
use \Drupal\Core\Config\ConfigFactoryInterface;

class Settings extends ConfigFormBase {


  private $settings;

  function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->settings = $config_factory->get('mcapi.signatures');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_signatures_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    //TODO This would look really tidy in a grid - but forms in tables are tricky
    //this prefix could go in hook_help, but is quite important to understand in more complex setups
    $form['#prefix'] = implode(' ', array(
      t('Each transaction type saves new transaction in an initial workflow state.'),
      t("This form overrides that initial state and puts new transactions into pending state while signatures are required."),
      t("When the last signature is gathered the transaction moves automatically into 'finished' state.")
    ));
    foreach (entity_load_multiple('mcapi_type') as $type) {
      $form[$type->id()] = array(
    	  '#title' => t('Who must approve transactions of type @name?', array('@name' => $type->label)),
        '#description' => $type->description,
        '#type' => 'checkboxes',
        '#options' => array(
      	  'both' => t('Payer & payee (The current user signs automatically)'),
          'exman' => t('The exchange manager')
        ),
        //checkboxes are a bit strange.
        //if we don't array filter, every array key will be read as a checked box
        '#value' => array_filter((array)$this->settings->get($type->id())),
      );
    }
    $form['signatures']['both']['#disabled'] = TRUE;
    $form['signatures']['both']['#value'] = TRUE;
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    form_state_values_clean($form_state);
    foreach ($form_state['values'] as $type_name => $vals) {
      //go to some extra effort to save booleans in the config
      $this->settings->set($type_name.'.both', !empty($vals['both']));
      $this->settings->set($type_name.'.exman', !empty($vals['exman']));
    }
    $this->settings->save();

    parent::submitForm($form, $form_state);

    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin.transactions'
    );
  }
}

