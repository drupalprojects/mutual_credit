<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builder for the base transaction entity form.
 */
class TransactionForm extends ContentEntityForm {

  /**
   * The tempstore service.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  private $tempstore;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Flag indicating whether this form is restricted by wallet directionality.
   *
   * @var boolean
   * whether the wallet widgets should be restricted by directionality
   *
   * @todo move this to the mcapi_forms module? probably not while the payin/payout
   * constraints must be defined in BaseFieldDefinitions.
   */
  public $restrict = FALSE;

  /**
   * Constructor.
   */
  public function __construct($entity_type_manager, $tempstore, $current_request, $current_user) {
    parent::__construct($entity_type_manager);
    $this->tempStore = $tempstore;
    $this->request = $current_request;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   *
   * @todo update to entity_type.manager
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Try to prevent the same wallet being in both payer and payee fields.
    // we can only do this is one field has only one option.
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
    $form['type']['#access'] = $this->currentUser->hasPermission('manage mcapi');
    $form['created']['#access'] = $this->currentUser->hasPermission('manage mcapi');

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @note we are overriding here because this form is neither for saving nor
   * deleting and because previewing is not optional.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Preview'),
    // Does NOT save()
        '#submit' => ['::submitForm'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Builds $this->entity.
    parent::submitForm($form, $form_state);
    $this->tempStore
      ->get('TransactionForm')
      ->set('mcapi_transaction', $this->entity);
    // Drupal\mcapi\TransactionSerialConverter
    // then
    // Drupal\mcapi\Plugin\Transition\Create
    // now we divert to the transition confirm form.
    $form_state->setRedirect(
      'mcapi.transaction.operation',
      ['mcapi_transaction' => 0, 'operation' => 'save']);
  }

  /**
   * {@inheritdoc}
   *
   * @todo test creating a transaction with and without specifying the creator
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    if ($entity->isNew()) {
      $entity->assemble();
    }
    return $entity;
  }

}
