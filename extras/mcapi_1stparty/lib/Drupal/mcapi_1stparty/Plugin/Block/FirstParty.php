<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\Plugin\Block\FirstParty
 * @todo I don't know how to call up the entity form with out using the router
 */

namespace Drupal\mcapi_1stparty\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\BlockInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;


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

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm(
      new \Drupal\mcapi_1stparty\Form\FirstPartyTransactionForm(
        \Drupal::EntityManager(),
        $this->configuration['editform_id']
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $this->editform = entity_load('1stparty_editform', $this->configuration['editform_id']);

    $route_name = \Drupal::request()->attributes->get('_route');
    //the block is available if the main page is not already a transaction form
    if (substr($route_name, 0, 6) == 'mcapi.') {
      if (substr($route_name, 6, 8) == '1stparty') {
        return FALSE;
      }
      elseif($route_name == 'mcapi.transaction.op') {
        return FALSE;
      }
    }

    //and if the current user is in the exchange of the block
    if ($exchange_id = $this->editform->get('exchange')) {
      //in fact such forms do not appear as blocks because we would have to to a whole lot
      //more work for exchange managers to then manage blocks
      return entity_load('mcapi_exchange', $exchange_id)->member();
    }
    //or if the the form has no exchange set.
    return TRUE;
  }

  /**
   *
   * @param array $form
   * @param array $form_state
   * @return array
   *   the form elements
   */
  function blockForm($form, &$form_state) {
    $options = array();
    //entity_load_multiple_by_properties doesn't seem to work on empty values
    //do we'll have to iterate though
    foreach (entity_load_multiple('1stparty_editform') as $id => $editform) {
      if ($editform->exchange) continue;
      $options[$id] = $editform->label();
    }
    //die($this->configuration['editform_id']);
    $form = array(
      'editform_id' => array(
       	'#title' => t('Form to use'),
        '#description' => t('Choose from all the firstparty forms which are not specific to one exchange'),
        '#type' => 'select',
        '#options' => $options,
        //@ prevents warning coz I can't see how to prepopulate this value before the block is ever saved
        '#default' => @$this->configuration['editform_id']
      )
    );
    if (!$options) {
      $this->errorHandler()->setErrorByName('editform_id', $form_state, t('No 1st party forms are available for all exchanges'));
    }
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['editform_id'] = $form_state['values']['editform_id'];
  }

}
