<?php

/**
 * @file
 * Contains \Drupal\mcapi\WalletAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
//use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\Language;


/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class WalletAccessController extends EntityAccessController {

  private $pluginManager;

  function __construct() {
    $this->pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access');
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $wallet, $op, $langcode, AccountInterface $account) {
    return TRUE;
    //remember we're only interested in view access but not for router callbacks because the wallet has no uri
    //although perhaps it should - like bitcoin!
    return $this->pluginManager
      ->getInstance($wallet->access, $wallet->settings)
      ->check($op, $account);
  }

  /**
   *
   * @param string $entity_type
   * @param EntityInterface $owner
   * @return integer
   *   The number of wallets that can be added
   *
   */
  function unused($entity_type, EntityInterface $owner) {
    //finally we check the number of wallets already owned against the max for this entity type
    $query = db_select('mcapi_wallets');
    $query->addExpression('COUNT(wid)');
    $query->condition('pid', $owner->id())->condition('entity_type', $entity_type);
    $already = $query->execute()->fetchField();
    $config_key = $entity_type.':'.$owner->bundle();
    return $config->get('entity_types.'.$config_key) - $already;
  }

}
