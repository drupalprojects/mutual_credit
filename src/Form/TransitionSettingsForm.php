<?php

namespace Drupal\mcapi\Form;

use Drupal\Mcapi\Exchange;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransitionSettingsForm extends ConfigFormBase {

  private $plugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, $transitionManager, $routeMatch) {
    $this->setConfigFactory($configFactory);
    $transition = $routeMatch->getParameters()->get('transition');
    $this->plugin = $transitionManager->getPlugin($transition, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mcapi.transitions'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_transition_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->t(
      "'@name' transition",
      ['@name' => $this->plugin->getConfiguration('title')]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $transition = NULL) {
    return parent::buildForm(
      $this->plugin->buildConfigurationForm($form, $form_state),
      $form_state
    );
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->plugin->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $transition = NULL) {
  	$form_state->cleanValues();;

  	$this->plugin->submitConfigurationForm($form, $form_state);
    $id = 'mcapi.transition.'.$this->plugin->getPluginId();
    $config = $this->configFactory->getEditable($id);
    foreach ($this->plugin->getConfiguration() as $key => $val) {
      $config->set($key, $val);
    }
    $config->save();
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('mcapi.admin.transactions');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return $this->transitionManager->getNames();
  }
}

