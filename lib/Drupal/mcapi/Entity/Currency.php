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
 *   id = "mcapi_currencies",
 *   label = @Translation("Currency"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\CurrencyStorageController",
 *     "access" = "Drupal\mcapi\CurrencyAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\CurrencyFormController",
 *       "edit" = "Drupal\mcapi\CurrencyFormController",
 *       "delete" = "Drupal\mcapi\Form\CurrencyDeleteConfirm"
 *     },
 *     "list" = "Drupal\mcapi\CurrencyListController",
 *   },
 *   admin_permission = "configure all currencies",
 *   config_prefix = "mcapi.currency",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
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
  public $issuance;
  public $uid;
  public $reservoir;
  public $type;
  public $settings;
  public $prefix;
  public $suffix;
  public $zero;
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
    $widgetManager = \Drupal::service('plugin.manager.mcapi.currency_widget');
    $widgets = array_keys($widgetManager->getOptions($values['type']));

    $values += array(
      'settings' => array(),
      'prefix' => '$',
      'suffix' => '',
      'zero' => '',
      'access' => array(),
      'access_operations' => array(),
      'widget' => reset($widgets),
      'widget_settings' => array(),
    );

    $values['settings'] += \Drupal::service('plugin.manager.mcapi.currency_type')->getDefaultSettings($values['type']);

    $values['widget_settings'] += $widgetManager->getDefaultSettings($values['widget']);

    $values['access'] += array(
      'membership' => 'user_chooser_segment_perms:transact',
      'trader_data' => 'user_chooser_segment_perms:transact',
      'system_data' => 'user_chooser_segment_perms:transact',
    );
  }
}
