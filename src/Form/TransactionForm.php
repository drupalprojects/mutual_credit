<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionForm extends ContentEntityForm {

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  private $tempstore;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  public function __construct($entity_type_manager, $tempstore, $current_request) {
    parent::__construct($entity_type_manager);
    $this->tempStore = $tempstore;
    $this->request = $current_request;
  }

  /**
   * {@inheritdoc}
   * @todo update to entity_type.manager
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    //try to prevent the same wallet being in both payer and payee fields.
    //we can only do this is one field has only one option
    $payer = &$form['payer']['widget'][0]['target_id'];
    $payee = &$form['payee']['widget'][0]['target_id'];
    if ($payer['#type'] == 'value') {
      if (isset($payee['#options'])) {
        unset($payee['#options'][$payer['#value']]);
      }
      else {
        $payee['#selection_settings']['exclude'] = [$payer['#value']];
      }
    }
    elseif ($payee['#type'] == 'value') {
      if (isset($payer['#options'])) {
        unset($payer['#options'][$payee['#value']]);
      }
      else {
        $payer['#selection_settings']['exclude'] = [$payee['#value']];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   * @note we are overriding here because this form is neither for saving nor
   * deleting and because previewing is not optional.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Preview'),
        '#submit' => ['::submitForm'],//does NOT save()
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);//builds $this->entity
    $this->tempStore
      ->get('TransactionForm')
      ->set('mcapi_transaction', $this->entity);
    //Drupal\mcapi\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Transition\Create
    //now we divert to the transition confirm form
    $form_state->setRedirect(
      'mcapi.transaction.operation',
      ['mcapi_transaction' => 0, 'operation' => 'save']);
  }

  /**
   * {@inheritdoc}
   * @todo test creating a transaction with and without specifying the creator
   */
  public function ______buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    //if a valid creator uid was submitted then use that
    //is this the best place to be putting defaults? not in Transaction::precreate?
    $uid = $form_state->getValue('creator') ? : \Drupal::currentUser()->id();
    $entity->creator->entity = \Drupal\user\Entity\User::load($uid);
    return $entity;
  }

}

