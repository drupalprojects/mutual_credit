<?php

namespace Drupal\mcapi_exchanges\Form;

use Drupal\mcapi\Form\MassPay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Multistep form to make an exchange group
 *
 * @todo
 */
class GroupMassTransactionForm extends MassPay {

  protected $exchange;

  /**
   * Constructor
   *
   * @param EntityManagerInterface $entity_manager
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param KeyValueFactory $key_value
   * @param MailManagerInterface $mail_manager
   * @param RouteMatchInterface $route_match
   * @param EntityFormBuilder $entity_form_builder
   *
   * @todo deprecated $entity_manager
   */
  public function __construct($entity_manager, EntityTypeManagerInterface $entity_type_manager, KeyValueFactory $key_value, MailManagerInterface $mail_manager, RouteMatchInterface $route_match, EntityFormBuilder $entity_form_builder) {
    parent::__construct($entity_manager, $entity_type_manager, $key_value, $mail_manager, $route_match);
    //get the group from the route
    $this->exchange = $route_match->getParameter('group');
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('plugin.manager.mail'),
      $container->get('current_route_match'),
      $container->get('entity.form_builder')
    );
  }



  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Restrict the wallet fields to within the group
    debug($form['payer']);
    debug($form['payee']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    debug($save);
  }

}
