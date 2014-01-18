<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\EntityChooser\Wallet
 */

namespace Drupal\mcapi\Plugin\EntityChooser;

use Drupal\entity_chooser\Plugin\EntityChooserInterface;

/**
 * Helps to choose wallets based on properties of their parents
 * bearing in mind that each parent could contain several wallets
 *
 * @EntityChooser(
 *   id = "wallet",
 *   label = @Translation("Select all wallets"),
 * )
 */
class Wallet implements EntityChooserInterface {

  // an array of all the walletable entities
  protected $entities;

  /**
   * The #excluded entity_ids as passed in the FormAPI element
   */
  protected $exclude;

  /**
   * The #included entity_ids as passed in the FormAPI element
   */
  protected $include;

  /**
   * Set any properties of this plugin from the given $element
   *
   * @param array $element
   *   may be empty
   * @param string $id
   *   the plugin id
   * @param array $definition
   *   the plugin definition
   */
  function __construct($element, $id, $definition) {
    if ($element) {
      $this->exclude = $element['#exclude'];
      $this->include = $element['#include'];
      //$this->setArgs($element['#args']);
    }
  }

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::getKeys()
   */
  public function getElementKeys() {
    //can't think of any for now
    return array();
  }

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::getAllValidIds()
   */
  public function validArgs() {
    return array();
  }

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::validString()
   */
  public function getIdsFromString($string) {
    $query = $this->query(array());

    if ($limit = \Drupal::config('entity_chooser.config')->get('limit')) {
      $query->range(0, $limit);
    }

    $string = '%'. db_like($string).'%';
    //in D7 there was a setting to decide whether to put the initial % there.
    $condition = db_or();
    foreach ($this->matchAgainst() as $fieldname) {
      $condition->condition('u.'.$fieldname, $string, 'LIKE');
    }
    $query->condition($condition);
    $result = $query->execute()->fetchCol();
    return $this->includeExclude($query->execute()->fetchCol());
  }

  public function getAllValidIds() {
    return db_select('mcapi_wallets', 'w')->fields('w', array('wid'))->execute()->fetchCol();
  }
  public function isValid($id) {
    return db_select('mcapi_wallets', w)->fields('w', array('wid'))->condition('wid', $id)->execute()->fetchField();
  }

  public function matchAgainst() {
    return array('name');
  }
  public function getEntityType() {
    return 'mcapi_wallet';
  }

  function setArgs(array $args) {
    debug($args, 'What args to set?');
  }

}
