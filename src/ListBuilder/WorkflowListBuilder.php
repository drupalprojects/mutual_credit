<?php

namespace Drupal\mcapi\ListBuilder;

use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\mcapi\TransactionOperations;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Entity\State;
use Drupal\system\Entity\Action;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Displays the workflow page in the management menu admin/accounting/workflow.
 */
class WorkflowListBuilder extends ControllerBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'name' => t('Operation'),
      'description' => t('Description'),
      'operations' => '',
      'weight' => t('Weight'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      $this->formBuilder()->getForm($this),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow($action) {
    $config = $action->getPlugin()->getConfiguration();
    return [
      '#weight' => $config['weight'],
      '#attributes' => new Attribute(['class' => ['draggable']]),
      // @todo wait for the \Drupal\Core\Config\Entity\DraggableListBuilder::buildrow to recognise Attribute object
      '#attributes' => ['class' => ['draggable']],
      'name' => [
        '#markup' => $config['title'],
      ],
      'description' => [
        '#markup' => $config['tooltip'],
      ],
      'operations' => [
        '#type' => 'link',
        '#title' => t('Edit'),
        '#url' => Url::fromRoute('mcapi.admin.workflow.actionedit', ['action' => $action->id()]),
      ],
      'weight' => [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $config['title']]),
        '#title_display' => 'invisible',
        '#default_value' => $config['weight'],
        '#attributes' => new Attribute(['class' => ['weight']]),
        // @todo replace with Attributes
        '#attributes' => ['class' => ['weight']],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form[] = $this->visualise();
    $form['plugins'] = [
      '#type' => 'table',
      '#caption' => $this->t('Other operations may be available to add at admin/config/system/actions'),
      '#header' => $this->buildHeader(),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#attributes' => [
        'style' => 'width:100%',
        'id' => 'actions-table'
      ],
      // '#attributes' => new Attribute($attributes)
      // @todo Attribute doesn't work according to the documentation
      // @see https://api.drupal.org/api/drupal/core!includes!common.inc/function/drupal_attach_tabledrag/8
    ];
    foreach (TransactionOperations::loadAllActions() as $action_id => $action) {
      if ($action->getPlugin() instanceof TransactionActionBase) {
        $form['plugins'][$action_id] = $this->buildRow($action);
      }
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // This is required by the interface.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues()['plugins'] as $id => $value) {
      $action = Action::load($id);
      $plugin = $action->getPlugin();
      $config = $plugin->getConfiguration();
      $config['weight'] = $value['weight'];
      $plugin->setConfiguration($config);
      $action->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_draggable_plugin_list';
  }

  /**
   * {@inheritdoc}
   */
  private function visualise() {
    foreach (Type::loadMultiple() as $info) {
      $types[] = '<dt>' . $info->label . '</dt><dd>' . $info->description . '</dd>';
    }
    $renderable['types'] = [
      '#type' => 'container',
      '#attributes' => new Attribute(['style' => 'display:inline-block; vertical-align:top;']),
      'title' => [
        '#markup' => "<h4>" . $this->t('Transaction types') . "</h4>",
      ],
      'states' => [
        '#markup' => "<dl>" . implode("\n\t", $types) . '</dl>',
      ],
    ];
    foreach (State::loadMultiple() as $info) {
      $states[] = '<dt>' . $info->label . '</dt><dd>' . $info->description . '</dd>';
    }
    $renderable['states'] = [
      '#type' => 'container',
      '#attributes' => new Attribute(['style' => 'display:inline-block; margin-left:5em; vertical-align:top;']),
      'title' => [
        '#markup' => "<h4>" . $this->t('Workflow states') . "</h4>",
      ],
      'states' => [
        '#markup' => "<dl>" . implode("\n\t", $states) . '</dl>',
      ],
    ];
    return $renderable;
  }

}
