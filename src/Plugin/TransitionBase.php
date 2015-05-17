<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\TransitionBase.
 */
namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\Exchange;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\McapiEvents;
use Drupal\mcapi\TransactionSaveEvents;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for Transitions for default methods.
 */
abstract class TransitionBase extends PluginBase implements TransitionInterface {

  private $transactionRelativeManager;
  private $relatives;
  private $eventDispatcher;
  protected $entityFormBuilder;
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    //can't work out how to inject this
    $this->transactionRelativeManager = \Drupal::service('mcapi.transaction_relative_manager');
    $this->relatives = $this->transactionRelativeManager->active();
    //todo how to inject all these things into a plugin?
    $this->entityFormBuilder = \Drupal::service('entity.form_builder');
    $this->eventDispatcher = \Drupal::service('event_dispatcher');
    $this->moduleHandler = \Drupal::service('module_handler');
  }
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //gives array keyed page_title, twig, format, button, cancel_button
    $tokens = implode(', ', Exchange::transactionTokens(FALSE));
    //@note currently there is NO WAY to put html in descriptions because twig autoescapes it
    //see cached classes extending TwigTemplate->doDisplay twig_drupal_escape_filter last argument
    $twig_help_link = \Drupal::l(
      $this->t('What is twig?'),
      Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
    );
    $this->help = t(
      'Use the following twig tokens: @tokens.',
      ['@tokens' => $tokens]
    ) .' '.$twig_help_link;
    //careful changing this form because the view transition alters it significantly
    $form['title'] = [
      '#title' => t('Link text'),
      '#description' => t('A one word title for this transition'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['title'],
      '#size' => 15,
      '#maxlength' => 15,
      '#weight' => 1,
    ];
    $form['tooltip'] = [
      '#title' => t('Short description'),
      '#description' => t('A few words suitable for a tooltip'),
      '#type' => 'textfield',
      '#default_value' => @$this->configuration['tooltip'],
      '#size' => 64,
      '#maxlength' => 128,
      '#weight' => 2,
    ];

    $form['states'] = [
      '#title' => $this->t('Applies to states'),
      '#description' => $this->t('The transaction states that this transition could apply to'),
      '#type' => 'mcapi_states',
      '#multiple' => TRUE,
      '#default_value' => array_filter($this->configuration['states']),
      '#weight' => 3,
    ];
    if ($element = $this->transitionSettings($form, $form_state)) {
      $form['settings'] = [
        '#type' => 'fieldset',
        '#title' => 'Editing',
        '#weight' => 5
      ] + $element;
    }

    $form['sure']= [
      '#title' => t('Are you sure page'),
      '#type' => 'fieldset',
      '#weight' => 3
    ];
    $form['sure']['page_title'] = [
      '#title' => t('Page title'),
      '#description' => t ("Page title for the transition's page") .
        ' @todo, make this use the serial number and twig.',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['page_title'],
      '#placeholder' => t('Are you sure?'),
      '#weight' => 4,
      '#required' => TRUE
    ];
    foreach (\Drupal::entityManager()->getViewModes('mcapi_transaction') as $id => $def) {
      $view_modes[$id] = $def['label'];
    }
    $form['sure']['format'] = [
      '#title' => t('View mode'),
      '#type' => 'radios',
      '#options' => $view_modes,
      '#default_value' => $this->configuration['format'],
      '#required' => TRUE,
      '#weight' => 6
    ];
    $form['sure']['twig'] = [
      '#title' => t('Template'),
      '#description' => $this->help,//@note this is escaped in twig so links don't work
      '#type' => 'textarea',
      '#default_value' => @$this->configuration['twig'],
      '#states' => [
        'visible' => [
          ':input[name="format"]' => [
            'value' => 'twig'
          ]
        ]
      ],
      '#weight' => 8
    ];

    $form['sure']['display'] = [
      '#title' => $this->t('Display'),
      '#type' => 'radios',
      '#options' => [
        MCAPI_CONFIRM_NORMAL => $this->t('Basic - Go to a fresh page'),
        MCAPI_CONFIRM_AJAX => $this->t('Ajax - Replace the form'),
        MCAPI_CONFIRM_MODAL => $this->t('Modal - Confirm in a dialogue box')
      ],
      '#default_value' => $this->configuration['display'],
      '#weight' => 10
    ];

    $form['sure']['button']= [
      '#title' => $this->t('Button text'),
      '#description' => $this->t('The text that appears on the button'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['button'],
      '#placeholder' => $this->t("I'm sure!"),
      '#weight' => 10,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    ];

    $form['sure']['cancel_button']= [
      '#title' => $this->t('Cancel button text'),
      '#description' => $this->t('The text that appears on the cancel button'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['cancel_button'],
      '#placeholder' => t('Cancel-o'),
      '#weight' => 12,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    ];

    $form['feedback']= [
      '#title' => t('Feedback'),
      '#type' => 'fieldset',
      '#weight' => 6
    ];
    $form['feedback']['format2']= [
      '#title' => t('Confirm form transaction display'),
      '#type' => 'radios',
      // TODO get a list of the transaction display formats from the entity type
      '#options' => [
        'certificate' => t('Certificate'),
        'twig' => t('Twig template'),
        'redirect' => t('Redirect to path') ." TODO this isn't working yet"
      ],
      '#default_value' => $this->configuration['format2'],
      '#required' => TRUE,
      '#weight' => 14
    ];
    $form['feedback']['redirect'] = [
      '#title' => t('Redirect path'),
      '#description' => implode(' ', [
        t('Enter a path from the Drupal root, without leading slash. Use replacements.') . '<br />',
        t('@token for the current user id', ['@token' => '[uid]']),
        t('@token for the current transaction serial', ['@token' => '[serial]'])
      ]),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['redirect'],
      '#placeholder' => 'transaction/[serial]',//@todo check this works
      '#states' => [
        'visible' => [
          ':input[name="format2"]' => [
            'value' => 'redirect'
          ]
        ]
      ],
      '#weight' => 16
    ];
    $form['feedback']['twig2']= [
      '#title' => t('Template'),
      '#description' => $this->help,
      '#type' => 'textarea',
      '#default_value' => $this->configuration['twig2'],
      '#states' => [
        'visible' => [
          ':input[name="format2"]' => [
            'value' => 'twig'
          ]
        ]
      ],
      '#weight' => 16
    ];
    $form['feedback']['message']= [
      '#title' => t('Success message'),
      '#description' => t('Appears in the message box along with the reloaded transaction certificate.') . 'TODO: put help for user and mcapi_transaction tokens, which should be working',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['message'],
      '#placeholder' => t('The transition was successful'),
      '#weight' => 18
    ];

    $form['access'] = [
      '#title' => t('Permission'),
      '#description' => t('When to show the @label link', ['@label' => $this->configuration['title']]),
      '#type' => 'details',
      '#open' => FALSE,
      '#weight' => 8,
    ];

    $this->accessSettingsForm($form['access']);

    return $form;
  }

  protected function transitionSettings(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state){
    //this is required by the interface
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state){
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, TransactionInterface $transaction) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    return $key ?
      @$this->configuration[$key] :
      $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    //this will prevent the config form showing blanks
    return [
      'title' => '',
      'tooltip' => '',
      'page_title' => '',
      'format' => '',
      'twig' => '',
      'button' => '',
      'cancel_button' => '',
      'access' => '',
      'format2' => '',
      'redirect' => '',
      'twig2' => '',
      'message' => ''
    ];
  }


  /**
   * {@inheritdoc}
   */
  function ajax_submit(FormStateInterface $form_state) {
die('ajax_submit');
    $renderable = $this->execute(
      $form_state->get('transaction_transition'),
      Transaction::load($form_state->getValue(['serial'])),
      $form_state->getValues()
    );
    // if this is ajax we return the result, otherwise redirect the form
    $commands[]= ajax_command_replace ('#transaction-transition-form', \Drupal::service('renderer')->render($renderable));
    ajax_deliver ([
      '#type' => 'ajax',
      '#commands' => $commands
   ]);
    exit();
  }

  /**
   * {@inheritdoc}
   */
  public function accessState(TransactionInterface $transaction, AccountInterface $account) {
    return in_array($transaction->state->target_id, $this->configuration['states']);
  }

  /**
   * The default plugin access allows selection of transaction relatives.
   *
   * @param array $element
   */
  protected function accessSettingsForm(&$element) {
    $element['access'] = [
      '#type' => 'checkboxes',
      '#options' => $this->transactionRelativeManager->options(),
      '#default_value' => $this->configuration['access'],
      '#weight' => $this->configuration['weight']
    ];
  }

  /**
   * default access callback for transaction transition
   * uses transaction relatives
   *
   * @return boolean
   */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    //children can't be edited that would be too messy
    if ($transaction->parent->value) {
      return FALSE;
    }
    foreach (array_filter($this->configuration['access']) as $relative) {
      //$check if the $acocunt is this relative to the transaction
      $relative = $this->relatives[$relative];
      if ($relative->isRelative($transaction, $account)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  function calculateDependencies() {
    return [
      'module' => ['mcapi']
    ];
  }


  /**
   * Perform a transition on the transaction and save it.
   *
   * @param TransactionInterface $transaction
   *
   * @param array $values
   *   the form state values from the transition form
   *
   * @return array
   *   a renderable array
   *
   * @todo make a drush command for doing transitions
   * E.g. drush mcapi-transition [serial] edit -v description blabla
   */
  protected function baseExecute(TransactionInterface $transaction, array $values) {

    $context = [
      'values' => $values,
      'old_state' => $transaction->state->value,
    ];

    $context['transition'] = $this;
    //notify other modules, especially rules.
    //the moduleHandler isn't loaded because__construct didn't run in beta10
    /*
    $renderable = $this->moduleHandler->invokeAll(
      'mcapi_transition',
      [$transaction, $context]
    );*/

    //@todo, need to get an $output from event handlers i.e. hooks
    //namely more $renderable items?
    $this->eventDispatcher->dispatch(
      McapiEvents::TRANSITION,
      new TransactionSaveEvents(clone($transaction), $context)
    );

    $transaction->save();

    if(!$renderable) {
      $renderable = ['#markup' => 'transition returned nothing renderable'];
    }
    return $renderable;
  }

  function validateForm(array $form, FormStateInterface $form_state) {

  }

}

