<?php

/**
 * @file
 * Contains \Drupal\mcapi_forms\Entity\FirstPartyFormDesign.
 * @todo need to find a way to limit access to these forms
 */

namespace Drupal\mcapi_forms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the firstparty_editform entity.
 *
 * @ConfigEntityType(
 *   id = "firstparty_editform",
 *   label = @Translation("Transaction form design"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\mcapi_forms\Form\FirstPartyFormDesigner",
 *       "edit" = "Drupal\mcapi_forms\Form\FirstPartyFormDesigner",
 *       "delete" = "Drupal\mcapi_forms\Form\FirstPartyEditFormDeleteConfirm",
 *       "enable" = "Drupal\mcapi_forms\Form\FirstPartyEditFormEnableConfirm",
 *       "disable" = "Drupal\mcapi_forms\Form\FirstPartyEditFormDisableConfirm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\mcapi_forms\Entity\FirstPartyRoutes",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   config_prefix = "firstpartyform",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/accounting/forms/{firstparty_editform}",
 *     "delete-form" = "/admin/accounting/forms/{firstparty_editform}/delete",
 *     "enable" = "/admin/accounting/forms/{firstparty_editform}/enable",
 *     "disable" = "/admin/accounting/forms/{firstparty_editform}/disable",
 *   }
 * )
 */

class FirstPartyFormDesign extends ConfigEntityBase {

  public $id;
  public $path;
  public $wallet_link_title;
  public $menu;
  public $title;
  public $status;
  public $type;
  public $incoming;
  public $hide_one_wallet;
  public $experience;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'id' => '',
      'path' => '',
      'wallet_link_title' => 1,
      'menu' => [],
      'title' => '',
      'status' => 1,
      'type' => 'default',
      'incoming' => FALSE,
      'hide_one_wallet' => FALSE,
      'experience' => [
        'twig' => 'Partner: [mcapiform:secondperson]
Direction: [mcapiform:direction]
[mcapiform:worth]',
        'button' => t('Preview'),
        'preview' => '',
      ],
    ];

  }

  /**
  * Make a transaction entity loaded up with the defaults from the Designed form
  *
  * @return \Drupal\mcapi\Entity\Transaction
  *   a (partially populated) transaction entity
  */
  function makeDefaultTransaction($overrides = []) {
    //prepare a transaction using the defaults here
    $vars = ['type' => $this->type];
    $vars += $overrides;
    return \Drupal\mcapi\Entity\Transaction::create($vars);
  }

}
