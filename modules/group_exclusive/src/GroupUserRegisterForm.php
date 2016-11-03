<?php

namespace Drupal\group_exclusive;

use Drupal\user\RegisterForm;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form handler for the user register forms.
 *
 * @todo review this when there is a proper way to create entities IN GROUPS
 */
class GroupUserRegisterForm extends RegisterForm {

  protected $exchange;

  /**
   * Constructs a new EntityForm object.
   */
  public function __construct($entity_manager, $language_manager, $entity_query, GroupInterface $exchange) {
    parent::__construct($entity_manager, $language_manager, $entity_query);
//    if ($exchange->type->target_id != 'exchange') {
//      throw new AccessDeniedHttpException(
//        $this->t("You can't join group '%name'", ['%name' => $exchange->label])
//      );
//    }
    $this->exchange = $exchange;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity.query'),
      $container->get('current_route_match')->getParameter('group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if ($this->exchange) {
      $form['#title'] = $this->t('Join exchange %groupname', ['%groupname' => $this->exchange->label()]);
      $form['exchange_id'] = [
        '#type' => 'value',
        '#value' => $this->exchange->id()
      ];
    }
    else {
      $exchanges = $this->entityManager->getStorage('group')->getQuery()->execute();
      $options = [];
      foreach ($exchanges as $id) {
        $options[$id] = Group::load($id)->label();
      }
      $form['exchange_id'] = [
        '#title' => $this->t('Exchange', [], ['context' => 'group of traders']),
        '#type' => 'select',
        '#options' => $options,
        '#required' => TRUE
      ];
    }

    $options = [];
    $type_terms =  $this->entityManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'wallet_types']);
    foreach ($type_terms as $tid => $term) {
      $options[$tid] = $term->label();
    }

    // @todo $options should be sorted by weight
    $form['wallet_type'] = [
      '#title' => $this->t('Wallet type'),
      '#type' => 'radios',
      '#options' => $options,
      '#required' => TRUE
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // This temp value is picked up when the new user's wallet is added
    // @see eserai_mcapi_wallet_insert
    $this->entity->address->country_code = $this->exchange->address->country_code;
    $this->entity->wallet_type = $form_state->getValue('wallet_type');
    parent::save($form, $form_state);
    $this->exchange->addContent($this->entity, 'group_membership');
  }

}
