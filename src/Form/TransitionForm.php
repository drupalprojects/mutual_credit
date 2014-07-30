<?php

namespace Drupal\mcapi\Form;


use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

//I don't know if it is a good idea to extend the confirm form if we want ajax.
class TransitionForm extends EntityConfirmFormBase {

  private $op;
  private $configuration;
  private $transaction;
  private $destination;

  function __construct() {
    $request = \Drupal::request();
    $this->transition = $request->attributes->get('transition') ? : 'view';
    $this->configuration = $this->config('mcapi.transition.'.$this->transition);

    if ($path = $this->configuration->get('redirect')) {
      $this->destination = $path;
    }
    else {
      if ($request->query->has('destination')) {
        $this->destination = $request->query->get('destination');
        $request->query->remove('destination');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'transaction_transition_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->configuration->get('page_title');
  }

  /**
   * we could add this to the plugin options.
   * although of course users don't know route names so there would be some complexity
   * How do we go back
   */
  public function getCancelRoute() {
    if ($this->transition == 'create') {//the transaction hasn't been created yet
      //we really want to go back the populated transaction form using the back button in the browser
      //failing that we want to go back to whatever form it was, fresh
      //failing that we go to the user page user.page
      return new Url('user.page');
    }
    return $this->entity->urlInfo('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->configuration->get('cancel_button') ? : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    //$this->entity->noLinks = FALSE;//this is a flag which ONLY speaks to template_preprocess_mcapi_transaction
    $this->entity->noLinks = TRUE;
    //this provides the transaction_view part of the form as defined in the transition settings
    switch($this->configuration->get('format')) {
    	case 'twig':
    	  module_load_include('inc', 'mcapi');
    	  return mcapi_render_twig_transaction($this->configuration->get('twig'), $this->entity);
    	default://certificate or even sentence, but without the links
    	  $renderable = entity_view($this->entity, $this->configuration->get('format'));
    	  return drupal_render($renderable);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    //this may be an entity form but we don't want the FieldAPI fields showing up.
    foreach (element_children($form) as $fieldname) {
      if (array_key_exists('#type', $form[$fieldname]) && $form[$fieldname]['#type'] == 'container') {
        unset($form[$fieldname]); //should do it;
      }
    }

    //add any extra form elements as defined in the transition plugin.
    $form += transaction_transitions($this->transition)->form($this->entity, $this->configuration);
    if ($this->transition == 'view') {
      unset($form['actions']);
    }

    return $form;


    //this form will submit and expect ajax
    //TODO this doesn't work in D8
    //$form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
    //TODO I don't know if those are needed
    $form['#attached']['js'][] = 'core/misc/jquery.form.js';
    $form['#attached']['js'][] = 'core/misc/ajax.js';


    //Left over from d7
     //when the button is clicked replace the whole transaction div with the results.
     $commands[] = ajax_command_replace('#transaction-'.$transaction->serial, drupal_render($form));
     return array(
      '#type' => 'ajax',
      '#commands' => $commands
     );

    return $form;
  }

  public function validate(array $form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $transaction = $this->entity;

    //the op might have injected values into the form, so it needs to be able to access them
    form_state_values_clean($form_state);
    try {
      $renderable = $transaction->transition($this->transition, $form_state['values']);
    }
    catch (\Exception $e) {
      throw new \Exception("An error occurred in performing the transition:".$e->getMessage());
    }
    if ($message = $this->configuration->get('message')) {
      //TODO put tokens in the message
      $tokens = array(
      	'user' => \Drupal::currentUser()->id(),
        'mcapi_transaction' => $transaction
      );
      drupal_set_message(\Drupal::token()->replace($message, $tokens));
    }
    //if there is no redirect then the link to this form should have a destination
    if ($redirect = $this->configuration->get('redirect')) {
      $path = strtr($redirect, array(
        '[uid]' => \Drupal::currentUser()->id(),
        '[serial]' => $transaction->serial->value
      ));
      $form_state['redirect'] = array(
        $path,
        array(),//$options to be passed to url(), I think
      );
    }

    //but since rules isn't written yet, we're going to use the transition settings to send a notification.
    if ($this->configuration->get('send')) {
      $recipients = array();
      //mail is sent to the user owners of wallets, and to cc'd people
      foreach (array('payer', 'payee') as $participant) {
        $walletowner = $transaction->payer->entity->getOwner();
        //note that a wallet owner can be any contentEntity, and is nothing to do with EntityOwnerInterface.
        //
        if ($walletowner->getEntityTypeId() == 'user') {
          $recipients[] = $walletowner->get('mail')->value;
        }
        elseif ($walletowner instanceof EntityOwnerInterface) {
          $recipients[] = $walletowner->getowner()->get('mail')->value;
        }
      }
      //with multiple recipients we have to choose one language
      //just English for now bcoz rules will sort this out
      if ($recipients) {
        drupal_mail(
          'mcapi',
          'transition',
          implode(',', $recipients),
          'en',
          array(
          	'mcapi' => $transaction,
          	'cc' => $this->configuration->get('cc'),
          	'subject' => $this->configuration->get('subject'),
          	'body' => $this->configuration->get('body')
          )
        );
      }
    }

    return $renderable;
  }

}

