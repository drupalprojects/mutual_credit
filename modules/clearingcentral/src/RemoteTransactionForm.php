<?php

/**
 * @file
 * Definition of Drupal\mcapi_cc\RemoteTransactionForm.
 */

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Form\TransactionForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;

class RemoteTransactionForm extends TransactionForm {

  protected $httpClient;
  protected $direction;

  /**
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\user\PrivateTempStore $tempstore
   */
  public function __construct($entity_type_manager, $tempstore, $http_client, $request) {
    parent::__construct($entity_type_manager, $tempstore);
    $this->httpClient = $http_client;
    $this->direction = $request
      ->attributes->get('_route_object')
      ->getOptions()['parameters']['operation'];
  }

  /**
   * {@inheritdoc}
   * @todo update to entity_type.manager
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore'),
      $container->get('http_client'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $wid = \Drupal\mcapi\Exchange::intertradingWalletId();
    if ($this->direction == 'bill') {
      $this->entity->payer->target_id = $wid;
    }
    else {
      $this->entity->payee->target_id = $wid;
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['remote_exchange_id'] = [
      '#title' => $this->t('Remote exchange'),
      '#type' => 'textfield',
      '#placeholder' => 'CES0001',
      '#weight' => 3
    ];
    $form['remote_user_id'] = [
      '#type' => 'textfield',
      '#size' => 20,
    ];

    if ($this->direction == 'bill') {
      $form['remote_user_id']['#title'] = $this->t('Buyer in remote exchange');
      $form['payer']['#type'] = 'intertrading_wallet';
    }
    else {
      $form['remote_user_id']['#title'] = $this->t('Seller in remote exchange');
      $form['payee']['#type'] = 'intertrading_wallet';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @note does NOT call parent.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $transaction = $this->buildEntity($form, $form_state);
    //these values are picked up in mcapi_cc_mcapi_transaction_insert
    $transaction->remote_exchange_id = $form_state->getValue('remote_exchange_id');
    $transaction->remote_user_id = $form_state->getValue('remote_user_id');

    try {
      $outgoing = mcapi_cc_convert_outwards($transaction);
      $url = CLEARING_CENTRAL_URL.'/txinput.php?'.http_build_query($outgoing);
      $result = $this->httpClient->post($url);
    }
    catch (GuzzleHttp\Exception\RequestException $e) {
      drupal_set_message($e->getMessage());
    }
    if ($result->getStatusCode() != 200) {
      $message = t('Clearing central failed to respond.');
      \Drupal::logger('clearingcentral')->error($message);
      drupal_set_message($message, 'error');
      $form_state->setRebuild(TRUE);
      return;
    }
    //there used to be a drupalish alternative to parse_str
    parse_str($result->getBody()->getContents(), $params);

    $response_code = $params['response'];
    if ($response_code != CEN_SUCCESS) {
      drupal_set_message(clearingcentral_lookup_response($response_code), 'error');
      $form_state->setRebuild(TRUE);
      return;
    }
    $this->updateChangedTime($this->entity);

    $transaction->save();
    $form_state->setRedirect('entity.user.canonical', ['user' => \Drupal::currentUser()->id()]);
  }

}

