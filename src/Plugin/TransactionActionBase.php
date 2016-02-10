<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\TransactionActionBase.
 *
 * @todo currently there's no easy way to override the redirect in actionFormBase::Save. Might need an alter hook
 */
namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for transaction actions
 */
abstract class TransactionActionBase extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  protected $transactionRelativeManager;
  protected $entityFormBuilder;
  protected $moduleHandler;
  protected $entityTypeManager;
  private $entityDisplayRepository;

  const CONFIRM_NORMAL = 0;
  const CONFIRM_AJAX = 1;
  const CONFIRM_MODAL = 2;

  function __construct(array $configuration, $plugin_id, array $plugin_definition, $entity_form_builder, $module_handler, $transaction_relative_manager, $entity_type_manager, $entity_display_respository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFormBuilder = $entity_form_builder;
    $this->moduleHandler = $module_handler;
    $this->transactionRelativeManager = $transaction_relative_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_respository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.form_builder'),
      $container->get('module_handler'),
      $container->get('mcapi.transaction_relative_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    $result = $this->accessOp($object, $account) && $this->accessState($object, $account);

    if (!$return_as_object) {
      return $result;
    }
    return $result ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Check whether this action is applicable for the current state of the transaction
   *
   * @param TransactionInterface $transaction
   * @param AccountInterface $account
   *
   * @return boolean
   *   TRUE if the transaction is in a viewable state
   */
  protected function accessState(TransactionInterface $transaction, AccountInterface $account = NULL) {
    $state = $transaction->state->target_id;
    return !empty($this->configuration['states'][$state]);
  }

  /**
   * Access control function to determine whether this
   * action can be performed on a given transaction
   *
   * @param TransactionInterface $transaction
   * @param AccountInterface $account
	 *
   * @return Boolean
   *   TRUE if access is granted
   */
  protected function accessOp(TransactionInterface $transaction, AccountInterface $account = NULL) {
    if (!$transaction->parent->value) {
      //children can't be edited that would be too messy
      return $this->transactionRelativeManager
        ->activatePlugins($this->configuration['access'])
        ->isRelative($transaction, $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state){
    $elements['title'] = [
      '#title' => t('Name of action to show on transaction'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['title'],
      '#maxlength' => 20,
      '#size' => 20,
      '#weight' => 1,
    ];
    $elements['tooltip'] = [
      '#title' => t('Short description'),
      '#description' => t('A few words suitable for a tooltip'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['tooltip'],
      '#size' => 64,
      '#maxlength' => 128,
      '#weight' => 2,
    ];

    $elements['states'] = [
      '#title' => $this->t('Applies to states'),
      '#description' => $this->t('The transaction states which this action could apply to'),
      '#type' => 'mcapi_states',
      '#multiple' => TRUE,
      '#default_value' => array_filter($this->configuration['states']),
      '#weight' => 3,
    ];
    $elements['access'] = [
      '#title' => t('Transaction relatives'),
      '#description' => $this->t('Which of these can @label the transaction?', ['@label' => $this->configuration['title']]),
      '#type' => 'transaction_relatives',
      '#default_value' => $this->configuration['access'],
      '#weight' => 8,
    ];

    $elements['sure'] = [
      '#title' => t('Are you sure page'),
      '#type' => 'fieldset',
      '#weight' => 3
    ];
    $elements['sure']['page_title'] = [
      '#title' => t('Page title'),
      '#description' => t ("Page title for the action's confirm page") .
        ' @todo, make this use the serial number and twig.',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['page_title'],
      '#placeholder' => t('Are you sure?'),
      '#weight' => 4,
      '#required' => TRUE
    ];
    $elements['sure']['format'] = [
      '#title' => t('View mode'),
      '#type' => 'radios',
      '#default_value' => $this->configuration['format'],
      '#required' => TRUE,
      '#weight' => 6
    ];
    foreach ($this->entityDisplayRepository->getViewModes('mcapi_transaction') as $id => $def) {
      $elements['sure']['format']['#options'][$id] = $def['label'];
    }

    $elements['sure']['twig'] = [
      '#title' => t('Template'),
      '#description' => Mcapi::twigHelp(),//@note this is escaped in twig so links don't work
      '#type' => 'textarea',
      '#default_value' => $this->configuration['twig'],
      '#states' => [
        'visible' => [
          ':input[name="format"]' => [
            'value' => 'twig'
          ]
        ]
      ],
      '#weight' => 8
    ];

    $elements['sure']['display'] = [
      '#title' => $this->t('Display'),
      '#type' => 'radios',
      '#options' => [
        SELF::CONFIRM_NORMAL => $this->t('Basic - Go to a fresh page'),
        SELF::CONFIRM_AJAX => $this->t('Ajax - Replace the form'),
        SELF::CONFIRM_MODAL => $this->t('Modal - Confirm in a dialogue box')
      ],
      '#default_value' => $this->configuration['display'],
      '#weight' => 10
    ];

    $elements['sure']['button'] = [
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

    $elements['sure']['cancel_link'] = [
      '#title' => $this->t('Cancel link text'),
      '#description' => $this->t('The text that appears on the cancel button'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['cancel_link'],
      '#placeholder' => t('Cancel'),
      '#weight' => 12,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    ];

    $elements['message'] = [
      '#title' => $this->t('Success message'),
      '#description' => $this->t('Appears in the message box along with the reloaded transaction certificate.') . 'TODO: put help for user and mcapi_transaction tokens, which should be working',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['message'],
      '#placeholder' => $this->t('The operation was successful'),
      '#weight' => 18
    ];
    return $elements;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    //this was required by PluginFormInterface
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state){
    $values = array_diff_key($form_state->getValues(), array_flip(['plugin_id', 'label', 'id', 'plugin']));
    foreach ($values as $field_name => $value) {
      $this->configuration[$field_name] = $value;
    }
    //@todo how does this play with the views operations field which offers to add a redirect to the link to the operation?
    $form_state->setRedirect('mcapi.admin.workflow');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    //if this IS NOT overridden the transaction will be saved as it was loaded
    $object->save();
    if ($this->configuration['message']) {
      drupal_set_message($this->configuration['message']);
    }
  }

  /**
   * {@inheritdoc}
   */
  function calculateDependencies() {
    return [
      'module' => ['mcapi']
    ];
  }


  //the following are from ConfigurablePluginInterface

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => '',
      'tooltip' => '',
      'states' => [],
      'page_title' => '',
      'format' => '',
      'twig' => '',
      'display' => '',
      'weight' => 0,
      'access' => '',
      'button' => '',
      'cancel_link' => '',
      'message' => '',
    ];
  }

}
