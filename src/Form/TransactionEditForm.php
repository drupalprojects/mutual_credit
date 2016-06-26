<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for Editing existing transaction entities.
 */
class TransactionEditForm extends ContentEntityForm {

  protected $entityQuery;
  protected $currentUser;
  protected $entityTypeManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager service.
   * @param AccountProxyInterface $current_user
   *   The current User Object.
   * @param Query $entity_query
   *   The transaction entity query service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, Query $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('entity.query')->get('mcapi_transaction')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $children = $this->entityQuery
      ->condition('parent', $this->entity->id())
      ->execute();
    if ($children) {
      drupal_set_message($this->t('Child transactions will be unaffected by changes to this transaction'), 'warning');
    }

    //$transaction = $this->entity->getEntityTypeId() == 'mcapi_transaction'
    //  ? $this->entity
    //  : Transaction::Create();
    $form = parent::form($form, $form_state);

    $is_admin = $this->currentUser->hasPermission('manage mcapi');
    $form['type']['#access'] = $is_admin;
    $form['state']['#access'] = $is_admin;
    $form['creator']['#access'] = $is_admin;
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @note does NOT call parent.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set $this->entity.
    parent::submitForm($form, $form_state);
    // Now we divert to the transition confirm form.
    $form_state->setRedirect(
      'entity.mcapi_transaction.canonical',
      ['mcapi_transaction' => $this->entity->serial->value]
    );
  }

}
