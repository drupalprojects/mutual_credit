<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransitionForm.
 */
namespace Drupal\mcapi\Form;

use Drupal\mcapi\McapiEvents;
use Drupal\mcapi\TransactionSaveEvents;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;



//I don't know if it is a good idea to extend the confirm form if we want ajax.
class TransitionForm extends EntityConfirmFormBase {

  private $transition;

  private $request;

  private $eventDispatcher;

  function __construct($request, $route_match, $transitionManager, $event_dispatcher) {
    $this->request = $request;
    $transitionId = $route_match->getparameter('transition') ? : 'view';
    $this->transition = $transitionManager->getPlugin($transitionId);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('mcapi.transition_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * calculate the route name and args
   * @return Url
   */
  private function getDestinationPath() {
    if ($this->request->getCurrentRequest()->query->has('destination')) {
      //@todo test the transition destination
      $path = $request->query->get('destination');
      $request->query->remove('destination');//can't remember why
    }
    elseif ($redirect = $this->transition->getConfiguration('redirect')) {
      $path = strtr($redirect, [
        '[uid]' => $this->currentUser()->id(),
        '[serial]' => $this->entity->serial->value
      ]);
    }
    else {
      $path = 'transaction/'.$this->entity->serial->value;
    }
    return Url::fromUri('base:'.$path);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'transaction_transition_'.$this->transition->getPluginId().'_form';
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
    $this->entity->noLinks = TRUE;
    //this provides the transaction_view part of the form as defined in the transition settings
    switch($this->transition->getConfiguration('format')) {
      case 'twig':
        module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');
        $renderable = [
          '#type' => 'inline_template',
          '#template' => $this->transition->getConfiguration('twig'),
          '#context' => get_transaction_vars($this->entity)
        ];
        break;
      default:
        $renderable = $this->entityManager->getViewBuilder('mcapi_transaction')->view(
          $this->entity,
          $this->transition->getConfiguration('format')
        );
    }
    //@todo inject the renderer service
    return \Drupal::service('renderer')->render($renderable);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->transition->getPluginId() == 'view') {
      unset($form['actions']);
    }
    //rename the confirmForm description field so it can't clash with the transaction field
    //see Edit transition
    $form['preview'] = $form['description'];
    unset($form['description']);

    //add any extra form elements as defined in the transition plugin.
    $this->transition->form($form, $this->entity);

    //not sure why this is sometimes not included from TransactionViewBuilder
    $form['#attached']['library'][] = 'mcapi/mcapi.transaction';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    //@todo in d8-beta10 it looks like EntityForm::validate is deprecated
    //parent::validate($form, $form_state);
    $this->transition->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //sets this->entity based on $form_state
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();

    //copy any fields from the form directly onto the transaction
    //EntityFormDisplay::collectRenderDisplay(
    //  $this->entity, 'admin'
    //)->extractFormValues($this->entity, $form, $form_state);
    $this->transition->setTransaction($this->entity);
    try {
      //the op might have injected values into the form, so it needs to be able to access them
      $renderable = $this->transition->execute($values);

      $events = new TransactionSaveEvents(
        clone($this->entity),
        [
          'values' => $values,
          'old_state' => $this->entity->state->value,
          'transition' => $this->transition
        ]
      );
      //namely more $renderable items?
      $this->eventDispatcher->dispatch(
        McapiEvents::TRANSITION,
        $events
      );
      if(!$renderable) {
        $renderable = ['#markup' => 'transition returned nothing renderable'];
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t(
        "Error performing @transition transition:",
        ['@transition' => $this->transition->label]
      ), 'error');
      return;
    }
    if ($message = $events->getMessage()) {
      drupal_set_message($message);
    }
    $form_state->setRedirectUrl($this->getDestinationPath());
  }


  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    //parent::copyFormValuesToEntity($entity, $form_state);
    //we are managing this function with $display->extractFormValues in $this->submitForm()
  }


  /**
   * {@inheritdoc}
   */
  public function __setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    $this->transition->setTransaction($entity);
    return $this;
  }

}

