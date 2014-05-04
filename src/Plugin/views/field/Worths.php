<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Worths
 */

namespace Drupal\mcapi\Plugin\views\field;

//TODO which of these are actually needed?
use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;

/**
 * When we look at transactions, we need to view the worths field 
 * which contains all the currency flows
 *
 * @todo This handler should use entities directly.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("worths")
 */
class Worths extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = array('default' => '');
    return $options;
  }

  /**
   * Provide link to taxonomy option
   */
  public function buildOptionsForm(&$form, &$form_state) {
     $form['separator'] = array(
      '#title' => t('Separator between different currency quantities'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => !empty($this->options['separator']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {
    $this->ensureMyTable();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->getEntity($values)->worths->getString();
  }

}
