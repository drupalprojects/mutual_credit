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
  public function buildForm(array $form, array &$form_state, $op = NULL) {
  	$plugin = transaction_operations($op);
  	$form = parent::buildForm($form, $form_state);
  	$form['#tree'] = 1;
    $config = $this->configFactory->get('mcapi.operation.'.$op);
  	$form = $plugin->SettingsForm($form, $config);

  	$form['transaction_operation'] = array(
  			'#type' => 'value',
  			'#value' => $op
  	);
    $form['#submit'][] = array($this, 'submitForm');
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state, $op = NULL) {
  	form_state_values_clean($form_state);
  	$config = $this->configFactory->get('mcapi.operation.'.$form_state['values']['transaction_operation']);
  	unset($form_state['values']['transaction_operation']);
  	foreach ($form_state['values'] as $key => $val) {
      $config->set($key, $val);
  	}
    $config->save();

    parent::submitForm($form, $form_state);
    $form_state['redirect'] = 'admin/accounting/workflow';
  }
}

