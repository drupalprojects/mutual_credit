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
  //might group all of these into one array of presets
  //especially because we need to include unknown field API fields as well
  public $mywallet;
  public $partner;
  public $incoming;
  public $description;
  public $fieldapi_presets;
  public $other;
  public $experience;
  public $message;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'id' => '',
      'title' => '',
      'path' => '',
      'status' => 1,
      'type' => 'default',
      'incoming' => FALSE,
      'mywallet' => [
        'stripped' => TRUE
      ],
      'partner' => [
        'selection' => '',
        'preset' => '',
        'stripped' => TRUE
      ],
      'description' => [
        'preset' => '',
        'placeholder' => '',
        'stripped' => TRUE
      ],
      'fieldapi_presets' => [
        'worth' => [],//and there are likely others
      ],
      'other' => [
        'intertrade' => FALSE
      ],
      'step1' => [
        'twig1' => 'Partner: [mcapiform:secondperson]
Direction: [mcapiform:direction]
[mcapiform:worth]',
        'next1' => 'page',
        'button1' => t('Preview'), //each transaction type should have an initial state
      ],
      'step2' => [
        'title2' => t('Are you sure?'),
        'format2' => 'certificate',
        'twig2' => '',
        'next2' => 'page',
        'button2' => t('Confirm'),
        'redirect' => ''
      ],
      'message' => '',
      'cache' => NULL
    ];

  }

}
