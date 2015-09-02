<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Transitions
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Field handler to show transaction transitions according to context
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("transaction_transitions")
 */
class Transitions extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['mode'] = array('default' => 'page');
    $options['view'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->transitionManager = \Drupal::service('mcapi.transition_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
     $form['mode'] = array(
      '#title' => t('Link mode'),
      '#type' => 'radios',
      '#options' => array(
     	  'page' => t('New page'),
        'modal' => t('Modal window'),
        'ajax' => t('In-place (AJAX)')
      ),
      '#default_value' => !empty($this->options['mode']),
    );
     $form['view'] = array(
      '#title' => t("'view' link"),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['view']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
    $this->viewBuilder = \Drupal::EntityManager()->getViewBuilder('mcapi_transaction');
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    return $this->transitionManager
      ->getLinks($this->getEntity($values, TRUE));
  }

}
