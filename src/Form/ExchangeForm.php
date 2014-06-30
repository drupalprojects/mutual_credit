<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\ExchangeForm.
 * Edit all the fields on an exchange
 *
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;

class ExchangeForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, array &$form_state) {
    drupal_set_message("NB there is a bug which, after saving and caching the referenced currency entities, then deletes them.", 'warning');
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
      '#weight' => 1
    );
    //@todo how to decide who to select exchange managers from?
    //really it could be any user IN that exchange, although the exchange has no members right now....
    foreach (entity_load_multiple('user') as $account) $managers[$account->id()] = $account->label();
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
      '#weight' => 4
    );
    $form['open'] = array(
      '#title' => t('Open'),
      '#description' => t('Is this exchange open to trade with other exchanges?'),
      '#type' => 'checkbox',
      '#default_value' => $exchange->get('open')->value,
      '#weight' => 5
    );
    //hide the currencies field if only one currency is available
    if (count(entity_load_multiple_by_properties('mcapi_currency', array('status' => TRUE))) == 1) {
      //TODO uncomment this when we are sure that the field is populating properly from installation
//      $form['currencies']['#attributes']['style'] = 'display:none;';
    }

    return $form;
  }

  public function validate(array $form, array &$form_state) {
    $samename = entity_load_multiple_by_properties('mcapi_exchange', array('name' => $form_state['values']['name']));
    foreach ($samename as $exchange) {
      if ($exchange->id() != $form_state['values']['id']) {
        $this->errorHandler()->setErrorByName('name', $form_state, t('Another exchange already has that name'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $status = $entity->save();
    //either SAVED_UPDATED or SAVED_NEW

    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.exchange.view',
      'route_parameters' => array('mcapi_exchange' => $entity->id())
    );
  }

}
