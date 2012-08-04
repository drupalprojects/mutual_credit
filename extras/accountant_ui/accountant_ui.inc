<?php

/*
 * menu form callback
 * $type denontes which of the 2 mass payments requested, many2one, one2many
 */
function mcapi_mass_pay($form, &$form_state, $vector) {
  form_load_include($form_state, 'inc', 'accountant_ui');
  $form = array();
  list($payer, $payee) = explode('2', $vector);
  if (empty($form_state['confirmed'])) {
    $one = array(
      '#type' => 'textfield',
      '#description' => t('A username, email, or user ID'),
      '#default_value' => '',
      '#element_validate' => array('mcapi_validate_usernames', 'mcapi_validate_transactor'),
      '#autocomplete_path' => 'user/autocomplete',
      '#weight' => 9,
    );
    $few = array(
      '#description' => t('A group of users'),
      '#type' => 'user_chooser_permissions',
      '#args' => array('transact'),
      '#me' => FALSE,
      '#multiple' => TRUE,
    );
    $many = array(
      '#type' => 'user_chooser_preselect',
      '#options' => module_invoke_all('user_chooser_preselect'),
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

    module_load_include('inc', 'mcapi');
    $defaults = array(
      'subject' => variable_get('mcapi_accounting_masspay_subject', ''),
      'body' => variable_get('mcapi_accounting_masspay_body', ''),
    );
    $form += _mcapi_mail_template_fields($defaults, NULL, FALSE);
    $form['mail']['#weight'] = 21;
    $form['mail']['#title'] = $payer == 'one' ? t('Mail all payees (optional)') : t('Mail all payers (optional)');
    unset($form['mail']['cc']);
  }
  else {
    $form['preview'] = transaction_view($form_state['confirmed'], 'sentences');
  }

  $form['buttons'] = array(
    '#weight' => 20,
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Record'),
      //this prevents double click, but doesn't prevent going back and resubmitting the form
      '#attributes' => array('onclick' => "this.disabled=true,this.form.submit();"),
      '#weight' => 20
    )
  );
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
    if (form_get_errors()) return;
    $transactions = mcapi_mass_pay_insert($form_state['values'], FALSE);
    if (!form_get_errors()) {
      $form_state['rebuild'] = TRUE;
      drupal_set_title(t('Are you sure?'));
      $form_state['storage'] = $form_state['values'];
      $form_state['confirmed'] = $transactions;
    }
  }
}
/*
 * form submit callback
 */
function mcapi_mass_pay_submit($form, &$form_state) {
  $values = $form_state['storage'];
  $transactions = mcapi_mass_pay_insert($values, TRUE);
  if ($values['subject'] && $values['body']) {
    $bcc = db_select('users', 'u')
      ->fields('u', array('mail'))
      ->condition('uid', isset($values['payers']) ? $values['payers'] : $values['payees'])
      ->execute()
      ->fetchCol();
    if (count($bcc)) {
      $values['bcc'] = implode(", ", $bcc);
    }
    global $language;
    drupal_mail('accountant_ui', 'mass', variable_get('site_mail', ''), $language, $values);
  }
  //store the mail so we can bring it up as a default next time
  variable_set('mcapi_accounting_masspay_subject', $values['subject']);
  variable_set('mcapi_accounting_masspay_body',$values['body']);
  $form_state['redirect'] = 'admin/accounting';
}

/*
 * implements hook_mail
 */
function accountant_ui_mail($key, &$message, $params) {
  $message['headers']['Bcc'] = $params['bcc'];
  $message['subject'] = $params['subject'];
  $message['body'][] = $params['body'];
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
  try {transactions_insert($transactions, $really);}
  catch (Exception $e) {form_set_error('', $e->getMessage());}
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
