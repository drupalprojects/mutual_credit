<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\Taxonomy.
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
 * Field handler to provide simple renderer that allows linking to a taxonomy
 * term.
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
    $worths = current($this->getEntity($values)->worths->getValue());
    //TODO there needs to be a system setting for the separator for mixed transactions
    return 'theme me!';
  }

}
