<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\ExchangeForm.
 * Edit all the fields on an exchange
 *
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;

class ExchangeForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $exchange = $this->entity;

    $form['id'] = array(
    	'#type' => 'value',
      '#value' => $exchange->id()
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Full name'),
      '#default_value' => $exchange->get('name')->value,
      '#required' => TRUE,
      '#weight' => 0
    );
    //TODO how to decide who to select exchange managers from?
    //really it could be any user IN that exchange, although the exchange has no members right now....
    foreach (User::loadMultiple() as $account) {
      $managers[$account->id()] = $account->label();
    }
    unset($managers[0]);
    $form['uid'] = array(
      '#title' => t('Manager of the exchange'),
      '#description' => t('The one user responsible for administration'),
      '#type' => 'select',
      '#options' => $managers,
      '#default_value' => $exchange->getOwnerId(),
      '#required' => TRUE,
      '#weight' => 3
    );

    $form['visibility'] = array(
      '#title' => t('Visibility'),
      '#description' => t('Is this exchange hidden from members of other exchanges?'),
      '#type' => 'radios',
      '#options' => $this->entity->visibility_options(),
      '#default_value' => $exchange->get('visibility')->value,
      '#required' => TRUE,
      '#weight' => 6
    );
    $form['open'] = array(
      '#title' => t('Open'),
      '#description' => t('Is this exchange open to trade with other exchanges?'),
      '#type' => 'checkbox',
      '#default_value' => $exchange->get('open')->value,
      '#weight' => 9
    );
    //hide the currencies field if only one currency is available
    if (count(entity_load_multiple_by_properties('mcapi_currency', array('status' => TRUE))) == 1) {
      //TODO uncomment this when we are sure that the field is populating properly from installation
//      $form['currencies']['#attributes']['style'] = 'display:none;';
    }

    return $form;
  }

  public function validate(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $samename = entity_load_multiple_by_properties('mcapi_exchange', array('name' => $values['name']));
    $values = $form_state->getValues();
    foreach ($samename as $exchange) {
      if ($exchange->id() != $values['id']) {
        $form_state->setErrorByName('name', t('Another exchange already has that name'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    $form_state->setRedirect('mcapi.exchange.view', array('mcapi_exchange' => $this->entity->id()));
    //either SAVED_UPDATED or SAVED_NEW
    return $status;
  }

}

