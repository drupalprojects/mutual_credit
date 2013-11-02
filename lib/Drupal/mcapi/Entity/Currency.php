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
 *     "edit-form" = "admin/accounting/currencies/{mcapi_currencies}"
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
  public $display;
  public $access;
  public $view_transaction_states;
  public $access_operations;
  public $weight;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    foreach (module_implements('permission') as $module) {
      $function = $module .'_permission';
      foreach ($function() as $perm => $info) {
        $options[$module][$perm] = strip_tags($info['title']);
      }
    }

    $values += array(
      'display' => array(
        'format' => '[quantity]',
        'divisions' => CURRENCY_DIVISION_MODE_NONE,
        'delimiter' => ':',
        'divisions_setting' => "0|/.\n25|1/4\n50|1/2\n75|3/4",
        'zero' => ''
      ),
      'access' => array(
        'membership' => array(current($options)),
        'trader_data' => array(current($options)),
        'system_data' => array(current($options))
      ),
      'access_operations' => array(),
    );
  }
}