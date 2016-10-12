<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Form builder for multiple payments between one and many wallets.
 */
class MassPay extends ContentEntityForm {

  protected $keyValue;
  protected $mailManager;
  protected $direction;

  /**
   * Constructor
   *
   * @param EntityManagerInterface $entity_manager
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param KeyValueFactory $key_value
   * @param MailManagerInterface $mail_manager
   * @param RouteMatchInterface $route_match
   */
  public function __construct($entity_manager, EntityTypeManagerInterface $entity_type_manager, KeyValueFactory $key_value, MailManagerInterface $mail_manager, RouteMatchInterface $route_match) {
    // @todo deprecated
    parent::__construct($entity_manager);
    $this->setEntityTypeManager($entity_type_manager);
    $this->keyValue = $key_value->get('masspay');
    $this->mailManager = $mail_manager;
    $this->direction = $route_match->getRouteObject()->getOption('direction');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('plugin.manager.mail'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form = [
      'submit' => [
        '#type' => 'submit',
        '#weight' => 50,
      ],
    ];
    if (empty($form_state->get('validated_transactions'))) {
      // We have to mimic some part of the normal entity form preparation.
      // @todo on the rebuilt form we need to make a default entity.
      // But how to get the submitted values from $form_state?
      // $this->entity = Transaction::create([]);
      // $form = parent::form($form, $form_state);.
      $form['submit']['#value'] = $this->t('Preview');
      if (empty($form_state->get('confirmed'))) {
        $this->step1($form, $form_state);
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

  /**
   * {@inheritdoc}
   */
  public function step1(array &$form, FormStateInterface $form_state) {
    $display = $this->getFormDisplay($form_state);
    $form['#parents'] = [];
    // Borrowed from Drupal\Core\Entity\Entity\EntityFormDisplay but without
    // making the weights so hard to alter
    foreach ($display->getComponents() as $name => $options) {
      if ($widget = $display->getRenderer($name)) {
        $items = $this->entity->get($name);
        $items->filterEmptyItems();
        $form[$name] = $widget->form($items, $form, $form_state);
        $form[$name]['#access'] = $items->access('edit');

        // Assign the correct weight. This duplicates the reordering done in
        // processForm(), but is needed for other forms calling this method
        // directly.
        $form[$name]['#weight'] = $options['weight'];
      }
    }

    // $form is now the default transaction entity form, but the element weights
    // will be imposed after form_alter. Normally we would create our own
    // formDisplay, but this clashes with the mcapi_forms module.
    // So we carry on adapting the form from admin/accounting/transactions/form-display/default

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
      // @todo start with nothing selected; force the user to choose something.
      '#options' => $mode_options,
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
      '#weight' => 100
    ];
    $form['worth'] = [
      '#title' => $this->t('Worth'),
      '#type' => 'worths_form',
      '#default_value' => NULL,
    ];

    if ($this->direction == '12many') {
      $this->one2many($form, $form_state);
    }
    else {
      $this->many21($form, $form_state);
    }

    $mail_defaults = $this->keyValue->get('default');
    $form['notification'] = [
      '#title' => $this->t('Notify all parties', [], array('context' => 'accounting')),
      // @todo decide whether to put rules in a different module
      '#description' => $this->moduleHandler->moduleExists('rules') ?
      $this->t('N.B. Ensure this mail does not clash with mails sent by the rules module.') : '',
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#weight' => 20,
      'subject' => [
        '#title' => $this->t('Subject'),
        '#type' => 'textfield',
        // This needs to be stored per-exchange.
        '#default_value' => $mail_defaults['subject'],
      ],
      'body' => [
        '#title' => $this->t('Message'),
        // @todo the tokens?
        '#description' => $this->t('The following tokens are available: [user:name]'),
        '#type' => 'textarea',
        '#default_value' => $mail_defaults['body'],
        '#weight' => 1,
      ],
    ];
  }

  function many21(&$form, FormStateInterface $form_state) {
    if (empty($form_state->get('confirmed'))) {
      $form['payee']['#title'] = $this->t('The one payee');
      $form['payee']['#weight'] = 1;
      $form['mode']['#weight'] = 2;
      $form['payer']['#weight'] = 3;
      $form['worth']['#weight'] = 4;
      unset($form['payee']['#selection_settings']);
      $form['payer']['#title'] = $this->t('The many');
      $form['payer']['#tags'] = TRUE;
      $form['mode']['#title'] = $this->t('Will receive from');
    }
  }

  function one2many(&$form, FormStateInterface $form_state) {
    if (empty($form_state->get('confirmed'))) {
      $form['payer']['#weight'] = 1;
      $form['mode']['#weight'] = 2;
      $form['payee']['#weight'] = 3;
      $form['worth']['#weight'] = 4;
      $form['description']['#weight'] = 5;
      $form['payer']['#title'] = $this->t('The one payer');
      unset($form['payer']['#selection_settings']);
      $form['payee']['#title'] = $this->t('The many');
      $form['payee']['#tags'] = TRUE;
      $form['mode']['#title'] = $this->t('Will pay');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return;
    }
    // Only validate step 1.
    if (empty($form_state->get('validated_transactions'))) {
      // Buildentity fails without this, but not so in NodeFormController.
      $form_state->cleanValues();

      $form_state->setValue('creator', $this->currentUser()->id());
      $form_state->setValue('type', 'mass');
      $values = $form_state->getValues();
      $transactions = [];
      foreach ((array) $form_state->getValue('payer') as $payer) {
        foreach ((array) $form_state->getValue('payee') as $payee) {
          if ($payer == $payee) {
            continue;
          }
          $wallets[] = $values['payer'] = is_array($payer) ? $payer['target_id'] : $payer;
          $wallets[] = $values['payee'] = is_array($payee) ? $payee['target_id'] : $payee;
          $transactions[] = Transaction::create($values);
        }
      }

      foreach ($transactions as $transaction) {
        // We do NOT add children.
        $violations = $transaction->validate();
        foreach ($violations as $violation) {
          $form_state->setErrorByName($violation->getpropertypath(), $violation->getMessage());
        }
      }
      // @todo update to d8
      // drupal_set_title(t('Are you sure?'));
      $form_state->set('validated_transactions', $transactions);
      // Mail the owners of these.
      $form_state->set('wallets', array_unique($wallets));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // @todo how do we inject stuff into forms?
    $this->keyValue->set('default', ['subject' => $values['subject'], 'body' => $values['body']]);

    // @todo what does this mean?
    if (!isset($values['op'])) {
      $form_state->setRebuild(TRUE);
      return;
    }

    $main_transaction = array_shift($form_state->get('validated_transactions'));
    $main_transaction->children = $form_state->get('validated_transactions');
    $main_transaction->save();

    // @todo make sure this is queueing
    $params['subject'] = $values['subject'];
    $params['body'] = $values['body'];
    $params['serial'] = $main_transaction->serial->value;
    foreach (Wallet::loadMultiple($form_state->get('wallets')) as $wallet) {
      $owner = $wallet->getOwner();
      $params['recipient_id'] = $owner->id();
      $this->mailManager->mail('mcapi', 'mass', $owner->getEmail(), user_preferred_langcode($owner), $params);
    }
    // Go to the transaction certificate.
    $form_state->setRedirect(
      'entity.mcapi_transaction.canonical',
      ['mcapi_transaction' => $main_transaction->serial->value]
    );

    $this->logger('mcapi')->notice(
      'User @uid created @num mass transactions #@serial',
      [
        '@uid' => $this->currentUser()->id(),
        '@count' => count($form_state->get('validated_transactions')),
        '@serial' => $this->entity->serial->value,
      ]
    );
  }

}
