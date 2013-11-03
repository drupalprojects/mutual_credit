<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OperationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_operation_settings_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $op) {
    debug($form_state);
    $config = $this->configFactory->get('mcapi.operations.'.$op);
kk
      //'#default_value' => intval($config->get('mix_mode'))


  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('mcapi.operation.'.$op)
      ->set('delete_mode', $form_state['values']['delete_mode'])
      ->set('show_balances', $form_state['values']['show_balances'])
      ->set('sentence_template', $form_state['values']['sentence_template'])
      ->set('mix_mode', $form_state['values']['mix_mode'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
