<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Action\View
 *
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Canonical viewing of a transaction
 *
 * @Action(
 *   id = "mcapi_transaction.view_action",
 *   label = @Translation("View a transaction"),
 *   type = "mcapi_transaction"
 * )
 */
class View extends \Drupal\mcapi\Plugin\TransactionActionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    unset($elements['sure']['#type']);
    $elements['sure']['format']['#value'] = 'twig';
    $elements['sure']['format']['#type'] = 'hidden';

    $elements['sure']['page_title']['#access'] = FALSE;//@todo enable this with tokens
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
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    $result = parent::access($object, $account, $return_as_object);
    $name = 'entity.mcapi_transaction.canonical';
    $params = \Drupal::routeMatch()->getRawParameters()->all();
    if ($return_as_object) {
      if ($result->isAllowed()) {
        $result->forbiddenIf(isset($params['mcapi_transaction']) && $params['mcapi_transaction'] == $object->serial->value);
      }
      $result->addCacheableDependency($object)->cachePerUser();
    }
    else {
      if ($result) {
        if (isset($params['mcapi_transaction']) && $params['mcapi_transaction'] == $object->serial->value) {
          $result = FALSE;
        }
      }
    }
    return $result;
  }

}
