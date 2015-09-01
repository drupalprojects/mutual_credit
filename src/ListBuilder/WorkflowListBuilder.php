<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WorkflowListBuilder.
 * NB this isn't an entity list
 */

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Url;//isn't this in the controllerBase?
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Entity\State;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the workflow page in the management menu admin/accounting/workflow
 *
 */
class WorkflowListBuilder extends ControllerBase implements FormInterface {

  private $config;
  private $transitionManager;

  function __construct($config_factory, $transition_manager) {
    $this->config = $config_factory->get('mcapi.misc');
    $this->transitionManager = $transition_manager;
  }

  static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('mcapi.transition_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function buildHeader() {
    return [
      'name' => t('Transition'),
      'description' => t('Description'),
      'operations' => '',
      'flip' => '',
      'weight' => t('Weight'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function render() {
    return [
      $this->visualise(),
      $this->formBuilder()->getForm($this)
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildRow($id) {
    $row = $this->disabledRow($id);

    if ($this->config->get('active_transitions')[$id]) {
      $row['operations'] = [
        '#markup' => $this->l(
          $this->t('Settings'),
          Url::fromRoute('mcapi.workflow_settings', ['transition' => $id])
        )
      ];

      $row['flip'] = [
        '#markup' => $id == 'view' ? '' : $this->l(
          t('Disable'),
          Url::fromRoute('mcapi.admin.workflow.toggle', ['transition' => $id])
        ),
      ];
    }
    return $row;
  }

  /**
   * {@inheritDoc}
   */
  private function disabledRow($id) {
    $config = $this->transitionManager->getConfig($id);
    $weight = $config->get('weight');
    return [
      '#weight' => $weight,
      '#attributes' => new Attribute(['class' => ['draggable']]),
      //@todo wait for the \Drupal\Core\Config\Entity\DraggableListBuilder::buildrow to recognise Attribute object
      '#attributes' => ['class' => ['draggable']],
      'name' => [
        '#markup' => $config->get('title')
      ],
      'description' => [
        '#markup' => $config->get('tooltip')
      ],
      'operations' => [
        '#markup' => $this->t('Disabled'),
      ],
      'flip' => [
        '#markup' => \Drupal::l(
          t('Enable'),
          Url::fromRoute(
            'mcapi.admin.workflow.toggle',
            ['transition' => $id]
          )
        )
      ],
      'weight' => [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $config->get('title')]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => new Attribute(['class' => ['weight']]),
        //@todo replace with Attributes
        '#attributes' => ['class' => ['weight']]
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //reset the form fresh
    $form = [];
    $form['plugins'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      //@todo Attribute doesn't work according to the documentation https://api.drupal.org/api/drupal/core!includes!common.inc/function/drupal_attach_tabledrag/8
      '#attributes' => [
        'id' => 'transitions-table',
        'style' => 'width:100%'
      ],
      '#attributes' => new Attribute(['style' => 'width:100%', 'id' => 'transitions-table'])
    ];
    foreach ($this->transitionManager->getDefinitions() as $id => $definition) {
      $form['plugins'][$id] = $this->buildRow($id);
    }

    uasort($form['plugins'], ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save order'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    //this is required by the interface
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues()['plugins'] as $id => $value) {
      $this->transitionManager->getConfig($id, TRUE)
        ->set('weight', $value['weight'])
        ->save();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'workflow_draggable_plugin_list';
  }

  private function visualise() {
    foreach (Type::loadMultiple() as $type => $info) {
      $types[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $renderable['types'] = [
      '#type' => 'container',
      '#attributes' => new Attribute(['style' => 'display:inline-block; vertical-align:top;']),
      'title' => [
        '#markup' => "<h4>".t('Transaction types')."</h4>"
      ],
      'states' => [
        '#markup' => "<dl>".implode("\n\t", $types) . '</dl>'
      ]
    ];
    foreach (State::loadMultiple() as $id => $info) {
      $states[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $renderable['states'] = [
      '#type' => 'container',
      '#attributes' => new Attribute(['style' => 'display:inline-block; margin-left:5em; vertical-align:top;']),
      'title' => [
        '#markup' => "<h4>".t('Workflow states')."</h4>"
      ],
      'states' => [
        '#markup' => "<dl>".implode("\n\t", $states) . '</dl>'
      ]
    ];
    return $renderable;
  }

}
