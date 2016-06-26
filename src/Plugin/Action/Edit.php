<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Action which leads to the entity edit form.
 *
 * @Action(
 *   id = "mcapi_transaction.edit_action",
 *   label = @Translation("Edit a transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "entity.mcapi_transaction.edit_form"
 * )
 */
class Edit extends TransactionActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    $elements['states']['erased'] = [
    // Setting #default value seems to have no effect.
      '#disabled' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {

  }

}
