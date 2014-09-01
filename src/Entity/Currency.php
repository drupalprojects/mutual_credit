<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Currency.
 * TODO implement EntityWithPluginBagInterface https://drupal.org/node/2203617
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
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
 *     "access" = "Drupal\mcapi\Access\CurrencyAccessController",
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\CurrencyViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\CurrencyForm",
 *       "edit" = "Drupal\mcapi\Form\CurrencyForm",
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
 *   },
 *   links = {
 *     "canonical" = "mcapi.currency",
 *     "edit-form" = "mcapi.admin_currency_edit",
 *     "delete-form" = "mcapi.admin_currency_delete",
 *   }
 * )
 */

class Currency extends ConfigEntityBase implements CurrencyInterface, EntityOwnerInterface {

  //configEntity ids are usually strings, but this must be numeric because it is used as a fieldListItems key
  public $id;
  public $uuid;
  public $name;
  public $uid;
  public $issuance;
  public $format;
  public $zero;
  public $color;
  public $weight;
  public $deletion;
  public $ticks; //exchange rate, expressed in ticks.

  function __construct($values) {
    parent::__construct($values, 'mcapi_currency');
    $this->preformat();
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += array(
      'issuance' => 'acknowledgement',
      'format' => array('$', 0, '.', '99'),
      'zero' => FALSE,
      'color' => '000',
      'ticks' => 1,
      'deletion' => array('erase' => 'erase'),
      'weight' => 0,
      'uid' => \Drupal::CurrentUser()->id() ? : 1
    );
  }

  /**
   * {@inheritdoc}
   * make a numerical currency id for new currencies
   */
  public function save() {
    if (empty($this->id())) {
      $this->set('id', max(array_keys($this->loadMultiple())) + 1);
    }
    return parent::save();
  }

  /**
   * @see \Drupal\mcapi\Entity\CurrencyInterface::label()
   */
  public function label($langcode = NULL) {
    //TODO how to we translate this?
  	return $this->name;
  }


  /**
   * @see \Drupal\mcapi\Entity\CurrencyInterface::transactions()
   */
  public function transactions(array $conditions = array(), $serial = FALSE) {
    $conditions += array('curr_id' => $this->id());
    $serials = $this->entityManager()
      ->getStorage('mcapi_transaction')
      ->filter($conditions);
    if ($serial) {
      return count(array_unique($serials));
    }
    return count($serials);
  }

  /**
   * @see \Drupal\mcapi\Entity\CurrencyInterface::volume()
   */
  public function volume(array $conditions = array()) {
    //get the transaction storage controller
    return $this->entityManager()
      ->getStorage('mcapi_transaction')
      ->volume($this->id());
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
   * @see \Drupal\mcapi\Entity\CurrencyInterface::format()
   */
  function format($raw_num) {
    if (!is_numeric($raw_num) || !is_integer($raw_num + 0)) {
      //TODO log an error
      return '';
    }
    //if there is a minus sign this needs to go before everything
    $minus_sign = $raw_num < 0 ? '-' : '';
    $parts = $this->formatted_parts(abs($raw_num));
    //now replace the parts back into the number format
    $template = $this->format;
    foreach ($parts as $key => $num) {
      $template[$key] = $num;
    }
    return $minus_sign . implode('', $template);
  }

  /**
   * break a raw value from the database into sections which can be dropped into the parts
   */
  public function formatted_parts($raw_num) {
    $first = TRUE;
    foreach (array_reverse($this->format_nums, TRUE) as $key => $subunits) {//e.g 59
      //remove the divisor of the subunits, which is only for widgets
      if ($first && ($pos = strpos($subunits, '/'))) {
        $subunits = substr($subunits, 0, $pos);
      }
      $first = FALSE;
      if ($subunits != 0) {
        $chars = strlen($subunits);
        $subunits++;//e.g. becomes 60
        //store the remainder when divided by the $divisor
        $parts[$key] = str_pad($raw_num % $subunits, $chars, '0', STR_PAD_LEFT);
        $raw_num = floor($raw_num / $subunits);
      }
    }
    $parts[$key] = intval($raw_num);
    return array_reverse($parts, TRUE);
  }

  /**
   * @see \Drupal\mcapi\Entity\CurrencyInterface::faux_format()
   */
  public function faux_format($raw_num, $format = array()) {
    //this is a wrapper around $this->format which temporarily changes the configured
    //by default it removes
    $temp = $this->format;
    //put the passed formatter into the currency object
    $this->format = $format;
    //work out a suitable format
    if (!$this->format) {
      //make a simple temp format using the first and second numbers separated by a point
      $this->format = array('', $temp[1]);
      if (array_key_exists(3, $temp)) {
        $this->format[2] = '.';
        $this->format[3] = $temp[3];
      }
    }
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


  /**
   * not very elegant way to get the odd-keyed values from an arbitrary length array
   */
  private function preformat($reset = FALSE) {
    if (property_exists($this, 'format_nums') && !$reset)return;
    $this->format_nums = array();
    foreach ($this->format as $i => $val) {
      if ($i % 2 == 1) {
        $this->format_nums[$i] = $val;
      }
    }
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
    $format = array_reverse($this->format_nums, TRUE);
    $raw = $divisor = 0;
    foreach ($format as $key => $value) {
      $raw += $parts[$key] * ($divisor + 1);
      $divisor = $value;
    }
    $raw += $parts[1] * ($divisor);
    return $raw;
  }

  /**
   * get the exchange entity ids which reference this currency
   * @return array
   *   the ids of the exchanges
   */
  function used_in() {
    //this is a reverse lookup on another entityType.
    //so I wrote a special function just this once.
    return $this->EntityManager()->getStorage('mcapi_exchange')->using_currency($this);
  }

  /**
   * {@inheritdoc}
   */
  static function user(AccountInterface $account = NULL) {
    $exchanges = Exchange::referenced_exchanges($account, TRUE);
    return exchange_currencies($exchanges, $ticks);
  }

}

