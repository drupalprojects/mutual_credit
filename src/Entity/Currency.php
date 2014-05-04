<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Currency.
 * TODO implement EntityWithPluginBagInterface https://drupal.org/node/2203617
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\mcapi\Plugin\Field\FieldType\Worth;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Currency entity.
 *
 * "storage" = "Drupal\mcapi\CurrencyStorage",
 *
 * @EntityType(
 *   id = "mcapi_currency",
 *   label = @Translation("Currency"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\CurrencyStorage",
 *     "access" = "Drupal\mcapi\CurrencyAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\CurrencyFormController",
 *       "edit" = "Drupal\mcapi\CurrencyFormController",
 *       "delete" = "Drupal\mcapi\Form\CurrencyDeleteConfirm",
 *       "enable" = "Drupal\mcapi\Form\CurrencyEnableConfirm",
 *       "disable" = "Drupal\mcapi\Form\CurrencyDisableConfirm"
 *     },
 *     "list" = "Drupal\mcapi\CurrencyListBuilder",
 *   },
 *   admin_permission = "configure mcapi",
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
class Currency extends ConfigEntityBase implements CurrencyInterface, EntityOwnerInterface {

  /**
   * Holds the transaction type plugin
   */
  private $plugin;

  /**
   * Holds the plugin object for the Widget when it is loaded
   */
  private $currencyTypeManager;
  private $typePlugin;
  private $widgetManager;
  private $widgetPlugin;

  public $id;
  public $uuid;
  public $name;
  public $status;
  public $uid;
  public $issuance;
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
  public $weight;
  public $limits_plugin;
  public $limits_settings;
  public $ticks; //exchange rate, expressed in ticks.

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->currencyTypeManager = \Drupal::service('plugin.manager.mcapi.currency_type');
    $this->widgetManager = \Drupal::service('plugin.manager.mcapi.currency_widget');
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $definitions = \Drupal::service('plugin.manager.mcapi.currency_type')->getDefinition($values['type']);

    $values += array(
      'settings' => array(),
    	'issuance' => 'acknowledgement',
      'prefix' => '$',
      'suffix' => '',
      'zero' => '',
      'color' => '',
      'access_operations' => array(),
      'access_undo' => array(),
      'widget' => $definitions['default_widget'],
      'widget_settings' => array(),
    );

    $values['settings'] += \Drupal::service('plugin.manager.mcapi.currency_type')->getDefaultSettings($values['type']);

    $values['widget_settings'] += \Drupal::service('plugin.manager.mcapi.currency_widget')->getDefaultSettings($values['widget']);

    $values['access_undo'] += array(
      1 => array('perm_manage' => 'perm_manage'),
      -1 => array('perm_manage' => 'perm_manage', 'is_signatory' => 'is_signatory')
    );
    $values['access_operations'] = array();//depends on what other operations are available.
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::getCurrencyType()
   */
  public function getCurrencyType() {
    return $this->type;
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::label()
   */
  public function label($langcode = NULL) {
    //TODO how to we translate this?
  	return $this->name;
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::getPlugin()
   */
  public function getPlugin() {
    if (!$this->plugin) {
      $this->plugin = \Drupal::service('plugin.manager.mcapi.currency_type')->getInstance(array(
        'currency' => $this,
        'configuration' => $this->settings,
      ));
    }
    return $this->plugin;
  }

  /**
   * does this replace the above getPlugin?
   * @todo
   * //not sure the difference between dependencies and pluginbags
   * https://drupal.org/node/2220437
   * https://drupal.org/node/2211557
   */
  function getPluginBag() {
    return parent::getPluginBag();
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::getWidgetPlugin()
   */
  public function getWidgetPlugin() {
    $settings = array(
      'currency' => $this,
      'configuration' => $this->widget_settings,
    );
    return \Drupal::service('plugin.manager.mcapi.currency_widget')->getInstance($settings);
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::format()
   */
  public function format($value) {
    if ($value === 0 && $this->zero) {
      return $this->zero;
    }
    //if there is a minus sign this needs to go before everything
    $minus_sign = $value < 0 ? '-' : '';
    return $minus_sign . $this->prefix . $this->getPlugin()->format(abs($value)) . $this->suffix;
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::format_raw()
   */
  public function format_decimal($value) {
    return $this->getPlugin()->decimal($value);
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::transactions()
   */
  public function transactions(array $conditions, $serial = FALSE) {
    $serials = \Drupal::entityManager()
      ->getStorage('mcapi_transaction')
      ->filter($conditions);
    if ($serial) {
      return count(array_unique($serials));
    }
    return count($serials);
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::volume()
   */
  public function volume(array $conditions) {
    //get the transaction storage controller
    return \Drupal::entityManager()
      ->getStorage('mcapi_transaction')
      ->volume($this->id());
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::delete()
   */
  public function delete() {
    if ($num = $this->transactions(FALSE)) {
      drupal_set_message(t('Before the currency can be deleted, @num transactions must be deleted from the database.', array('@num' => $num)) .' '.
        t('Use drush-wipeslate or edit the database manually'), 'error');
      return;
    }
    parent::delete();
  }


  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

}

