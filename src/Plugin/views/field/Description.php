<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Description.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Field handler to link the transaction description to the transaction itself
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_description")
 */
class Description extends Standard {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link'] = array(
      '#title' => t('Link to the transaction'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $txt = $values->{$this->field_alias};
    if ($this->options['link']) {
      return $this->getEntity($values)->link($txt);
    }
    return $txt;
  }

}
