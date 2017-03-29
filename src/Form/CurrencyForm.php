<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\CurrencyInterface;

/**
 * Builder for currency entity form.
 */
class CurrencyForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $currency = $this->entity;

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of currency'),
      '#description' => $this->t('Use the plural'),
      '#default_value' => $currency->label(),
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
      '#weight' => 0,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description of the currency'),
      '#description' => $this->t('Why is this currency credible? Who guarantees it? What is its value? What is its story?'),
      '#default_value' => $currency->description,
      '#weight' => 1,
    ];

    if ($currency->id()) {
      $form['id'] = [
        '#type' => 'value',
        '#value' => $currency->id(),
      ];
    }
    else {
      $form['id'] = [
        '#type' => 'machine_name',
      // This is the max length of the worth->curr_id column.
        '#maxlength' => 8,
        '#machine_name' => [
          'exists' => '\Drupal\mcapi\Entity\Currency::load',
          'source' => ['name'],
        ],
        '#description' => $this->t('A unique machine-readable name for this Currency. It must only contain lowercase letters, numbers, and underscores.'),
      ];
    }
    if (user_roles(TRUE, 'manage mcapi')) {
      $currentUser = \Drupal::currentUser();
      $form['uid'] = [
        '#title' => $this->t('Comptroller'),
        '#description' => $this->t("Any user with 'Manage community accounting' role"),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#selection_handler' => 'default:user',
        '#selection_settings' => [
          'filter' => [
            // 'role' => [ROLE].
          ],
        ],
        '#tags' => FALSE,
        '#default_value' => $currency->getOwner(),
        '#access' => $currentUser->hasPermission('configure mcapi') || $currentUser->id() == $currency->getOwner()->id()
      ];
    }
    else {
      // Default option for before any roles have been defined.
      $form['uid'] = [
        '#type' => 'value',
        '#value' => 1,
      ];
    }
    $form['#attached']['library'][] = 'mcapi/currency-form';

    $form['acknowledgement'] = [
      '#type' => 'container',
    // <br /> breaks don't work here.
      '#children' => implode(" ", [
        $this->t('Acknowledgement currencies are abundant - they are usually issued to pay for something of value and are not redeemable.'),
        $this->t("These are sometimes called 'fiat' currencies and have no value in themselves."),
        $this->t('Most timebanking systems and most LETS should choose this.'),
      ]),
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => CurrencyInterface::TYPE_ACKNOWLEDGEMENT],
        ],
      ],
      '#weight' => 3,
    ];
    $form['promise'] = [
      '#type' => 'container',
      '#children' => implode(" ", [
        $this->t("Promise, or credit currencies are 'sufficient' - they are issued and redeemed as as users earn and spend."),
        $this->t('The sum of all balances of active accounts, including the reservoir account, is zero, and ideally, accounts are returned to zero before being deactivated.'),
        $this->t('To stop accounts straying too far from zero, positive and negative balance limits are often used.'),
        $this->t('This model is sometimes called mutual credit, barter, or reciprocal exchange.'),
      ]),
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => CurrencyInterface::TYPE_PROMISE],
        ],
      ],
      '#weight' => 3,
    ];
    $form['commodity'] = [
      '#type' => 'container',
      '#children' => implode(" ", [
        $this->t('Commodity currencies are limited to the quantity of a valuable commodity in storage.'),
        $this->t('They are valued according to that commodity, and redeemed for that commodity, although fractional reserve rules may apply.'),
        $this->t('Effectively the commodity is monetised, for the cost of the stuff in storage.'),
        $this->t("This would be the choice for all 'backed' complementary currencies."),
      ]),
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => CurrencyInterface::TYPE_COMMODITY],
        ],
      ],
      '#weight' => 3,
    ];

    $form['issuance'] = [
      '#title' => $this->t('Basis of issuance'),
      '#description' => $this->t('Currently only affects visualisation.'),
      '#type' => 'radios',
      '#options' => Currency::issuances(),
      '#default_value' => property_exists($currency, 'issuance') ? $currency->issuance : 'acknowledgement',
      // This should have an API function to work with other transaction
      // controllers. Disable this if transactions have already happened.
      '#disabled' => !empty($currency->transactionCount()),
      '#required' => TRUE,
      '#weight' => 4,
    ];

    $serials = $currency->isNew() ?
      0 :
      $currency->transactionCount(['worth.value' => 0]);

    $form['zero'] = [
      '#title' => $this->t('Allow zero transactions'),
      '#description' => $this->t('Enter an HMTL snippet to show when a transaction is worth zero units. Leave blank to disallow zero transactions.'),
      '#type' => 'checkbox',
      '#default_value' => $currency->zero,
      // This is required if any existing transactions have zero value.
      '#weight' => 7,
    ];
    if ($serials) {
      $form['zero']['#required'] = TRUE;
      $form['zero']['#description'] .= $this->t("Zero transaction already exist so this field is required");
    }
    $help[] = $this->t('E.g. for Hours, Minutes put Hr00:60/4mins , for dollars and cents put $0.00; for loaves put 0 loaves.');
    $help[] = $this->t('The first number is always a string of zeros showing the number of characters (powers of ten) the widget will allow.');
    $help[] = $this->t('The optional /n at the end will render the final number widget as a dropdown field showing intervals, in the example, of 15 mins.');
    $help[] = $this->t('Special effects can be accomplised with HTML & css.');
    $form['format'] = [
      '#title' => $this->t('Format'),
      '#description' => implode(' ', $help),
      '#type' => 'textfield',
      '#default_value' => implode('', $currency->format),
      '#element_validate' => [
        [$this, 'validateFormatElement'],
      ],
      '#max_length' => 32,
      '#size' => 32,
      '#weight' => 10
    ];
    $form['#attached']['library'][] = 'mcapi/currency-form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;
    $status = $currency->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Currency %label has been updated.', ['%label' => $currency->label()]));
    }
    else {
      drupal_set_message(t('Currency %label has been added.', ['%label' => $currency->label()]));
    }
    $form_state->setRedirect('entity.mcapi_currency.collection');
  }

  /**
   * Element validation callback.
   */
  public function validateFormatElement(&$element, FormStateInterface $form_state) {
    $array = self::transformFormat($element['#value']);
    if (is_array($array)) {
      $form_state->setValue('format', $array);
    }
    else {
      $form_state->setErrorByName('format', t('Bad Format'));
    }
  }

  /**
   * Explode the format string into alternating numbers and template chunks.
   *
   * @param string $string
   *   The input from the currency form 'format' field.
   *
   * @return array
   *   With values alternating string / number / string / number etc.
   */
  public static function transformFormat($string) {
    // A better regular expression would make this function much shorter
    // ($not_nums) | ([nums] | [not nums])+ | (/num)? | (not nums) ?
    $patterns = [
      '/([^9]*)(9+)([:,.])([^ ]*)(.*)$/',
      '/([^9]*)(9+)(.*)$/',
    ];
    foreach ($patterns as $pattern) {
      $parts = [];
      if (\preg_match_all($pattern, $string, $parts)){
        break;
      }
    }
    if ($parts) {
      array_shift($parts);
      $format = [];
      foreach($parts as $array) {
        $format[] = $array[0];
      }
      // Ensure the first value of the result array corresponds to a template
      // string, not a numeric string if the format string started with a number.
      if (is_numeric(substr($string, 0, 1))) {
        array_unshift($format, '');
      }
      // If the last value of $combo is empty remove it.
      if (end($format) == '') {
        array_pop($format);
      }
      return $format;
    }
  }

}
