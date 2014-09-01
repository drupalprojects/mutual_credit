<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Edit
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity\Entity\EntityFormDisplay;

/**
 * Edit transition
 *
 * @Transition(
 *   id = "edit",
 *   label = @Translation("Edit"),
 *   description = @Translation("Edit given fields on transactions without children"),
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Editing..."
 *   }
 * )
 */
class Edit extends Transition2Step {

  /**
   *  access callback for transaction transition 'edit'
   * is the transaction too old according to settings?
   * does the current user have permission?
  */
  public function opAccess(TransactionInterface $transaction) {

    $days = $this->configuration['window'];
    if ($transaction->created->value + 86400*$days < REQUEST_TIME) {
      return FALSE;
    }
    //because children responding to parent edits is too much of a headache
    if ($transaction->children) return FALSE;
    return parent::opAccess($transaction);
  }

  /**
   * {inheritdoc}
   */
  public function form(TransactionInterface $transaction) {
    // Test generating widgets for all fields.
    //TODO there has to be a better way than this to make a populated entity form
    $object = new \Drupal\mcapi\Form\TransactionForm(\Drupal::EntityManager());
    $object->setEntity($transaction);
    $object->setModuleHandler(\Drupal::moduleHandler());
    $form = \Drupal::formBuilder()->getForm($object);

    $additional_fields = array();
    foreach (array_filter($this->configuration['fields']) as $fieldname) {
      $additional_fields[$fieldname] = $form[$fieldname];
      $additional_fields[$fieldname]['#weight'] = 15;
    }
    //how do we set the weights? we're not using the display!!

    return $additional_fields;
  }

  /**
   * {inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    module_load_include('inc', 'mcapi');
//    $fields = array_diff(mcapi_transaction_list_tokens(), array('serial', 'state', 'type', 'creator', 'exchange'));
    //TODO these fields will work, but the fieldAPI fields won't
    $fields = array('payer', 'payee', 'created', 'description');
    drupal_set_message("More work needs to be done to make the field API fields, including 'worth', editable");
    $form['fields'] = array(
      '#title' => t('Editable fields'),
      '#description' => t('select the fields which can be edited'),
      '#type' => 'checkboxes',
      '#options' => array_combine($fields, $fields),//this might not be the best list
      '#default_value' => $this->configuration['fields']
    );

    $form['window'] = array(
      '#title' => t('Editable window'),
      '#description' => t('Number of days after creation that the transaction can be edited'),
      '#type' => 'number',
      '#default_value' => $this->configuration['window'],
      '#min' => 0
    );
    return $form;
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
    $transaction->save();//it might be nice to have a transaction edited date.
    return array('#markup' => 'The transaction has been modified!');
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
