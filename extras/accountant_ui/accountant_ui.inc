<?php

/*
 * menu form callback
 * $type denontes which of the 2 mass payments requested, many2one, one2many
 */
function mcapi_mass_pay($form, &$form_state, $vector) {
  form_load_include($form_state, 'inc', 'accountant_ui');
  $form = array();
  if (empty($form_state['confirmed'])) {
    list($payer, $payee) = explode('2', $vector);

    $one = array(
      '#title' => t('Account number to be credited'),
      '#type' => 'textfield',
      '#description' => t('A username, email, or user ID'),
      '#default_value' => '',
      '#element_validate' => array('mcapi_validate_usernames', 'mcapi_validate_transactor'),
      '#autocomplete_path' => 'user/autocomplete',
      '#weight' => 9,
    );
    $few = array(
      '#title' => t('Payees'),
      '#description' => t('A group of users'),
      '#type' => 'user_chooser_permissions',
      '#args' => array('transact'),
      '#me' => FALSE,
      '#multiple' => TRUE,
    );
    $many = array(
      '#title' => t('Payees'),
      '#type' => 'user_chooser_preselect',
      '#options' => module_invoke_all('trading_groups'),
      '#me' => FALSE,
    );

    if ($payer == 'one') {
      $form['payers'] = array(
        '#title' => 'Payer',
        '#weight' => 1
      ) + $one;
      $form['payees'] = array(
        '#title' => 'Payees',
        '#weight' => 2
      )+ $$payee;
    }
    elseif ($payee == 'one') {
      $form['payees'] = array(
        '#title' => 'Payee',
        '#weight' => 1
      ) + $one;
      $form['payers'] = array(
        '#title' => 'Payers',
        '#weight' => 2
      )+ $$payer;
    }
    field_attach_form('transaction', NULL, $form, $form_state);
    unset($form['worth'][LANGUAGE_NONE]['add_more']);
    $form['alter'] = array(
      '#title' => t('Process individually'),
      '#description' => t("Will add dependent transactions if applicable and send notifications"),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#weight' => 15,
      '#disabled' => isset($form_state['confirmed'])
    );
  }
  else {
    $form['preview'] = transaction_view($form_state['confirmed'], 'sentences');
  }

  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Record'),
    //this prevents double click, but doesn't prevent going back and resubmitting the form
    '#attributes' => array('onclick' => "this.disabled=true,this.form.submit();")
  );
  $form['buttons']['#weight'] = 25;
  return $form;
}
/*
 * form validation callback
 * ensure that all the users have permission and test write all transactions
 */
function mcapi_mass_pay_validate($form, &$form_state) {
  if (!isset($form_state['confirmed'])) {
    $form_state['values']['creator'] = $GLOBALS['user']->uid;
    $form_state['values']['type'] = 'mass';
    $form_state['values']['state'] = TRANSACTION_STATE_FINISHED;
    $transactions = mcapi_mass_pay_insert($form_state['values'], FALSE);
    if (!form_get_errors()) {
      $form_state['rebuild'] = TRUE;
      drupal_set_title(t('Are you sure?'));
      $form_state['storage'] = $form_state['values'];
      $form_state['confirmed'] = $transactions;
    }
  }
}

function mcapi_mass_pay_submit($form, &$form_state) {
  $messages = mcapi_mass_pay_insert($form_state['storage'], TRUE);
  $form_state['redirect'] = 'admin/accounting';
}

function mcapi_mass_pay_insert($values, $really) {
  $payers = (array)$values['payers'];
  $payees = (array)$values['payees'];
  unset($values['payers'], $values['payees']);
  $template = new_transaction($values);
  foreach ($payers as $payer) {
    foreach ($payees as $payee) {
      $template->payer = $payer;
      $template->payee = $payee;
      $transactions[] = clone $template;
    }
  }
  $messages = array();
  if ($values['alter']) {//test each transaction individually
    foreach ($transactions as $transaction) {
      try {transaction_insert_new($transaction, $really);}
      catch (Exception $e) {$messages[] = $e->getMessage();}
    }
  }
  else {
    try {transactions_insert($transactions, $really);}
    catch (Exception $e) {$messages[] = $e->getMessage();}
  }
  if ($messages) {
    form_set_error('', implode('<br />', $messages));
  }
  return $transactions;
}

/*
 * trading_groups callback declared in hook_user_chooser_preselect
 * return all the users with permission to trade
 * needs no arguments
 */
function accounting_list_all_users($element, $form_state) {
  $roles = user_roles(FALSE, 'transact');
  $query = db_select('users', 'u')
    ->fields('u', array('uid'))
    ->condition('status', TRUE);
  if (empty($roles[DRUPAL_AUTHENTICATED_RID])) {
    $query->join('users_roles', 'ur', 'ur.uid = u.uid');
    //if we chain this it breaks with: Call to a member function condition() on a non-object 
    $query->condition('ur.rid', array_keys($roles));
  }
  return $query->execute()->fetchCol();
}
