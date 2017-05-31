<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Event\McapiEvents;
use Drupal\mcapi\Event\TransactionSaveEvents;
use Drupal\mcapi\TransactionOperations;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Utility\Token;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\Time;

/**
 * I don't know if it is a good idea to extend the confirm form if we want ajax.
 */
class OperationForm extends ContentEntityConfirmFormBase {

  private $action;
  private $plugin;
  private $config;
  private $eventDispatcher;
  private $renderer;
  private $destination;
  // See parent.
  protected $viewBuilder;

  /**
   * Constructor.
   *
   * @param \Drupal\Entity\EntityTypeManager $entity_type_manager
   *   The transaction viewBuilder object.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The routematching service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, Time $time, CurrentRouteMatch $route_match, RequestStack $request_stack, ContainerAwareEventDispatcher $event_dispatcher, Renderer $renderer, Token $token) {
    $this->viewBuilder = $entity_type_manager->getViewBuilder('mcapi_transaction');
    $this->action = TransactionOperations::loadOperation($route_match->getparameter('operation'));
    $this->plugin = $this->action->getPlugin();
    $this->config = $this->plugin->getConfiguration();
    $this->eventDispatcher = $event_dispatcher;
    $this->renderer = $renderer;
    $this->token = $token;
    $query = $request_stack->getCurrentRequest()->query;
    if ($query->has('destination')) {
      $this->destination = $query->get('destination');
    }
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
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
  public function getFormId() {
    return 'mcapi_' . $this->action->id() . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->config['page_title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // The transaction hasn't been created yet.
    if ($this->plugin->getPluginId() == 'mcapi_transaction.save_action') {
      // We really want to go back the populated transaction form using the back
      // button in the browser. Failing that we want to go back to whatever form
      // it was, fresh failing that we go to the user page user.page.
      return new Url('user.page');
    }
    return $this->entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->config['cancel_link'] ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // Provides the transaction_view part of the form from the action's config.
    $format = $this->config['format'];
    if ($format == 'twig') {
      // module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');.
      $renderable = [
        '#type' => 'inline_template',
        '#template' => $this->config['twig'],
        '#context' => $this->token->replace(
          $this->config['twig'],
          ['mcapi_transaction' => $this->entity],
          ['sanitize' => TRUE]
        ),
      ];
    }
    else {
      $renderable = $this->viewBuilder->view($this->entity, $format);
    }
    return $this->renderer->render($renderable);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['description']['#weight'] = -1;
    // Not sure why this is sometimes not included from TransactionViewBuilder.
    $form['#attached']['library'][] = 'mcapi/mcapi.transaction';
    $form['#attributes']['class'][] = 'transaction-operation-form';
    $form['actions']['submit']['#value'] = $this->config['button'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Sets this->entity based on $form_state.
    parent::submitForm($form, $form_state);
    $this->entity->setValidationRequired(FALSE);
    try {
      // The op might have injected values into the form, so it needs to be able
      // to access them.
      $this->plugin->execute($this->entity);
      if ($this->action->id() == 'transaction_delete') {
        if (!$this->destination) {
          // Front page.
          $this->destination = '/';
        }
      }
      $args = [
        'values' => $form_state->getValues(),
        'old_state' => $this->entity->state->value,
        'action' => $this->action,
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
          '@error' => $e->getMessage(),
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
      $path = 'transaction/' . $this->entity->serial->value;
    }
    $form_state->setRedirectUrl(Url::fromUri('base:' . $path));
  }

}
