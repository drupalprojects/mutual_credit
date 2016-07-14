<?php

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Exchange;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Form\TransactionForm;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class foro building a remote transaction form.
 */
abstract class RemoteTransactionForm extends TransactionForm {

  protected $httpClient;
  protected $direction;
  protected $exchange;
  protected $viewBuilder;
  protected $intertradingSettings;

  /**
   * Constructor.
   */
  public function __construct($entity_type_manager, $tempstore, $current_request, $current_user, $http_client, $keyValue) {
    parent::__construct($entity_type_manager, $tempstore, $current_request, $current_user);
    $this->direction = $current_request
      ->attributes->get('_route_object')
      ->getOptions()['parameters']['operation'];
    $this->httpClient = $http_client;
    // @todo get the ONE group we are in
    $this->exchange = NULL;
    $this->viewBuilder = $entity_type_manager->getViewBuilder('mcapi_transaction');
    $this->intertradingSettings = $keyValue->get('clearingcentral');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // @todo watch ContentEntityForm::Create and update this
      $container->get('entity.manager'),
      $container->get('user.private_tempstore'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user'),
      $container->get('http_client'),
      $container->get('keyvalue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    unset($form['created']);
    unset($form['creator']);
    unset($form['type']);

    $url = Url::fromUri(CLEARING_CENTRAL_URL . '/nidsearch.php', ['external' => TRUE, 'attributes' => ['target' => '_blank']]);
    $form['remote_exchange_id'] = [
      '#title' => $this->t('Remote exchange'),
      '#type' => 'textfield',
      '#placeholder' => 'CEN0001',
      '#weight' => -1,
      '#field_suffix' => Link::fromTextAndUrl(t('Search'), $url)->toString(),
    ];
    $form['remote_user_id'] = [
      '#type' => 'textfield',
      '#size' => 20,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->has('remote_transaction')) {
      parent::validateForm($form, $form_state);
      $form_state->set('remote_transaction', $this->buildEntity($form, $form_state));
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function confirmForm($form, $form_state) {
    // This is the confirmation page, so make the submit button final.
    $form['certificate'] = $this->viewBuilder->view($form_state->get('remote_transaction'), 'certificate');
    $form['operation'] = [
      '#type' => 'value',
      '#value' => t('Send'),
    ];
    $form['message'] = [
      '#markup' => t('This cannot be undone!'),
    ];
    // Disable input validation on confirm form. Was validated in last step.
    $form['#validate'] = [];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($form_state->has('remote_transaction') && !$form_state->hasAnyErrors()) {
      $actions['submit']['#value'] = $this->t('Submit to remote server');
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   *
   * @note does NOT call parent.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transaction = $form_state->get('remote_transaction');
    try {
      $outgoing = $this->prepareToSend($transaction);
      \Drupal::logger('Clearing Central')->debug('<pre>' . print_r($outgoing, 1) . '</pre>');
      $url = CLEARING_CENTRAL_URL . '/txinput.php?' . http_build_query($outgoing);
      \Drupal::logger('Clearing Central')->debug($url);
      $result = $this->httpClient->post($url);
    }
    catch (RequestException $e) {
      drupal_set_message($e->getMessage());
    }
    if ($result->getStatusCode() != 200) {
      $message = t('Clearing central failed to respond.');
      \Drupal::logger('clearingcentral')->error($message);
      drupal_set_message($message, 'error');
      $form_state->setRebuild(TRUE);
      return;
    }
    parse_str($result->getBody()->getContents(), $params);

    $response_code = $params['response'];
    if ($response_code == CEN_SUCCESS) {
      $this->updateChangedTime($transaction);
      if ($transaction->outgoing) {
        $settings = $this->intertradingSettings->get($transaction->payee->target_id);
        $currency = Currency::load($settings['curr_id']);
        $transaction->worth->value = $this->backFormat($params['amount'], $currency);
        $transaction->worth->curr_id = $currency->id;
      }
      \Drupal::logger('Clearing Central')->debug('<pre>' . print_r($transaction->toArray(), 1) . '</pre>');
      $transaction->save();
      $mywid = $transaction->outgoing ? $transaction->payer->target_id : $transaction->payee->target_id;
      $form_state->setRedirect('entity.mcapi_wallet.canonical', ['mcapi_wallet' => $mywid]);
    }
    else {
      drupal_set_message(clearingcentral_lookup_response($response_code), 'error');
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $transaction = parent::buildEntity($form, $form_state);
    // This transaction may now have children.
    $transaction->type->target_id = 'remote';
    // These values are picked up in mcapi_cc_mcapi_transaction_insert.
    $transaction->remote_exchange_id = $form_state->getValue('remote_exchange_id');
    $transaction->remote_user_id = $form_state->getValue('remote_user_id');
    $transaction->remote_user_name = $form_state->getValue('remote_user_id');
    $transaction->outgoing = $form_state->getValue('outgoing');
    if ($transaction->outgoing) {
      // This is needed later.
      $transaction->amount = $form_state->getValue('amount');
    }

    return $transaction;
  }

  /**
   * Convert an transaction entity to a Clearing central transaction array.
   *
   * @param TransactionInterface $transaction
   *   A Community Accounting transaction.
   *
   * @return array
   *   A transaction in clearing central format.
   */
  protected function prepareToSend(TransactionInterface $transaction) {
    $wid = Exchange::intertradingWalletId();
    $intertrading_settings = \Drupal::keyValue('clearingcentral')->get($wid);
    $currency = Currency::load($intertrading_settings['curr_id']);
    $cc_transaction = [
      'txid' => $transaction->uuid->value,
      'description' => $transaction->description->value,
      'outgoing' => $transaction->outgoing,
    ];
    $payer = $transaction->payer->entity;
    $payee = $transaction->payee->entity;

    if ($transaction->outgoing) {
      $cc_transaction += [
        'buyer_nid' => $intertrading_settings['login'],
        'password' => $intertrading_settings['pass'],
        'buyer_id' => $payer->id(),
        'buyer_name' => $payer->label(),
      // This is the remote id.
        'seller_nid' => $transaction->remote_exchange_id,
      // This is the remote id.
        'seller_id' => $transaction->remote_user_id,
        'amount' => $transaction->amount,
      ];
    }
    elseif ($payee->payways->value == Wallet::PAYWAY_AUTO) {
      $cc_transaction += [
        'seller_nid' => $intertrading_settings['login'],
        'password' => $intertrading_settings['pass'],
        'seller_id' => $payee->id(),
        'seller_name' => $payee->label(),
      // This is the remote id.
        'buyer_nid' => $transaction->remote_exchange_id,
      // This is the remote id.
        'buyer_id' => $transaction->remote_user_id,
        'amount' => $currency->format($transaction->worth->value, Currency::DISPLAY_PLAIN),
      ];
    }
    return $cc_transaction;
  }

  /**
   * Convert from the clearing central value to the currency's raw format.
   *
   * @param float $val
   *   Incoming clearing central value with 2 decimal places.
   * @param \Drupal\mcapi\Entity\Currency $currency
   *   The intertrading currency.
   */
  public function backFormat($val, Currency $currency) {
    $parts = [];
    list($parts[1], $parts[3]) = explode('.', $val);
    if (isset($currency->format_nums[3]) && $currency->format_nums[3] != 99) {
      list($sub) = explode('/', $currency->format_nums[3]);
      $sub++;
      $parts[3] *= $sub / 100;
    }
    $raw = $currency->unformat($parts);
    if ($raw === 0 && !$currency->zero && $parts[3]) {
      $raw = 1;
    }
    return $raw;
  }

}
