<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign.
 * @todo need to find a way to limit access to these forms
 */

namespace Drupal\mcapi_1stparty\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Annotation\EntityType;

/**
 * Defines the 1stparty_editform entity.
 *
 * @ConfigEntityType(
 *   id = "1stparty_editform",
 *   label = @Translation("Transaction form design"),
 *   handlers = {
 *     "access" = "Drupal\mcapi_1stparty\FirstPartyEditFormAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\mcapi_1stparty\Form\FirstPartyFormDesigner",
 *       "edit" = "Drupal\mcapi_1stparty\Form\FirstPartyFormDesigner",
 *       "delete" = "Drupal\mcapi_1stparty\Form\FirstPartyEditFormDeleteConfirm",
 *       "enable" = "Drupal\mcapi_1stparty\Form\FirstPartyEditFormEnableConfirm",
 *       "disable" = "Drupal\mcapi_1stparty\Form\FirstPartyEditFormDisableConfirm"
 *     },
 *     "list_builder" = "Drupal\mcapi_1stparty\FirstPartyEditFormListBuilder",
 *   },
 *   admin_permission = "configure mcapi",
 *   config_prefix = "editform",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "status" = "status"
 *   },
 *   field_ui_base_route = "mcapi.admin_1stparty_editform_list",
 *   links = {
 *     "edit-form" = "/admin/accounting/forms/{1stparty_editform}",
 *     "delete-form" = "/admin/accounting/forms/{1stparty_editform}/delete",
 *     "enable" = "/admin/accounting/forms/{1stparty_editform}/enable",
 *     "disable" = "/admin/accounting/forms/{1stparty_editform}/disable",
 *   }
 * )
 */

class FirstPartyFormDesign extends ConfigEntityBase {

  public $id;
  public $path;
  public $menu;
  public $title;
  public $status;
  public $type;
  //especially because we need to include unknown field API fields as well
  public $mywallet;
  public $partner;
  public $incoming;
  public $fieldapi_presets;
  public $experience;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'id' => '',
      'path' => '',
      'menu' => [],
      'title' => '',
      'status' => 1,
      'type' => 'default',
      'mywallet' => [
        'stripped' => TRUE
      ],
      'partner' => [
        'selection' => '',
        'preset' => '',
        'stripped' => TRUE
      ],
      'incoming' => FALSE,
      'fieldapi_presets' => [
        'worth' => [],
        'description' => []
      ],
      'experience' => [
        'twig' => 'Partner: [mcapiform:secondperson]
Direction: [mcapiform:direction]
[mcapiform:worth]',
        'button' => $this->t('Preview'),
        'preview' => '',
      ],
    ];

  }

}
