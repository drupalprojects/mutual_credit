<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Mcapi;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder to create a new wallet for a given ContentEntity.
 */
class WalletAddForm extends ContentEntityForm {

  private $holder;
  private $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wallet_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $params = $route_match->getParameters()->all();
    $entity = $this->entityManager
      ->getStorage($entity_type_id)
      ->create(['holder' => reset($params)]);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($route_match, $entity_type_manager, $database) {
    $params = $route_match->getParameters();
    $this->holder = $entity_type_manager
      ->getStorage($params->getIterator()->key())
      ->load($params->getIterator()->current()->id());
    $this->database = $database;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return
      $this->t("New wallet for @entity_type '%title'",
      [
        '@entity_type' => $this->holder->getEntityType()->getLabel(),
        '%title' => $this->holder->label(),
      ]
      );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['wid'] = [
      '#type' => 'value',
      '#value' => NULL,
    ];
    $form['holder_entity_type'] = [
      '#type' => 'value',
      '#value' => $this->holder->getEntityTypeId(),
    ];
    $form['holder_entity_id'] = [
      '#type' => 'value',
      '#value' => $this->holder->id(),
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name or purpose of wallet'),
      '#default_value' => '',
      '#required' => '',
    ];

    if (Mcapi::maxWalletsOfBundle($this->holder->getEntityTypeId(), $this->holder->bundle()) == 1) {
      $form['name']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Create');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Just check that the name isn't the same.
    // This unique check would be better in a walletStorageController.
    $values = $form_state->getValues();
    $query = $this->walletQuery->condition('name', $values['name'])->execute();

    if (!\Drupal::config('mcapi.settings')->get('unique_names')) {
      $query->condition('holder_entity_id', $values['holder_entity_id']);
      $query->condition('holder_entity_type', $values['holder_entity_type']);
    }
    if ($query->execute()) {
      $form_state->setErrorByName(
        'name',
        t("The wallet name '%name' is already used.", ['%name' => $values['name']])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
  }

}
