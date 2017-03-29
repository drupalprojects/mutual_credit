<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\mcapi\Element\WorthsView;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * The computed worth field cannot be retrieved by the views query so we take it
 * from the loaded entity instead.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("computed_worths")
 */
class ComputedWorths extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['format'] = ['default' => CurrencyInterface::DISPLAY_NORMAL];
    $options['context'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['format'] = array(
      '#title' => t('Format'),
      '#decriptions' => $this->t('Not all formats support multiple cardinality.'),
      '#type' => 'radios',
      '#options' => Currency::formats(),
      '#default_value' => $this->options['format'],
    );
    $form['context'] = [
      '#type' => 'radios',
      '#title' => $this->t('Worth view context'),
      '#options' => WorthsView::options(),
      '#default_value' => $this->options['context'],
      '#weight' => 10,
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */

  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    //Mcnasty
    $fieldname = substr($this->getField(), 1);

    return $this->getEntity($values)
      ->{$fieldname}
      ->view(['label' => 'hidden', 'context' => WorthsView::MODE_BALANCE]);
  }

}
