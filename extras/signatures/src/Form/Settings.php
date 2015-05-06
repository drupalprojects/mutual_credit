<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Form\Settings
 */

namespace Drupal\mcapi_signatures\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Type;

class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_signatures_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //@todo This would look really tidy in a grid - but forms in tables are tricky
    //this prefix could go in hook_help, but is quite important to understand in more complex setups
    $form['#prefix'] = implode(' ', array(
      t('Each transaction type saves new transaction in an initial workflow state.'),
      t("This form overrides that initial state and puts new transactions into pending state while signatures are required."),
      t("When the last signature is gathered the transaction moves automatically into 'finished' state.")
    ));
    foreach (Type::loadMultiple() as $type) {
      $id = $type->id();
      $form[$id] = array(
        '#title' => t('Who must approve transactions of type @name?', array('@name' => $type->label)),
        '#description' => $type->description,
        '#type' => 'checkboxes',
        '#options' => array(
          'both' => t('Payer & payee (The current user signs automatically)'),
//          'exman' => t('The exchange manager')
        ),
        //checkboxes are a bit strange.
        //if we don't array filter, every array key will be read as a checked box
        '#value' => array_filter(_mcapi_signature_overrides($id)),
      );
    }
    debug('Need to improve how these settings are stored and retrieved');
    $form['signatures']['both']['#disabled'] = TRUE;
    $form['signatures']['both']['#value'] = TRUE;
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $settings = $this->config_factory->getEditable('mcapi.signatures');
    foreach ($form_state->getValues() as $type_name => $vals) {
      //go to some extra effort to save booleans in the config
      $this->settings->set($type_name.'.both', !empty($vals['both']));
      $this->settings->set($type_name.'.exman', !empty($vals['exman']));
    }
    $this->settings->save();

    parent::submitForm($form, $form_state);

    $form_state->setRedirect('mcapi.admin.transactions');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mcapi.signatures'];
  }

}


