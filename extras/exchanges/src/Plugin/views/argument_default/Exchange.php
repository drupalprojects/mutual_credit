<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\argument_default\Exchange.
 */

namespace Drupal\mcapi\Plugin\views\argument_default;

use Drupal\views\Annotation\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The fixed argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "exchange",
 *   title = @Translation("Current user's exchange(s)")
 * )
 */
class Exchange extends ArgumentDefaultPluginBase {

  /**
   * Return the default argument.
   */
  public function getArgument() {
    return key(Exchange::referenced_exchanges(NULL, TRUE));
  }

}
