<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\OperationForm.
 */
namespace Drupal\mcapi\Form;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\McapiEvents;
use Drupal\mcapi\Event\TransactionSaveEvents;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

//I don't know if it is a good idea to extend the confirm form if we want ajax.
class OperationForm extends ContentEntityConfirmFormBase {

  private $action;
  private $plugin;
  private $config;
  private $eventDispatcher;
  private $renderer;
  private $destination;
  protected $viewBuilder;//see parent

  /**
   *
   * @param \Drupal\mcapi\ViewBuilder\TransactionViewBuilder $transactionViewBuilder
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   * @param \Drupal\Core\Render\Renderer $renderer
   * @param \Drupal\Core\Utility\Token $token
   */
  function __construct($transactionViewBuilder, $route_match, $request_stack, $event_dispatcher, $renderer, $token) {
    $this->entityTypeManager = $entity_type_manager;
    $this->action = Mcapi::transactionActionLoad($route_match->getparameter('operation'));
    $this->plugin = $this->action->getPlugin();
    $this->config = $this->plugin->getConfiguration();
    $this->eventDispatcher = $event_dispatcher;
    $this->renderer = $renderer;
    $this->token = $token;
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
      $container->get('entity_type.manager')->getViewBuilder('mcapi_transaction'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('event_dispatcher'),
      $container->get('renderer'),
      $container->get('token')
    );
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
    if ($this->plugin->getPluginId() == 'mcapi_transaction.save_action') {//the transaction hasn't been created yet
      //we really want to go back the populated transaction form using the back button in the browser
      //failing that we want to go back to whatever form it was, fresh
      //failing that we go to the user page user.page
      return new Url('user.page');
    }
    return $this->entity->toUrl();
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
    //this provides the transaction_view part of the form as defined in the action settings
    $format = $this->config['format'];
    if ($format == 'twig') {
      //module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');
      $renderable = [
        '#type' => 'inline_template',
        '#template' => $this->config['twig'],
        '#context' => $this->token->replace(
          $this->config['twig'],
          ['mcapi_transaction' => $this->entity],
          ['sanitize' => TRUE]
        )
      ];
    }
    else {
      $renderable = $this->entityTypeManager
        ->getViewBuilder('mcapi_transaction')
        ->view($this->entity, $format);
    }
    return $this->renderer->render($renderable);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['description']['#weight'] = -1;
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
      if ($this->action->id() == 'transaction_delete') {
        if (!$this->destination) {
          $this->destination = '/';//front page
        }
      }
      $args = [
        'values' => $form_state->getValues(),
        'old_state' => $this->entity->state->value,
        'action' => $this->action
      ];
      $events = new TransactionSaveEvents(clone($this->entity), $args);
      $events->setMessage($this->config['message']);
      $this->eventDispatcher->dispatch(McapiEvents::ACTION, $events);
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
    foreach ($events->getMessages() as $type => $messages) {
      foreach ($messages as $message) {
        drupal_set_message($message, $type);
      }
    }
    if ($this->destination) {
      $path = $this->destination;
    }
    else {
      $path = 'transaction/'.$this->entity->serial->value;
    }
    $form_state->setRedirectUrl(Url::fromUri('base:'.$path));
  }


}

