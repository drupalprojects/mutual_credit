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
    return parent::accessOp($transaction, $account);
  }

  /**
   * {inheritdoc}
   */
  public function form(array &$form, TransactionInterface $transaction) {
    $display = EntityFormDisplay::collectRenderDisplay($transaction, 'admin');
    $form['#parents'] = [];//this is needed otherwise the nested entity API fields collide
    foreach (array_filter($this->configuration['fields']) as $name) {
      //see EntityFormDisplay::bulidForm but we just want certain fields
      if ($widget = $display->getRenderer($name)) {
        $items = $transaction->get($name);
        $items->filterEmptyItems();
        $form[$name] = $widget->form($items, $form, new Formstate());
        $form[$name]['#access'] = $items->access('edit');
        $options = $display->getComponent($name);
        $form[$name]['#weight'] = $options['weight'];
      }
    }
    //assigns weights and hides extra fields.
    $form['#process'][] = array($display, 'processForm');
  }

  /**
   * {inheritdoc}
   */
  public function transitionSettings(array $form, FormStateInterface $form_state) {
    $fields = ['payer', 'payee', 'created', 'description', 'worth'];
    //drupal_set_message("More work needs to be done to make the field API fields, including 'worth', editable");
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
  public function execute(TransactionInterface $transaction, array $values) {

    //run the hooks and save the transaction
    $this->baseExecute($transaction, $values);

    //@todo make this string configurable
    return ['#markup' => 'The transaction has been re-saved!'];
  }

}
