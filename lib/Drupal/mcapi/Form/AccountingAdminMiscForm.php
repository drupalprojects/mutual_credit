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

    $form['delete_mode'] = array(
      '#title' => t('Undo mode'),
      '#description' => t('What should happen when a user or admin deletes a transaction.?') .' '.
        t("Some system operations may 'scratch' transactions") .' '.
        t('Cannot be changed after a transaction has been undone'),
      '#type' => 'radios',
      '#options' => array(
        MCAPI_CURRENCY_UNDO_DELETE => t('Wipe slate - remove transactions from database'),
        MCAPI_CURRENCY_UNDO_ERASE => t('Scratch - use deleted transaction state'),
        MCAPI_CURRENCY_UNDO_REVERSE => t('Reverse - create an equal and opposite transaction'),
      ),
      '#default_value' => $config->get('delete_mode'),
      '#disabled' => !$config->get('change_undo_mode'),
    );
    $form['show_balances'] = array(
      '#title' => t('View balances in user profile'),
      '#description' => t('Small impact on performance; it may be necessary to edit user-profile.tpl.php'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('show_balances'),
    );

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('mcapi.misc')
      ->set('delete_mode', $form_state['values']['delete_mode'])
      ->set('show_balances', $form_state['values']['show_balances'])
      ->set('sentence_template', $form_state['values']['sentence_template'])
      ->set('mix_mode', !$form_state['values']['mix_mode'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
