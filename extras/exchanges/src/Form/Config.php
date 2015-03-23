<?php

namespace Drupal\mcapi_exchanges\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
//use Drupal\mcapi\Mcapi;

class Config extends ConfigFormBase {

  private $entity_manager;  
  private $module_handler;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_exchanges_setup_form';
  }
  
  public function __construct(ConfigFactoryInterface $configFactory, $module_handler, $entity_manager) {
    $this->setConfigFactory($configFactory);
    $this->entity_manager = $entity_manager;
    $this->module_handler = $module_handler;
  }
  
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity.manager')
    );
  }    

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('mcapi.exchanges');
    $form['ticks_name'] = array(
      '#title' => t('Base Unit'),
      '#description' => t('Plural name of the base unit, used for intertrading.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('ticks_name'),
      '#weight' => 7
    );
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('mcapi.misc');

    $config
      ->set('ticks_name', $values['ticks_name'])
      ->save();

    parent::submitForm($form, $form_state);
  }
  protected function getEditableConfigNames() {
    return ['mcapi.exchanges'];
  }

}
