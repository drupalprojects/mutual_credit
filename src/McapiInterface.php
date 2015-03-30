<?php

/**
 * @file
 * Contains \Drupal\mcapi\Mcapi.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi\WalletInterface;

/**
 * 
 * 
 */
interface Mcapi {
  
  
  /**
   * Helper for theming transactions either through theme system or tokens
   *
   * @param array &$vars
   *
   * @param TransactionInterface $transaction
   */
  public static function processTransactionVars(array &$vars, TransactionInterface $transaction);
  
 }

