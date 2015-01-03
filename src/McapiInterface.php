<?php

/**
 * @file
 * Contains \Drupal\mcapi\Mcapi.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * 
 * 
 */
interface Mcapi {
  
  /**
   * helper function to get the token names for helptext token service and twig
   * get the entity properties from mcapi_token_info, then the fieldapi fields
   * this function would be handy for any entity_type, so something equivalent may exist already
   *
   * @param boolean
   *   if TRUE the result will include tokens to non-fields, such as the transition links
   *
   * @return array
   *   names of replicable elements in the transaction
   */
  function transactionTokens($include_virtual = FALSE);
  
  /**
   * Helper for theming transactions either through theme system or tokens
   *
   * @param array &$vars
   *
   * @param TransactionInterface $transaction
   */
  function processTransactionVars(array &$vars, TransactionInterface $transaction);
}
