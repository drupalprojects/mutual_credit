<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Action\TransactionNotification.
 */

namespace Drupal\mcapi\Plugin\Action;


use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pays or charges an entity a fixed amount IF the specified currency is available.
 *
 * @Action(
 *   id = "transaction_notification",
 *   label = @Translation("Transaction Notification"),
 *   context = {
 *     "recipient" = @ContextDefinition("string",
 *       label = @Translation("Recipient"),
 *       description = @Translation("Tokens should be available")
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Description"),
 *       description = @Translation("What the payment is for")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *       description = @Translation("The transaction type, or workflow path")
 *     ),
 *     "reply" = @ContextDefinition("email",
 *       label = @Translation("Reply to"),
 *       description = @Translation("The mail's reply-to address. Leave it empty to use the site-wide configured address."),
 *       default_value = NULL,
 *       required = FALSE,
 *     ),
 *     "language" = @ContextDefinition("language",
 *       label = @Translation("Language"),
 *       description = @Translation("If specified, the language used for getting the mail message and subject."),
 *       default_value = NULL,
 *       required = FALSE,
 *     ),
 *     "ccs" = @ContextDefinition("string",
 *       label = @Translation("CCs"),
 *       description = @Translation("Tokens should be available")
 *     ),
 *     "transaction" = @ContextDefinition("entity",
 *       label = @Translation("Transaction"),
 *       description = @Translation("The transaction triggering the notification")
 *     ),
 *   }
 * )
 */
class TransactionNotification extends RulesActionBase {//implements ContainerFactoryPluginInterface {

  /**
   * The logger channel the action will write log messages to.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;
  
  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;


  /**
   * Constructs a SendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The alias storage service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('mcapi'),
      $container->get('plugin.manager.mail')
    );
  }
  
  /**
   * 
   * @param type $object
   * @param AccountInterface $account
   * @param type $return_as_object
   * 
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Just allow access per default for now.
    if ($return_as_object) {
      return AccessResult::allowed();
    }
    return TRUE;
  }
  
  protected function doExecute($recipient, $subject, $message, $reply = NULL, LanguageInterface $language = NULL, $ccs, $transaction) {
    
    $langcode = isset($language) ? $language->getId() : LanguageInterface::LANGCODE_SITE_DEFAULT;
    $params = [
      'subject' => $subject,
      'message' => $message,
      'ccs' => $ccs
    ];

    $recipients = implode(', ', $to);
    //@todo implement mcapi_rules to do token replacement and CCs on the mail
    $message = $this->mailManager->mail('mcapi', 'rules', $recipients, $langcode, $params, $reply);
    if ($message['result']) {
      $this->logger->notice('Successfully sent email to %recipient', ['%recipient' => $recipients]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'subject' => t('New transaction on [site:name]'),
      'body' => 'A transaction for [mcapi_transaction:worth] was created between [mcapi_transaction:payer] and [mcapi_transaction:payee]',
      'recipients' => '[mcapi_transaction:payee:mail]',
      'ccs' => ''
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('At time of writing (d8 alpha 14) there is no way to trigger actions so this action is untested');
    $w = 0;
    $form['direction'] = array(
      '#title' => t('Direction'),
      '#type' => 'radios',
      '#options' => array(
          'entitypays' => t('User pays reservoir account'),
          'paysentity' => t('Reservoir account pays user')
      ),
      '#default_value' => $this->configuration['direction'],
    	'#weight' => $w++
    );
    $form['otherwallet'] = array(
    	'#title' => $this->t('Other wallet'),
    	'#type' => 'wallet_reference_autocomplete',
    	'#default_value' => $this->configuration['otherwallet'],
    	'#weight' => $w++
    );

    $currencies = Currency::loadMultiple();
    $form['worth_items'] = array(
      '#title' => t('Worth'),
      '#type' => 'fieldset',
      '#name' => 'worth_items',//this helps in the fieldset validation
      '#description' => t('If either wallet cannot access a currency, an intertrading transaction will be attempted. Failre will produce a warning on screen and in the log.'),
      'worth' => array(
        //'#title' => t('Worths'),
        '#type' => 'worth',
        '#default_value' => $this->configuration['worth'],
        '#preset' => TRUE,//ensures that all currencies are rendered
      ),
    	'#weight' => $w++
    );
    $form['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['description'],
    	'#weight' => $w++
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();;
    foreach ($form_state->getValues() as $key => $val)
    $this->configuration['key'] = $val;
  }

}
