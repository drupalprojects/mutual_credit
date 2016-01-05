<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Action\Levy.
 *
 * @todo how do we limit this to certain triggers?
 * @todo determine how the child transaction is added to the parent - depends on how the trigger works
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\mcapi\Element\Worth;
use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Adds a transaction to a cluster during validation phase
 *
 * @Action(
 *   id = "levy",
 *   label = @Translation("Add a levy to a new transaction"),
 *   context = {
 *     "mcapi_transaction" = @ContextDefinition("entity",
 *       label = @Translation("Transaction"),
 *       description = @Translation("The transaction to which the levy will be added")
 *     ),
 *     "rate" = @ContextDefinition("field",
 *       label = @Translation("Worth config"),
 *       description = @Translation("Amount to be levied.")
 *     ),
 *     "target" = @ContextDefinition("mcapi_wallet",
 *       label = @Translation("Partner"),
 *       description = @Translation("The wallet which give's/receives the extra payment")
 *     )
 *   }
 * )
 */
class Levy extends RulesActionBase {//implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($transaction = NULL) {
    $transaction = $this->getContextValue('mcapi_transaction');
    $worth = $this->getContextValue('worth');
    $target = $this->getContextValue('target');
    $round = $this->getContextValue('round');
    //@todo what should this output?
    //old code follows
    $values = [];
    foreach ($worth as $delta => $item) {
      //if this currency was in the prime transaction, pass it to the calculator
      $calculated = Worth::calc($item['value'], $transaction->worth->val($item['curr_id']));
      //don't save zero value auto transactions, even if the currency settings permit
      if ($rounded == mcapi_round($calculated, $item['currcode'], $this->configuration['round'] == 'up')) {
        //if a quant was returned (and there really should be, from at least one currency), add it to the $dependent
        $values['worth'][$delta] = array(
          'value' => $rounded,
          'curr_id' => $item['curr_id']
        );
      }
    }
    if (!isset($values['worth'])) return;

    switch ($this->configuration['mapping']) {
    	case 'payer>':
    	  $values['payee'] = $this->configuration['otheruser'];
    	  $values['payer'] = $transaction->payer->target_id;
    	  break;
    	case 'payee>':
    	  $values['payee'] = $this->configuration['otheruser'];
    	  $values['payer'] = $transaction->payee->target_id;
    	  break;
    	case '>payer':
    	  $values['payee'] = $transaction->payer->target_id;
    	  $values['payer'] = $this->configuration['otheruser'];
    	  break;
    	case '>payee':
    	  $values['payee'] = $transaction->payee->target_id;
    	  $values['payer'] = $this->configuration['otheruser'];
    	  break;
    }
    $values += array(
      'description' => $this->configuration['description'],
      'type' => 'auto',//@todo children should be able to have their own transaction type without setting the state
      'state' => $transaction->state->target_id
    );
    return entity_create('mcapi_transaction', $values);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'description' => '',
      'worth' => array(0 => array('curr_id' => 'cc', 'value' => '1*[q]')),
      'otherwallet' => '',
      'mapping' => 'payer>'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('At time of writing (d8 alpha 14) there is no way to trigger actions so this action is untested');
    drupal_set_message('This tool is a bit crude but it will have to do until someone can figure out how to do it with rules');
    drupal_set_message('This action ONLY goes on one the hook, and adds a dependent transaction to the cluster. with the same serial number');
    drupal_set_message('It will be easy to make this action more sophisticated');
    $w = 5;
    $form = array(
      'participants' => array(
        '#title' => t('Participants'),
        '#description' => t('Determine who will pay whom'),
        '#type' => 'fieldset',
        'mapping' => array(
          '#title' => t('Mapping'),
          '#description' => t('On a node trigger, the payer and payee will both evaluate to the node author'),
          '#type' => 'radios',
          '#options' => array(
            'payer>' => t('Payer pays other'),
            'payee>' => t('Payee pays other'),
            '>payer' => t('Other pays payer'),
            '>payee' => t('Other pays payee'),
          ),
          '#default_value' => $this->configuration['mapping']
        ),
        'otheruser' => array(
          '#title' => t('Other account'),
          '#type' => 'wallet_reference_autocomplete',
          //@todo
          '#default_value' => $this->configuration['otherwallet'],
          '#weight' => 1,
        ),
        '#weight' => $w++
      ),
      'worth_items' => array(
        '#title' => t('Worth'),
        '#description' => t('Enter a number, a percentage, or a formula using [q] for the transaction quantity.'),
        '#type' => 'fieldset',
        'round' => array(
          '#title' => t('Rounding'),
          '#type' => 'radios',
          '#options' => array('up' => t('Up'), 'down' => t('Down')),
          '#default_value' => $this->configuration['worth'],
        ),
        'worths' => array(
          '#type' => 'worth',
          '#default_value' => $this->configuration['worth'],
          '#config' => TRUE,//allow a formula
          '#weight' => 1
        )
      ),
      '#weight' => $w++
    );
    $form['description'] = array(
      '#title' => t('Transaction description'),
      '#type' => 'textfield',
      '#placeholder' => $this->t('Why this levy was added...'),
      '#default_value' => $this->configuration['description'],
      '#weight' => $w++
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();;
    foreach ($form_state->getValues() as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }


}
