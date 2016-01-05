<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Form\ExchangeForm.
 * Edit all the fields on an exchange
 */

namespace Drupal\mcapi_exchanges\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;

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
      '#description' => t('Must be unique'),
      '#default_value' => $exchange->get('name')->value,
      '#required' => TRUE,
      '#weight' => 0
    );
    $form['code'] = [
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => [
        'exists' => '\Drupal\mcapi_exchanges\Exchanges::exchangeLoadByCode',
        'source' => ['name'],
      ],
      '#description' => $this->t('A unique machine-readable name for this currency. It must only contain lowercase letters, numbers, and underscores.'),
    ];
    //@todo how to decide who to select exchange managers from?
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

    $form['mail'] = array(
      '#title' => t('Main contact email'),
      '#type' => 'email',
      '#default_value' => $exchange->mail->value,
      '#required' => TRUE,
      '#weight' => 4
    );

    $form['visibility'] = array(
      '#title' => t('Visibility'),
      '#description' => t('Is this exchange hidden from members of other exchanges?'),
      '#type' => 'radios',
      '#options' => $this->entity->visibility_options(),
      '#default_value' => $exchange->visibility->value,
      '#required' => TRUE,
      '#weight' => 6
    );
    $form['open'] = array(
      '#title' => t('Open'),
      '#description' => t('Is this exchange open to trade with other exchanges?'),
      '#type' => 'checkbox',
      '#default_value' => $exchange->open->value,
      '#weight' => 9
    );
    
    
    return $form;
  }

  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge(
      ['name', 'uid', 'mail', 'visibility', 'open'], 
      parent::getEditedFieldNames($form_state)
    );
  }
  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    $field_names = [
      'name',
      'uid',
      'mail',
      'visibility',
      'open',
    ];
    foreach ($violations->getByFields($field_names) as $violation) {
      list($field_name) = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setErrorByName($field_name, $violation->getMessage());
    }
    parent::flagViolations($violations, $form, $form_state);
  }
}
