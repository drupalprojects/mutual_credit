<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\mcapi\Mcapi;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler for the name of the transaction state.
 *
 * I would hope for a generic filter to come along to render list key/values.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_state")
 */
class State extends FieldPluginBase {

  private $states;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    // Is this needed?
    parent::init($view, $display, $options);
    $this->states = Mcapi::entityLabelList('mcapi_state');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $raw = $this->getValue($values);
    $label = \Drupal\mcapi\Entity\State::load($raw)->label();
    // Should be translated.
    return ['#markup' => '<span class = "mcapi-state-'. $raw .'">' . $label . '</span>'];
  }

}
