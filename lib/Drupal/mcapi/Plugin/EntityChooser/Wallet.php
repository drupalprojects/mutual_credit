<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\EntityChooser\Wallet
 */

namespace Drupal\mcapi\Plugin\EntityChooser;

use Drupal\entity_chooser\Plugin\EntityChooserBase;

/**
 * Helps to choose wallets based on properties of their parents
 * bearing in mind that each parent could contain several wallets
 *
 * @EntityChooser(
 *   id = "wallet",
 *   label = @Translation("Select all wallets"),
 * )
 */
class Wallet Extends EntityChooserBase {

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
   * {@inheritdoc}
   */
  function __construct($element, $id, $definition) {
    $this->entity_type = 'mcapi_wallet';
    parent::__construct($element, $id, $definition);
  }

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::getIdsFromString()
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

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::getAllValidIds()
   */
  public function getAllValidIds() {
    return db_select('mcapi_wallets', 'w')->fields('w', array('wid'))->execute()->fetchCol();
  }

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::isValid()
   */
  public function isValid($id) {
    return db_select('mcapi_wallets', 'w')->fields('w', array('wid'))->condition('wid', $id)->execute()->fetchField();
  }


  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::match_against()
   */
  public function matchAgainst() {
    return array('name');
  }

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::getEntityType()
   */
  public function getEntityType() {
    return 'mcapi_wallet';
  }

  /**
   * @see \Drupal\entity_chooser\Plugin\EntityChooserInterface::validArgs()
   */
  function validArgs() {
    return array(t('All wallets'));
  }

}
