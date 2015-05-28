<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\Plugin\Block\FirstParty
 */

namespace Drupal\mcapi_1stparty\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi_1stparty\FirstPartyTransactionForm;
use Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign;


/**
 * Provides a transaction form
 *
 * @Block(
 *   id = "mcapi_1stparty",
 *   admin_label = @Translation("Transaction form"),
 *   category = @Translation("Community Accounting")
 * )
 */
class FirstParty extends BlockBase {

  function build() {
    return \Drupal::service('entity.form_builder')
      ->getForm(
        Transaction::create(),
        $this->configuration['editform_id']
      );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = false) {
    $access = parent::access($account, TRUE);
    if ($access->allowed()) {
      $route_name = \Drupal::service('current_route_match')->getRouteName();
      //the block is available if the main page is not already a transaction form
      if (substr($route_name, 0, 14) == 'mcapi.1stparty') {
        $access = AccessResult::forbidden();
      }
      //or a transition form (including 'view' which is a transition)
      elseif($route_name == 'mcapi.transaction.op') {
        $access = AccessResult::forbidden();
      }
      //@todo check access settings of the form itself
      //$this->editform = FirstPartyFormDesign::load($this->configuration['editform_id']);
    }
    return $return_as_object ? $access : $access->isAllowed();
  }


  /**
   * {@inheritdoc}
   */
  function blockForm($form, FormStateInterface $form_state) {
    $options = [];
    foreach (FirstPartyFormDesign::loadMultiple() as $id => $editform) {
      $options[$id] = $editform->label();
    }
    $form['editform_id'] = [
      '#title' => t('Form to use'),
      '#description' => t('Choose from all the firstparty forms which are not specific to one exchange'),
      '#type' => 'select',
      '#options' => $options,
      '#default' => $this->configuration['editform_id']
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['editform_id'] = $form_state->getValues()['editform_id'];
  }

}
