<?php

namespace Drupal\mcapi_cc;

use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A hook to check the limits.
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      //Drupal\migrate\Event\MigrateEvents::PRE_ROW_SAVE
      'migrate.pre_row_save' => [['migratePreRowSave']]
    ];
  }

  /**
   *
   * @param MigratePreRowSaveEvent $event
   *   No ->arguments() are passed. getSubject() gives the transaction.
   * @param string $eventName
   *   Which is always mcapi_transaction.assemble.
   * @param ContainerAwareEventDispatcher $container
   *   The container.
   */
  public function migratePreRowSave(MigratePreRowSaveEvent $event, $eventName, ContainerAwareEventDispatcher $container) {
    $migration = $event->getMigration();
//    echo $migration->id();
    if ($migration->id() == 'd7_mcapi_transaction') {
      $row = $event->getRow();
      if ($row->getSourceProperty('type') == 'remote') {
        $result = $migration->getSourcePlugin()->getDatabase()->select('mcapi_cc', 'cc')
          ->fields('cc', ['txid', 'remote_exchange_id', 'remote_user_id', 'remote_user_name'])
          ->condition('xid', $row->getSourceProperty('xid'))
          ->execute();
        $properties = (array)$result->fetch();
        foreach ($properties as $prop => $val) {
          $row->setDestinationProperty($prop, $val);
        }
      }
    }
  }

}
