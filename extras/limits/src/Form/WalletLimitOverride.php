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

class WalletLimitOverride extends FormBase {

  function __construct() {
    if ($wid = \Drupal::routeMatch()->getParameter('mcapi_wallet')) {
      $this->wallet = Wallet::load($wid);
    }
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

    //@todo put some inline css here when drupal_process_attached no longer uses deprecated _drupal_add_html_head
    //see https://api.drupal.org/api/drupal/core!includes!common.inc/function/drupal_process_attached/8
    //$form['#attached']['html_head']

    $overridden = mcapi_limits($wallet)->saved_overrides();
    //@todo can we refactor this so we don't need to call on Exchanges?
    foreach (Exchange::currenciesAvailable($wallet) as $curr_id => $currency) {
      $config = $currency->getThirdPartySettings('mcapi_limits');
      if (!$config || $config['plugin'] == 'none')
        continue;
      $defaults = mcapi_limits($wallet)->default_limits($currency);
      $limits = array_filter($defaults);
      if (array_key_exists('min', $limits)) {
        $desc[] = t('Min: !worth', array('!worth' => $currency->format($limits['min'])));
      }
      if (array_key_exists('max', $limits)) {
        $desc[] = t('Max: !worth', array('!worth' => $currency->format($limits['max'])));
      }
      if ($config['override']) {
        //for now the per-wallet override allows admin to declare absolute min and max per user.
        //the next thing would be for the override to support different plugins and settings per user.
        //this should be in the plugin
        $form[$curr_id] = array(
          '#title' => t('Values for this wallet'),
          '#description' => $desc ?
            t('Default values !values', array('!values' => implode(', ', $desc))) :
            t('No default limits are set'),
          '#type' => 'minmax',
          '#curr_id' => $curr_id,
          '#default_value' => array(
            'min' => @$overridden[$curr_id]['min'],
            'max' => @$overridden[$curr_id]['max']
          ),
          '#placeholder' => array(
            'min' => $defaults['min'],
            'max' => $defaults['max']
          )
        );
      }
      else {//currency is not overridable
        //don't show anything
        $form[$curr_id]['override'] = array(
          '#markup' => t('This currency is not overridable')
        );
      }
    }
    //@todo the currencies could be sorted by weight; v low priority!

    if (\Drupal\Core\Render\Element::children($form)) {
      $form['help'] = array(
        '#markup' => t("Leave fields blank to use the currencies' own settings") . '<br />',
        '#weight' => -1
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
        '#weight' => 10
      );
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
    ;
    $wid = $this->wallet->id();
    //clear db and rewrite
    try {
      $t = db_transaction();
      db_delete('mcapi_wallets_limits')->condition('wid', $wid)->execute();
      $q = db_insert('mcapi_wallets_limits')->fields(array('wid', 'curr_id', 'max', 'value', 'editor', 'date'));
      $values = $form_state->getValues();
      //rearrange the values so they are easier to save currency by currency
      foreach ($values as $curr_id => $minmax) {
//        unset($minmax['limits']);//here because I don't understand fully how the form values nesting works
        foreach ($minmax as $limit => $worths) {
          if (empty($worths))
            continue;
          $row = [
            'wid' => $wid,
            'curr_id' => $curr_id,
            'max' => (int) ($limit == 'max'),
            'value' => $worths[0]['value'],
            'editor' => $this->currentUser()->id(),
            'date' => REQUEST_TIME
          ];
          $q->values($row);
        }
      }
      if (isset($row))
        $q->execute();
      else
        drupal_set_message('No limits were overridden');
    }
    catch (\Exception $e) {
      $t->rollback();
      //are there security concerns about showing the user this message?
      drupal_set_message('Failed to save limit overrides: ' . $e->getMessage(), 'error');
    }
    //@todo clear the wallet cache???
    $form_state->setRedirect('entity.mcapi_wallet.canonical', array('mcapi_wallet' => $this->wallet->id()));
  }

}
