<?php

namespace Drupal\mcapi_command\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

class CommandTest extends ConfigFormBase {

  public function title() {
    print_r(func_get_args());
  }
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_test_command_form';
  }

  public function buildform(array $form, $form_state) {
    $form['command'] = array(
      '#title' => t('Command line interface'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
    );
    $form['command']['sender'] = array(
      '#title' => t('Sender'),
      '#description' => t('Ensure this user is able to use the currency'),
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'user.autocomplete',
      '#required' => FALSE,
      '#default_value' => \Drupal::currentUser()->id()
    );
    $form['command']['input'] = array(
      '#title' => t('Command'),
      '#description' => t('Test your commands here, based on the configured expressions. Max 160 characters.') .'<br />'. t('E.g. pay admin 14 for cleaning'),
      '#type' => 'textfield',
      '#maxlength' => 160,
      '#required' => TRUE
    );
    $form['command']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Send'),
      '#weight' => 1
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    try {
      drupal_set_message('TESTING ONLY: '. $values['input'], 'status', FALSE);
      $response = process_mcapi_command(
        $values['input'],
        User::load($values['sender']),
        FALSE
      );
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'warning');
      return;
    }
    drupal_set_message($response);

  }
}
