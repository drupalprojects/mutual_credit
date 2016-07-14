<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\user\RegisterForm;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form handler for the user register forms.
 */
class ExchangeUserRegisterForm extends RegisterForm {

  protected $gid;

  /**
   * Constructs a new EntityForm object.
   */
  public function __construct($entity_manager, $language_manager, $entity_query, $group_id) {
    parent::__construct($entity_manager, $language_manager, $entity_query);
    $this->gid = $group_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    $gid = $container->get('current_route_match')->getParameter('group');
    if (!Group::load($gid)) {
      throw new NotFoundHttpException();
    }

    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity.query'),
      $gid
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // Add the new member to the grpoup
    GroupContent::create([
      'gid' => $this->gid,
      // $exchange->getGroupType()->getContentPlugin('group_membership')->getContentTypeConfigId()
      'type' => 'exchange-group_membership',
      'entity_id' => $this->entity->id(),
      // Note that the uid field created here is zero, but the meaning of it isn't clear yet
    ])->save();
  }
}
