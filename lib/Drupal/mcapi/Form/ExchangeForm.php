<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\ExchangeForm.
 * Edit all the fields on an exchange
 *
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityFormController;

class ExchangeForm extends ContentEntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
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
      '#default_value' => $exchange->get('uid')->value,
      '#weight' => 3
    );

    $form['visibility'] = array(
      '#title' => t('Visibility'),
      '#description' => t('Is this exchange hidden from members of other exchanges?'),
    	'#type' => 'radios',
      '#options' => $this->entity->visibility_options(),
      '#default_value' => $exchange->get('visibility')->value,
      '#weight' => 5
    );
    //hide the currencies field if only one currency is available
    if (count(entity_load_multiple_by_properties('mcapi_currency', array('status' => TRUE))) < 2) {
      $form['field_currencies']['#attributes']['style'] = 'display:none;';
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


  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions();
    if (!$this->entity->deletable($this->entity)) {
      unset($actions['delete']);
    }
    return $actions;
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

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   * Borrowed from NodeFormController
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $query = \Drupal::request()->query;
    if ($query->has('destination')) {
      $destination = drupal_get_destination();
      $query->remove('destination');
    }
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.exchange.delete_confirm',
      'route_parameters' => array(
        'mcapi_exchange' => $this->entity->id(),
      ),
      'options' => array(
         'query' => $destination,
      ),
    );
  }


}

