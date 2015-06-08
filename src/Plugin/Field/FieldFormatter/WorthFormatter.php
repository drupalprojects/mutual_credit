<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WorthFormatter.
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'worth' formatter.
 *
 * @FieldFormatter(
 *   id = "worth",
 *   label = @Translation("Currency value"),
 *   field_types = {
 *     "worth",
 *   }
 * )
 */
class WorthFormatter extends FormatterBase {

      /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['format'] = ['default' => 'normal'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['format'] = [
      '#title' => $this->t("Format"),
      '#type' => 'radios',
      '#options' => [
        'normal' => $this->t('Normal'),
        'native' => $this->t('Native integer'),
        'decimalised' => $this->t('Forced decimal')
      ],
      '#required' => TRUE,
      '#default_value' => !empty($this->options['format']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) { 
    //we're shovelling all the $items into 1 element because the have already
    //been rendered together, with a separator character
    $elements[0] = $items->view($this->options['format'] == 'normal');

    return $elements;
  }

}