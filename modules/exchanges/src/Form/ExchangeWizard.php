<?php

namespace Drupal\mcapi_exchange\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;


/**
 * Multistep form to make an exchange group
 *
 * @todo
 */
class ExchangeWizard extends GroupForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    if (!$form_state->get('exchange')) {
      $form['#title'] = t('Create exchange wizard');
      $form = $this->exchangeForm($form, $form_state);
    }
    elseif (!$form_state->get('manager')) {
      $form['#title'] = $this->t('Who will manage %exchange?', ['%exchange' => $form_state->get('exchange')->label()]);
      $form = $this->managerForm($form, $form_state);
    }
    elseif (!$form_state->get('currency')) {
      $form['#title'] = $this->t('What is the new currency of %exchange?', ['%exchange' => $form_state->get('exchange')->label()]);
      $form = $this->currencyForm($form, $form_state);
    }
    return $form;
  }

  /**
   * Form builder for the basic exchange.
   */
  function exchangeForm($form, $form_state) {
    $form = parent::form($form, $form_state);

    $form['uid']['#options'] = [0 => '--' . t('New user') . '--'] + $form['uid']['#options'];
    unset($form['uid']['#default_value']);
    unset($form['uid']['#required']);

    $form['new_currency'] = [
      '#title' => $this->t('Make a new currency'),
      '#type' => 'checkbox',
      '#weight' => $form['currencies']['#weight'] + 1,
    ];
    $form['currencies']['#title'] = t('Use exising currencies');
    return $form;
  }

  /**
   * Sub form to create a new user who will be manager.
   */
  function managerForm(&$form, $form_state) {
    // This is equivalent to the user create form.
    $form['subtitle'] = [
      '#markup' => t('Create a new user to manage the exchange'),
      '#weight' => -1,
    ];
    $form['username'] = [
      '#title' => $this->t('Username'),
      '#type' => 'textfield',
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t('Spaces are allowed; punctuation is not allowed except for periods, hyphens, apostrophes, and underscores.'),
      '#attributes' => [
        'class' => array('username'),
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#required' => TRUE,
      '#weight' => 1,
    ];
    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 25,
      '#weight' => 2,
      // Do not let web browsers remember this password, since we are
      // trying to confirm that the person submitting the form actually
      // knows the current one.
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
    ];
    $form['mail'] = [
      '#title' => $this->t('E-mail address'),
      '#description' => $this->t('A valid e-mail address. All e-mails from the system will be sent to this address. The e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.'),
      '#type' => 'email',
      '#default_value' => '',
      '#weight' => 3,
      '#required' => TRUE,
    ];
    $form['notify_new_manager'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user of new account'),
      '#weight' => 5,
    ];
    return $form;
  }

  /**
   * Sub-form to create a new currency for the exchange.
   */
  function currencyForm($form, $form_state) {
    $form['subtitle'] = [
      '#markup' => t('Create a new currency.'),
      '#weight' => -1,
    ];
    // And we allow to choose from existing currencies AND create a new one
    // NB currencies are entity_reference field API, hard coded onto the Exchange entity.
    $form['currencies']['widget']['#title'] = t('Existing currencies');
    unset($form['currencies']['widget']['add_more']);
    unset($form['currencies']['widget']['#required']);
    unset($form['currencies']['widget'][0]['target_id']['#required']);
    // And we allow to choose from existing currencies AND create a new one
    // NB currencies are entity_reference field API, hard coded onto the Exchange entity.
    $form['currency_name'] = [
      '#title' => t('New currency name'),
      '#description' => t('Use the plural'),
      '#type' => 'textfield',
      '#size' => 40,
      '#maxlength' => 80,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => [
        'exists' => '\Drupal\mcapi\Entity\Currency::load',
        'source' => ['currency_name'],
      ],
      '#description' => $this->t('A unique machine-readable name for this Currency. It must only contain lowercase letters, numbers, and underscores.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!$form_state->get('exchange')) {
      $this->validateExchange($form, $form_state);
    }
    elseif (!$form_state->get('manager')) {
      $this->validateManager($form, $form_state);
    }
    elseif (!$form_state->get('currency')) {
      $this->validateCurrency($form, $form_state);
    }

    if (!$form_state->get('exchange') || !$form_state->get('manager') || !$form_state->get('currency')) {
      $form_state->setRebuild(TRUE);
    }

  }

  /**
   *
   */
  function validateExchange($form, FormStateInterface $form_state) {
    $validated_exchange = parent::validateForm($form, $form_state);
    if ($uid = $form_state->getValue('uid')) {
      if (User::load($uid)) {
        $form_state->set('manager', TRUE);
      }
      else {
        $form_state->setErrorByName('uid', $this->t('Unknown user cannot manage an exchange.'));
      }
    }
    if (!$form_state->getValue('new_currency')) {
      $form_state->set('currency', TRUE);
    }
    if (!$form_state->getErrors()) {
      $form_state->set('exchange', $validated_exchange);
    }
  }

  /**
   *
   */
  function validateManager($form, FormStateInterface $form_state) {
    $account = User::create([
      'name' => $form_state->getValue('username'),
      'pass' => $form_state->getValue('pass'),
      'mail' => $form_state->getValue('mail'),
      'status' => TRUE,
      'roles' => [],
    ]);
    $form_state->set('notifyMananger', $form_state->getValue('notify_new_manager'));

    $violations = $account->validate()
      ->filterByFieldAccess($this->currentUser())
      ->filterByFields(array_diff(array_keys($account->getFieldDefinitions()), $this->getEditedFieldNames($form_state)));
    $this->flagViolations($violations, $form, $form_state);
    // @todo might need to implement getEditedFieldNames and flagfieldViolations
    if (!$form_state->getErrors()) {
      $form_state->set('manager', $account);
    }
  }

  /**
   *
   */
  function validateCurrency($form, FormStateInterface $form_state) {
    // can't think of any validation to do here.
    $currency = Currency::create([
      'id'  => $form_state->getValue('id'),
      'name' => Html::escape($form_state->getValue('id')),
    ]);

    // @todo what if the currency name isn't unique/
    if (!$form_state->getErrors()) {
      $form_state->set('currency', $currency);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    // Which was already validated.
    $this->entity = $form_state->get('exchange');
    $currency = $form_state->get('currency');
    $manager = $form_state->get('manager');

    if (is_object($manager)) {
      $manager->save();
      if ($form_state->get('notifyMananger')) {
        // See RegisterForm::Save()
        if (_user_mail_notify('register_admin_created', $manager)) {
          drupal_set_message(
            $this->t(
              'A welcome message with further instructions has been e-mailed to the new user %linked.',
              ['%linked' => $manager->toLink()]
            )
          );
        }
      }
      $this->entity->uid->setValue($manager);
    }

    if (is_object($currency)) {
      $currency->save();
      // Add this new currency to any other selected currencies
      // there's no quicker way I think.
      $currencies = $this->entity->currencies->referencedEntities();
      $currencies[] = $currency;
      $this->entity->currencies->setValue($currencies);
    }

    // This will also redirect.
    parent::save($form, $form_state);
    $form_state->setRedirect('mcapi.admin_exchange_list');
  }

}
