<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\AccessSettings.
 *
 */
namespace Drupal\mcapi\Form;

use Drupal\mcapi\Entity\Type;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AccessSettings extends ConfigFormBase {

  private $op;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_access_settings_form';
  }

  public function __construct($route_match) {
    $this->entityManager = $entity_manager;
    $this->op = $route_match->getParameters()->get('op');
    $ops = [
      'view' => t('View'),
      'edit' => t('Edit')
    ];
    $this->opName = $ops[$this->op];
  }

  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('Need to configure extra fields for editing transactions, or an extra module');
    drupal_set_message('How to put this form into a table? See the permissions page for an example');
    //then maybe the view and edit can be done on the same page.
    
    foreach (Type::loadMultiple() as $type) {
      $form[$type->id()] = [
        '#title' => t('Who can @action transactions of type: @type', ['@action' => $this->opName, '@type' => $type->label()]),
        '#description' => t('Check whichever relatives apply'),
        '#type' => 'transaction_relatives',
        '#default_value' => $type->get($this->op)
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $type_name => $relatives) {
      $type = Type::load($type_name)
        ->set($this->op, array_filter($relatives))
        ->save();
    }
    parent::submitForm($form, $form_state);
  }
  
  public function getEditableConfigNames() {
    return [];
  }

}
