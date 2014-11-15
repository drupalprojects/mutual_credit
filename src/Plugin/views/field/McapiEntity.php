<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\McapiEntity.
 * based on Drupal\node\Plugin\views\field\Node
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide simple renderer that allows linking to a transaction.
 * Definition terms:
 * - link_to_transaction default: //TODO Should this field have the checkbox "link to transaction" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_entity")
 */
class McapiEntity extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    // Don't add the additional fields to groupby
    if (!empty($this->options['link_to_transaction'])) {
      $this->additional_fields['serial'] = array('table' => $this->table, 'field' => 'serial');
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_transaction'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to node option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_transaction'] = array(
      '#title' => t('Link this field to the original transaction'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_transaction']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the node.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   * @todo test this
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_transaction'])&& !empty($this->additional_fields['serial'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = $this->getEntity($values)->url();
        if (isset($this->aliases['langcode'])) {
          $languages = language_list();
          $langcode = $this->getValue($values, 'langcode');
          if (isset($languages[$langcode])) {
            $this->options['alter']['language'] = $languages[$langcode];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
