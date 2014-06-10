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
      'name' => t('Name'),
      'description' => t('Description'),
      'operations' => t('Link'),
      'weight' => t('Weight'),
    );
  }

  public function render() {
    return array(
      $this->visualise(),
      $this->formBuilder()->getForm($this)
    );
  }

  public function buildRow($transition) {
    $id = $transition->getPluginId();

    $config = $transition->getConfiguration();
    return array(
      '#weight' => $config['weight'],
      '#attributes' => array('class' => array('draggable')),
      'name' => array(
        '#markup' => $config['title']
      ),
      'description' => array(
        '#markup' => $transition->description
      ),
      'operations' => array(
        '#markup' => $this->l($this->t('Settings'), 'mcapi.workflow_settings', array('op' => $id))
      ),
      'weight' => array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $transition->label)),
        '#title_display' => 'invisible',
        '#default_value' => $config['weight'],
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
      //TODO remove edit from this OR make the edit plugin work
      if ($id == 'create' || $id == 'edit') continue;//empty rows break the tabledrag.js
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

  private function visualise() {
    $output = "\n<h4>".t('States')."</h4>\n";
    foreach (entity_load_multiple('mcapi_state') as $id => $info) {
      $states[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $output .= "\n<dl>".implode("\n", $states) . "</dl>\n";

    $output .= "\n<h4>".t('Types')."</h4>\n";
    foreach (entity_load_multiple('mcapi_type') as $type => $info) {
      $types[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $output .= "\n<dl>".implode("\n", $types) . "</dl>\n";

    $output .= "\n<h4>".t('Transitions')."</h4>\n";
    return array('#markup' => $output);
  }
}
