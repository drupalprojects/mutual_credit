<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Action\TransactionNotification.
 */

namespace Drupal\mcapi\Plugin\Action;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Pays or charges an entity a fixed amount IF the specified currency is available.
 *
 * @Action(
 *   id = "one_off_payment",
 *   label = @Translation("Transaction Notification"),
 *   context = {
 *     "transaction" = @ContextDefinition("string",
 *       label = @Translation("Transaction"),
 *       description = @Translation("The transaction triggering the notification")
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Description"),
 *       description = @Translation("What the payment is for")
 *     ),
 *     "body" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *       description = @Translation("The transaction type, or workflow path")
 *     ),
 *     "recipients" = @ContextDefinition("string",
 *       label = @Translation("Recipient"),
 *       description = @Translation("Tokens should be available")
 *     ),
 *     "ccs" = @ContextDefinition("string",
 *       label = @Translation("CCs"),
 *       description = @Translation("Tokens should be available")
 *     )
 *   }
 * )
 */
class TransactionNotification extends RulesActionBase {//implements ContainerFactoryPluginInterface {

  function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mailManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mailManager;
  }
  
  /**
   * 
   * @param type $object
   * @param AccountInterface $account
   * @param type $return_as_object
   * 
   * @todo I'm not sure how to determine access to an action
   */
  function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    
  }
  
  function create($container) {
    $vars = parent::create($container);
    $vars[] = $container->get('mail.manager');
    return $vars;
  }
  
  /**
   * {@inheritdoc}
   * 
   * @todo
   */
  public function execute($wallet = NULL) {
    $transaction = $this->getContextValue('transaction');
    $subject = $this->getContextValue('subject');
    $body = $this->getContextValue('body');
    $recipients = $this->getContextValue('recipients');
    $ccs = $this->getContextValue('ccs');
    //replace the tokens 
    //send the mail
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'subject' => t('New transaction on [site:name]'),
      'body' => 'A transaction for [mcapi_transaction:worth] was created between [mcapi_transaction:payer] and [mcapi_transaction:payee]',
      'recipients' => '[mcapi_transaction:payee:mail]',
      'ccs' => ''
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
          'paysentity' => t('Reservoir account pays user')
      ),
      '#default_value' => $this->configuration['direction'],
    	'#weight' => $w++
    );
    $form['otherwallet'] = array(
    	'#title' => $this->t('Other wallet'),
    	'#type' => 'select_wallet',
    	'#default_value' => $this->configuration['otherwallet'],
    	'#weight' => $w++
    );

    $currencies = Currency::loadMultiple();
    $form['worth_items'] = array(
      '#title' => t('Worth'),
      '#type' => 'fieldset',
      '#name' => 'worth_items',//this helps in the fieldset validation
      '#description' => t('If either wallet cannot access a currency, an intertrading transaction will be attempted. Failre will produce a warning on screen and in the log.'),
      'worth' => array(
        //'#title' => t('Worths'),
        '#type' => 'worth',
        '#default_value' => $this->configuration['worth'],
        '#preset' => TRUE,//ensures that all currencies are rendered
      ),
    	'#weight' => $w++
    );
    $form['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
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
    foreach ($form_state->getValues() as $key => $val)
    $this->configuration['key'] = $val;
  }

}
