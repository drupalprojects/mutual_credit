<?php

/**
 * @file
 * Contains \Drupal\mcapi\CurrencyInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a currency configuration entity.
 */
interface CurrencyInterface extends ConfigEntityInterface {

  /**
   * returns the currency label
   *
   * @return string
   */
  public function label($langcode = NULL);

  /**
   * return the number of transactions, in all states
   *
   * @param array $conditions
   *   an array of conditions to meet, keyed by mcapi_entity property name
   * @param boolean $serial
   *   whether to return the number of serials, or the number of xids
   *
   * @return integer.
   */
  public function transactions(array $conditions, $serial = FALSE);


  /**
   * return the sum of all transactions, in all states
   *
   * @param array $conditions
   *   an array of conditions to meet, keyed by mcapi_entity property name
   *
   * @return integer
   *   raw quantity which should be formatted using currency->format($value);
   */
  public function volume(array $conditions);


  /**
   * check that a currency has no transactions and if so, call the parent delete method
   */
  public function delete();


  /**
   * Format the value as a decimal which resembles the formatted value
   *
   * @var integer
   */
  public function format_decimal($value);


  /**
   * apply the currency's own formatting to the quantity.
   * note that the raw value may bear little resemblance to the final value
   *
   * @var integer
   */
  public function format($value);


  /**
   * Get the Currency Type Plugin
   * @return object plugin
   */
  public function getPlugin();

  /**
   * Get the currencies widget plugin
   * Likely to be just once per page, so no need for saving the manager or the plugin
   *
   * @return object
   */
  public function getWidgetPlugin();

  /**
   * Fetch the Currency Type.
   *
   * @return string
   *  The plugin Id for the Currency Type.
   */
  public function getCurrencyType();
  /*
   * //TODO I don't know if hook implementations should be part of the interface
   */
  //public static function preCreate(EntityStorageInterface $storage_controller, array &$values);
  //public function __construct(array $values, $entity_type);
  //public static function postDelete(EntityStorageInterface $storage_controller, array $entities);
  //public static function postDelete(EntityStorageInterface $storage_controller, array $entities);
}