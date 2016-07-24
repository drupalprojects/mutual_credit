<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide running balance for a given transaction.
 *
 * @note reads from the transaction index table
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("transaction_running_balance")
 *
 * @todo inject transaction storage controller
 */
class RunningBalance extends Worth {

  private $transactionStorage;

  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transactionStorage = $entity_type_manager->getStorage('mcapi_transaction');
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function query() {
    // Determine the wallet id for which we want the balance.
    // It could from one of two args, or a filter.
    $this->addAdditionalFields();

  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $worth_field = $this->getEntity($values)->worth;
    $vals = [];
    foreach ($worth_field->currencies() as $curr_id) {
      $raw = $this->transactionStorage->runningBalance(
        $this->getFirstWalletFieldAlias($values),
        $curr_id,
        $values->xid,
        'xid'
      );
      $vals[] = ['curr_id' => $curr_id, 'value' => $raw];
    }
    $worth_field->setValue($vals);
    $options = [
      'label' => 'hidden',
      'settings' => [],
    ];
//    if (property_exists($values, 'curr_id')) {
//      $options['settings']['curr_ids'] = [$values->curr_id];
//    }
    return $worth_field->view($options);
  }

  /**
   * helpers
   */
  private function getFirstWalletFieldAlias($values) {
    static $falias;
    if (!$falias) {
      $q = $this->view->getQuery();
      foreach ($q->tables as $table_name => $relations) {
        foreach (array_keys($relations) as $alias) {
          $info = $q->getTableInfo($alias);
          if ($info['table'] == 'mcapi_wallet' && $info['join']->leftField == 'wallet_id') {
            foreach ($q->fields as $falias => $f_info) {
              if ($f_info['table'] == $info['alias']) {
                continue 3;
              }
            }
          }
        }
      }
      if (!$falias) {
        throw new \Exception('Having difficulties rendering the running balance. Best to remove it');
      }
    }
    return $values->{$falias};
  }

}
