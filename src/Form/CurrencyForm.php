<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\CurrencyFormController.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Component\Plugin\PluginManagerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CurrencyForm extends EntityForm {

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
      '#weight' => 0,
    );

    $form['id'] = array(
      '#type' => 'value',
      '#value' => $currency->id()
    );

    //get all users in the exchanges that this currency is in.
    //which exchanges reference this currency?
    $exchange_ids = db_select('mcapi_exchange__currencies', 'c')
      ->fields('c', array('entity_id'))
      ->condition('currencies_target_id', $this->entity->id())
      ->execute()->fetchCol();
    //now get all the members of those exchanges who have manage permission
    //would be great if we could somehow just feed into a entity_reference widget here
    $roles = user_roles(TRUE, 'manage own exchanges');
    $options = array(1 => user_load(1)->label());
    if ($roles) {
      $query = db_select('users', 'u')->fields('u', array('uid'));
      $query->join('users_roles', 'ur', 'ur.uid = u.uid');
      $query->join('user__exchanges', 'e', 'e.entity_id = u.uid');
      $uids = $query->condition('ur.rid', array_keys($roles))
        ->execute()->fetchCol();
      //wow this is getting long-winded
      foreach (entity_load_multiple('user', $uids) as $account) {
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
      '#children' => implode("\n<br /><br />\n", array(
        t('Acknowledgement currencies are abundant - they are issued whenever valued is created; they can be used as a medium of exchange but there is no guarantee of redemption.'),
        t("These are sometimes called 'social' currencies, because by encouraging and recognising volunteer service, they bind the community together."),
        t('This is the choice for all timebanking systems and most LETS.')
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
      '#children' => implode("\n<br /><br />\n", array(
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
      '#children' => implode("\n<br /><br />\n", array(
        t('Commodity currencies are scarce - the quantity is tied to the amount of a valuable commodity in a trusted warehouse.'),
        t('They are valued according to that commodity, and redeemed for that commodity, although fractional reserve rules may apply.'),
        t('Effectively the commodity is monetised, this brings confidence to the commodity, for the cost of the stuff in storage.'),
        t("This would be the choice for all 'dollar-backed' complementary currencies.")
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
      '#element_validate' => array(array($this, 'validate_format')),
      '#max_length' => 16,
      '#size' => 10,
    );

    $serials = $this->entity->transactions(array('curr_id' => $currency->id(), 'value' => 0));
    $form['display']['zero'] = array(
      '#title' => t('Zero value display'),
      '#description' => t('Use html.') .' ',
      '#type' => 'textfield',
      '#default_value' => $currency->zero,
      //this is required if any existing transactions have zero value
      '#required' => $serials
    );
    if ($form['display']['zero']['#required']) {
      $form['display']['zero']['#description'] = t("Zero transaction already exist so this field is required");
    }
    else {
      $form['display']['zero']['#description'] = t("Leave blank to disallow zero value transactions");
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
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $currency = $this->entity;
    //find the next numerical id if this is a new currency
    if (empty($currency->id())) {
      $id = max(array_keys(entity_load_multiple('mcapi_currency'))) + 1;
      $currency->set('id', $id);
    }
    //save the currency format in a that is easier to process at runtime.
    $currency->format = $this->submit_format($currency->format);
    $status = $currency->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Currency %label has been updated.', array('%label' => $currency->label())));
    }
    else {
      drupal_set_message(t('Currency %label has been added.', array('%label' => $currency->label())));
    }

    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_currency_list'
    );
  }

  /**
   * element validation callback
   */
  function validate_format(&$element, &$form_state) {
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
    //of the last value of $combo is empty remove it.
    if (end($combo) == '')array_pop($combo);
    return $combo;
  }
}
