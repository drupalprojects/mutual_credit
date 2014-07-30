<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\UserStats
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;
use Drupal\mcapi\Entity\Wallet;

/**
 * Field handler to present trading stats for the user
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_userstat")
 */
class UserStats extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = array('default' => '');
    $options['stat'] = array('default' => 'balance');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['separator'] = array(
      '#title' => t('Separator between different stats'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->options['separator'],
    );
    $form['stat'] = array(
      '#title' => t('Metric'),
      '#type' => 'radios',
      '#options' => array(
        'balance' => t('Balance'),
        'gross_in' => t('Gross income'),
        'gross_out' => t('Gross expenditure'),
        'volume' => t('Volume (Gross in + gross out)'),
        'trades' => t('Balance'),
        'partners' => t('Number of trading partners'),
      ),
      '#default_value' => $this->options['stat'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $account = $this->getEntity($values);
    //shows only the first wallet
    $wid = reset(mcapi_get_wallet_ids($account));
    $exchanges = referenced_exchanges($account, TRUE);
    //TODO stats really belong to wallets, but they might be wanted per user.
    die("Drupal\mcapi\Plugin\views\field\UserStats needs to be rethought");

    //@todo make this work with the right entity_reference syntax
    $currency = reset($exchanges)->currencies->getvalue(TRUE)->entity;

    $stats = Wallet::load($wid)->getStats($currency->id());


    if (in_array($this->options['stat'], array('trades', 'partners'))) {
      return $stats[$this->options['stat']];
    }
    else return $currency->format($stats[$this->options['stat']]);

  }

}
