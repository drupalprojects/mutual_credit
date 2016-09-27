<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Currency;

/**
 * Pays or charges an entity a fixed amount IF the currency is available.
 *
 * @Action(
 *   id = "one_off_payment",
 *   label = @Translation("One off payment"),
 *   context = {
 *     "description" = @ContextDefinition("field",
 *       label = @Translation("Description"),
 *       description = @Translation("What the payment is for")
 *     ),
 *     "type" = @ContextDefinition("field",
 *       label = @Translation("Type"),
 *       description = @Translation("The transaction type, or workflow path")
 *     ),
 *     "otherwallet" = @ContextDefinition("mcapi_wallet",
 *       label = @Translation("Other wallet"),
 *       description = @Translation("The wallet to trade with")
 *     )
 *   }
 * )
 */
/**
 * Implements ContainerFactoryPluginInterface {.
 */
class OneOffPayment extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($wallet = NULL) {
    $description = $this->getContextValue('description');
    $type = $this->getContextValue('type');
    $otherwallet = $this->getContextValue('otherwallet');
    debug('unused variables $description, $type, $otherwallet');
    $values = array(
      // Allow a price to be fixed on the entity using the worth field.
      'worth' => $this->configuration['worth'],
      'type' => 'auto',
      'state' => 'done',
      'description' => $this->configuration['description'],
    );
    debug('underfined variable $direction');
    if ($direction == 'entitypays') {
      $values['payee'] = $this->configuration['otherwallet'];
      $values['payer'] = $wallet->id();
    }
    else {
      $values['payee'] = $wallet->id();
      $values['payer'] = $this->configuration['otherwallet'];
    }
    try {
      $transaction = entity_create('transaction', $values);
      if ($warnings = $transaction->save()) {
        foreach ($warnings as $warning) {
          drupal_set_message($warning);
        }
      }
    }
    catch (Exception $e) {
      drupal_set_message(t('Automated transaction failed: @message', array('@message' => $e->getMessage)), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'description' => '',
      'direction' => 'entitypays',
      'worth' => array(0 => array('curr_id' => 'cc', 'value' => 0)),
      'otherwallet' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('At time of writing (d8 alpha 14) there is no way to trigger actions so this action is untested');
    $w = 0;
    $form['direction'] = array(
      '#title' => t('Direction'),
      '#type' => 'radios',
      '#options' => array(
        'entitypays' => t('User pays reservoir account'),
        'paysentity' => t('Reservoir account pays user'),
      ),
      '#default_value' => $this->configuration['direction'],
      '#weight' => $w++,
    );
    $form['otherwallet'] = array(
      '#title' => $this->t('Other wallet'),
      '#type' => 'wallet_reference_autocomplete',
      '#default_value' => $this->configuration['otherwallet'],
      '#weight' => $w++,
    );

    $currencies = Currency::loadMultiple();
    $form['worth_items'] = array(
      '#title' => t('Worth'),
      '#type' => 'fieldset',
    // This helps in the fieldset validation.
      '#name' => 'worth_items',
      'worth' => array(
        // '#title' => t('Worths'),.
        '#type' => 'worth',
        '#default_value' => $this->configuration['worth'],
    // Ensures that all currencies are rendered.
        '#preset' => TRUE,
      ),
      '#weight' => $w++,
    );
    $form['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['description'],
      '#weight' => $w++,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();;
    foreach ($form_state->getValues() as $key => $val) {
      $this->configuration['key'] = $val;
    }
  }

}
