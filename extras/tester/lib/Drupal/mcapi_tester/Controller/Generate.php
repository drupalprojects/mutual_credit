<?php
/**
 * @file
 * Contains \Drupal\mcapi_tester\Controller\Generate.
 */

namespace Drupal\mcapi_tester\Controller;


use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;

class Generate extends ConfigFormBase {

  private $autoadd;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_test_generate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {

    $this->autoadd = \Drupal::config('mcapi.wallets')->get('autoadd');

    $form['#prefix'] = 'Warning: all previous items will be deleted. Any items created together will be associated. To get a thoroughly random mix of exchanges, wallets, currencies, create them one at a time.';

    $form['currencies'] = array(
      '#title' => 'New currencies',
      '#type' => 'number',
      '#min' => 0,
      '#weight' => 1
    );

    $form['exchanges'] = array(
      '#title' => 'New exchanges',
      '#description' => 'Each with one currency',
      '#type' => 'number',
      '#min' => 0,
      '#weight' => 2
    );
    $link = l('edit', 'admin/accounting/wallets');
    $form['users'] = array(
      '#title' => t('New users'),
      '#description' => 'Each user will have their own wallet.',
      '#type' => 'number',
      '#min' => 0,
      '#weight' => 3
    );

    $form['transactions'] = array(
      '#title' => t('New transactions'),
      '#description' => 'between all wallets or all wallets just created',
      '#type' => 'number',
      '#min' => 0,
      '#weight' => 5
    );
    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = &$form_state['values'];
    foreach (entity_load_multiple('mcapi_transaction') as $t) {
      mcapi_wipeslate();
    }
    foreach (entity_load_multiple('mcapi_wallet') as $wallet) {
      $wallet->delete();
    }
    foreach (entity_load_multiple('user') as $account) {
      if ($account->id() > 1)$account->delete();
    }
    foreach (entity_load_multiple('mcapi_exchange') as $exchange) {
      if ($exchange->id() > 1)$exchange->delete();
    }

    foreach (entity_load_multiple('mcapi_currency') as $currency) {
      if ($currency->id() <> 'credunit')$currency->delete();
    }

    $currencies = array();
    for ($i = 2; $i < $values['currencies']+2; $i++) {
      $props = array(
      	'id' => 'currency_'.$i,
      	'name' => 'Currency #'.$i,
        'intertrade' => 1,
        'type' => 'time',
        'widget' => 'currency_time_single',
        'ticks' => $i
      );
      $currency = entity_create('mcapi_currency', $props);
      $currency->save();
      $currencies[$currency->id()] = $currency;
    }
    reset($currencies);

    $newexchanges = array();
    for ($i = 2; $i < $values['exchanges'] + 2; $i++) {
      $props = array(
        'name' => 'exchange_'.$i,
        'uid' => 1,
        'visibility' => 'restricted',
        'open' => 1,
        'active' => 1,
        'langcode' => 'und',
        'field_currencies' => array(
          array('target_id' => $currencies ? array_shift($currencies) : 'credunit')
        )
      );
      $exchange = entity_create('mcapi_exchange', $props);
      $exchange->save();
      $newexchanges[$exchange->id()] = $exchange;
    }
    if ($values['users']) {
      \Drupal::config('mcapi.wallets')->set('autoadd', 1);
      $first = array('Adam', 'Barry', 'Carry', 'Dave', 'Elizabeth', 'Fanny', 'Garry', 'Harry', 'Isa', 'Josephine', 'Kerry', 'Larry', 'Mathieu', 'Nancy', 'Oliver', 'Perry', 'Quentin', 'Rosy', 'Sly', 'Trudy', 'Ursula', 'Veronica', 'William', 'Xanadu', 'Yuri', 'Zoe');
      $last = array('Adams', 'Bastock', 'Critchley', 'Dearthart', 'Epstein', 'Fox', 'Guilder', 'Hornby', 'Ingrams', 'Johnson', 'Kant', 'Loafer', 'Meadows', 'Norfolk', 'Orwell', 'Philipps', 'Quarkson', 'Rottenbottom', 'Smith', 'Trotter', 'Underwood', 'Vernon', 'Wishart', 'X', 'Ypres', 'Zenithson');
      shuffle($first);
      shuffle($last);
      if ($newexchanges) {
        foreach ($newexchanges as $exchange) {
          $exchanges[] = $exchange->id();
        }
      }
      else {
        $exchanges = array_keys(entity_load_multiple('mcapi_exchange'));
      }
      for ($i = 2; $i < $values['users'] + 2; $i++) {
        $props = array(
          'name' => $first[rand(1, 26)] .' '.$last[rand(1, 26)],
          'mail' => (REQUEST_TIME -$i) .'@mutualcredit.org',
          'roles' => array(),
          'created' => strtotime("-$i days"),
          'field_exchanges' => array(
            array('target_id' => next($exchanges) ? : reset($exchanges))
          )
        );
        $account = entity_create('user', $props);
        $account->save();
        $new_uids[] = $account->id();
        //make the user the owner of their exchange, so in the end only the last created user owns the exchange they are in.
        db_query(
          "UPDATE {mcapi_exchanges} SET uid = :uid WHERE id = ".$props['field_exchanges'][0]['target_id'],
          array(':uid' => $account->id())
        );
      }
      \Drupal::config('mcapi.wallets')->set('autoadd', 1);
    }
    if (!$values['transactions']) return;

    $wallet_query = db_select('mcapi_wallets', 'w')
    ->fields('w', array('wid'))
    ->condition('entity_type', 'user');
    if (count($new_uids)) {
      $wallet_query->condition('pid', $new_uids);
    }
    $wids = $wallet_query->execute()->fetchCol();

    for ($i = 0; $i < $values['transactions']; $i++) {
      if (!count($currencies)) {
        $currencies = entity_load_multiple('mcapi_currency');
      }
      shuffle($currencies);//this loses the keys
      shuffle($wids);
      $t = array(
        'payer' => reset($wids),
        'payee' => next($wids),
        'worths' => array(
          'credunit' => array(
            'currcode' => $currencies[0]->id(),
            'value' => rand(100, 5000)
          )
        ),
        'description' => 'test transaction',
        'created' => REQUEST_TIME - rand(1, 25*3600*100)
        //'exchange' => $exchange->id(),determined during validation
      );
//      print_r($t);
      $transaction = entity_create('mcapi_transaction', $t);
      $transaction->validate();
      $transaction->save();
    }
  }

}
