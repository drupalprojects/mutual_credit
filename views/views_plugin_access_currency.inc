<?php


/**
 * Access plugin that provides permission-based access control.
 */

class views_plugin_access_currency extends views_plugin_access {

  function access($account) {
    //grant access only if none of the included currencies deny it
    $currcodes = array();
    foreach($this->view->display_handler->options['filters'] as $filter) {
      if($filter['field'] == 'currcode') {
        $currcodes = $filter['value'];
        break;
      }
    }
    foreach ($currcodes as $currcode) {
      if (!currency_access($this->options['mode'], $currcode, $account)) return FALSE;
    }
    return TRUE;
  }


  function option_definition() {
    $options = parent::option_definition();
    $options['mode'] = array('default' => 'membership');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['mode'] = array(
      '#title' => t('Currency access mode'),
      '#description' => t("See the settings on each currency's page."),
      '#type' => 'radios',
      '#options' => array(
        'membership' => t('According to the membership of the currency'),
        'user_data' => t('According to whether the user summary data is accessible'),
        'system_data' => t('According to whether the system summary data is accessible'),
      ),
      '#default_value' => $this->options['mode']
    );

  }


  function get_access_callback() {
    if (module_exists('devel'))ddebug_backtrace();
    return array('currency_access', $this->options['mode'], CURRCODE, 'account');
  }

  function summary_title() {
    switch($this->options['mode']) {
      case 'membership': return t('Membership');
      case 'user_data': return t('User summary data');
      case 'system_data': return t('System-wide summary data');
    }
  }
}