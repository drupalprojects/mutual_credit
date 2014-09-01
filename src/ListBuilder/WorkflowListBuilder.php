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
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Entity\State;

/**
 * Displays the workflow page in the management menu admin/accounting/workflow
 */
class WorkflowListBuilder extends ControllerBase implements FormInterface {

  public function buildHeader() {
    return array(
      'name' => t('Transition'),
      'description' => t('Description'),
      'operations' => t('Link'),
      'flip' => '',
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
        '#markup' => $this->l($this->t('Settings'), 'mcapi.workflow_settings', array('transition' => $id))
      ),
      'flip' => array(
        '#markup' => l(t('Disable'), 'admin/accounting/workflow/'. $id .'/flip'),
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
  public function disabledRow($transition) {
    $id = $transition->getPluginId();

    $config = $transition->getConfiguration();
    return array(
      '#weight' => 100,
      '#attributes' => array('class' => array('draggable')),
      'name' => array(
        '#markup' => $config['title']
      ),
      'description' => array(
        '#markup' => $transition->description
      ),
      'operations' => array(
        '#markup' => $this->t('Disabled'),
      ),
      'flip' => array(
        '#markup' => l(t('Enable'), 'admin/accounting/workflow/'. $id .'/flip')
      ),
      'weight' => array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $transition->label)),
        '#title_display' => 'invisible',
        '#default_value' => 100,
        '#attributes' => array('class' => array('weight')),
      ),
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
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
    foreach (\Drupal::service('mcapi.transitions')->all() as $id => $plugin) {
      if ($id == 'create') continue;
      if ($plugin->getConfiguration('status')) {
        $form['plugins'][$id] = $this->buildRow($plugin);
      }
      else {
        //TODO put a submit button here
        $form['plugins'][$id] = $this->disabledRow($plugin);
      }
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

  function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['plugins'] as $id => $value) {
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
    foreach (State::loadMultiple() as $id => $info) {
      $states[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $output .= "\n<dl>".implode("\n", $states) . "</dl>\n";

    $output .= "\n<h4>".t('Types')."</h4>\n";
    foreach (Type::loadMultiple() as $type => $info) {
      $types[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $output .= "\n<dl>".implode("\n", $types) . "</dl>\n";

    return array('#markup' => $output);
  }
}
