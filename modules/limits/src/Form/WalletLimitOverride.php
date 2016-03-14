<?php

/**
 * @file
 * Contains Drupal\mcapi_limits\Form\WalletLimitOverride
 *
 * Allow an administrator to set the per-wallet limits
 *
 */

namespace Drupal\mcapi_limits\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Exchange;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WalletLimitOverride extends FormBase {

  function __construct($routeMatch, $database) {
    if ($wid = $routeMatch->getParameter('mcapi_wallet')) {
      $this->wallet = Wallet::load($wid);
    }
    $this->database = $database;
  }

  static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'wallet_limits_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //this is tricky. We need to know all the currency that could go in the wallet.
    //to do that we have to know all the currencies in the all the active exchanges the wallets parent is in.
    $wallet = $this->wallet;
    $submit = FALSE;

    //@todo put some inline css here when drupal_process_attached no longer uses deprecated _drupal_add_html_head
    //see https://api.drupal.org/api/drupal/core!includes!common.inc/function/drupal_process_attached/8
    //$form['#attached']['html_head']

    $limiter = \Drupal\mcapi_limits\WalletLimiter::create($wallet);
    $overrides = $limiter->overrides();
    foreach ($wallet->currenciesAvailable() as $curr_id => $currency) {
      $config = $currency->getThirdPartySettings('mcapi_limits');
      if (!$config || $config['plugin'] == 'none') {
        continue;
      }
      $defaults = $limiter->defaults($currency);
      $limits = array_filter($defaults);
      if (array_key_exists('min', $limits)) {
        $desc[] = t('Min: %worth', ['%worth' => $currency->format($limits['min'])]);
      }
      if (array_key_exists('max', $limits)) {
        $desc[] = t('Max: %worth', ['%worth' => $currency->format($limits['max'])]);
      }
      if ($config['override']) {
        //for now the per-wallet override allows admin to declare absolute min and max per user.
        //the next thing would be for the override to support different plugins and settings per user.
        //this should be in the plugin
        $form[$curr_id] = [
          '#title' => $currency->label(),
          '#description' => t('Leave blank to revert to currency defaults'),
          '#type' => 'details',
          '#open' => TRUE,
          '#tree'=> TRUE,
          'min' => [
            '#title' => $this->t('Min'),
            '#type' => 'worth_form',
            '#weight' => 0,
            '#default_value' => '',
            '#placeholder' => $defaults['min'],
            '#allowed_curr_ids' => [$curr_id],
            '#config' => TRUE,
            '#minus' => TRUE
          ],
          'max' => [
            '#title' => $this->t('Max'),
            '#type' => 'worth_form',
            '#weight' => 1,
            '#default_value' => '',
            '#placeholder' => $defaults['max'],
            '#allowed_curr_ids' => [$curr_id],
            '#config' => TRUE
          ]
        ];
        if (isset($overrides[$curr_id])) {
          $vals = $overrides[$curr_id];
          if (isset($vals['min'])) {
            $form[$curr_id]['min']['#default_value'] = $vals['min']['value'];
          }
          if (isset($vals['max'])) {
            $form[$curr_id]['max']['#default_value'] = $vals['max']['value'];
          }
        }
        $submit = TRUE;
      }
    }
    if ($submit) {
      $form['help'] = [
        '#markup' => t("Leave fields blank to use the currencies' own settings") . '<br />',
        '#weight' => -1
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#weight' => 10
      ];
    }
    else {
      $form['empty']['#markup'] = t('This wallet cannot use any currencies which can be overridden.');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $wid = $this->wallet->id();
    //clear db and rewrite
    try {
      $t = db_transaction();
      $this->database
        ->delete('mcapi_wallets_limits')
        ->condition('wid', $wid)
        ->execute();
      $q = $this->database
        ->insert('mcapi_wallets_limits')
        ->fields(['wid', 'curr_id', 'max', 'value', 'editor', 'date']);
      $values = $form_state->getValues();
      //rearrange the values so they are easier to save currency by currency
      foreach ($values as $curr_id => $minmax) {
        foreach ($minmax as $limit => $worth) {
          if (!isset($worth['value'])) continue;
          $row = [
            'wid' => $wid,
            'curr_id' => $curr_id,
            'max' => (int) ($limit == 'max'),
            'value' => $worth['value'],
            'editor' => $this->currentUser()->id(),
            'date' => REQUEST_TIME
          ];
          $q->values($row);
        }
      }
      if (isset($row)) {
        $q->execute();
      }
      else {
        drupal_set_message('No limits were overridden');
      }
    }
    catch (\Exception $e) {
      $t->rollback();
      //are there security concerns about showing the user this message?
      drupal_set_message('Failed to save limit overrides: ' . $e->getMessage(), 'error');
    }
    //no need to clear the wallet I think
    $form_state->setRedirect(
      'entity.mcapi_wallet.canonical',
      ['mcapi_wallet' => $this->wallet->id()]
    );
  }

}
