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

  private $editform;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    ////this bit isn't working...

    return \Drupal::formBuilder()->getForm(
      new \Drupal\mcapi_1stparty\Form\FirstPartyTransactionForm(\Drupal::EntityManager(), $this->editform)
      //$this->editform
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {

    return FALSE;

    //the block is available if the current user is in the exchange of the block
    $this->editform = entity_load('1stparty_editform', $this->configuration['1stparty_form_id']);

    if ($exchange_id = $this->editform->get('exchange')) {
      return entity_load('mcapi_exchange', $exchange_id)->member();
    }
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
    foreach (entity_load_multiple_by_properties('1stparty_editform', array('exchange' => '')) as $id => $editform) {
      $options[$id] = $editform->label();
    }
    $form = array(
      '1stparty_form_id' => array(
       	'#title' => t('Form to use'),
        '#description' => t('Choose from all the firstparty forms which are not specific to one exchange'),
        '#type' => 'select',
        '#options' => $options,
        '#default' => $this->configuration['1stparty_form_id']
      )
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['1stparty_form_id'] = $form_state['values']['1stparty_form_id'];
  }

}
