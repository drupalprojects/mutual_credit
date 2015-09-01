<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\CurrencyForm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Currency;

class CurrencyForm extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $currency = $this->entity;

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of currency'),
      '#description' => t('Use the plural'),
      '#default_value' => $currency->label(),
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
      '#weight' => 0,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description of the currency'),
      '#description' => t('Use the plural'),
      '#default_value' => $currency->description,
      '#weight' => 1,
    ];

    if ($currency->id()) {
      $form['id'] = [
        '#type' => 'value',
        '#value' => $currency->id()
      ];
    }
    else {
      $form['id'] = [
        '#type' => 'machine_name',
        '#maxlength' => 128,
        '#machine_name' => [
          'exists' => '\Drupal\mcapi\Entity\Currency::load',
          'source' => ['name'],
        ],
        '#description' => $this->t('A unique machine-readable name for this Currency. It must only contain lowercase letters, numbers, and underscores.'),
      ];
    }
    if ($roles = user_roles(TRUE, 'manage mcapi')) {
      $form['uid'] = [
        '#title' => t('Comptroller'),
        '#description' => t("Any user with 'Manage community accounting' role"),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#selection_handler' => 'default:user',
        '#selection_settings' => [
          'filter' => [
            'permission' => ['manage mcapi']
          ]
        ],
        '#tags' => FALSE,
        '#default_value' => $currency->getOwner()
      ];
    }
    else {
      //default option for before any roles have been defined.
      $form['uid'] = [
        '#type' => 'value',
        '#value' => 1
      ];
    }
    $form['css'] = [
    	'#markup' => '<style>#edit-acknowledgement, #edit-exchange, #edit-commodity{float:right;width:50%;margin-left:1em;}'
        //this is because the classy theme puts radio buttons display:table cell, which goes under the float
        . 'form#mcapi-currency-edit-form #edit-issuance--wrapper{display:inherit;}</style>'
    ];
    $form['acknowledgement'] = [
      '#type' => 'container',
      '#children' => implode(" ", [//<br /> breaks don't work here
        t('Acknowledgement currencies are abundant - they are usually issued to pay for something of value and are not redeemable.'),
        t("These are sometimes called 'fiat' currencies and have no value in themselves."),
        t('Most timebanking systems and most LETS should choose this.')
      ]),
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => Currency::TYPE_ACKNOWLEDGEMENT]
        ]
      ],
      '#weight' => 3,
    ];
    $form['exchange'] = [
      '#type' => 'container',
      '#children' => implode(" ", [
        t("Exchange currencies are 'sufficient' - they are issued and redeemed as as users earn and spend."),
        t('The sum of all balances of active accounts, including the reservoir account, is zero, and ideally, accounts are returned to zero before being deactivated.'),
        t('To stop accounts straying too far from zero, positive and negative balance limits are often used.'),
        t('This model is sometimes called mutual credit, barter, or reciprocal exchange.'),
      ]),
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => Currency::TYPE_EXCHANGE]
        ]
      ],
      '#weight' => 3,
    ];
    $form['commodity'] = [
      '#type' => 'container',
      '#children' => implode(" ", [
        t('Commodity currencies are limited to the quantity of a valuable commodity in storage.'),
        t('They are valued according to that commodity, and redeemed for that commodity, although fractional reserve rules may apply.'),
        t('Effectively the commodity is monetised, for the cost of the stuff in storage.'),
        t("This would be the choice for all 'backed' complementary currencies.")
      ]),
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => Currency::TYPE_COMMODITY]
        ]
      ],
      '#weight' => 3,
    ];

    $form['issuance'] = [
      '#title' => t('Basis of issuance'),
      '#description' => t('Currently only affects visualisation.'),
      '#type' => 'radios',
      '#options' => [
        Currency::TYPE_ACKNOWLEDGEMENT => t('Acknowledgement', [], ['context' => 'currency-type']),
        Currency::TYPE_EXCHANGE => t('Exchange', [], ['context' => 'currency-type']),
        Currency::TYPE_COMMODITY => t('Backed by a commodity', [], ['context' => 'currency-type']),
      ],
      '#default_value' => property_exists($currency, 'issuance') ? $currency->issuance : 'acknowledgement',
      //this should have an API function to work with other transaction controllers
      //disable this if transactions have already happened
      '#disabled' => !empty($currency->transactions()),
      '#required' => TRUE,
      '#weight' => 4,
    ];

    $serials = $currency->isNew() ?
      [] :
      $currency->transactions(['curr_id' => $currency->id(), 'value' => 0]);

    $form['zero'] = [
      '#title' => t('Allow zero transactions'),
      '#type' => 'checkbox',
      '#default_value' => $currency->zero,
      //this is required if any existing transactions have zero value
      '#weight' => 7
    ];
    if ($serials) {
      $form['zero']['#required'] = TRUE;
      $form['zero']['#description'] = t("Zero transaction already exist so this field is required");
    }
    else {
      $form['zero']['#description'] = t("Leave blank to disallow zero value transactions");
    }

    $form['deletion'] = [
      '#title' => t('Deletion'),
      '#type' => 'radios',
      '#options' => [
        Currency::UNDO_REVERSE => $this->t('Transactions are permanent (and deleting creates a reverse transaction in the cluster)'),
    	  Currency::UNDO_ERASE => $this->t('keep transaction in a deleted state'),
    	  Currency::UNDO_DELETE => $this->t('remove completely from the database'),
      ],
      '#default_value' => $currency->deletion,
      '#required' => TRUE,
      '#weight' => 8
    ];
    $form['display'] = [
      '#title' => t('Appearance'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#weight' => 10,
    ];
    $help[] = t('E.g. for Hours, Minutes put Hr00:60/4mins , for dollars and cents put $0.00; for loaves put 0 loaves.');
    $help[] = t('The first number is always a string of zeros showing the number of characters (powers of ten) the widget will allow.');
    $help[] = t('The optional /n at the end will render the final number widget as a dropdown field showing intervals, in the example, of 15 mins.');
    $form['display']['format'] = [
      '#title' => t('Format'),
      '#description' => implode(' ', $help),
      '#type' => 'textfield',
      '#default_value' => implode('', $currency->format),
      '#element_validate' => [
        [$this, 'validate_format']
      ],
      '#max_length' => 16,
      '#size' => 10,
    ];

    $form['display']['color'] = [
    	'#title' => t('Colour'),
    	'#description' => t('Colour may be used in visualisations'),
    	'#type' => 'color',
    	'#default_value' => $currency->color,
    ];
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
      drupal_set_message(t('Currency %label has been updated.', ['%label' => $currency->label()]));
    }
    else {
      drupal_set_message(t('Currency %label has been added.', ['%label' => $currency->label()]));
    }

    $form_state->setRedirect('entity.mcapi_currency.collection');
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
