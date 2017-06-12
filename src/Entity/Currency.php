<?php

namespace Drupal\mcapi\Entity;

use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;
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
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\CurrencyViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\CurrencyForm",
 *       "edit" = "Drupal\mcapi\Form\CurrencyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
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
 *     "add-form" = "/admin/accounting/currencies/add",
 *     "delete-form" = "/admin/accounting/currencies/{mcapi_currency}/delete",
 *   }
 * )
 */
class Currency extends ConfigEntityBase implements CurrencyInterface, EntityOwnerInterface {

  /**
   * A short code identifying the currency within this site.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The name of the currency (plural)
   *
   * Plain text, probabliy with the first char capitalised.
   *
   * @var string
   */
  public $name;

  /**
   * Something about what the currency is for.
   *
   * @var string
   */
  public $description;

  /**
   * The owner user id.
   *
   * @var integer
   */
  public $uid;

  /**
   * One of the constants, TYPE_ACKNOWLEDGEMENT, TYPE_COMMODITY, TYPE_PROMISE.
   *
   * @var string
   */
  public $issuance;

  /**
   * The html string to be rendered if the transaction value is zero.
   *
   * An empty value means that zero transactions are not allowed.
   *
   * @var string
   */
  public $zero;

  /**
   * The weight of the currency compared to other currencies.
   *
   * @var integer
   */
  public $weight;

  /**
   * The format of the currency display.
   *
   * Intricately formatted expression which tells the system how to transform a
   * 'raw' integer value from the db into a rendered currency value, or form
   * widget and back again.  Consists of an array with alternating strings used
   * for formatting and numbers to indicate max values of each part of the field.
   *
   * @var array
   */
  public $format;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += array(
      'issuance' => 'acknowledgement',
      'format' => ['$', '999', '.', '99'],
      'zero' => FALSE,
      'weight' => 0,
      'uid' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transactionCount(array $conditions = [], $serial = FALSE) {
    $query = $this->entityTypeManager()
      ->getStorage('mcapi_transaction')->getQuery()
      ->count()
      ->accessCheck(FALSE)
      ->condition('worth.curr_id', $this->id());
    foreach ($conditions as $field => $val) {
      $operator = is_array($val) ? 'IN' : '=';
      $query->condition($field, $val, $operator);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function volume(array $conditions = []) {
    return $this->entityTypeManager()
      ->getStorage('mcapi_transaction')
      ->volume($this->id(), $conditions);
  }

  public function firstUsed() {
    return $this->entityTypeManager()
      ->getStorage('mcapi_transaction')
      ->firstUsed($this);
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
  public function format($raw_num, $display = SELF::DISPLAY_NORMAL, $linked = TRUE) {
    $raw_num = (int) $raw_num;
    if ($display == SELF::DISPLAY_NATIVE) {
      return $raw_num;
    }
    // If there is a minus sign this needs to go before everything, but
    // otherwise this function behaves the same.
    $minus_sign = $raw_num < 0 ? '-' : '';

    $parts = $this->formattedParts(abs($raw_num));
    // Now replace the parts back into the number format.
    $output = $this->format;
    foreach ($parts as $key => $num) {
      $output[$key] = $num;
    }

    // Try to display the value as a machine-readable number
    if ($display == SELF::DISPLAY_PLAIN) {
      // Convert 1hr 23mins to 1.23
      // replace likely separators with decimal point dots
      // hopefully that's all of them.
      $output = preg_replace('/([.,: ]+)/', '.', $output);
      // Remove everything except numbers and dots.
      $output = preg_replace('/([^0-9.]+)/', '', $output);
    }
    $text = Markup::create($minus_sign . implode('', $output));
    if ($linked) {
      return Link::createFromRoute(
        Markup::create($text),
        'entity.mcapi_currency.canonical',
        ['mcapi_currency' => $this->id()],
        [
          'html' => TRUE,
          'attributes' => [
            'title' => t("View @currency dashboard", ['@currency' => $this->name]),
          ],
        ]
      );
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function formattedParts($raw_num) {
    if (count($this->format) < 4 ) {
      $formatted[1] = $raw_num;
    }
    if (count($this->format) > 3) {
      list($atoms) = explode('/', $this->format[3]);
      $divisor = $atoms + 1;
      $formatted[1] = floor($raw_num / $divisor);
      $formatted[3] = str_pad($raw_num % $divisor, strlen($atoms), '0', STR_PAD_LEFT);
    }
    return $formatted;
  }

  /**
   * Convert multiple components (coming from a form widget) into a raw value.
   *
   * @param array $parts
   *   Short array containing dollars & cents, or hours, minutes etc.
   *   EG [1=>9, 3=>99]
   *
   * @return int
   *   The raw value to be saved
   */
  public function unformat(array $parts) {
    if (count($this->format) < 4 ) {
      $raw_num = $parts[1];
    }
    if (count($this->format) > 3) {
      $divisor = $this->format[3] + 1;
      $raw_num = $parts[1] * $divisor + $parts[3];
    }
    return $raw_num;
  }

  /**
   * {@inheritdoc}
   */
  public function deletable() {
    $all_transactions = $this->transactionCount();
    $deleted_transactions = $this->transactionCount(['state' => 'erased']);
    return $all_transactions == $deleted_transactions;
  }

  /**
   * {@inheritdoc}
   */
  public static function formats() {
    return [
      CurrencyInterface::DISPLAY_NORMAL => t('Normal'),
      CurrencyInterface::DISPLAY_NATIVE => t('Native integer'),
      CurrencyInterface::DISPLAY_PLAIN => t('Force decimal (Rarely needed)'),
    ];
  }

  /**
   * Lookup function.
   */
  public static function issuances() {
    return [
      CurrencyInterface::TYPE_ACKNOWLEDGEMENT => t('Acknowledgement', [], ['context' => 'currency-type']),
      CurrencyInterface::TYPE_PROMISE => t('Promise of value', [], ['context' => 'currency-type']),
      CurrencyInterface::TYPE_COMMODITY => t('Backed by a commodity', [], ['context' => 'currency-type']),
    ];
  }

  /**
   * Generate a random value.
   *
   * The values should fall within a range 0-1 of the main counting unit i.e
   * dollars not cents, hours not minutes, and then something reasonably
   * rounded. We do this by choosing a nice number like 3.6  and unformatting
   * it.
   */
  public function sampleValue() {
    $parts[1] = rand(2, 10);
    // Could be of the format 59/4.
    if (isset($this->format[3])) {
      $calc = explode('/', $this->format[3]);
      $num = $calc[0] + 1;
      if (isset($calc[1])) {
        $parts[3] = rand(0, intval($calc[1])) * $num / $calc[1];
      }
      else {
        $parts[3] = rand(0, $num);
      }
    }
    return $this->unformat($parts);
  }

  /**
   * {@inheritdoc}
   *
   * Override to never invalidate the entity's cache tag; the config system
   * already invalidates it.
   */
  protected function invalidateTagsOnSave($update) {
    Cache::invalidateTags(['mcapi_currency_list']);
  }
}
