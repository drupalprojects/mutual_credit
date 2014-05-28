<?php

/**
 * Definition of Drupal\mcapi_1stparty\FirstPartyEditFormList.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a listing of contact categories.
 */
class FirstPartyEditFormList extends ConfigEntityListBuilder  implements FormInterface{

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['exchange'] = t('Exchange');
    $header['transaction_type'] = t('Workflow');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    if (\Drupal::currentUser()->hasPermission('configure mcapi') ||
      entity_load('mcapi_exchange', $entity->exchange)->is_member() ) {
    	$style = array('style' => $entity->status ? '' : 'color:#999');
    	//$class = array('style' => $entity->status ? 'enabled' : 'disabled');
    	//TODO make a link out of this
      $row['title'] = $style + array(
      	'data' => array(
      	  '#type' => 'link',
      	  '#title' => $entity->label(),
      	  '#route_name' => 'mcapi.1stparty.' .$entity->id()
      	)
      );
      $exchange = $entity->exchange ? entity_load('mcapi_exchange', $entity->exchange) : NULL;
      $row['exchange'] = $style + array(
      	'data' => array(
      	  '#markup' => $exchange ? $exchange->label() : t('- All -')
        )
      );

      $row['transaction_type'] = $style + array(
      	'data' => array('#markup' => $entity->type)
      );

      //TODO load mcapi.css somehow to show the disabled forms in gray using ths class above.
      //Also see the McapiForm list controller.
      return $row + parent::buildRow($entity);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function __render() {
    // @todo make this list filter by exchange, like on admin/structure/views
    // views has its own javascript though, so maybe not so simple
    $build['list'] = array(
      '#theme' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => array(),
      '#empty' => $this->t('There is no @label yet.', array('@label' => $this->entityInfo['label'])),
    );
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['list']['#rows'][$entity->id()] = $row;
      }
    }
    //get the form and put it in the final row of the table
    $build['form'] = Drupal::formBuilder()->getForm($this);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'exchanges_list';
  }

  public function buildForm(array $form, array &$form_state) {
    //$form = parent::buildForm($form, $form_state);
    $form['title'] = array(
      '#type' => 'textfield',
      '#title_display' => 'invisible',
      '#title' => t('Name of new exchange'),
      '#size' => 15,
      '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . $this->t('Add new currency') .'</div>',
    );
    //I can't help but think there's a better way to get a list of entity labels, keyed by entity id
    foreach (entity_load_multiple('mcapi_exchange') as $id => $exchange) {
      $options[$id] = $exchange->label();
    }
    $form['id'] = array(
    	'#type' => 'machine_name',
    	'#default_value' => '',
    	'#machine_name' => array(
    		'exists' => 'mcapi_editform_load',
    		'source' => array('title'),
    	),
    	'#maxlength' => 12,
    );
    $form['exchange'] = array(
      '#title' => t('Exchange') .':',
    	'#type' => 'select',
      '#empty_option' => t('- All -'),
      '#empty_value' => '',
      '#options' => $options,
    );

    $form['actions']  = array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => t('Continue')
      )
    );
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $editform = entity_create('1stparty_editform', $form_state['values']);
    $editform->save();
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_1stparty_editform_edit',
      'route_parameters' => array('1stparty_editform' => $editform->id())
    );
  }

}
