<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransitionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_transition_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $op = NULL) {
  	$plugin = transaction_transitions($op);
  	$form = parent::buildForm($form, $form_state);
  	$plugin->SettingsForm($form, $this->configFactory->get('mcapi.transition.'.$op));
    $form['submit']['#weight'] = 20;
  	$form['transaction_transition'] = array(
      '#type' => 'value',
      '#value' => $op
  	);
    $form['#submit'][] = array($this, 'submitForm');
    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {
    if ($form_state['values']['send']) {
      if (!$form_state['values']['subject']) {
        $this->setFormError('subject', $form_state, t("You can't send a mail without a subject"));
      }
      if (!$form_state['values']['body']) {
        $this->setFormError('subject', $form_state, t("You can't send a mail without a body"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state, $op = NULL) {
  	form_state_values_clean($form_state);
  	$config = $this->configFactory->get('mcapi.transition.'.$form_state['values']['transaction_transition']);
  	unset($form_state['values']['transaction_transition']);
  	foreach ($form_state['values'] as $key => $val) {
      $config->set($key, $val);
  	}
    $config->save();

    parent::submitForm($form, $form_state);
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin.workflow'
    );
  }
}

