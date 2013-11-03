<?php

/**
 * @file
 * Definition of Drupal\mcapi\CurrencyFormController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityFormController;

class CurrencyFormController extends EntityFormController {
  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $currency = $this->entity;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of currency'),
      '#description' => t('Use the plural'),
      '#default_value' => $currency->label(),
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $currency->id(),
      '#machine_name' => array(
        'exists' => 'mcapi_currencies_load',
        'source' => array('name'),
      ),
      '#disabled' => !$currency->isNew(),
    );

    $form['acknowledgement'] = array(
      '#type' => 'container',
      '#children' => implode("\n<br /><br />\n", array(
        t('Acknowledgement currencies are abundant - they are issued whenever valued is created; they can be used as a medium of exchange but there is no guarantee of redemption.'),
        t("These are sometimes called 'social' currencies, because by encouraging and recognising volunteer service, they bind the community together."),
        t('This is the choice for all timebanking systems and most LETS.')
      )),
      '#weight' => 2,
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_ACKNOWLEDGEMENT)
        )
      ),
    );
    $form['exchange'] = array(
      '#type' => 'container',
      '#children' => implode("\n<br /><br />\n", array(
        t("Exchange currencies are 'sufficient' - they are issued and redeemed as as users earn and spend."),
        t('The sum of all balances of active accounts, including the reservoir account, is zero, and ideally, accounts are returned to zero before being deactivated.'),
        t('To stop accounts straying too far from zero, positive and negative balance limits are often used.'),
        t('This model is sometimes called mutual credit, barter, or reciprocal exchange.'),
      )),
      '#weight' => 2,
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_EXCHANGE)
        )
      )
    );
    $form['commodity'] = array(
      '#type' => 'container',
      '#children' => implode("\n<br /><br />\n", array(
        t('Commodity currencies are scarce - the quantity is tied to the amount of a valuable commodity in a trusted warehouse.'),
        t('They are valued according to that commodity, and redeemed for that commodity, although fractional reserve rules may apply.'),
        t('Effectively the commodity is monetised, this brings confidence to the commodity, for the cost of the stuff in storage.'),
        t("This would be the choice for all 'dollar-backed' complementary currencies.")
      )),
      '#weight' => 2,
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_COMMODITY)
        )
      )
    );
    $form['issuance'] = array(
      '#title' => t('Basis of issuance'),
      '#description' => t('Currently only affects visualisation.'),
      '#type' => 'radios',
      '#options' => array(
        CURRENCY_TYPE_ACKNOWLEDGEMENT => t('Acknowledgement', array(), array('context' => 'currency-type')),
        CURRENCY_TYPE_EXCHANGE => t('Exchange', array(), array('context' => 'currency-type')),
        CURRENCY_TYPE_COMMODITY => t('Backed by a commodity', array(), array('context' => 'currency-type')),
      ),
      '#default_value' => property_exists($currency, 'issuance') ? $currency->issuance : 'acknowledgement',
      '#weight' => 3,
      //this should have an API function to work with other transaction controllers
      //disable this if transactions have already happened
      '#disabled' => property_exists($currency, 'info') ?
        transaction_filter(array('currcode' => $currency->info['currcode'])) :
        FALSE
    );
    $form['uid'] = array(
      '#title' => t('Declared by'),
      '#description' => t("Choose from users with permission '@permission'", array('@permission' => t('Declare currency'))),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('declare currency'),
      '#default_value' => property_exists($currency, 'uid') ? $currency->uid : \Drupal::currentUser()->name,
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#weight' => 4,
    );
    $form['reservoir'] = array(
      '#title' => t('Reservoir account'),
      '#description' => t('Account used for issuing and taxing'),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('transact'),
      '#default_value' => property_exists($currency, 'reservoir') ? $currency->reservoir : 1,
      '#multiple' => FALSE,
      '#weight' => 4
    );
    $form['display'] = array(
      '#title' => t('Appearance'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#weight' => 5,
      '#tree' => TRUE,
    );

    $form['display']['type'] = array(
      '#title' => t('Type'),
      '#type' => 'radios',
      '#options' => array(
        //TODO wrap this in t() when finalised
        'time' => 'Time (minutes, formated by php locale)',
        'decimal' => 'Decimal (to 2 places, for now)'
      ),
      '#default_value' => $currency->display['type'],
      '#weight' => 1
    );
    $form['display']['granularity'] = array(
      '#title' => t('Granularity'),
      '#type' => 'number',
      '#max_length' => 1,
      '#size' => 1,
      '#min' => 0,
      '#max' => 8,
      '#default_value' => $currency->display['granularity'],
      '#weight' => 2
    );
    $form['display']['widget'] = array(
      '#title' => t('Widget'),
      '#type' => 'select',
      '#options' => array(
        CURRENCY_WIDGET_SINGLEFIELD => t('Cents in same field'),
        CURRENCY_WIDGET_TEXT => t('Cents in separate field'),
        CURRENCY_WIDGET_SELECT => t('Custom divisions')
      ),
      '#default_value' => $currency->display['widget'],
      '#weight' => 1
    );
    $form['display']['delimiter'] = array(
      '#title' => t('Delimiter'),
      '#description' => t('A character to go in between your integer and cents or other formatted subdivisions'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 1,
      '#default_value' => $currency->display['delimiter'],
      '#states' => array(
        'invisible' => array(
          ':input[name="display[type]"]' => array('value' => 'decimal'),
        ),
      ),
      '#weight' => 2
    );
    $form['display']['select'] = array(
      '#title' => t('Custom divisions'),
      '#description' => t('Key value pairs'),
      '#type' => 'textarea',
      '#rows' => 4,
      '#default_value' => $currency->display['options'],
      '#states' => array(
        'visible' => array(
          ':input[name="display[widget]"]' => array('value' => CURRENCY_WIDGET_SELECT),
        ),
      ),
      '#weight' => 2
    );
    $form['display']['before'] = array(
      '#title' => t('Css before'),
      '#type' => 'textfield',
      '#default_value' => $currency->display['before'],
      '#max_length' => 6,
      '#size' => 6,
      '#weight' => 4
    );
    $form['display']['after'] = array(
      '#title' => t('Css after'),
      '#type' => 'textfield',
      '#max_length' => 6,
      '#size' => 6,
      '#default_value' => $currency->display['after'],
      '#weight' => 5
    );

    $zeros = property_exists($currency, 'info') && transaction_filter(array('quantity' => 0, 'currcode' => $currency->info['currcode']));
    $form['display']['zero'] = array(
      '#title' => t('Zero value display'),
      '#description' => t('Use html.') .' ',
      '#type' => 'textfield',
      '#default_value' => $currency->display['zero'],
      '#required' => property_exists($currency, 'display') ? $zeros : FALSE,
      '#weight' => 6
    );
    if ($zeros) {
      $form['display']['zero']['#description'] = t("Zero transaction already exist so this field is required");
    }
    else {
      $form['display']['zero']['#description'] = t("Leave blank to disallow zero value transactions");
    }

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 10,
    );
    $form['access'] = array(
      '#title' => t('Currency access'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#weight' => -1,
      '#tree' => TRUE,
    );
    $weight = 0;

    $form['access']['membership'] = array(
      '#title' => t('Use the currency'),
      '#description' => t('Determine which users are permitted to use this currency'),
      '#type' => 'user_chooser_many',
      '#config' => TRUE,
      '#default_value' => property_exists($currency, 'access') ? $currency->access['membership'] : 'user_chooser_segment_perms:transact',
      '#weight' => $weight++,
    );
    $form['access']['trader_data'] = array(
      '#title' => t('View aggregated user transaction data'),
      '#description' => t("Such as users' balances, gross income, number of transactions"),
      '#type' => 'user_chooser_many',
      '#config' => TRUE,
      '#default_value' => property_exists($currency, 'access') ? $currency->access['trader_data'] : 'user_chooser_segment_perms:transact',
      '#weight' => $weight++,
    );
    $form['access']['system_data'] = array(
      '#title' => t('View aggregated system data'),
      '#description' => t('Look at currency usage stats stripped of personal information'),
      '#type' => 'user_chooser_many',
      '#config' => TRUE,
      '#default_value' => property_exists($currency, 'access') ? $currency->access['system_data'] : 'user_chooser_segment_perms:transact',
      '#weight' => $weight++,
    );
    $i = 0;
    $access_callbacks = module_invoke_all('transaction_access_callbacks');
    //These two fieldsets should REALLY use a grid of checkboxes, like on admin/people/permissions,
    //but I couldn't work out how to do it, and it might require an hook_update to convert the saved $currency objects
    $form['access_operations'] = array(
      '#title' => t('Transaction operations'),
      '#description' => t('Determine who can do what to transactions') .'. '. t('Any of the checked conditions must return TRUE'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#weight' => 2,
      '#tree' => TRUE,
    );
    foreach (transaction_operations(TRUE, FALSE) as $callback => $op_info) {
      if ($callback == 'mcapi_view') {
        continue;
      }
      if ($op_info['access form']) {
        $form['access_operations'][$callback] = $op_info['access form']($op_info, $currency);
      }
    }

    $form['view_transaction_states'] = array(
      '#title' => t('Privacy'),
      '#description' => t('Determine who can view transactions in each state.') .' '. t('Any the checked conditions must return TRUE'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#tree' => TRUE,
      '#weight' => 5
    );
    foreach (mcapi_get_states('#full') as $constant => $state) {
      $states = isset($currency->view) ? $currency->view : array();
      $form['view_transaction_states'][$constant] = array(
        '#title' => t("Transactions in state '@state'", array('@state' => $state['name'])),
        '#description' => $state['description'],
        '#type' => 'checkboxes',
        '#options' => $access_callbacks,
        '#default_value' => property_exists($currency, 'view_transaction_states') && isset($currency->view_transaction_states[$constant]) ?
           $currency->view_transaction_states[$constant] : array(current($access_callbacks)),
        '#weight' => $i++,
      );
    }

    $form['#attached'] = array(
      'css' => array(
        drupal_get_path('module', 'mcapi') . '/css/admin_currency.css',
      ),
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    //check that the reservoir account is allowed to use the currency
    $callback = 'in_'. strtok($form_state['values']['access']['membership'], ':');
    $arg = strtok(':');
    if (!function_exists($callback)) {
      form_set_error('reservoir', t('Invalid callback @callback.', array('@callback' => $callback)));
    }
    elseif (!$callback(array($arg), $form_state['values']['reservoir'])) {
      form_set_error('reservoir', t('Reservoir account does not have access to the currency!'));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $currency = $this->entity;
    foreach (array('access_operations', 'view_transaction_states') as $property) {
      foreach ($currency->{$property} as $key => $values) {
        $currency->{$property}[$key] = array_filter($values);
      }
    }

    $status = $currency->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Currency %label has been updated.', array('%label' => $currency->label())));
    }
    else {
      drupal_set_message(t('Currency %label has been added.', array('%label' => $currency->label())));
    }

    $form_state['redirect'] = 'admin/accounting/currencies';
  }

  /**
   * currency form validate callback
   * deconstruct, validate, reconstruct and set_value
   * this sorts out any leading zeros on the centiles
   */
  public function validate_divisions(array &$element, array &$form_state) {
    if ($form_state['values']['display']['divisions'] != CURRENCY_WIDGET_SELECT) {
      return;
    }
    $validated = array();
    $lines = explode("\n", $element['#value']);
    foreach (explode("\n", $element['#value']) as $line) {
      if (strpos($line, '|') === FALSE) {
        form_error($element, t('line "@val" should contain a pipe character, |', array('@val' => $line)));
      }
      list($cents, $display) = explode('|', $line);
      if (!is_numeric($cents) || !strlen($display)) {
        form_error($element,
          t("'@val' should be an integer from  0 to 99, followed directly by a pipe, |, followed directly by a word or phrase with no unusual characters",
            array('@val' => $line)
          )
        );
      }
      $validated[intval($cents)] = check_plain($display);
    }
    if (count($lines) <> count($validated)) {
      form_error($element, t('Keys must be unique in field @fieldname', array('@fieldname' => $element['#title'])));
    }
    if (count($validated) < 2) {
      form_error($element, t("There should be at least two lines in field '@fieldname'", array('@fieldname' => $element['#title'])));
    }
    $element_value = '';
    foreach ($validated as $cents => $display) {
      $element_value .= "$cents|$display\n";
    }
    form_set_value($element, trim($element_value), $form_state);
  }

  /**
   * currency form validate callback
   */
  public function validate_format(array &$element, array &$form_state) {
    if (strpos($element['#value'], '[quantity]') === FALSE) {
      form_error($element, t("Currency format must contain token '[quantity]'"));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/accounting/currencies/' . $this->entity->id() . '/delete';
  }
}
