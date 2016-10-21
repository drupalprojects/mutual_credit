<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Form builder for multiple payments between one and many wallets.
 */
class MassPay extends ContentEntityForm {

  const MASSINCLUDE = 0;
  const MASSEXCLUDE = 1;

  /**
   * @var Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Either '12many' or 'many21'
   * @var string
   */
  protected $direction;

  /**
   * Mail template
   * @var array
   */
  protected $configfactory;


  /**
   * Constructor
   *
   * @param EntityManagerInterface $entity_manager
   * @param MailManagerInterface $mail_manager
   * @param RouteMatchInterface $route_match
   */
  public function __construct($entity_manager, MailManagerInterface $mail_manager, RouteMatchInterface $route_match, $config_factory) {
    // @todo deprecated
    parent::__construct($entity_manager);
    $this->mailManager = $mail_manager;
    $this->direction = $route_match->getRouteObject()->getOption('direction');
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('current_route_match'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->init($form_state);
    $form['actions'] = $this->actionsElement($form, $form_state);

    if (empty($form_state->get('wallets'))) {
      if (empty($form_state->get('confirmed'))) {
        $this->step1($form, $form_state);
      }
      $form['actions']['submit']['#value'] = $this->t('Preview');
    }
    else {
      $form['preview'][] = $this->entityTypeManager
        ->getViewBuilder('mcapi_transaction')
        ->viewMultiple($this->entity->flatten(), 'sentence');
      $form['actions']['submit']['#value'] = $this->t('Confirm');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function step1(array &$form, FormStateInterface $form_state) {
    $display = $this->getFormDisplay($form_state);
    $form['#parents'] = [];

    // Build the default transaction form
    foreach ($display->getComponents() as $name => $options) {
      if ($widget = $display->getRenderer($name)) {
        if($name == PAYER_FIELDNAME && $this->direction == '12many') {
          $widget->forceMultipleValues();
        }
        elseif($name == PAYEE_FIELDNAME && $this->direction == 'many21') {
          $widget->forceMultipleValues();
        }
        $items = $this->entity->get($name);
        $items->filterEmptyItems();
        $form[$name] = $widget->form($items, $form, $form_state);
        $form[$name]['#access'] = $items->access('edit');
      }
    }
    // Don't restrict wallets by payin/payout settings
    $form_state->set('restrictWallets', FALSE);
    unset($form['type'], $form['creator'], $form['created']);
    $form['description']['#weight'] = 5;

    $form['mode'] = [
      '#type' => 'radios',
    ];
    if (Mcapi::maxWalletsOfBundle('user', 'user') == 1) {
      $form['mode']['#options'] = [
        $this->t('The named users'),
        $this->t("All users except those named"),
      ];
    }
    else {
      $form['mode']['#options'] = [
        SELF::MASSINCLUDE => $this->t('The named wallets'),
        SELF::MASSEXCLUDE => $this->t("All wallets except those named"),
      ];
    }
    if (empty($form_state->get('confirmed'))) {
      if ($this->direction == '12many') {
        $this->one2many($form, $form_state);
      }
      else {
        $this->many21($form, $form_state);
      }
    }
    unset($form[PAYEE_FIELDNAME]['widget']['target_id']['#description']);
    unset($form[PAYER_FIELDNAME]['widget']['target_id']['#description']);

    $mail_setting  = $this->configFactory->get('mcapi.settings')->get('masspay_mail');
    //@todo use rules for this
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
        '#default_value' => $mail_setting['subject'],
      ],
      'body' => [
        '#title' => $this->t('Message'),
        // @todo the tokens?
        '#description' => $this->t('The following tokens are available: [user:name]'),
        '#type' => 'textarea',
        '#default_value' => $mail_setting['body'],
        '#weight' => 1,
      ],
    ];
  }

  function many21(&$form, FormStateInterface $form_state) {
    $form[PAYEE_FIELDNAME]['widget']['target_id']['#title'] = $this->t('The one payee');
    $form[PAYER_FIELDNAME]['widget']['target_id']['#title'] = $this->t('The many');
    //$form[PAYER_FIELDNAME]['widget'][0]['target_id']['#multiple'] = TRUE;
    $form[PAYEE_FIELDNAME]['#weight'] = 1;
    $form['mode']['#weight'] = 2;
    $form[PAYER_FIELDNAME]['#weight'] = 3;
    $form['worth']['#weight'] = 4;
    unset($form[PAYEE_FIELDNAME]['#selection_settings']);
    $form[PAYER_FIELDNAME]['#tags'] = TRUE;
    $form['mode']['#title'] = $this->t('Will receive from');
  }

  function one2many(&$form, FormStateInterface $form_state) {
    $form[PAYER_FIELDNAME]['widget']['target_id']['#title'] = $this->t('The one payer');
    $form[PAYEE_FIELDNAME]['widget']['target_id']['#title'] = $this->t('The many');
    //$form[PAYEE_FIELDNAME]['widget'][0]['target_id']['#multiple'] = TRUE;
    $form[PAYER_FIELDNAME]['#weight'] = 1;
    $form['mode']['#weight'] = 2;
    $form[PAYEE_FIELDNAME]['#weight'] = 3;
    $form['worth']['#weight'] = 4;
    $form['description']['#weight'] = 5;
    $form[PAYEE_FIELDNAME]['#tags'] = TRUE;
    unset($form[PAYER_FIELDNAME]['#selection_settings']);
    $form['mode']['#title'] = $this->t('Will pay');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      // Only validate step 1.
      if (empty($form_state->get('wallets'))) {
        // Unlike normal one-step entity forms, save the entiry here for step 2
        $this->entity = parent::validateForm($form, $form_state);

        // We will mail the owners of these wallets
        $wids = array_unique(array_merge(
          (array)$form_state->getValue(PAYER_FIELDNAME)['target_id'],
          (array)$form_state->getValue(PAYEE_FIELDNAME)['target_id']
        ));
        $form_state->set('wallets', $wids);
        $form_state->setRebuild(TRUE);

        //store this for now coz its lost in stage 2
        $mail = [
          'subject' => $form_state->getValue('subject'),
          'body' => $form_state->getValue('body')
        ];
        $form_state->set('mail', $mail);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory->getEditable('mcapi.settings')
      ->set('masspay_mail', $form_state->get('mail'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // @todo make sure this mail is queueing
    $params = $form_state->get('mail');
    $params['serial'] = $this->entity->serial->value;
    foreach (Wallet::loadMultiple($form_state->get('wallets')) as $wallet) {
      $owner = $wallet->getOwner();
      $params['recipient_id'] = $owner->id();
      $this->mailManager->mail(
        'mcapi',
        'mass',
        $owner->getEmail(),
        $owner->getPreferredLangcode(),
        $params
      );
    }
    // Go to the transaction certificate.
    $form_state->setRedirect(
      'entity.mcapi_transaction.canonical',
      ['mcapi_transaction' => $this->entity->serial->value]
    );

    $this->logger('mcapi')->notice(
      'User @uid created @num mass transactions #@serial',
      [
        '@uid' => $this->currentUser()->id(),
        '@num' => count($this->entity->children) + 1,
        '@serial' => $this->entity->serial->value,
      ]
    );
  }

  public function buildEntity(array $form, FormStateInterface $form_state) {
    if ($form_state->get('wallets')) {
      return $this->entity;
    }
    $entity = parent::buildEntity($form, $form_state);

    if ($this->direction == '12many') {
      $one_fieldname = PAYER_FIELDNAME;
      $many_fieldname = PAYEE_FIELDNAME;
    }
    else {
      $one_fieldname = PAYEE_FIELDNAME;
      $many_fieldname = PAYER_FIELDNAME;
    }
    $one_wid = $form_state->getValue($one_fieldname)['target_id'];

    if ($form_state->getValue('mode') == SELF::MASSEXCLUDE) {
      $field_definition = $entity->get($many_fieldname)->getFieldDefinition();
      $$many_fieldname = \Drupal::service('plugin.manager.entity_reference_selection')
        ->getSelectionHandler($field_definition)
        ->inverse($form_state->getValue($many_fieldname)['target_id']);
    }
    else {
      $$many_fieldname = $form_state->getValue($many_fieldname)['target_id'];
    }
    $entity->creator->target_id = $this->currentUser()->id();
    $entity->type->target_id = 'mass';
    $transactions = $wallets = [];
    foreach ($$many_fieldname as $many) {
      if ($many == $one_wid) {
        continue;
      }
      $transaction = $entity->createDuplicate();
      $transaction->set($many_fieldname, $many);
      $transaction->set($one_fieldname, $one_wid);
      $transactions[] = $transaction;
    }
    if (empty($transactions)) {
      throw new \Exception('Problem creating transaction on MassPay form');
    }
    // Invoke all specified builders for copying form values to entity
    // properties.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($function, array($entity->getEntityTypeId(), $entity, &$form, &$form_state));
      }
    }
    $entity = array_shift($transactions);
    $entity->children = $transactions;
    return $entity;
  }


}
