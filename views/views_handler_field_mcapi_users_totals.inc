<?php


use Drupal\user\Entity\User;

/**
 * @file
 * Given the user, this shows the balance.
 * choose ONE currency from a list of options.
 * note that this is a bit of a pseudo field, since it makes a separate call to the transaction controller layer.
 */
class views_handler_field_mcapi_users_totals extends views_handler_field {

  var $fetched;

  function query() {
    $this->ensure_my_table();
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['curr_id'] = array('default' => array());
    $options['total'] = array('default' => 'balance');
    $options['strip'] = array('default' => FALSE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $currencies = currencies_load();
    module_load_include('inc', 'mcapi');
    $form['curr_id'] = currency_picker_element(array_keys($currencies), $this->options['curr_id'], TRUE);
    $form['strip'] = array(
      '#title' => t('Strip formatting'),
      '#description' => t('Just show the unformatted quantity (best with only one currency)'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['strip']
    );
    $form['total'] = array(
      '#title' => t('Aggregate'),
      '#description' => t('How do you want to summarise the the transaction totals?'),
      '#type' => 'radios',
      '#options' => array(
        'count' => t('Number of transactions'),
        'gross_in' => t('Gross income'),
        'gross_out' => t('Gross expenditure'),
        'balance' => t('Balance'),
        'participation' => t('Participation')
      ),
      '#default_value' => $this->options['total']
    );
    parent::options_form($form, $form_state);
  }

  function render($values) {
    $uid = &$values->uid;
    //the settings imply there is only one curr_id, but actually many can be rendered
    $curr_ids = empty($this->options['curr_id']) ? array_keys(currencies_load(User::load($uid))): (array)$this->options['curr_id'];
    $elements = array();
    foreach ($curr_ids as $curr_id) {
      if (empty($this->fetched[$uid][$curr_id])) {
        $this->fetched[$uid][$curr_id] = transaction_totals($uid, $curr_id);
      }
      $quant = $this->fetched[$uid][$curr_id]->{$this->options['total']};
      if ($this->options['strip']) {
        $elements[$curr_id][] = array('#type' => 'markup', '#markup' => $quant);
      }
      else {
        $elements[$curr_id] = array(
          '#theme' => 'worth_item',
          '#quantity' => $quant,
          '#curr_id' => $curr_id
        );
      }
    }
      drupal_set_message($elements[$curr_id]['#markup']);
    return $elements;
  }
}
