<?php

namespace Drupal\mcapi_forms\Plugin\migrate\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Writes the upgraded mcapiform onto an entity_form_display, creating a view_mode if needed.
 *
 * @MigrateDestination(
 *   id = "mcapi_form"
 * )
 * @todo this might need improving for multiple translations. Compare with Drupal\migrate\Plugin\migrate\destination\Config
 */
class McapiForm extends DestinationBase {

  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    static $w = 0;

    $dest = $row->getDestination();
    $mode = $dest['mode'];
    $mode_id = 'mcapi_transaction.'.$mode;
    if (!EntityFormMode::load($mode_id)) {
      //because the rollback isn't being called, as expected, this isn't deleted
      $form_mode = EntityFormMode::create(
        [
          'id' => $mode_id,
          'targetEntityType' => 'mcapi_transaction',
          //'status' => TRUE,//creates a default display
          'cache' => FALSE,//unless we can cache by user
          'label' => $dest['title']
        ]
      );
      $form_mode->save();
    }
    $display = EntityFormDisplay::load('mcapi_transaction.mcapi_transaction.'.$mode);
    if (!$display) {
      $display = EntityFormDisplay::create(
        [
          'mode' => $mode,
          'targetEntityType' => 'mcapi_transaction',
          'bundle' => 'mcapi_transaction',
          'status' => TRUE,
        ]
      );
    }
    foreach ($row->getDestinationProperty('content') as $fieldname => $info) {
      $display->setComponent($fieldname, $info);
    }
    foreach ($row->getDestinationProperty('hidden') as $fieldname => $info) {
      $display->removeComponent($fieldname);
    }

    $display
      ->setThirdPartySetting('mcapi_forms', 'title', $dest['title'])
      ->setThirdPartySetting('mcapi_forms', 'wallet_link_title', $dest['menu_title'] .' [mcapi_wallet:name]')
      ->setThirdPartySetting('mcapi_forms', 'path', $dest['path'])
      ->setThirdPartySetting('mcapi_forms', 'permission', $dest['permission'])
      ->setThirdPartySetting('mcapi_forms', 'menu_title', $dest['menu_title'])
      ->setThirdPartySetting('mcapi_forms', 'menu_parent', $dest['menu_parent'])
      ->setThirdPartySetting('mcapi_forms', 'transaction_type', $dest['transaction_type'])
      ->setThirdPartySetting('mcapi_forms', 'experience_twig', $dest['experience_twig'])
      ->setThirdPartySetting('mcapi_forms', 'experience_preview', $dest['experience_preview'])
      ->setThirdPartySetting('mcapi_forms', 'experience_button', $dest['experience_button'])
      ->setThirdPartySetting('mcapi_forms', 'menu_weight', $w++)
      ->save();

    return [$mode];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // I'm not sure what to put here. We are really creating a new EntityFormDisplay, so probably need the fields for that.
    // However this doesn't seem to be used...
    die('Drupal\mcapi_forms\Plugin\migrate\destination\McapiForm::fields() not populated');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mode']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $mode = $destination_identifier['mode'];
    if ($entity = EntityFormMode::load('mcapi_transaction.'.$mode)) {
      // This automatically deletes the displays based on it.
      $entity->delete();
    }
  }

}
