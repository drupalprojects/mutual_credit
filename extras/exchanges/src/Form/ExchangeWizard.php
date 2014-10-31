<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Form\ExchangeWizard.
 * Create a new exchange, and possibly the referenced manager (a user) and referenced currency
 */

namespace Drupal\mcapi_exchanges\Form;

use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;


class ExchangeWizard extends ExchangeForm {

  /**
   * Overrides Drupal\Core\Entity\ExchangeForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    //this is a default exchange object
    //it references user 1 as manager and cc as currency
    $exchange = $this->entity;

    $form = parent::form($form, $form_state);
    //to the exchange form we are going to add a few possibilities
    //allow to choose no user as manager and specfify a new user account below.
    if (\Drupal::currentUser()->hasPermission('administer users')) {
      //TODO I'd really like to use \Drupal\user\AccountForm::form() here
      //but it is abstract class and nonstatic method
      //and I don't know OOP well enough!
      $form['uid']['#required'] = FALSE;
      $form['uid']['#empty_option'] = t('- Create new -');
      $form['manager_new'] = array(
        '#title' => t('Create new exchange manager'),
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#weight' => $form['uid']['#weight']+1,
        'username' => array(
          '#title' => $this->t('Username'),
          '#type' => 'textfield',
          '#maxlength' => USERNAME_MAX_LENGTH,
          '#description' => $this->t('Spaces are allowed; punctuation is not allowed except for periods, hyphens, apostrophes, and underscores.'),
          '#attributes' => new Attribute(array(
            'class' => array('username'),
            'autocorrect' => 'off',
            'autocapitalize' => 'off',
            'spellcheck' => 'false',
          )),
          '#weight' => 1,
        ),
        'pass' => array(
          '#type' => 'password',
          '#title' => $this->t('Password'),
          '#size' => 25,
          '#weight' => 2,
          // Do not let web browsers remember this password, since we are
          // trying to confirm that the person submitting the form actually
          // knows the current one.
          '#attributes' => new Attribute(array('autocomplete' => 'off')),
        ),
        'mail' => array(
          '#title' => $this->t('E-mail address'),
          '#description' => $this->t('A valid e-mail address. All e-mails from the system will be sent to this address. The e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.'),
          '#type' => 'email',
          '#default_value' => '',
          '#weight' => 3,
        ),
        'roles' => array(
          '#type' => 'checkboxes',
          '#title' => $this->t('Roles'),
          '#default_value' => array(),
          '#options' => array_map('check_plain', user_role_names(TRUE)),
          '#weight' => 4,
        ),
        'notify' => array(
          '#type' => 'checkbox',
          '#title' => $this->t('Notify user of new account'),
          '#weight' => 5,
        ),
        '#states' => array(
          'visible' => array(
            ':input[name="uid"]' => array('value' => '')
          )
        )
      );
      unset($form['manager_new']['roles']['#options'][DRUPAL_AUTHENTICATED_RID]);
    }

    //and we allow to choose from existing currencies AND create a new one
    //NB currencies are entity_reference field API, hard coded onto the Exchange entity
    $form['currencies']['widget']['#title'] = t('Existing currencies');
    unset($form['currencies']['widget']['add_more']);
    unset($form['currencies']['widget']['#required']);
    unset($form['currencies']['widget'][0]['target_id']['#required']);
    //and we allow to choose from existing currencies AND create a new one
    //NB currencies are entity_reference field API, hard coded onto the Exchange entity
    $form['currencies_new'] = array(
      '#title' => t('New currency...'),
      '#type' => 'details',
      '#open' => FALSE,
      '#weight' => 10,
      'currency_name' => array(
        '#title' => t('New currency name'),
        '#description' => t('Use the plural'),
        '#type' => 'textfield',
        '#size' => 40,
        '#maxlength' => 80,
      ),
      'ticks' => array(
        '#title' => t('Currency value, expressed in @units', array('@units' => \Drupal::config('mcapi.misc')->get('ticks_name'))),
        '#type' => 'number',
        '#min' => 0,
        '#weight' => 6
      )
    );

    return $form;
  }

  public function validate(array $form, FormStateInterface $form_state) {
    //check that the new name is unique
    parent::validate($form, $form_state);

    $values = $form_state->getValues();
    //validate the new user, if required
      $user_values = $values['manager_new'];
    if ($user_values['username']) {
      if (empty($user_values['pass']) || empty($user_values['mail'])) {
        $form_state->setErrorByName('username', $this->t('New user must have mail and password'));
      }
      //validate the username
      //would rather have used Accountform::validate()
      if ($error = user_validate_name($user_values['username'])) {
        $form_state->setErrorByName('username', $error);
      }
      else {
        if (entity_load_multiple_by_properties('user', array('name' => $user_values['username']))) {
          $form_state->setErrorByName('manager_new][username', $this->t('The name %name is already taken.', array('%name' => $form_state['values']['username'])));
        }
      }

      if (entity_load_multiple_by_properties('user', array('mail' => $user_values['mail']))) {
        $this->setErrorByName('username', $this->t('The e-mail address %email is already taken.', array('%email' => $user_values['mail'])));
      }
      $defaults = array(
        'name' => $user_values['username'],
        'mail' => $user_values['mail'],
        'pass' => $user_values['pass'],
//TODO we need a role configured to be the exchange manager
        'roles' => array()
      );
      $this->manager = User::Create($defaults);

      //TODO how now to validate the user entity? $this->manager->validate() coming after alpha12?
    }
    else {
      $this->manager = User::load($values['uid']);
    }
    if (!$this->manager->hasPermission('manage own exchanges') && !$this->manager->hasPermission('manage mcapi')) {
      $form_state->setErrorByName('uid', $this->t('Exchange manager does not have permission'));
    }
    $defaults = array(
      'name' => $values['currency_name'],
      'ticks' => $values['ticks']
    );
    $this->currency = Currency::create($defaults);
    //TODO handle violations
    foreach ($this->currency->validate() as $field => $violation) {
      $form_state->setErrorByName($field, (string) $violation);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($this->manager)) {
      $this->manager->save();
      if ($values['manager_new']['notify']) {
        //see RegisterForm::Save()
        if (_user_mail_notify('register_admin_created', $this->manager)) {
          drupal_set_message($this->t('A welcome message with further instructions has been e-mailed to the new user <a href="@url">%name</a>.', array('@url' => $account->url(), '%name' => $account->getUsername())));
        }
      }
    }
    foreach ($values['currencies'] as $item) {
      if ($item['target_id']) {
        $curr_ids[] = $currency;
      }
    }
    if (isset($this->currency)) {
      $this->currency->save();
      $curr_ids[] = $this->currency->id();
    }

    $this->entity->get('currencies')->setValue($curr_ids);
    $this->entity->get('uid')->setValue($this->manager);
    $this->entity->save();

    $status = parent::save($form, $form_state);//this will also redirect
    //now we know the exchange ID we can put the manager user in this new exchange
    $this->manager->get('exchanges')->setValue(array($this->entity->id()));
    $this->manager->save();

  }

}

