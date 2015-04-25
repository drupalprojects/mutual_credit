<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\TransitionBase.
 */
namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\Exchange;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Entity\Transaction;
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

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    //can't work out how to inject this
    $this->transactionRelativeManager = \Drupal::service('mcapi.transaction.relatives');
    $this->relatives = $this->transactionRelativeManager->active();
  }
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //gives array keyed page_title, twig, format, button, cancel_button
    $tokens = implode(', ', Exchange::transactionTokens(FALSE));
    //TODO currently there is NO WAY to put html in descriptions because twig autoescapes it
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
      '#default_value' => array_filter($this->configuration['states'])
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
      '#description' => t ("Page title for the transition's page") . ' TODO, make this use the serial number and description tokens or twig. Twig would make more sense, in this context.',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['page_title'],
      '#placeholder' => t('Are you sure?'),
      '#weight' => 4,
      '#required' => TRUE
    ];
    $form['sure']['format'] = [
      '#title' => t('Transaction display'),
      '#type' => 'radios',
      //TODO might want to get the full list of the transaction entity display modes
      '#options' => [
        'certificate' => t('Certificate (can be themed per-currency)'),
        'twig' => t('Custom twig template')
      ],
      '#default_value' => $this->configuration['format'],
      '#required' => TRUE,
      '#weight' => 6
    ];
    $form['sure']['twig'] = [
      '#title' => t('Template'),
      '#description' => $this->help,//TODO this is escaped in twig so links don't work
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
   * this method is for plugins which don't have a form, like view and create
   * @see \Drupal\mcapi\TransitionInterface::form($transaction)
   */
  public function form(TransactionInterface $transaction) {
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
    ];
  }


  /**
   * {@inheritdoc}
   */
  function ajax_submit(FormStateInterface $form_state) {
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

  public function accessState($transaction) {
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
   *
   * @todo rewrite this with transaction relatives.
  */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
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

}

