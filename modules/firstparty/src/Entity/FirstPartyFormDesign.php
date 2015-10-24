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
 * Defines the firstparty_editform entity.
 *
 * @ConfigEntityType(
 *   id = "firstparty_editform",
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
 *     "route_provider" = {
 *       "html" = "Drupal\mcapi_1stparty\Entity\FirstPartyRoutes",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   config_prefix = "editform",
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
  
  /**
  * Make a transaction entity loaded up with the defaults from the Designed form
  *
  * @return \Drupal\mcapi\Entity\Transaction Transaction
  *   a (partially populated) transaction entity
  */
  function makeDefaultTransaction() {
    //the partner is either the holder of the current page's entity, under certain circumstances
    //or is taken from the form preset.
    //or is yet to be determined.
    $partner = $this->partner['preset'] ? : '';
    //prepare a transaction using the defaults here
    $vars = ['type' => $this->type];
    //now handle the payer and payee, based on partner and direction
    if ($this->incoming) {
      $vars['payee'] = \Drupal::currentUser()->id();
      $vars['payer'] = $partner;
    }
    else {
      $vars['payer'] = \Drupal::currentUser()->id();
      $vars['payee'] = $partner;
    }
    foreach ($this->fieldapi_presets as $fieldname => $setting) {
      if (isset($setting['preset'])) {
        $vars[$fieldname] = $setting['preset'];
      }
    }
    return \Drupal\mcapi\Entity\Transaction::create($vars);
  }

}
