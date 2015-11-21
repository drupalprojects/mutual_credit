<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransitionForm.
 */
namespace Drupal\mcapi\Form;

use Drupal\mcapi\McapiEvents;
use Drupal\mcapi\TransactionSaveEvents;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

//I don't know if it is a good idea to extend the confirm form if we want ajax.
class TransitionForm extends ContentEntityConfirmFormBase {

  private $action;
  private $plugin;
  private $config;

  private $destination;

  private $eventDispatcher;

  /**
   * 
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   */
  function __construct($route_match, $request_stack, $event_dispatcher) {
    $this->action = mcapi_transaction_action_load($route_match->getparameter('operation'));
    $this->plugin = $this->action->getPlugin();
    $this->config = $this->plugin->getConfiguration();
    $this->eventDispatcher = $event_dispatcher;
    $query = $request_stack->getCurrentRequest()->query;
    if ($query->has('destination')) {
      $this->destination = $query->get('destination');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * calculate the route name and args
   * 
   * @return Url
   */
  private function getDestinationPath() {
    if ($this->destination) {
      $path = $this->destination;
    }
    elseif ($redirect = $this->config['redirect']) {
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
    return 'mcapi_'.$this->action->id().'_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->config['page_title'];
  }

  /**
   * we could add this to the plugin options.
   * although of course users don't know route names so there would be some complexity
   * How do we go back
   */
  public function getCancelUrl() {
    if ($this->plugin->getPluginId() == 'mcapi_transaction.create_action') {//the transaction hasn't been created yet
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
    return $this->config['cancel_link'] ? : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $this->entity->noLinks = TRUE;
    //this provides the transaction_view part of the form as defined in the transition settings
    $format = $this->config['format'];
    if ($format == 'twig') {
      module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');
      $renderable = [
        '#type' => 'inline_template',
        '#template' => $this->config['twig'],
        '#context' => get_transaction_vars($this->entity),
      ];
    }
    else {
      $renderable = $this->entityManager
        ->getViewBuilder('mcapi_transaction')
        ->view($this->entity, $format);
    }
    //@todo inject the renderer service
    return \Drupal::service('renderer')->render($renderable);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    //rename the confirmForm description field so it can't clash with the transaction description field
    //@see Edit transition
    $form['preview'] = $form['description'];
    unset($form['description']);
    $form['preview']['#weight'] = -1;

    //not sure why this is sometimes not included from TransactionViewBuilder
    $form['#attached']['library'][] = 'mcapi/mcapi.transaction';
    $form['#attributes']['class'][] = 'transaction-operation-form';
    $form['actions']['submit']['#value'] = $this->config['button'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //sets this->entity based on $form_state
    parent::submitForm($form, $form_state);
    $this->entity->setValidationRequired(FALSE);
    try {
      //the op might have injected values into the form, so it needs to be able to access them
      $this->plugin->execute($this->entity);
      if ($this->action->id() != 'transaction_delete') {
        $this->entity->setValidationRequired(FALSE)->save();
      }
      $events = new TransactionSaveEvents(
        clone($this->entity),
        [
          'values' => $form_state->getValues(),
          'old_state' => $this->entity->state->value,
          'action' => $this->action
        ]
      );
      $this->eventDispatcher->dispatch(McapiEvents::ACTION, $events);
      if ($message = $this->config['messsage']) {
        drupal_set_message($message);
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t(
        "Error performing @action action: @error",
        [
          '@action' => $this->config['title'], 
          '@error' => $e->getMessage()
        ]
      ), 'error');
      return;
    }
    if ($message = $events->getMessage()) {
      drupal_set_message($message);
    }
    $form_state->setRedirectUrl($this->getDestinationPath());
  }

}

