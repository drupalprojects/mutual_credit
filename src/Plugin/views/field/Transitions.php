<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Transitions
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show transaction transitions according to context
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("transaction_transitions")
 */
class Transitions extends FieldPluginBase {

  private $transitionManager;


  public function __construct(array $configuration, $plugin_id, $plugin_definition, $transition_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transitionManager = $transition_manager;
  }
  
    /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('mcapi.transition_manager')
    );
  }

  
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
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    return $this->transitionManager
      ->getLinks($this->getEntity($values, TRUE));
  }
  
  /**
   * {@inheritdoc}
   */
  protected function allowAdvancedRender() {
    return FALSE;
  }

}
