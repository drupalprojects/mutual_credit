<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\WalletAddForm.
 * Add a new wallet from url parameters
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WalletAddForm extends ContentEntityForm {

  private $holder;
  private $pluginManager;
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wallet_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($route_match, $entity_type_manager, $database) {
    $params = $route_match->getParameters();
    $this->holder = $entity_type_manager
      ->getStorage($params->getIterator()->key())
      ->load($params->getIterator()->current()->id());
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return
      $this->t("New wallet for @entity_type '!title'",
      array(
       '@entity_type' => $this->holder->getEntityType()->getLabel(),
       '!title' => $this->holder->label()
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['wid'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name or purpose of wallet'),
      '#default_value' => '',
    );
    $form['entity_type'] = array(
      '#type' => 'value',
      '#value' => $this->holder->getEntityTypeId()
    );
    $form['pid'] = array(
      '#type' => 'value',
      '#value' => $this->holder->id()
    );
/*
 * @todo should we do wallet permission on the walletAddForm?
    foreach ($this->pluginManager->getDefinitions() as $def) {
      $plugins[$def['id']] = $def['label'];
    }

    $form['access'] = array(
      '#title' => t('Acccess settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      'viewers' => array(
    	  '#title' => t('Who can view?'),
        '#type' => 'select',
        '#options' => $plugins
      ),
      'payees' => array(
    	  '#title' => t('Who can request from this wallet?'),
        '#type' => 'select',
        '#options' => $plugins
      ),
      'payers' => array(
    	  '#title' => t('Who can contribute to this wallet?'),
        '#type' => 'select',
        '#options' => $plugins
      )
    );
    */
    $form['submit'] = array(
    	'#type' => 'submit',
      '#value' => t('Create')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    //just check that the name isn't the same
    //if there was a wallet storage controller this unique checking would happen there.
    $values = $form_state->getValues();
    $query = $this->database->select('mcapi_wallet', 'w')
    ->fields('w', array('wid'))
    ->condition('name', $values['name']);

    if (!\Drupal::config('mcapi.settings')->get('unique_names')) {
      $query->condition('pid', $values['pid']);
      $query->condition('entity_type', $values['entity_type']);
    }
    if ($query->execute()->fetchField()) {
      $form_state->setErrorByName(
        'name',
        t("The wallet name '!name' is already used.", array('!name' => $values['name']))
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $wallet = Wallet::create($form_state->getValues());
    $wallet->save();
    $form_state->setRedirectUrl($wallet->getHolder()->urlInfo());
  }

}

