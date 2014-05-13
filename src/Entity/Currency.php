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
//use Drupal\Core\Entity\Annotation\EntityType;
//use Drupal\Core\Annotation\Translation;
use Drupal\mcapi\Plugin\Field\FieldType\Worth;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Currency entity.
 *
 * @ConfigEntityType(
 *   id = "mcapi_currency",
 *   label = @Translation("Currency"),
 *   controllers = {
 *     "storage" = "Drupal\mcapi\Storage\CurrencyStorage",
 *     "access" = "Drupal\mcapi\Access\CurrencyAccessController",
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\CurrencyViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\CurrencyFormController",
 *       "edit" = "Drupal\mcapi\Form\CurrencyFormController",
 *       "delete" = "Drupal\mcapi\Form\CurrencyDeleteConfirm",
 *       "enable" = "Drupal\mcapi\Form\CurrencyEnableConfirm",
 *       "disable" = "Drupal\mcapi\Form\CurrencyDisableConfirm"
 *     },
 *     "list_builder" = "Drupal\mcapi\ListBuilder\CurrencyListBuilder",
 *   },
 *   admin_permission = "configure mcapi",
 *   config_prefix = "currency",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "mcapi.currency",
 *     "edit-form" = "mcapi.admin_currency_edit",
 *     "enable" = "mcapi.admin_currency_enable",
 *     "disable" = "mcapi.admin_currency_disable"
 *   }
 * )
 */
class Currency extends ConfigEntityBase implements CurrencyInterface, EntityOwnerInterface {

  public $id;
  public $uuid;
  public $name;
  public $status;
  public $uid;
  public $issuance;
  public $reservoir;
  public $format;
  public $zero;
  public $color;
  public $weight;
  public $limits_plugin;
  public $limits_settings;//these actually belong to mcapi_limits module, but what hook to use?
  public $ticks; //exchange rate, expressed in ticks.

  function __construct($values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->preformat();
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    //I don't how these defaults are used
    $values += array(
      'settings' => array(),
      'issuance' => 'acknowledgement',
      'format' => array('$', 0, '.', '99'),
      'zero' => '',
      'color' => '',
      'ticks' => 1,
      'weight' => 0
    );
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::label()
   */
  public function label($langcode = NULL) {
    //TODO how to we translate this?
  	return $this->name;
  }


  /**
   * @see \Drupal\mcapi\CurrencyInterface::transactions()
   */
  public function transactions(array $conditions = array(), $serial = FALSE) {
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
  public function volume(array $conditions = array()) {
    //get the transaction storage controller
    return \Drupal::entityManager()
      ->getStorage('mcapi_transaction')
      ->volume($this->id());
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::delete()
   */
  public function delete() {
    if ($num = $this->transactions()) {
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
    return $this->get('uid');
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

  /**
   * {@inheritdoc}
   */
  //in parent, configEntityBase, $rel is set to edit-form by default - why would that be?
  //Is is assumed that every entity has an edit-form link? Any case this overrides it
  public function urlInfo($rel = 'canonical') {
    return parent::urlInfo($rel);
  }


  /**
   * @see \Drupal\mcapi\CurrencyInterface::format()
   */
  function format($raw_num) {
    //return the zero value if required
    if ($raw_num === 0 && $this->zero) {
      return $this->zero;//which is a string or url
    }
    $parts = $this->formatted_parts($raw_num);
    //now replace the parts back into the number format
    $template = $this->format;
    foreach ($parts as $key => $num) {
      $template[2*$key + 1] = $num;
    }
    //if there is a minus sign this needs to go before everything
    $minus_sign = $raw_num < 0 ? '-' : '';
    return $minus_sign . implode('', $template);
  }

  /**
   * break a raw value from the database into sections which can be dropped into the parts
   */
  public function formatted_parts($raw_num) {
    foreach  (array_reverse($this->format_nums, TRUE) as $key => $divisor) {//e.g 59
      $pad_len = strlen($divisor);
      $divisor++;//becomes 60
      //store the remainder when divided by the $divisor
      $parts[$key] = str_pad($raw_num % $divisor, $pad_len, '0', STR_PAD_LEFT);
      $raw_num = (int) ($raw_num / $divisor);
    }
    $parts[1] = $raw_num;//this is main integer value which will replace the first 0 in the format string.
    return array_reverse($parts, TRUE);
  }

  /**
   * @see \Drupal\mcapi\CurrencyInterface::format_numeric()
   */
  public function format_numeric($raw_num, $format = array('', 0, '.', 99)) {
    //this is a wrapper around $this->format which temporarily changes the configured
    //formatter setting, $this->format, and formats the $raw num witih the passed setting instead
    $temp = $this->format;
    //put the passed formatter into the currency object
    $this->format = $format;
    //reset the preformatter with the new format
    $this->preformat(TRUE);
    //get the formatted value, with the temp markup
    $result = $this->format($raw_num);
    //set the formatter back to normal
    $this->format = $temp;
    //reset the preprocessor with the normal formatter
    $this->preformat(TRUE);
    //return the number, having been formatted with the passed formatter
    return $result;
  }


  /*
   * not very elegant way to get the odd-keyed values from an arbitrary length array
   */
  function preformat($reset = FALSE) {
    if (property_exists($this, 'format_nums') && !$reset)return;
    $this->format_nums = array();
    foreach ($this->format as $key => $val) {
      if ($key % 2 == 1) {
        $this->format_nums[$key] = $val;
      }
    }
    //get rid of the first value, which we know is zero because of the CurrencyFormController validation.
    unset($this->format_nums[1]);
  }

  /**
   * convert multiple components (coming from a form widget) into a raw value
   *
   * @param array $parts
   *   a short array containing dollars and cents, or hours, minutes & seconds etc.
   * @return integer
   *   the raw value to be saved
   */
  function unformat(array $parts) {
    //the number of $parts is always one more than the number of $this->format_nums
    //need to work backwards
    //$data = array_reverse($parts, TRUE);
    $format = array_reverse($this->format_nums, TRUE);
    $raw = $divisor = 0;
    foreach ($format as $key => $value) {
      $raw += $parts[$key] * ($divisor + 1);
      $divisor = $value;
    }
    $raw += $parts[1] * ($divisor + 1);
    return $raw;
  }

}

