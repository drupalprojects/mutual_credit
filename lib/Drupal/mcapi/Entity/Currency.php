<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Currency.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the Currency entity.
 *
 * @EntityType(
 *   id = "mcapi_currency",
 *   label = @Translation("Currency"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\CurrencyStorageController",
 *     "access" = "Drupal\mcapi\CurrencyAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\CurrencyFormController",
 *       "edit" = "Drupal\mcapi\CurrencyFormController",
 *       "delete" = "Drupal\mcapi\Form\CurrencyDeleteConfirm",
 *       "enable" = "Drupal\mcapi\Form\CurrencyEnableConfirm",
 *       "disable" = "Drupal\mcapi\Form\CurrencyDisableConfirm"
 *     },
 *     "list" = "Drupal\mcapi\CurrencyListController",
 *   },
 *   admin_permission = "configure all currencies",
 *   config_prefix = "mcapi.currency",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "mcapi.admin_currency_edit"
 *   }
 * )
 */
class Currency extends ConfigEntityBase implements CurrencyInterface {
  /**
   * Holds the plugin object for the Widget when it is loaded
   */
  private $widgetPlugin;

  public $id;
  public $uuid;
  public $name;
  public $status;
  public $issuance;
  public $uid;
  public $reservoir;
  public $type;
  public $settings;
  public $prefix;
  public $suffix;
  public $zero;
  public $color;
  public $widget;
  public $widget_settings;
  public $formatter;
  public $formatter_settings;
  public $access;
  public $access_view;
  public $access_undo;
  public $access_operations;
  public $weight;
  public $limits_plugin;
  public $limits_settings;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    $currencyTypeManager = \Drupal::service('plugin.manager.mcapi.currency_type');
    $widgetManager = \Drupal::service('plugin.manager.mcapi.currency_widget');
    $widgets = array_keys($widgetManager->getOptions($values['type']));

    $defintions = $currencyTypeManager->getDefinition($values['type']);

    $values += array(
      'settings' => array(),
    	'issuance' => 'acknowledgement',
      'prefix' => '$',
      'suffix' => '',
      'zero' => '',
      'color' => '',
      'access' => array(),
      'access_operations' => array(),
      'access_view' => array(),
      'access_undo' => array(),
      'widget' => $defintions['default_widget'],
      'widget_settings' => array(),
    );

    $values['settings'] += $currencyTypeManager->getDefaultSettings($values['type']);

    $values['widget_settings'] += $widgetManager->getDefaultSettings($values['widget']);
    //TODO this will use a new wallet_chooser plugin
    $values['access'] += array(
      'membership' => 'user_chooser_segment_perms:transact',
      'trader_data' => 'user_chooser_segment_perms:transact',
      'system_data' => 'user_chooser_segment_perms:transact',
    );
    $values['access_view'] += array(
      1 => array('perm_transact' => 'perm_transact'),
      0 => array('perm_manage' => 'perm_manage', 'is_signatory' => 'is_signatory'),
      -1 => array('is_signatory' => 'is_signatory'),
    );
    $values['access_undo'] += array(
      1 => array('perm_manage' => 'perm_manage'),
      -1 => array('perm_manage' => 'perm_manage', 'is_signatory' => 'is_signatory')
    );
  }

  /**
   * Fetch the Currency Type.
   *
   * @return string
   *  The plugin Id for the Currency Type.
   */
  public function getCurrencyType() {
    return $this->type;
  }

  public function label($langcode = NULL) {
  	return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    $cache_tags = array();
    foreach ($entities as $currency) {
      $cache_tags['mcapi.available_currency'] = $currency->id();
    }
    cache_invalidate_tags($cache_tags);
  }

  /**
   * Get the currencies widget plugin
   */
  public function getWidgetPlugin() {
    if (!$this->widgetPlugin) {
      $this->widgetPlugin = \Drupal::service('plugin.manager.mcapi.currency_widget')->getInstance(array(
        'currency' => $this,
        'configuration' => $this->widget_settings,
      ));
    }

    return $this->widgetPlugin;
  }
  /*
   * return the $value formatted with this currency
   */
  public function format($value) {
    //get the formatter...
    return '#formatted:'.$value;
  }
  /*
   * return the number of transactions, in all states
   */
  public function transactions() {
    //get the transaction storage controller
    $transactionStorageController = \Drupal::entityManager()->getStorageController('mcapi_transaction');
    return $transactionStorageController->count($this->id());
  }
  /*
   * return the number of transactions, in all states
   */
  public function volume() {
    //get the transaction storage controller
    $transactionStorageController = \Drupal::entityManager()->getStorageController('mcapi_transaction');
    return $this->format($transactionStorageController->volume($this->id()));
  }

  /*
   * check that a currency has no transactions before deleting it.
   */
  public function delete() {
    if ($this->transactions()) {
      drupal_set_message("Transactions must be deleted from the database, before the currency can be deleted. use drush-wipeslate or edit the database manually", 'error');
      return;
    }
    parent::delete();
  }
}
