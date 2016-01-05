<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\SettingsForm.
 *
 */
namespace Drupal\mcapi_cc;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Exchange;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  private $moduleHandler;
  private $transactionRelativeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_cc_settings_form';
  }

  public function __construct(ConfigFactoryInterface $configFactory, $module_handler, $transaction_relative_manager) {
    $this->setConfigFactory($configFactory);
    $this->moduleHandler = $module_handler;
    $this->transactionRelativeManager = $transaction_relative_manager;
  }

  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('mcapi.transaction_relative_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('mcapi.settings');
    $build['ticks_name'] = [
      '#title'  => $this->t('Base accounting unit name'),
      '#description' => $this->t('The unit that all others will be measured in'),
      '#description_display' => 'after',
      '#type' => 'textfield',
      '#default_value' => $config->get('ticks_name'),
      '#weight' => -1,
      '#required'  => TRUE
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('mcapi.settings');

    $config
      ->set('ticks_name', $values['ticks_name'])
      ->save();

    parent::submitForm($form, $form_state);
    if($config->get('counted') != $values['counted']) {
      SELF::rebuild_mcapi_index($form, $form_state);
    }
  }

  static function rebuild_mcapi_index(array &$form, FormStateInterface $form_state) {
    //not sure where to put this function
    \Drupal::entityTypeManager()->getStorage('mcapi_transaction')->indexRebuild();
    drupal_set_message("Index table is rebuilt");
    $form_state->setRedirect('system.status');
  }

  protected function getEditableConfigNames() {
    return ['mcapi.cc.settings'];
  }

}
