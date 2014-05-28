<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WorkflowListBuilder.
 * @TODO this would like to be a draggable list,
 * but the DraggableListBuilder is designed for entities, not plugins
 */

namespace Drupal\mcapi\ListBuilder;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormInterface;

/**
 * Displays the workflow page in the management menu admin/accounting/workflow
 */
class WorkflowListBuilder extends ControllerBase implements FormInterface {

  public function buildHeader() {
    return array(
      'weight' => t('Weight'),
      'name' => t('Name'),
      'description' => t('Description'),
      'settings' => t('Link')
    );
  }

  public function render() {
    return $this->formBuilder()->getForm($this);
  }

  public function buildRow($transition) {
    $id = $transition->definition['id'];
    if ($id == 'edit')return array();
    return array(
      '#weight' => $transition->settings['weight'],
      '#attributes' => array('class' => array('draggable')),
      'name' => array(
        '#markup' => $transition->label
      ),
      'description' => array(
        '#markup' => $transition->definition['description']
      ),
      'settings' => array(
        '#markup' => $this->l($this->t('Settings'), 'mcapi.workflow_settings', array('op' => $id))
      ),
      'weight' => array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $transition->label)),
        '#title_display' => 'invisible',
        '#default_value' => $transition->settings['weight'],
        '#attributes' => array('class' => array('weight')),
      ),
    );
  }

  public function buildForm(array $form, array &$form_state) {
    $form = array();
    $form['plugins'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ),
      ),
    );
    foreach (transaction_transitions() as $id => $plugin) {
      //TODO instead of settings get transitions edit, enable, disable like in the EntityListBuilder
      $form['plugins'][$id] = $this->buildRow($plugin);
    }
    uasort($form['plugins'], array('\Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save order'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $plugins = transaction_transitions();
    foreach ($form_state['values']['plugins'] as $id => $value) {
      \Drupal::config('mcapi.transition.'.$id)
        ->set('weight', $value['weight'])
        ->save();
    }
  }

  public function getFormId() {
    return 'workflow_draggable_plugin_list';
  }
}
