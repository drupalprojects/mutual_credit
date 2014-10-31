<?php

namespace Drupal\mcapi\Form;


use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

//I don't know if it is a good idea to extend the confirm form if we want ajax.
class TransitionForm extends EntityConfirmFormBase {

  private $op;
  private $transaction;
  private $destination;
  private $transition;

  function __construct() {
    $request = \Drupal::request();
//    $this->transition = $request->attributes->get('transition') ? : 'view';
    $id = \Drupal::RouteMatch()->getparameter('transition') ? : 'view';
    $this->transition = \Drupal::service('mcapi.transitions')
      ->getPlugin($id);

    if ($path = $this->transition->getConfiguration('redirect')) {
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
    return $this->transition->getConfiguration('page_title');
  }

  /**
   * we could add this to the plugin options.
   * although of course users don't know route names so there would be some complexity
   * How do we go back
   */
  public function getCancelUrl() {
    if ($this->transition->getConfiguration('id') == 'create') {//the transaction hasn't been created yet
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
    return $this->transition->getConfiguration('cancel_button') ? : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    //$this->entity->noLinks = FALSE;//this is a flag which ONLY speaks to template_preprocess_mcapi_transaction
    $this->entity->noLinks = TRUE;
    //this provides the transaction_view part of the form as defined in the transition settings
    switch($this->transition->getConfiguration('format')) {
    	case 'twig':
    	  module_load_include('inc', 'mcapi');
    	  return mcapi_render_twig_transaction($this->transition->getConfiguration('twig'), $this->entity);
    	default://certificate or even sentence, but without the links
    	  $renderable = entity_view($this->entity, $this->transition->getConfiguration('format'));
    	  return drupal_render($renderable);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    //no form elements yet have a #weight
    //this may be an entity form but we don't want the FieldAPI fields showing up.
    foreach (\Drupal\Core\Render\Element::children($form) as $fieldname) {
      if (array_key_exists('#type', $form[$fieldname]) && $form[$fieldname]['#type'] == 'container') {
        unset($form[$fieldname]); //should do it;
      }
    }

    //add any extra form elements as defined in the transition plugin.
    $form += $this->transition->form($this->entity);

    if ($this->transition->getConfiguration('id') == 'view') {
      unset($form['actions']);
    }

    return $form;


    //this form will submit and expect ajax
    //TODO this doesn't work in D8
    $form['actions']['submit']['#attributes'] = new Attribute(array('class' => array('use-ajax-submit')));
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

  public function validate(array $form, FormStateInterface $form_state) {

    //Array ( [langcode] => en [submit] => Confirm [confirm] => 1 [form_build_id] => form-BTLEz_Go1Ki2RPLIWTMrqStNN2fZUObxqA53BkydD5w [form_token] => 8ky7alqz0rLrAZy_wRyoyq3j2RMfomQEPtlx6yqBgJU [form_id] => transaction_transition_form_id [op] => Confirm )
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $transaction = $this->entity;

    //the op might have injected values into the form, so it needs to be able to access them
    $form_state->cleanValues();;
    $values = $form_state->getValues();


    unset($values['confirm'], $values['langcode']);
    try {
      $renderable = $transaction->transition($this->transition, $values);
    }
    catch (\Exception $e) {
      throw new \Exception("An error occurred in performing the transition:".$e->getMessage());
    }
    //if there is no redirect then the link to this form should have a destination
    if ($redirect = $this->transition->getConfiguration('redirect')) {
      $path = strtr($redirect, array(
        '[uid]' => \Drupal::currentUser()->id(),
        '[serial]' => $transaction->serial->value
      ));
      $form_state->setRedirectUrl(Url::createFromPath($path));
    }
    else {
      //if it's not set, go to the transaction's own page
      $form_state->setRedirect(
        'mcapi.transaction_view',
        array('mcapi_transaction' => $transaction->serial->value)
      );
    }

    return $renderable;
  }

}

