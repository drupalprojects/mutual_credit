<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
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
 *
 * @todo its not clear whether admin should be able to edit outside the
 * specified window. For now, not.
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
    $elements['period'] = [
      '#title' => t('Period'),
      '#description' => $this->t('Time after creation that transaction can be edited.'),
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('Forever'),
        '3888000' => $this->t('45 days'),
      ],
    ];
    return $elements;
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    $result = AccessResult::forbidden();
    if ($this->accessState($object, $account)) {
      if ($this->accessOp($object, $account)) {
        if ($this->configuration['period']) {
          $result = AccessResult::allowedIf($this->configuration['period'] > REQUEST_TIME);
        }
        else {
          $result = AccessResult::allowed();
        }
      }
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {

  }

}
