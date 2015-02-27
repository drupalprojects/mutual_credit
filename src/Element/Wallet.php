<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Wallet.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Template\Attribute;
use Drupal\mcapi\Exchanges;

/**
 * Provides a widget to select wallets, using autocomplete
 *
 * @FormElement("select_wallet")
 */
class Wallet extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $element = parent::getInfo();
    $class = get_class($this);

    $element['#exchanges'] = [];
    $element['#autocomplete_route_name'] = 'mcapi.wallets.autocomplete';
    array_unshift($element['#process'], array($class, 'process_select_wallet'));
    $element['#prerender'][] = array($class, 'prerender_wallet_field');
    $element['#element_validate'][] = array($class, 'local_wallet_validate_id');
    $element['#required'] = TRUE;
    $element['#multiple'] = FALSE;
    //TODO this causes an error in alpha 15 which isn't all expecting attribute objects yet
    //$element['#attributes'] = new Attribute(array('style' => "width:100%"));
    return $element;
  }

  /**
   * process callback
   */
  static function process_select_wallet($element, FormStateInterface $form_state) {
    $element['#placeholder'] = t('Wallet name or #number');
    return $element;
  }

  /**
   * value callback
   * takes from the autocomplete select_wallet field and returns an integer candidate wallet id.
   */
  static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) return;
    if (is_numeric($input) && is_integer($input + 0)) {
      return $input;
    }
    //get the number after the last hash
    return substr($input, strrpos($input, '#')+1);
  }

  /**
   * element_validate callback for select_wallet
   * ensure the passed value is a wallet id, not of an intertrading wallet
   */
  static function local_wallet_validate_id(&$element, FormStateInterface $form_state) {
    $message = '';
    if (is_numeric($element['#value'])) {
      $wallet = \Drupal\mcapi\Entity\Wallet::load($element['#value']);
      if (!$wallet) {
        $message = t('Invalid wallet id: @value', array('@value' => $element['#value']));
      }
    }
    if ($message) {
      $form_state->setError($element, $message);
    }
  }

  /**
   * element_prerender callback for select_wallet
   *
   * convert the #default value from a wallet id to the autocomplete format
   * ensure the autocomplete address is going to the right place
   */
  static function prerender_wallet_field($element) {
    if (is_numeric($element['#default_value'])) {
      $wallet = Wallet::load($element['#default_value']);
      if ($wallet) {
        //this label contains the #id which is picked up by the value callback
        $element['#default_value'] = $wallet->label(NULL, FALSE);
      }
      else {
        drupal_set_message(t("Wallet @num does not exist", array('@num' => $element['#default_value'])), 'warning');
      }
    }
    return $element;
  }

}
