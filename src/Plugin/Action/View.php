<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Canonical viewing of a transaction.
 *
 * @Action(
 *   id = "mcapi_transaction.view_action",
 *   label = @Translation("View a transaction"),
 *   type = "mcapi_transaction"
 * )
 *
 * @todo inject currentuser & routeMatch
 */
class View extends TransactionActionBase {


  private $routeMatch;

  /**
   * Constructor.
   * As parent, plus
   * @param RouteMatchInterface $route_match
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, $entity_form_builder, $module_handler, $relative_active_plugins, $entity_type_manager, $entity_display_respository, $current_user, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_form_builder, $module_handler, $relative_active_plugins, $entity_type_manager, $entity_display_respository, $current_user);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.form_builder'),
      $container->get('module_handler'),
      $container->get('mcapi.transaction_relative_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('current_user'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    unset($elements['sure']['#type']);
    $elements['sure']['format']['#value'] = 'twig';
    $elements['sure']['format']['#type'] = 'hidden';

    // @todo enable this with tokens
    $elements['sure']['page_title']['#access'] = FALSE;
    $elements['sure']['format']['#access'] = FALSE;
    $elements['sure']['button']['#access'] = FALSE;
    $elements['sure']['cancel_link']['#access'] = FALSE;
    $elements['message']['#access'] = FALSE;
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($object, $account, TRUE);
    if ($result->isAllowed()) {
      $params = $this->routeMatch->getRawParameters()->all();
      $result->forbiddenIf(isset($params['mcapi_transaction']) && $params['mcapi_transaction'] == $object->serial->value);
    }
    return $return_as_object ?
      $result->addCacheableDependency($object)->cachePerUser() :
      $result->isAllowed();
  }

}
