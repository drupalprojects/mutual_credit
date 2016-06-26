<?php

namespace Drupal\mcapi\Form;
use Drupal\action\ActionEditForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for action edit forms.
 */
class TransactionActionEditForm extends ActionEditForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('mcapi.admin.workflow');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // I don't know why the ActionFormbase removes the delete button.
    // Putting it back here.
    $id = $this->plugin->getPluginId();

    if (!in_array($id, ['mcapi_transaction.save_action', 'mcapi_transaction.view_action'])) {
      $route_info = $this->entity->urlInfo('delete-form');
      if ($this->getRequest()->query->has('destination')) {
        $query = $route_info->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $route_info->setOption('query', $query);
      }
      $actions['delete'] = array(
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#access' => $this->entity->access('delete'),
        '#attributes' => array(
          'class' => array('button', 'button--danger'),
        ),
      );
      $actions['delete']['#url'] = $route_info;
    }

    return $actions;
  }

}
