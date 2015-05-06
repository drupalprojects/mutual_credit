<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Edit
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Edit transition
 *
 * @Transition(
 *   id = "edit"
 * )
 */
class Edit extends TransitionBase {

  /**
   *  access callback for transaction transition 'edit'
   * is the transaction too old according to settings?
   * does the current user have permission?
   * @todo override access settings in the base configuration base form
  */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    $days = $this->configuration['window'];
    if ($transaction->created->value + 86400*$days < REQUEST_TIME) {
      return FALSE;
    }
    //because children responding to parent edits is too much of a headache
    if ($transaction->children) return FALSE;
    return parent::accessOp($transaction, $account);
  }

  /**
   * {inheritdoc}
   */
  public function form(TransactionInterface $transaction) {
    //Test generating widgets for all fields.

    $display = entity_get_form_display('mcapi_transaction', 'mcapi_transaction');
    //$entityForm;
    $form = [];
    $form_state = new FormState();
    //$transaction_form = $entityForm->setFormDisplay($display, $form_state)->form($form, $form_state);


    $transaction_form = \Drupal::service('entity.form_builder')->getForm($transaction, 'admin');
    $additional_fields = [];
    foreach (array_filter($this->configuration['fields']) as $fieldname) {
      $additional_fields[$fieldname] = $transaction_form[$fieldname];
    }

    return $additional_fields;
  }

  /**
   * {inheritdoc}
   */
  public function transitionSettings(array $form, FormStateInterface $form_state) {
    $fields = ['payer', 'payee', 'created', 'description'];
    drupal_set_message("More work needs to be done to make the field API fields, including 'worth', editable");
    $element['fields'] = [
      '#title' => t('Editable fields'),
      '#description' => t('select the fields which can be edited'),
      '#type' => 'checkboxes',
      '#options' => array_combine($fields, $fields),//this might not be the best list
      '#default_value' => $this->configuration['fields']
    ];

    $element['window'] = [
      '#title' => t('Editable window'),
      '#description' => t('Number of days after creation that the transaction can be edited'),
      '#type' => 'number',
      '#default_value' => $this->configuration['window'],
      '#min' => 0
    ];
    return $element;
  }



  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $context) {
    $values = &$context['values'];
    if ($values['created']) {
      $values['created'] = strtotime($values['created']);
    }
    foreach ($values as $fieldname => $value) {
      $transaction->set($fieldname, $value);
    }
    return ['#markup' => 'The transaction has been modified!'];
  }
  /*
   * $values looks like this
   * array (
  'payer' => '6',
  'payee' => '2',
  'created' => '2014-08-30 19:34:59 +0200',
  'categories' =>
  array (
    0 =>
    array (
      'target_id' => NULL,
    ),
  ),
  'worth' =>
  array (
    0 =>
    array (
      'curr_id' => 'cc',
      'value' => '80',
    ),
  ),
)*/

}
