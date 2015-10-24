<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\views\field\TransactionBulkForm.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\mcapi\TransactionInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Defines a transaction operations bulk form element.
 *
 * @ViewsField("mcapi_transaction_bulk_form")
 */
class TransactionBulkForm extends BulkForm {

  private $transitionManager;
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, $language_manager, $transition_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $language_manager);
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
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('mcapi.transition_manager')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    //@todo inject this if we are sure we are using it
    $this->actions = $this->transitionManager->active_names(['create', 'view']);
  }
  
  /**
   * {@inheritdoc}
   *
   * Provide a more useful title to improve the accessibility.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    parent::viewsForm($form, $form_state);

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row_index => $result) {
        $transaction = $result->_entity;
        if ($transaction instanceof TransactionInterface) {
          $form[$this->options['id']][$row_index]['#title'] = $this->t('Update transaction  %serial', array('%serial' => $transaction->serial->value));
        }
      }
    }
  }

  
  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user tried to access an action without access to it.
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 'views_form_views_form') {
      // Filter only selected checkboxes.
      $selected = array_filter($form_state->getValue($this->options['id']));
      $entities = array();
      $transition_name = $form_state->getValue('action');
      $accessController = $this->entityManager->getAccessController('mcapi_transaction');
      $count = 0;

      foreach ($selected as $bulk_form_key) {
        $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);
        // Skip execution if the user did not have access.
        if ($accessController->access($entity, $transition_name)->isForbidden()) {
          $this->drupalSetMessage($this->t('No access to execute %action on the @entity_type_label %entity_label.', [
            '%action' => $action->label(),
            '@entity_type_label' => $entity->getEntityType()->getLabel(),
            '%entity_label' => $entity->label()
          ]), 'error');
          continue;
        }

        $count++;

        $entities[$bulk_form_key] = $entity;
      }
      foreach ($entities as $entity) {
        $context = ['values' => [], 'old_state' => $entity->state->target_id];
        $this->transitionManager->getPlugin($transition_name, $entity)->execute($context);
      }
      $operation_definition = $action->getPluginDefinition();
      if (!empty($operation_definition['confirm_form_route_name'])) {
        $options = array(
          'query' => $this->getDestinationArray(),
        );
        $form_state->setRedirect($operation_definition['confirm_form_route_name'], array(), $options);
      }
      else {
        // Don't display the message unless there are some elements affected and
        // there is no confirmation form.
        $count = count(array_filter($form_state->getValue($this->options['id'])));
        if ($count) {
          drupal_set_message($this->formatPlural($count, '%action was applied to @count item.', '%action was applied to @count items.', array(
            '%action' => $action->label(),
          )));
        }
      }
    }
  }
  
  /**
   * Returns the available operations for this form.
   *
   * @param bool $filtered
   *   (optional) Whether to filter actions to selected actions.
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  protected function getBulkOptions($filtered = TRUE) {
    $options = array();
    // Filter the action list.
    drupal_set_message(implode(array_keys($this->actions)));
    drupal_set_message(implode($this->options['selected_actions']));
    foreach ($this->actions as $id => $label) {
      if ($filtered) {
        $in_selected = in_array($id, $this->options['selected_actions']);
        // If the field is configured to include only the selected actions,
        // skip actions that were not selected.
        if (($this->options['include_exclude'] == 'include') && !$in_selected) {
          continue;
        }
        // Otherwise, if the field is configured to exclude the selected
        // actions, skip actions that were selected.
        elseif (($this->options['include_exclude'] == 'exclude') && $in_selected) {
          continue;
        }
      }
      $options[$id] = $label;
    }

    return $options;
  }
  
  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No transactions selected.');
  }
  
  

}
