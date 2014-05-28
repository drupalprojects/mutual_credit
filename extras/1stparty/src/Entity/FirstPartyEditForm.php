<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\Entity\FirstPartyEditForm.
 */

namespace Drupal\mcapi_1stparty\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the 1stparty_form entity.
 *
 * @ConfigEntityType(
 *   id = "1stparty_editform",
 *   label = @Translation("Designed transaction form"),
 *   controllers = {
 *     "access" = "Drupal\mcapi_1stparty\FirstPartyEditFormAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi_1stparty\FirstPartyEditFormController",
 *       "edit" = "Drupal\mcapi_1stparty\FirstPartyEditFormController",
 *       "delete" = "Drupal\mcapi_1stparty\Form\FirstPartyEditFormDeleteConfirm",
 *       "enable" = "Drupal\mcapi_1stparty\Form\FirstPartyEditFormEnableConfirm",
 *       "disable" = "Drupal\mcapi_1stparty\Form\FirstPartyEditFormDisableConfirm"
 *     },
 *     "list_builder" = "Drupal\mcapi_1stparty\FirstPartyEditFormList",
 *   },
 *   admin_permission = "configure mcapi",
 *   config_prefix = "editform",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "mcapi.admin_1stparty_editform_edit",
 *     "enable" = "mcapi.admin.1stparty_editform.enable_confirm",
 *     "disable" = "mcapi.admin.1stparty_editform.disable_confirm"
 *   }
 * )
 */
class FirstPartyEditForm extends ConfigEntityBase {

  public $id;
  public $exchange;
  public $path;
  public $title;
  public $status;
  public $type;
  //might group all of these into one array of presets
  //especially because we need to include unknown field API fields as well
  public $partner;
  public $direction;
  public $description;
  public $worths;
  public $other;
  public $experience;
  public $message;
  public $cache;//TODO cache by user,

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += array(
    	'id' => '',
      'title' => '',
      'path' => '',
    	'status' => 1,
      'type' => 'default',
      'partner' => array(
        //@todo fill in the selection with something the entity_reference widget would understand
    	  'selection' => '',
      	'preset' => ''
      ),
    	'direction' => array(
    	  'preset' => 'outgoing',
    		'widget' => 'radios',
    		'incoming' => 'I request',
    		'outgoing' => 'I am paying'
      ),
      'description' => array(
    	  'preset' => '',
    	  'placeholder' => ''
      ),
        //this needs to be a worths field, but I don't know how to make such an object
      'worths' => array(
    	  'preset' => array('credunit' => array('curr_id' => 'credunit', 'value' => 0))
      ),
      'other' => array(
        'intertrade' => FALSE
      ),
    	'step1' => array(
        'twig1' => 'Partner: [mcapiform:secondperson]
Direction: [mcapiform:direction]
[mcapiform:worth]',
    	  'next1' => 'page',
        'button1' => t('Preview'), //each transaction type should have an initial state
    	),
    	'step2' => array(
    		'title2' => t('Are you sure?'),
    		'format2' => 'certificate',
    	  'twig2' => '',
    	  'next2' => 'page',
        'button2' => t('Confirm'),
    		'redirect' => ''
    	),
    	'message' => '',
      'cache' => NULL
    );

  }

  public function label($langcode = NULL) {
  	return $this->title;
  }
}
