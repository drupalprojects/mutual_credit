<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\MassPayBase.
 * Create a cluster of transactions, based on a single entity form
 * N.B. This could be an entity form for Transaction OR Exchange.
 * If the latter, it will be appropriately populated.
 *
 * @todo access for this form depends on having currenciesAvailableToUser as well as permission
 *
 */

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MassPayBase extends ContentEntityForm {

  protected $keyValue;
  protected $mailManager;

  /**
   * Constructs a ContentEntityForm object.
   */
  public function __construct($entity_manager, $entity_type_manager, $key_value, $mail_manager) {
    parent::__construct($entity_manager);//@todo deprecated
    $this->setEntityTypeManager($entity_type_manager);
    $this->keyValue = $key_value->get('masspay');
    $this->mailManager = $mail_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * Overrides Drupal\mcapi\Form\TransactionForm::form().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form = ['submit' => [
      '#type' => 'submit',
      '#weight' => 50
    ]];
    if (empty($form_state->get('validated_transactions'))) {
      //we have to mimic some part of the normal entity form preparation.
      //@todo on the rebuilt form we need to make a default entity.
      //But how to get the submitted values from $form_state?
      //$this->entity = Transaction::create([]);
      //$form = parent::form($form, $form_state);
      $form['submit']['#value'] = $this->t('Preview');
      if (empty($form_state->get('confirmed'))) {
        $this->step_1($form, $form_state);
      }
    }
    else {
      $viewBuilder = $this->entityTypeManager->getViewBuilder('mcapi_transaction');
      foreach ($form_state->get('validated_transactions') as $transaction) {
        $form['preview'][] = $viewBuilder->view($transaction, 'sentence');
      }
      $form['submit']['#value'] = $this->t('Confirm');
    }
    return $form;
  }

  public function step_1(array &$form, FormStateInterface $form_state) {

    $this->getFormDisplay($form_state)->buildForm($this->entity, $form, $form_state);
    unset($form['type'], $form['creator'], $form['created']);
    if (Mcapi::maxWalletsOfBundle('user', 'user') == 1) {
      $mode_options = [
        $this->t('The named users'),
        $this->t("All users except those named"),
      ];
    }
    else {
      $mode_options = [
        $this->t('The named wallets'),
        $this->t("All wallets except those named"),
      ];
    }
    $form['mode'] = [
      '#type' => 'radios',
      //@todo start with nothing selected to force the user to choose something.
      '#options' => $mode_options,
      '#weight' => '10'
    ];
    $form['payer'] = [
      '#title' => $this->t('Payer'),
      '#type' => 'wallet_entity_auto',
      '#selection_settings' => ['direction' => 'payout'],
    ];

    $form['payee'] = [
      '#title' => $this->t('Payee'),
      '#type' => 'wallet_entity_auto',
      '#selection_settings' => ['direction' => 'payin'],
    ];
    $form['description'] = [
      '#title' => $this->t('Description'),
      '#placeholder' => $this->t('What this payment is for...'),
      '#type' => 'textfield',
    ];
    $form['description']['#weight'] = 100;
    $form['worth'] = [
      '#title' => $this->t('Worth'),
      '#type' => 'worths_form',
      '#default_value' => NULL,
    ];

    $mail_defaults = $this->keyValue->get('default');
    $form['notification'] = [
      '#title' => $this->t('Notify all parties', [], array('context' => 'accounting')),
      //@todo decide whether to put rules in a different module
      '#description' => $this->moduleHandler->moduleExists('rules') ?
        $this->t('N.B. Ensure this mail does not clash with mails sent by the rules module.') : '',
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#weight' => 20,
      'subject' => [
        '#title' => $this->t('Subject'),
        '#type' => 'textfield',
        //this needs to be stored per-exchange.
        '#default_value' => $mail_defaults['subject']
      ],
      'body' => [
        '#title' => $this->t('Message'),
        //@todo the tokens?
        '#description' => $this->t('The following tokens are available:') .' [user:name]',
        '#type' => 'textarea',
        '#default_value' => $mail_defaults['body'],
        '#weight' => 1
      ]
    ];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) return;
    //only validate step 1
    if (empty($form_state->get('validated_transactions'))) {
      $form_state->cleanValues();;//without this, buildentity fails, but again, not so in nodeFormController

      $form_state->setValue('creator', $this->currentUser()->id());
      $form_state->setValue('type', 'mass');
      $values = $form_state->getValues();
      $transactions = [];
      foreach ((array)$form_state->getValue('payer') as $payer) {
        foreach ((array)$form_state->getValue('payee') as $payee) {
          if ($payer == $payee) {
            continue;
          }
          $wallets[] = $values['payer'] = is_array($payer) ? $payer['target_id'] : $payer;
          $wallets[] = $values['payee'] = is_array($payee) ? $payee['target_id'] : $payee;
          $transactions[] = Transaction::create($values);
        }
      }

      foreach ($transactions as $transaction) {
        //we do NOT add children
        $violations = $transaction->validate();
        foreach ($violations as $violation) {
          $form_state->setErrorByName($violation->getpropertypath(), $violation->getMessage());
        }
      }
      //@todo update to d8
      //drupal_set_title(t('Are you sure?'));
      $form_state->set('validated_transactions', $transactions);
      $form_state->set('wallets', array_unique($wallets));//mail the owners of these
    }
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    //@todo how do we inject stuff into forms?
    $this->keyValue->set('default', ['subject' => $values['subject'], 'body' => $values['body']]);

    if (!isset($values['op'])) {//@todo what does this mean?
      $form_state->setRebuild(TRUE);
      return;
    }

    $main_transaction = array_shift($form_state->get('validated_transactions'));
    $main_transaction->children = $form_state->get('validated_transactions');
    $main_transaction->save();

    //@todo make sure this is queueing
    $params['subject'] = $values['subject'];
    $params['body'] = $values['body'];
    $params['serial'] = $main_transaction->serial->value;
    foreach (Wallet::loadMultiple($form_state->get('wallets')) as $wallet) {
      $owner = $wallet->getOwner();
      $params['recipient_id'] = $owner->id();
      $this->mailManager->mail('mcapi', 'mass', $owner->getEmail(), user_preferred_langcode($owner), $params);
    }
    //go to the transaction certificate
    $form_state->setRedirect(
      'entity.mcapi_transaction.canonical',
      ['mcapi_transaction' => $main_transaction->serial->value]
    );

    $this->logger('mcapi')->notice(
      'User @uid created @num mass transactions #@serial',
      [
        '@uid' => $this->currentUser()->id(),
        '@count' => count($form_state->get('validated_transactions')),
        '@serial' => $this->entity->serial->value
      ]
    );
  }

  /**
   *
   * @param type $element
   * @param type $input
   * @param FormStateInterface $form_state
   * @return type
   * @deprecated
   */
  function form_type_wallet_reference_autocompletes_value(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) {
      return;
    }
    foreach (explode(', ', $input) as $val) {
      $values[] = form_type_wallet_reference_autocomplete_value($element, $val, $form_state);
    }
    return $values;
  }

}
