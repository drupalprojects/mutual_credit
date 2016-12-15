<?php

namespace Drupal\mcapi_signatures\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\Condition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a payee relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "pending_signatory",
 *   label = @Translation("Pending signatory"),
 *   description = @Translation("Users whose signature the transaction is awaiting")
 * )
 */
class PendingSignatory extends PluginBase implements TransactionRelativeInterface, ContainerFactoryPluginInterface {

  private $database;

  /**
   * Constructor.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * Injector.
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    // @todo extend the entity query to cover such queries so we don't have to call the db from here.
    return $this->database->select('mcapi_signatures', 's')
      ->fields('s')
      ->condition('uid', $account->id())
      ->condition('serial', $transaction->serial->value)
      ->condition('signed', 0)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    $query->join('mcapi_wallet', 'signature_wallets', 'mcapi_transaction.payer = signature_wallets.wid OR mcapi_transaction.payee = signature_wallets.wid');
    $query->join('users', 'signatory_users', "signatory_users.uid = signature_wallets.holder_entity_id AND signature_wallets.holder_entity_type = 'user'");
    $query->join('mcapi_signatures', 'signatories', 'signatories.uid = signatory_users.uid AND signatories.signed = 0');
    $or_group->condition('signatories.uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    // @todo inject the database connection
    return $this->database->select('mcapi_signatures', 's')->fields('s', ['uid'])
      ->condition('serial', $transaction->serial->value)
      ->condition('signed', 0)
      ->execute()->fetchCol();
  }

}
