<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Currency.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the Mcapi Form entity.
 *
 * @EntityType(
 *   id = "mcapi_form",
 *   label = @Translation("Designed transaction form"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\McapiFormFormController",
 *       "edit" = "Drupal\mcapi\McapiFormFormController",
 *       "delete" = "Drupal\mcapi\Form\McapiFormDeleteConfirm",
 *       "enable" = "Drupal\mcapi\Form\McapiFormEnableConfirm",
 *       "disable" = "Drupal\mcapi\Form\McapiFormDisableConfirm"
 *     },
 *     "list" = "Drupal\mcapi\McapiFormListController",
 *   },
 *   admin_permission = "configure all currencies",
 *   config_prefix = "mcapi.form",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "mcapi.admin_mcapiform_edit"
 *   }
 * )
 */
class Mcapiform extends ConfigEntityBase {

  public $id;
  public $title;
  public $status;
  public $type;
  //might group all of these into one array of presets
  //especially because we need to include unknown field API fields as well
  public $partner;
  public $direction;
  public $description;
  public $worths;
  public $created;

  public $step1;
  public $step2;
  public $message;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {

    $values += array(
    	'id' => '',
      'title' => '',
    	'status' => 1,
      'type' => 'default',
      'partner' => array(
    	  'user_chooser_config' => 'user_chooser_segment_perms:transact',
      	'preset' => ''
      ),
    	'direction' => array(
    	  'preset' => 'outgoing',
    		'widget' => 'radios',
    		'incoming' => '',
    		'outgoing' => ''
      ),
      'description' => array(
    	  'preset' => ''
      ),
      'worths' => array(),
      'created' => array(
    	  'show' => FALSE
      ),
    	'step1' => array(
        'template1' => 'Partner: [mcapiform:secondperson]
Direction: [mcapiform:direction]
[mcapiform:worth]',
    	  'next1' => 'page',
        'button1' => t('Preview'), //each transaction type should have an initial state
    	),
    	'step2' => array(
    		'format2' => 'certificate',
    		'title2' => t('Are you sure?'),
    	  'template2' => '',
    	  'next2' => 'page',
        'button2' => t('Confirm'),
    		'redirect' => ''
    	),
    	'message' => ''
    );

  }

}
