<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AccountingAdminMiscForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.context.free')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_misc_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('mcapi.misc');

    $form['sentence_template'] = array(
      '#title' => t('Default transaction sentence'),
      '#description' => t('Use the tokens to define how the transaction will read when displayed in sentence mode'),
      '#type' => 'textfield',
      '#default_value' => $config->get('sentence_template'),
      '#weight' => 5
    );

    $form['mix_mode'] = array(
      '#title' => t('Restrict transactions to one currency'),
      '#description' => t('Applies only when more than one currency is available'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('mix_mode'),
    );
    $form['rebuild_mcapi_index'] = array(
      '#title' => t('Rebuild index'),
      '#description' => t('The transaction index table stores the transactions in an alternative format which is helpful for building views'),
      '#type' => 'fieldset',
      '#weight' => 10,
      'button' => array(
        '#type' => 'submit',
        '#value' => 'rebuild_mcapi_index',
      )
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('mcapi.misc')
      ->set('sentence_template', $form_state['values']['sentence_template'])
      ->set('mix_mode', !$form_state['values']['mix_mode'])
      ->save();

    parent::submitForm($form, $form_state);

    if($form_state['triggering_element']['#value'] == 'rebuild_mcapi_index') {
      //not sure where to put this function
       \Drupal::entityManager()->getStorageController('mcapi_transaction')->indexRebuild();
       drupal_set_message("Index table is rebuilt");
       $form_state['redirect'] = 'admin/reports/status';
    }
  }
}


