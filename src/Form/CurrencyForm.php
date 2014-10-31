<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\CurrencyForm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class CurrencyForm extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
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
      '#weight' => 0,
    );

    if ($currency->id()) {
      $form['id'] = array(
        '#type' => 'value',
        '#value' => $currency->id()
      );
    }
    else {
      $form['id'] = array(
        '#type' => 'machine_name',
        '#maxlength' => 128,
        '#machine_name' => array(
          'exists' => '\Drupal\mcapi\Entity\Currency::load',
          'source' => array('name'),
        ),
        '#description' => $this->t('A unique machine-readable name for this Currency. It must only contain lowercase letters, numbers, and underscores.'),
      );
    }

    //get all users in the exchanges that this currency is in.
    //which exchanges reference this currency?
    $exchange_ids = db_select('mcapi_exchange__currencies', 'c')
      ->fields('c', array('entity_id'))
      ->condition('currencies_target_id', $this->entity->id())
      ->execute()->fetchCol();
    //now get all the members of those exchanges who have manage permission
    //would be great if we could somehow just feed into a entity_reference widget here
    $roles = user_roles(TRUE, 'manage own exchanges');
    $options = array(1 => User::load(1)->label());
    if ($roles) {
      $query = db_select('users', 'u')->fields('u', array('uid'));
      $query->join('users_roles', 'ur', 'ur.uid = u.uid');
      $query->join('user__exchanges', 'e', 'e.entity_id = u.uid');
      $uids = $query->condition('ur.rid', array_keys($roles))
        ->execute()->fetchCol();
      //wow this is getting long-winded
      foreach (User::loadMultiple($uids) as $account) {
        $options[$account->id()] = $account->label();
      }
    }
    $form['uid'] = array(
    	'#title' => t('Comptroller'),
      '#description' => t('The one user who can edit this currency'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $currency->getOwnerId()
    );
    $form['css'] = array(
    	'#markup' => '<style>#edit-acknowledgement, #edit-exchange, #edit-commodity{float:right;width:50%;margin-left:1em;}</style>'
    );
    $form['acknowledgement'] = array(
      '#type' => 'container',
      '#children' => implode(" ", array(//<br /> breaks don't work here
        t('Acknowledgement currencies are abundant - they are usually issued to pay for something of value and are not redeemable.'),
        t("These are sometimes called 'fiat' currencies and have no value in themselves."),
        t('Most timebanking systems and most LETS should choose this.')
      )),
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_ACKNOWLEDGEMENT)
        )
      ),
      '#weight' => 3,
    );
    $form['exchange'] = array(
      '#type' => 'container',
      '#children' => implode(" ", array(
        t("Exchange currencies are 'sufficient' - they are issued and redeemed as as users earn and spend."),
        t('The sum of all balances of active accounts, including the reservoir account, is zero, and ideally, accounts are returned to zero before being deactivated.'),
        t('To stop accounts straying too far from zero, positive and negative balance limits are often used.'),
        t('This model is sometimes called mutual credit, barter, or reciprocal exchange.'),
      )),
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_EXCHANGE)
        )
      ),
      '#weight' => 3,
    );
    $form['commodity'] = array(
      '#type' => 'container',
      '#children' => implode(" ", array(
        t('Commodity currencies are limited to the quantity of a valuable commodity in storage.'),
        t('They are valued according to that commodity, and redeemed for that commodity, although fractional reserve rules may apply.'),
        t('Effectively the commodity is monetised, for the cost of the stuff in storage.'),
        t("This would be the choice for all 'backed' complementary currencies.")
      )),
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_COMMODITY)
        )
      ),
      '#weight' => 3,
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
      //this should have an API function to work with other transaction controllers
      //disable this if transactions have already happened
      '#disabled' => (bool)$this->entity->transactions(),
      '#required' => TRUE,
      '#weight' => 4,
    );

    $unit_name = \Drupal::config('mcapi.misc')->get('ticks_name');
    $form['ticks'] = array(
    	'#title' => t('Currency value, expressed in @units', array('@units' => $unit_name)),
      '#description' => implode(' ', array(
        t('Exchange rates are not determined by a free market, but negotiated and fixed.'),
        t('@units are the base units used to convert between currencies.', array('@units' => $unit_name)),
        t('Leave blank if this currency cannot be intertraded.'))),
      '#type' => 'number',
      '#min' => 0,
      '#default_value' => $currency->ticks,
      '#weight' => 6
    );

    $form['deletion'] = array(
      '#title' => t('Deletion'),
      '#type' => 'radios',
      '#options' => array(
        //'0' => $this->t('Delete nothing but add a counter transaction'),//reverse mode
        '0' => $this->t('Transactions are permanent (but reverse transactions can be made manually)'),
    	  '1' => $this->t('keep transaction in a deleted state'),
    	  '2' => $this->t('remove completely from the database'),
      ),
      '#default_value' => $currency->deletion,
      '#weight' => 7
    );
    $form['display'] = array(
      '#title' => t('Appearance'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#weight' => 8,
    );
    $help[] = t('E.g. for Hours, Minutes put Hr00:60/4mins , for dollars and cents put $0.00; for loaves put 0 loaves.');
    $help[] = t('The first number is always a string of zeros showing the number of characters (powers of ten) the widget will allow.');
    $help[] = t('The optional /n at the end will render the final number widget as a dropdown field showing intervals, in the example, of 15 mins.');
    $form['display']['format'] = array(
      '#title' => t('Format'),
      '#description' => implode(' ', $help),
      '#type' => 'textfield',
      '#default_value' => implode('', $currency->format),
      '#element_validate' => array(
        array($this, 'validate_format')
      ),
      '#max_length' => 16,
      '#size' => 10,
    );

    $serials = $this->entity->transactions(array('curr_id' => $currency->id(), 'value' => 0));
    $form['zero'] = array(
      '#title' => t('Allow zero transactions'),
      '#type' => 'checkbox',
      '#default_value' => $currency->zero,
      //this is required if any existing transactions have zero value
      '#required' => !empty($serials)
    );
    if ($form['zero']['#required']) {
      $form['zero']['#description'] = t("Zero transaction already exist so this field is required");
    }
    else {
      $form['zero']['#description'] = t("Leave blank to disallow zero value transactions");
    }
    $form['display']['color'] = array(
    	'#title' => t('Colour'),
    	'#description' => t('Colour may be used in visualisations'),
    	'#type' => 'color',
    	'#default_value' => $currency->color,
    );
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;
    //save the currency format in a that is easier to process at runtime.
    $currency->format = $this->submit_format($currency->format);
    $status = $currency->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Currency %label has been updated.', array('%label' => $currency->label())));
    }
    else {
      drupal_set_message(t('Currency %label has been added.', array('%label' => $currency->label())));
    }

    $form_state->setRedirect('mcapi.admin_currency_list');
  }

  /**
   * element validation callback
   */
  function validate_format(&$element, FormStateInterface $form_state) {
    $nums = preg_match_all('/[0-9]+/', $element['#value'], $chars);
    if (!is_array($this->submit_format($element['#value'])) || $nums[0] != 0) {
      $this->errorHandler()->setErrorByName($element['#name'], $form_state, t('Bad Format'));
    }
  }

  /**
   * helper to explode the format string into an array of alternating numbers and template chunks
   *
   * @param string $string
   *   the input from the currency form 'format' field
   *
   * @return array
   *   with values alternating string / number / string / number etc.
   */
  function submit_format($string) {
    //a better regular expression would make this function much shorter
    //(everything until the first number) | ([numbers] | [not numbers])+ | (slash number)? | (not numbers) ?
    preg_match_all('/[0-9\/]+/', $string, $matches);
    $numbers = $matches[0];
    preg_match_all('/[^0-9\/]+/', $string, $matches);
    $chars = $matches[0];
    //Ensure the first value of the result array corresponds to a template string, not a numeric string
    if (is_numeric(substr($string, 0, 1))) {//if the format string started with a number
      array_unshift($chars, '');
    }
    foreach ($chars as $snippet) {
      $combo[] = $snippet;
      $combo[] = array_shift($numbers);
    }
    //if the last value of $combo is empty remove it.
    if (end($combo) == '') {
      array_pop($combo);
    }
    return $combo;
  }

}
