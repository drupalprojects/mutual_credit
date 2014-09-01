<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\TransitionFlip.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Builds the form to delete a currency
 */
class TransitionFlip extends ConfirmFormBase {

  private $transition;

  public function __construct() {
    if ($id = \Drupal::routeMatch()->getParameter('transition')) {
      $this->transition = \Drupal::service('mcapi.transitions')
      ->getPlugin($id);
    }
  }

  public function getFormId() {
    return 'transition_flip';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $args = array('%name' => $this->transition->label);
    return $this->transition->getConfiguration('status') ?
      $this->t('Are you sure you want to disable %name?', $args) :
      $this->t('Are you sure you want to enable %name?', $args);
    }


  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    //want to go back to the list builder but its not normal to put the list in the entity->links property
    return new Url('mcapi.admin.workflow');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t("I'm sure");
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //print_r($this->transition->getConfiguration());die();
    $config = \Drupal::config('mcapi.transition.' .$this->transition->getConfiguration('id'));
    $config->set('status', !$config->get('status'));
    $config->save();
    if ($config->get('status')) {
      drupal_set_message($this->t('Transition %label has been enabled.', array('%label' => $this->transition->label)));
    }
    else {
      drupal_set_message($this->t('Transition %label has been disabled.', array('%label' => $this->transition->label)));
    }
    $form_state->setRedirect('mcapi.admin.workflow');
  }

  public function title() {
    $id = \Drupal::routeMatch()->getParameter('transition');
    $plugin = \Drupal::service('mcapi.transitions')
    ->getPlugin($id);
    $args = array('@name' => $plugin->label);
    return $plugin->getConfiguration('status') ?
      $this->t('Disable transition @name', $args) :
      $this->t('Enable transition @name', $args);
  }

}
