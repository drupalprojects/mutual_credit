<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Wallet.
 */

namespace Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Provides a widget to select wallets, using autocomplete
 *
 * @FormElement("select_wallet")
 */
class Wallet extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#exchanges' => array(),
      '#size' => 60,
      '#autocomplete_route_name' => 'mcapi.wallets.autocomplete',
      '#process' => array(
        array($class, 'mcapi_process_select_wallet'),
        'form_process_autocomplete',
        'ajax_process_form'
      ),
      '#pre_render' => array(
        'form_pre_render_textfield',
        array($class, 'mcapi_prerender_wallet_field')
      ),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
      '#value_callback' => array($class, 'form_type_select_wallet_value'),
      '#element_validate' => array(
        array($class, 'local_wallet_validate_id')
      ),
      '#required' => TRUE,
      '#multiple' => FALSE,
      //TODO I'm not sure how best to put in default css
      //the empty class is required to prevent an overload error in alpha7
      //'#attributes' => new Attribute(array('style' => "width:100%", 'class' => array()))
      //TODO enable this when _form_set_attributes no longer attempts to merge this attribute object with an actual array
      '#attributes' => array('style' => 'width:100%')
    );
  }

  /**
   * process callback
   */
  function mcapi_process_select_wallet($element, FormStateInterface $form_state) {
    $exchanges = $element['#exchanges'] ? : array_keys(Exchange::referenced_exchanges());
    $element['#autocomplete_route_parameters'] = array('exchanges' => implode(',', $exchanges));
    return $element;
  }

  /**
   * value callback
   * takes from the autocomplete select_wallet field and returns an integer candidate wallet id.
   */
  function form_type_select_wallet_value(&$element, $input, FormStateInterface $form_state) {
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
  function local_wallet_validate_id(&$element, FormStateInterface $form_state) {
    $message = '';
    if (is_numeric($element['#value'])) {
      $wallet = Wallet::load($element['#value']);
      if (!$wallet) {
        $message = t('Invalid wallet id: @value', array('@value' => $element['#value']));
      }
    }
    if ($message) {
      $form_state->setError($element, $message);
    }
  }

  /**
   * convert the #default value from a wallet id to the autocomplete format
   * ensure the autocomplete address is going to the right place
   */
  function mcapi_prerender_wallet_field($element) {
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