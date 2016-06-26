<?php

namespace Drupal\mcapi_signatures\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for signature settings.
 */
class Settings extends ConfigFormBase {

  private $transactionRelativeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $transactionRelativeManager) {
    parent::__construct($config_factory);
    $this->transactionRelativeManager = $transactionRelativeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mcapi.transaction_relative_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mcapi_signatures_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo This would look really tidy in a grid - but forms in tables are tricky
    $options = $this->transactionRelativeManager->options();
    unset($options['signatory'], $options['pending_signatory']);
    foreach (Type::loadMultiple() as $type) {
      $form[$type->id()] = array(
        '#title' => $this->t("Signatories of '@type' transactions", ['@type' => $type->label]),
        '#description' => $type->description,
        '#type' => 'checkboxes',
        '#options' => $options,
        // Checkboxes are a bit strange.
        // if we don't filter, every array key will be read as a checked box.
        '#default_value' => $type->getThirdPartySetting('mcapi_signatures', 'signatures'),
        '#required' => FALSE,
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // This is required.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $type_name => $vals) {
      $type = Type::load($type_name);
      $type->setThirdPartySetting('mcapi_signatures', 'signatures', array_filter($vals));
      $type->save();
    }
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('mcapi.admin.workflow');
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {}

}
