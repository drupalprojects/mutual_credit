<?php
/**
 * @file
 * Contains \Drupal\mcapi_tester\Controller\Generate.
 */

namespace Drupal\mcapi_tester\Controller;


use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Exchange;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Form\FormStateInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state) {

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getvalues();
    foreach (Transaction::loadMultiple() as $t) {
      mcapi_wipeslate();
    }
    foreach (Wallet::loadMultiple() as $wallet) {
      $wallet->delete();
    }
    foreach (User::loadMultiple() as $account) {
      if ($account->id() > 1)$account->delete();
    }
    foreach (Exchange::loadMultiple() as $exchange) {
      if ($exchange->id() > 1)$exchange->delete();
    }

    foreach (currency::loadMultiple() as $currency) {
      if ($currency->id() <> 1)$currency->delete();
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
      $currency = Currency::create($props);
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
        'currencies' => array(
          array('target_id' => $currencies ? array_shift($currencies) : 1)
        )
      );
      $exchange = Exchange::create($props);
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
        $exchanges = array_keys(Exchange::loadMultiple());
      }
      for ($i = 2; $i < $values['users'] + 2; $i++) {
        $props = array(
          'name' => $first[rand(1, 26)] .' '.$last[rand(1, 26)],
          'mail' => (REQUEST_TIME -$i) .'@mutualcredit.org',
          'roles' => array(),
          'created' => strtotime("-$i days"),
          'exchanges' => array(
            array('target_id' => next($exchanges) ? : reset($exchanges))
          )
        );
        $account = User::create($props);
        $account->save();
        $new_uids[] = $account->id();
        //make the user the owner of their exchange, so in the end only the last created user owns the exchange they are in.
        db_query(
          "UPDATE {mcapi_exchange} SET uid = :uid WHERE id = ".$props['exchanges'][0]['target_id'],
          array(':uid' => $account->id())
        );
      }
      \Drupal::config('mcapi.wallets')->set('autoadd', 1);
    }
    if (!$values['transactions']) return;

    $wallet_query = db_select('mcapi_wallet', 'w')
    ->fields('w', array('wid'))
    ->condition('entity_type', 'user');
    if (count($new_uids)) {
      $wallet_query->condition('pid', $new_uids);
    }
    $wids = $wallet_query->execute()->fetchCol();

    for ($i = 0; $i < $values['transactions']; $i++) {
      if (!count($currencies)) {
        $currencies = Currency::loadMultiple();
      }
      shuffle($currencies);//this loses the keys
      shuffle($wids);
      $props = array(
        'payer' => reset($wids),
        'payee' => next($wids),
        'worths' => array(
          1 => array(
            'curr_id' => 1,
            'value' => rand(100, 5000)
          )
        ),
        'description' => 'test transaction',
        'created' => REQUEST_TIME - rand(1, 25*3600*100)
        //'exchange' => $exchange->id(),determined during validation
      );
      $transaction = Transaction::create($props);
      $transaction->validate();
      $transaction->save();
    }
  }

}
