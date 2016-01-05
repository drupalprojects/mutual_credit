<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Form\Join.
 * Form for a user to join a specific exchange 
 */

namespace Drupal\mcapi_exchanges\Form;

use Drupal\mcapi_exchanges\Entity\Exchange;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class Join extends \Drupal\user\RegisterForm {
  
  protected $routeMatch;

  public function __construct($entity_manager, $language_manager, $entity_query, $route_match) {
    parent::__construct($entity_manager, $language_manager, $entity_query);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity.query'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    
    // Start with the default user account fields.
    $form = parent::form($form, $form_state);
    drupal_set_message('@todo find out why the Membership field is not hiding');
    $form[EXCHANGE_OG_FIELD]['#access'] = 0;
    $exid = $this->routeMatch->getParameter('mcapi_exchange');
    $form[EXCHANGE_OG_FIELD]['widget'][0]['target_id']['#value'] = Exchange::load($exid)->label();
    return $form;
  }

}

