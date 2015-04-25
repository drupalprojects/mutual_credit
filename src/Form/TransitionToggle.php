<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\TransitionToggle.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a currency
 */
class TransitionToggle extends ConfirmFormBase {

  private $id;
  private $transition;
  private $config;

  public function __construct($configFactory, $transition_manager, $route_match) {
    $this->setConfigFactory($configFactory);
    $this->id = $route_match->getParameter('transition');
    if ($this->id) {
      $this->transition = $transition_manager->getPlugin($this->id)->getConfiguration();
    }
    $this->config = $this->configFactory->getEditable('mcapi.misc');
  }

  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mcapi.transitions'),
      $container->get('current_route_match')
    );
  }

  public function getFormId() {
    return 'transition_toggle';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $args = array('%name' => $this->transition['title']);
    return $this->config->get('active_transitions')[$this->id] ?
      $this->t('Are you sure you want to disable %name?', $args) :
      $this->t('Are you sure you want to enable %name?', $args);
    }


  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    //want to go back to the list builder but its not normal to put the list in the entity->links property
    return new Url('mcapi.admin.transactions');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will affect your transaction workflows.');
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t("I'm sure");
  }

  /**
   * {@inheritdoc}
   * @todo might want to clear the rules cache or something
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $active = $this->config->get('active_transitions');
    $active[$this->id] = !$active[$this->id];
    $this->config->set('active_transitions', $active)->save();
    if ($active[$this->id]) {
      drupal_set_message($this->t(
        'Transition %label has been enabled.',
        ['%label' => $this->transition['title']]
      ));
    }
    else {
      drupal_set_message($this->t(
        'Transition %label has been disabled.',
        ['%label' => $this->transition['title']]
      ));
    }
    $form_state->setRedirect('mcapi.admin.transactions');
  }

  public function title() {
    $args = array('@name' => $this->transition->label);
    return $plugin->getConfiguration('status') ?
      $this->t('Disable transition @name', $args) :
      $this->t('Enable transition @name', $args);
  }

}
