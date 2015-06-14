<?php

/**
 * @file
 *
 * Contains \Drupal\mcapi\Entity\Currency.
 */

namespace Drupal\mcapi\Entity;

use Drupal\mcapi\Plugin\Field\FieldType\Worth;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Exchanges;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Currency entity.
 *
 * @ConfigEntityType(
 *   id = "mcapi_currency",
 *   label = @Translation("Currency"),
 *   handlers = {
 *     "access" = "Drupal\mcapi\Access\CurrencyAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\CurrencyForm",
 *       "edit" = "Drupal\mcapi\Form\CurrencyForm",
 *       "delete" = "Drupal\mcapi\Form\CurrencyDeleteConfirm",
 *       "enable" = "Drupal\mcapi\Form\CurrencyEnableConfirm",
 *       "disable" = "Drupal\mcapi\Form\CurrencyDisableConfirm"
 *     },
 *     "list_builder" = "Drupal\mcapi\ListBuilder\CurrencyListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\mcapi\Entity\CurrencyRouteProvider",
 *     },
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
 *     "canonical" = "/currency/{mcapi_currency}",
 *     "collection" = "/admin/accounting/currencies",
 *     "edit-form" = "/admin/accounting/currencies/{mcapi_currency}",
 *     "delete-form" = "/admin/accounting/currencies/{mcapi_currency}/delete",
 *   }
 * )
 */

class Currency extends ConfigEntityBase implements CurrencyInterface, EntityOwnerInterface {

  public $id;

  public $uuid;

  /**
   * The name of the currency
   * Plain text, probabliy with the first char capitalised
   * @var string
   */
  public $name;

  /**
   * The owner user id
   * @var integer
   */
  public $uid;

  /*&
   * one of 'acknowledgement', 'commodity', 'exchange'
   * @var string
   */
  public $issuance;

  /**
   * The html string to be rendered of the transaction value is zero
   * An empty value means that zero transactions are not allowed
   * @var string
   */
  public $zero;

  /**
   * The color hex code to be used in rendering the currency in visualisations
   *
   * @var string
   */
  public $color;

  /**
   *  The weight of the currency compared to other currencies
   *
   * @var integer
   */
  public $weight;

  /**
   * The delete mode of transactions using this currency.
   * Note that transactions with multiple currencies will be deleted in the
   * least destructive way
   *
   * @var type
   */
  public $deletion;

  /*
   * the $format is an intricately formatted expression which tells the system
   * how to transform an integer value into a rendered currency value, or form
   * widget and back again.
   *
   * @var string
   */
  public $format;

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
      'format' => ['$', 0, '.', '99'],
      'zero' => FALSE,
      'color' => '000',
      'deletion' => ['erase' => 'erase'],
      'weight' => 0,
      'uid' => \Drupal::CurrentUser()->id() ? : 1
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function transactions(array $conditions = [], $serial = FALSE) {
    $serials = [];
    if ($this->id()) {
      $conditions += array('curr_id' => $this->id());
      $serials = $this->getTransactionStorage()->filter($conditions);
      if ($serial) {
        $serials = array_unique($serials);
      }
    }
    return count($serials);
  }

  /**
   * {@inheritdoc}
   */
  public function volume(array $conditions = []) {
    return $this->getTransactionStorage()->volume($this->id(), $conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return User::load($this->uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->uid;
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
  function format($raw_num) {
    $raw_num += 0;
    if (!is_integer($raw_num + 0)) {
      $this->loggerFactory('mcapi')->log(
        \Psr\Log\LogLevel::ERROR,
        'Attempting to format a currency value with a non-integer'
      );
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   * @todo I think this is is replaced by format('integer')
   */
  public function faux_format($raw_num, $format = []) {
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
    if (!property_exists($this, 'format_nums') || $reset) {
      $this->format_nums = [];
      foreach ($this->format as $i => $val) {
        if ($i % 2 == 1) {
          $this->format_nums[$i] = $val;
        }
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
   * {@inheritdoc}
   */
  function deletable() {
    $all_transactions = $this->transactions(array('state' => 0));
    $deleted_transactions = $this->transactions(array('state' => 0));
    return $all_transactions == $deleted_transactions;
  }

  //I think this needed to render the canonical view, which is usually assumed
  //to be a contentEntity
  //called in EntityViewBuilder::getBuildDefaults()
  function isDefaultRevision() {
    return TRUE;
  }

  private function getTransactionStorage() {
    //couldn't / shouldn't this be injected?
    if (!$this->transactionStorage) {
      $this->transactionStorage = \Drupal::entityManager()->getStorage('mcapi_transaction');
    }
    return $this->transactionStorage;
  }

}
