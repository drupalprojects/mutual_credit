<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OperationSettingsForm extends ConfigFormBase {


  public function title() {
    debug(func_get_args());
  }
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
    //we could get the fuller op name and also set the title using mcapi.routing.yml
    drupal_set_title(t('Mail settings for operation: @op', array('@op' => $op)));
    //I've set defaults for mcapi.operation but do they work if $op isn't set?
    $config = $this->configFactory->get('mcapi.operation.'.$op);
    $token_service = \Drupal::token();
    $all_tokens = $token_service->getInfo('tokens');
    foreach(array_keys($all_tokens['tokens']['transaction']) as $token) {
      $tokens[] = "[transaction:$token]";
    }
    $tokens[] = "[operation]";//dunno if this faux token will work. its only needed if the defaults work
    $email_token_help = $this->t('Available variables are:') .' '. implode(', ', $tokens);
    $form['help'] = array(
      '#markup' => $email_token_help
    );
    $form['transaction_operation'] = array(
      '#type' => 'hidden',
      '#value' => $op
    );
    $form['subject'] = array(
      '#title' => t('Mail subject'),
      '#description' => '',
      '#type' => 'textfield',
      '#default_value' => $config->get('subject'),
      '#weight' =>  1
    );
    $form['body'] = array(
      '#title' => t('Mail body'),
      '#description' => '',
      '#type' => 'textarea',
      '#default_value' => $config->get('body'),
      '#weight' => 2
    );
    $form['cc'] = array(
      '#title' => t('Carbon copy to'),
      '#description' => 'A valid email address',
      '#type' => 'email',
      '#default_value' => $config->get('cc'),
      '#weight' => 3
    );

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state, $op = NULL) {
    $this->configFactory->get('mcapi.operation.'.$form_state['values']['transaction_operation'])
      ->set('subject', $form_state['values']['subject'])
      ->set('body', $form_state['values']['body'])
      ->set('cc', $form_state['values']['cc'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}

