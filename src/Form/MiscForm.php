<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\MiscForm.
 *
 */
namespace Drupal\mcapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Exchange;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MiscForm extends ConfigFormBase {

  private $entityManager;
  private $moduleHandler;
  private $transactionRelativeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_misc_options_form';
  }

  public function __construct(ConfigFactoryInterface $configFactory, $module_handler, $entity_manager, $transaction_relative_manager) {
    $this->setConfigFactory($configFactory);
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->transactionRelativeManager = $transaction_relative_manager;
  }

  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity.manager'),
      $container->get('mcapi.transaction_relative_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('mcapi.settings');
    foreach (Exchange::transactionTokens(TRUE) as $token) {
      $tokens[] = "[mcapi:$token]";
    }
    $form['sentence_template'] = [
      '#title' => $this->t('Sentence view mode.'),
      '#description' => $this->t('Use the following tokens to define how the transaction will read when displayed in sentence view mode: @tokens', array('@tokens' => implode(', ', $tokens))),
      '#type' => 'textfield',
      '#default_value' => $config->get('sentence_template'),
      '#weight' => 2
    ];
    $form['mail_errors'] = [
      '#title' => $this->t('Send diagnostics to user 1 by mail'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('mail_errors'),
      '#weight' => 10
    ];
    $form['worths_delimiter'] = [
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('What characters should be used to separate values when a transaction has multiple currencies?'),
      '#type' => 'textfield',
      '#default_value' => $config->get('worths_delimiter'),
      '#weight' => 12,
      '#size' => 10,
      '#maxlength' => 10,
    ];
    $form['zero_snippet'] = [
      '#title' => $this->t('Zero snippet'),
      '#description' => $this->t("string to replace '0:00' when the currency allows zero transactions"),
      '#type' => 'textfield',
      '#default_value' => $config->get('zero_snippet'),
      '#weight' => 13,
      '#size' => 20,
      '#maxlength' => 128,
    ];
    //NB Instead of this, 'counted' could be a property of each transaction state
    //however at the moment that would involve user 1 editing the yaml files
    //because transaction states have no ui to edit them
    $form['counted'] = [
    	'#title' => $this->t('Counted transaction states'),
      '#description' => $this->t("Transactions in these states will comprise the wallet's balance"),
      '#type' => 'checkboxes',
      '#options' => mcapi_entity_label_list('mcapi_state'),
      '#default_value' => $config->get('counted'),
      '#weight' => 14,
      //these values are absolutely fixed
      TRANSACTION_STATE_FINISHED => [
    	  '#disabled' => TRUE,
        '#value' => TRUE,
      ],
      TRANSACTION_STATE_ERASED => [
    	  '#disabled' => TRUE,
        '#value' => FALSE,
      ]
    ];
    foreach ($this->transactionRelativeManager->getDefinitions() as $id => $definition) {
      $options[$id] = $definition['label'];
    }
    $form['relatives_details'] = [
      '#title' => $this->t('Transaction relatives'),
      '#description' => $this->t(
        'Check those needed to determine access to transaction operations'
      ),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#weight' => 16,
      'active_relatives' => [
        '#type' => 'checkboxes',
        '#description' => $this->t(
          'Warning: Unchecking these may make other settings unviable.'
        ),
        '#options' => $options,
        '#default_value' => $config->get('active_relatives'),
      ],
    ];

    $form['rebuild_mcapi_index'] = [
      '#title' => $this->t('Rebuild index'),
      '#description' => $this->t('The index table stores the transactions in an views-friendly format. It should never need rebuilding on a working system.'),
      '#type' => 'fieldset',
      '#weight' => 15,
      'button' => [
        '#type' => 'submit',
        '#value' => 'Rebuild transaction index',
        '#submit' => [
          [get_class($this), 'rebuild_mcapi_index']
        ]
      ]
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('mcapi.settings');

    $config
      ->set('sentence_template', $values['sentence_template'])
      //careful the mix_mode flag is inverted!!
      ->set('exchange_menu', !$values['exchange_menu'])
      ->set('ticks_name', $values['ticks_name'])
      ->set('zero_snippet', $values['zero_snippet'])
      ->set('worths_delimiter', $values['worths_delimiter'])
      ->set('mail_errors', $values['mail_errors'])
      ->set('counted', $values['counted'])
      ->set('active_relatives', $values['active_relatives'])
      ->save();

    parent::submitForm($form, $form_state);
    if($config->get('counted') != $values['counted']) {
      SELF::rebuild_mcapi_index($form, $form_state);
    }
  }

  static function rebuild_mcapi_index(array &$form, FormStateInterface $form_state) {
    //not sure where to put this function
    \Drupal::entityManager()->getStorage('mcapi_transaction')->indexRebuild();
    drupal_set_message("Index table is rebuilt");
    $form_state->setRedirect('system.status');
  }

  protected function getEditableConfigNames() {
    return ['mcapi.settings'];
  }

}
