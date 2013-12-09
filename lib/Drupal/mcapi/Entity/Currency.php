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
  public $view_transaction_states;
  public $access_operations;
  public $weight;

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
      'view_transaction_states' => array(),
      'widget' => $defintions['default_widget'],
      'widget_settings' => array(),
    );

    $values['settings'] += $currencyTypeManager->getDefaultSettings($values['type']);

    $values['widget_settings'] += $widgetManager->getDefaultSettings($values['widget']);

    $values['access'] += array(
      'membership' => 'user_chooser_segment_perms:transact',
      'trader_data' => 'user_chooser_segment_perms:transact',
      'system_data' => 'user_chooser_segment_perms:transact',
    );

    $values['access_operations'] += array(
      'undo' => array('transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'),
    );

    $values['view_transaction_states'] += array(
      0 => array('transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'),
      1 => array('transaction_access_callback_perm_transact' => 'transaction_access_callback_perm_transact')
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
}
