<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Plugin\views\argument_default\CurrentUserExchanges.
 */

namespace Drupal\mcapi_exchanges\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * return as a views argument, the exchange ids the current user is in
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "current_user_exchanges",
 *   title = @Translation("The exchanges the viewed entity is in")
 * )
 */
class CurrentUserExchanges extends ArgumentDefaultPluginBase {

  /**
   * Return the default argument.
   */
  public function getArgument() {
    $ids = [];
    //@todo is it possible to iterate through an entity_reference field like this?
    //can we not just pull out all the target_ids?
    foreach (\Drupal::currentUser()->exchanges->referencedEntities() as $exchange) {
      $ids[] = $exchange->id();
    }
    return implode('+', $ids);
    //returning nothing means the view doesn't show
  }

}
