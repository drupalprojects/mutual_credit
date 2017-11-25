<?php

namespace Drupal\mcapi_cc\Form;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Form\TransactionForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi_cc\ClearingCentral;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoteTransactionForm extends TransactionForm {

  protected $direction;
  protected $viewBuilder;

  public function __construct($entity_manager, $tempstore, $current_request, $current_user) {
    parent::__construct($entity_manager, $tempstore, $current_request, $current_user);
    $this->direction = $current_request
      ->attributes->get('_route_object')
      ->getOptions()['parameters']['operation'];
    $this->viewBuilder = $entity_type_manager->getViewBuilder('mcapi_transaction');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),//@todo watch ContentEntityForm::Create and update this
      $container->get('user.private_tempstore'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user')
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

    $url = \Drupal\Core\Url::fromUri(
      ClearingCentral::nidSearchUrl(),
      ['external'=> TRUE, 'attributes' => ['target' => '_blank']]
    );
    $form['remote_exchange_id'] = [
      '#title' => $this->t('Remote exchange'),
      '#type' => 'textfield',
      '#placeholder' => 'CEN0001',
      '#weight' => -1,
      '#field_suffix' => \Drupal\Core\Link::fromTextAndUrl(t('Search'), $url)->toString()
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
    //this is the confirmation page, so make the submit button final
    $form['certificate'] = $this->viewBuilder->view($form_state->get('remote_transaction'), 'certificate');
    $form['operation'] = [
      '#type' => 'value',
      '#value' => t('Send'),
    ];
    $form['message'] = [
      '#markup' => t('This cannot be undone!'),
    ];
    // Disable input validation on confirm form. It has been already validated in
    // last step.
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
    $params = clearingCentral()->send(
      $transaction,
      $form_state->getValue('outgoing'),
      $form_state->getValue('remote_exchange_id'),
      $form_state->getValue('remote_user_id')
    );

    $response_code = $params['response'];
    if ($response_code == CEN_SUCCESS) {
      $this->updateChangedTime($transaction);
      if ($transaction->outgoing) {
        $currency = Currency::load(clearingCentral($wid)->getCurrencyId());
        $transaction->worth->value = $this->backFormat($params['amount'], $currency);
        $transaction->worth->curr_id = $currency->id;
      }
      \Drupal::logger('Clearing Central')->debug('<pre>'. print_r($transaction->toArray(), 1) .'</pre>');
      $transaction->save();
      $mywid = $transaction->outgoing ? $transaction->payer->target_id : $transaction->payee->target_id;
      $form_state->setRedirect('entity.mcapi_wallet.canonical', ['mcapi_wallet' => $mywid]);
    }
    else {
      drupal_set_message($cc->response($response_code), 'error');
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  function buildEntity(array $form, FormStateInterface $form_state) {
    $transaction = parent::buildEntity($form, $form_state);
    // This transaction may have children by now.
    $transaction->type->target_id = 'remote';

    if ($form_state->getValue('outgoing')) {
      // Improvised transaction property
      $transaction->amount = $form_state->getValue('amount');
    }

    return $transaction;
  }

  /**
   * Convert from the 2 decimal places value provided by clearing central to the
   * currency's raw format
   * @param float $val
   * @param \Drupal\mcapi\Entity\Currency $currency
   */
  function backFormat($val, $currency) {
    $parts = [];
    list($parts[1], $parts[3]) = explode('.', $val);
    if (isset($currency->format_nums[3]) && $currency->format_nums[3] != 99) {
       list($sub) = explode('/', $currency->format_nums[3]);
      $sub++;
      $parts[3] *= $sub/100;
    }
    $raw = $currency->unformat($parts);
    if ($raw === 0 && !$currency->zero && $parts[3]) {
      $raw = 1;
    }
    if (!is_numeric($raw)) {
      throw new \Exception("Could not backformat '$val' in ".$currency->label());
    }
    return $raw;
  }

}

