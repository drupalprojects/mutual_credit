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
    return $this->t("@name transition settings", ['@name' => $this->plugin->label]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $transition = NULL) {
    
    $form = $this->plugin->buildConfigurationForm($form, $form_state);

    //TODO move the following to an action when rules is readier
    if ($transition != 'view' && 0) {
      $form['rules'] = array(
        '#markup' => t('Use the rules module to send notifications'),
        '#weight' => 20
      );

      //TODO this will be replaced by rules
      $defaults = $this->configFactory->get('mcapi.transition.'.$this->plugin->getPluginId());
      $tokens = Exchange::transactionTokens(TRUE);
      unset($tokens[array_search('links', $tokens)]);
      $form['notify'] = array(
        '#type' => 'fieldset',
        '#title' => t('Notification'),
        '#description' => t(
          'Customise the subject and body of the mail with the following tokens: @tokens',
          array('@tokens' => '[mcapi:'. implode('], [mcapi:', $tokens) .']')
        ),
        '#weight' => 0
      );
      $form['notify']['send'] = array(
        '#title' => t('Mail the transactees, (but not the current user)'),
        '#type' => 'checkbox',
        '#default_value' => $defaults->get('send'),
        '#weight' =>  0
      );
      $form['notify']['subject'] = array(
        '#title' => t('Mail subject'),
        '#description' => '',
        '#type' => 'textfield',
        '#default_value' => $defaults->get('subject'),
        '#weight' =>  1,
        '#states' => array(
          'visible' => array(
            ':input[name="send"]' => array('checked' => TRUE)
          )
        )
      );
      $form['notify']['body'] = array(
        '#title' => t('Mail body'),
        '#type' => 'textarea',
        '#default_value' => $defaults->get('body'),
        '#weight' => 2,
        '#states' => array(
        'visible' => array(
          ':input[name="send"]' => array('checked' => TRUE)
          )
        )
      );
      $form['notify']['cc'] = array(
        '#title' => t('Carbon copy to'),
        '#description' => 'A valid email address',
        '#type' => 'email',
        '#default_value' => $defaults->get('cc'),
        '#weight' => 3,
        '#states' => array(
          'visible' => array(
            ':input[name="send"]' => array('checked' => TRUE)
          )
        )
      );
    }

    return parent::buildForm($form, $form_state);
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

    $config = $this->configFactory->getEditable('mcapi.transition.'.$this->plugin->getPluginId());
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

