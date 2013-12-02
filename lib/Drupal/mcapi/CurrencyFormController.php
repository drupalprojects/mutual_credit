<?php

/**
 * @file
 * Definition of Drupal\mcapi\CurrencyFormController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Component\Plugin\PluginManagerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CurrencyFormController extends EntityFormController {

  /**
   * The currency plugin manager
   *
   * @var \Drupal\Component\Plugin\PluginManagerBase
   */
  protected $pluginCurrencyManager;

  /**
   * The widget  plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerBase
   */
  protected $pluginWidgetManager;

  /**
   * Constructs a new CurrencyFormController.
   *
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The widget or formatter plugin manager.
   */
  public function __construct(PluginManagerBase $currency_manager, PluginManagerBase $widget_manager) {
    $this->pluginCurrencyManager = $currency_manager;
    $this->pluginWidgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mcapi.currency_type'),
      $container->get('plugin.manager.mcapi.currency_widget')
    );
  }

  public function getWidgetPlugin($configuration) {
    $plugin = NULL;

    if ($configuration) {
      $plugin = $this->pluginWidgetManager->getInstance(array(
        'currency' => $this->entity,
        'configuration' => $configuration
      ));
    }

    return $plugin;
  }

  /**
   * Get the widget options.
   */
  public function getWidgetOptions($currency_type) {
    return $this->pluginWidgetManager->getOptions($currency_type);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $currency = $this->entity;

    $form_state += array(
      'widget_settings_edit' => NULL,
      'widget_settings' => array(
        $currency->widget => $currency->widget_settings
      ),
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of currency'),
      '#description' => t('Use the plural'),
      '#default_value' => $currency->label(),
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $currency->id(),
      '#machine_name' => array(
        'exists' => 'mcapi_currencies_load',
        'source' => array('name'),
      ),
      '#disabled' => !$currency->isNew(),
    );

    $form['acknowledgement'] = array(
      '#type' => 'container',
      '#children' => implode("\n<br /><br />\n", array(
        t('Acknowledgement currencies are abundant - they are issued whenever valued is created; they can be used as a medium of exchange but there is no guarantee of redemption.'),
        t("These are sometimes called 'social' currencies, because by encouraging and recognising volunteer service, they bind the community together."),
        t('This is the choice for all timebanking systems and most LETS.')
      )),
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_ACKNOWLEDGEMENT)
        )
      ),
    );
    $form['exchange'] = array(
      '#type' => 'container',
      '#children' => implode("\n<br /><br />\n", array(
        t("Exchange currencies are 'sufficient' - they are issued and redeemed as as users earn and spend."),
        t('The sum of all balances of active accounts, including the reservoir account, is zero, and ideally, accounts are returned to zero before being deactivated.'),
        t('To stop accounts straying too far from zero, positive and negative balance limits are often used.'),
        t('This model is sometimes called mutual credit, barter, or reciprocal exchange.'),
      )),
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_EXCHANGE)
        )
      )
    );
    $form['commodity'] = array(
      '#type' => 'container',
      '#children' => implode("\n<br /><br />\n", array(
        t('Commodity currencies are scarce - the quantity is tied to the amount of a valuable commodity in a trusted warehouse.'),
        t('They are valued according to that commodity, and redeemed for that commodity, although fractional reserve rules may apply.'),
        t('Effectively the commodity is monetised, this brings confidence to the commodity, for the cost of the stuff in storage.'),
        t("This would be the choice for all 'dollar-backed' complementary currencies.")
      )),
      '#states' => array(
        'visible' => array(
          ':input[name="issuance"]' => array('value' => CURRENCY_TYPE_COMMODITY)
        )
      )
    );
    $form['issuance'] = array(
      '#title' => t('Basis of issuance'),
      '#description' => t('Currently only affects visualisation.'),
      '#type' => 'radios',
      '#options' => array(
        CURRENCY_TYPE_ACKNOWLEDGEMENT => t('Acknowledgement', array(), array('context' => 'currency-type')),
        CURRENCY_TYPE_EXCHANGE => t('Exchange', array(), array('context' => 'currency-type')),
        CURRENCY_TYPE_COMMODITY => t('Backed by a commodity', array(), array('context' => 'currency-type')),
      ),
      '#default_value' => property_exists($currency, 'issuance') ? $currency->issuance : 'acknowledgement',
      //this should have an API function to work with other transaction controllers
      //disable this if transactions have already happened
      '#disabled' => property_exists($currency, 'info') ?
        transaction_filter(array('currcode' => $currency->info['currcode'])) :
        FALSE
    );
    $form['uid'] = array(
      '#title' => t('Declared by'),
      '#description' => t("Choose from users with permission '@permission'", array('@permission' => t('Declare currency'))),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('declare currency'),
      '#default_value' => property_exists($currency, 'uid') ? $currency->uid : \Drupal::currentUser()->name,
      '#multiple' => FALSE,
      '#required' => TRUE,
    );
    $form['reservoir'] = array(
      '#title' => t('Reservoir account'),
      '#description' => t('Account used for issuing and taxing'),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('transact'),
      '#default_value' => property_exists($currency, 'reservoir') ? $currency->reservoir : 1,
      '#multiple' => FALSE,
    );
    $form['display'] = array(
      '#title' => t('Appearance'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#prefix' => '<div id="currency-display-wrapper">',
      '#suffix' => '</div>',
    );

    $currency_type = $this->pluginCurrencyManager->createInstance($currency->type);
    $type_definition = $this->pluginCurrencyManager->getDefinition($currency->type);

    $form['display']['type'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('@label - @description', array('@label' => $type_definition['label'], '@description' => $type_definition['description'])),
    );

    if (method_exists($currency_type, 'settingsForm')) {
      $form['display']['settings'] = array(
        '#tree' => TRUE,
      );
      $form['display']['settings'] = $currency_type->settingsForm($form['display']['settings'], $form_state, $currency);
    }

    $form['display']['widget'] = array(
      '#type' => 'select',
      '#title' => $this->t('Widget'),
      '#default_value' => $currency->widget,
      '#options' => $this->getWidgetOptions($currency->type),
      '#attributes' => array('class' => array('field-plugin-type')),
    );

    $form['display']['widget_settings'] = array(
      '#tree' => TRUE,
    );

    $form['display']['widget_summary'] = array();

    $widget_type = isset($form_state['input']['widget']) ? $form_state['input']['widget'] : $currency->widget;

    $options = array(
      'type' => $widget_type,
      'settings' => isset($form_state['widget_settings'][$widget_type]) ? $form_state['widget_settings'][$widget_type] : array(),
    );
    $widgetPlugin = $this->getWidgetPlugin($options);

    $base_button = array(
      '#submit' => array(array($this, 'multistepSubmit')),
      '#ajax' => array(
        'callback' => array($this, 'multistepAjax'),
        'wrapper' => 'currency-display-wrapper',
        'effect' => 'fade',
      ),
    );

    if ($form_state['widget_settings_edit']) {
      // We are currently editing this field's plugin settings. Display the
      // settings form and submit buttons.
      $field_row['plugin']['settings_edit_form'] = array();

      if ($widgetPlugin) {
        // Generate the settings form and allow other modules to alter it.
        $settings_form = $widgetPlugin->settingsForm($form, $form_state);
        $this->alterWidgetSettingsForm($settings_form, $widgetPlugin, $form, $form_state);

        if ($settings_form) {
          $form['display']['widget_settings'] = array(
            '#type' => 'container',
            '#attributes' => array('class' => array('widget-plugin-settings-edit-form')),
            'label' => array(
              '#markup' => $this->t('Plugin settings'),
            ),
            'widget_settings' => array('#tree' => TRUE) + $settings_form,
            'actions' => array(
              '#type' => 'actions',
              'save_settings' => $base_button + array(
                '#type' => 'submit',
                '#name' => 'widget_plugin_settings_update',
                '#value' => $this->t('Update'),
                '#op' => 'update',
                '#limit_validation_errors' => array(array('widget_settings')),
              ),
              'cancel_settings' => $base_button + array(
                '#type' => 'submit',
                '#name' => 'widget_plugin_settings_cancel',
                '#value' => $this->t('Cancel'),
                '#op' => 'cancel',
                // Do not check errors for the 'Cancel' button, but make sure we
                // get the value of the 'plugin type' select.
                '#limit_validation_errors' => array(),
              ),
            ),
          );
        }
      }
    }
    else {
      if ($widgetPlugin) {
        // Display a summary of the current plugin settings, and (if the
        // summary is not empty) a button to edit them.
        $summary = $widgetPlugin->settingsSummary();

        // Allow other modules to alter the summary.
        $this->alterWidgetSettingsSummary($summary, $widgetPlugin);
        $form['display']['widget_summary'] = array();

        if (!empty($summary)) {
          $form['display']['widget_summary']['summary'] = array(
            '#markup' => '<div class="widget-plugin-summary">' . implode('<br />', $summary) . '</div>',
          );
        }
        if ($widgetPlugin->getSettings()) {
          $form['display']['widget_summary']['edit'] = $base_button + array(
            '#type' => 'image_button',
            '#name' => 'widget_settings_edit',
            '#src' => 'core/misc/configure-dark.png',
            '#attributes' => array('class' => array('widget-plugin-settings-edit'), 'alt' => $this->t('Edit')),
            '#op' => 'edit',
            // Do not check errors for the 'Edit' button, but make sure we get
            // the value of the 'plugin type' select.
            '#limit_validation_errors' => array(),
            '#prefix' => '<div class="widget-plugin-settings-edit-wrapper">',
            '#suffix' => '</div>',
          );
        }
        if (!empty($form['display']['widget_summary'])) {
          $form['display']['widget_summary'] += array(
            '#prefix' => '<div class="currency-plugin-summary clearfix">',
            '#suffix' => '</div>',
          );
        }
      }
    }

    $form['display']['prefix'] = array(
      '#title' => t('Prefix'),
      '#type' => 'textfield',
      '#default_value' => $currency->prefix,
      '#max_length' => 6,
      '#size' => 6,
    );
    $form['display']['suffix'] = array(
      '#title' => t('Suffix'),
      '#type' => 'textfield',
      '#max_length' => 6,
      '#size' => 6,
      '#default_value' => $currency->suffix,
    );

    $zeros = property_exists($currency, 'info') && transaction_filter(array('quantity' => 0, 'currcode' => $currency->info['currcode']));
    $form['display']['zero'] = array(
      '#title' => t('Zero value display'),
      '#description' => t('Use html.') .' ',
      '#type' => 'textfield',
      '#default_value' => $currency->zero,
      //'#required' => property_exists($currency, 'display') ? $zeros : FALSE,
    );
    if ($zeros) {
      $form['display']['zero']['#description'] = t("Zero transaction already exist so this field is required");
    }
    else {
      $form['display']['zero']['#description'] = t("Leave blank to disallow zero value transactions");
    }

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
    );
    $form['access'] = array(
      '#title' => t('Currency access'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#tree' => TRUE,
    );
    $weight = 0;

    $form['access']['membership'] = array(
      '#title' => t('Use the currency'),
      '#description' => t('Determine which users are permitted to use this currency'),
      '#type' => 'user_chooser_many',
      '#config' => TRUE,
      '#default_value' => property_exists($currency, 'access') ? $currency->access['membership'] : 'user_chooser_segment_perms:transact',
    );
    $form['access']['trader_data'] = array(
      '#title' => t('View aggregated user transaction data'),
      '#description' => t("Such as users' balances, gross income, number of transactions"),
      '#type' => 'user_chooser_many',
      '#config' => TRUE,
      '#default_value' => property_exists($currency, 'access') ? $currency->access['trader_data'] : 'user_chooser_segment_perms:transact',
    );
    $form['access']['system_data'] = array(
      '#title' => t('View aggregated system data'),
      '#description' => t('Look at currency usage stats stripped of personal information'),
      '#type' => 'user_chooser_many',
      '#config' => TRUE,
      '#default_value' => property_exists($currency, 'access') ? $currency->access['system_data'] : 'user_chooser_segment_perms:transact',
    );
    $i = 0;
    $access_callbacks = module_invoke_all('transaction_access_callbacks');
    //These two fieldsets should REALLY use a grid of checkboxes, like on admin/people/permissions,
    //but I couldn't work out how to do it, and it might require an hook_update to convert the saved $currency objects
    $form['access_operations'] = array(
      '#title' => t('Transaction operations'),
      '#description' => t('Determine who can do what to transactions') .'. '. t('Any of the checked conditions must return TRUE'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#tree' => TRUE,
    );
    foreach (transaction_operations(TRUE, FALSE) as $op => $op_info) {
      if ($op == 'view') {
        continue;
      }
      if ($op_info['access form']) {
        $form['access_operations'][$op] = $op_info['access form']($op_info, $currency);
      }
    }

    $form['view_transaction_states'] = array(
      '#title' => t('Privacy'),
      '#description' => t('Determine who can view transactions in each state.') .' '. t('Any the checked conditions must return TRUE'),
      '#type' => 'details',
      '#group' => 'additional_settings',
      '#tree' => TRUE,
    );
    foreach (mcapi_get_states('#full') as $constant => $state) {
      $states = isset($currency->view) ? $currency->view : array();
      $form['view_transaction_states'][$constant] = array(
        '#title' => t("Transactions in state '@state'", array('@state' => $state['name'])),
        '#description' => $state['description'],
        '#type' => 'checkboxes',
        '#options' => $access_callbacks,
        '#default_value' => property_exists($currency, 'view_transaction_states') && isset($currency->view_transaction_states[$constant]) ?
           $currency->view_transaction_states[$constant] : array(current($access_callbacks)),
      );
    }

    $form['refresh'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
      '#op' => 'refresh_display',
      '#limit_validation_errors' => array(),
      '#submit' => array(array($this, 'multistepSubmit')),
      '#ajax' => array(
        'callback' => array($this, 'multistepAjax'),
        'wrapper' => 'currency-display-wrapper',
        'effect' => 'fade',
        // The button stays hidden, so we hide the Ajax spinner too. Ad-hoc
        // spinners will be added manually by the client-side script.
        'progress' => 'none',
      ),
      '#attributes' => array('class' => array('visually-hidden'))
    );

    $form['#attached'] = array(
      'css' => array(
        drupal_get_path('module', 'mcapi') . '/css/admin_currency.css',
      ),
      'js' => array(
        drupal_get_path('module', 'mcapi') . '/js/admin_currency.js',
      ),
    );

    return $form;
  }

  public function multistepSubmit($form, &$form_state) {
    $trigger = $form_state['triggering_element'];
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        $form_state['widget_settings_edit'] = TRUE;
        break;

      case 'update':
        $form_state['widget_settings'][$form_state['input']['widget']] = $form_state['values']['widget_settings'];
        $form_state['widget_settings_edit'] = NULL;
        break;

      case 'cancel':
        $form_state['widget_settings_edit'] = NULL;
        break;

      case 'refresh_display':
        break;
    }

    $form_state['rebuild'] = TRUE;
  }

  public function multistepAjax($form, &$form_state) {
    return array('display' => $form['display']);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    //check that the reservoir account is allowed to use the currency
    $callback = 'in_'. strtok($form_state['values']['access']['membership'], ':');
    $arg = strtok(':');
    if (!function_exists($callback)) {
      form_set_error('reservoir', t('Invalid callback @callback.', array('@callback' => $callback)));
    }
    elseif (!$callback(array($arg), $form_state['values']['reservoir'])) {
      form_set_error('reservoir', t('Reservoir account does not have access to the currency!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $form_state['values']['widget_settings'] = isset($form_state['widget_settings'][$form_state['values']['widget']]) ? $form_state['widget_settings'][$form_state['values']['widget']] : array();

    parent::submit($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $currency = $this->entity;
    foreach (array('access_operations', 'view_transaction_states') as $property) {
      foreach ($currency->{$property} as $key => $values) {
        $currency->{$property}[$key] = array_filter($values);
      }
    }

    if ($currency->widget == CURRENCY_WIDGET_SELECT) {
      foreach(explode("\n", $currency->select) as $line) {
        list($cent, $display) = explode('|', $line);
        //TODO: this will all be handled by the plugin.
        $currency->divisions[$cent] = trim($display);
      }
    }
    else {
      $currency->divisions = array();
    }

    $status = $currency->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Currency %label has been updated.', array('%label' => $currency->label())));
    }
    else {
      drupal_set_message(t('Currency %label has been added.', array('%label' => $currency->label())));
    }

    $form_state['redirect'] = 'admin/accounting/currencies';
  }

  /**
   * currency form validate callback
   * deconstruct, validate, reconstruct and set_value
   * this sorts out any leading zeros on the centiles
   */
  public function validate_divisions(array &$element, array &$form_state) {
    if ($form_state['values']['display']['divisions'] != CURRENCY_WIDGET_SELECT) {
      return;
    }
    $validated = array();
    $lines = explode("\n", $element['#value']);
    foreach (explode("\n", $element['#value']) as $line) {
      if (strpos($line, '|') === FALSE) {
        form_error($element, t('line "@val" should contain a pipe character, |', array('@val' => $line)));
      }
      list($cents, $display) = explode('|', $line);
      if (!is_numeric($cents) || !strlen($display)) {
        form_error($element,
          t("'@val' should be an integer from  0 to 99, followed directly by a pipe, |, followed directly by a word or phrase with no unusual characters",
            array('@val' => $line)
          )
        );
      }
      $validated[intval($cents)] = check_plain($display);
    }
    if (count($lines) <> count($validated)) {
      form_error($element, t('Keys must be unique in field @fieldname', array('@fieldname' => $element['#title'])));
    }
    if (count($validated) < 2) {
      form_error($element, t("There should be at least two lines in field '@fieldname'", array('@fieldname' => $element['#title'])));
    }
    $element_value = '';
    foreach ($validated as $cents => $display) {
      $element_value .= "$cents|$display\n";
    }
    form_set_value($element, trim($element_value), $form_state);
  }

  /**
   * currency form validate callback
   */
  public function validate_format(array &$element, array &$form_state) {
    if (strpos($element['#value'], '[quantity]') === FALSE) {
      form_error($element, t("Currency format must contain token '[quantity]'"));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/accounting/currencies/' . $this->entity->id() . '/delete';
  }

  /**
   * {@inheritdoc}
   */
  protected function alterWidgetSettingsForm(array &$settings_form, $plugin, array $form, array &$form_state) {
    $context = array(
      'widget' => $plugin,
      'form_mode' => 'full',
      'form' => $form,
    );
    drupal_alter('field_widget_settings_form', $settings_form, $form_state, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterWidgetSettingsSummary(array &$summary, $plugin) {
    $context = array(
      'widget' => $plugin,
      'field' => 'currency_type_' . $this->entity->type,
      'entity' => $this->entity,
    );
    drupal_alter('field_widget_settings_summary', $summary, $context);
  }
}
