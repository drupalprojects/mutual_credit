<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send an email tell the user about a newly created transaction.
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
/**
 * Implements ContainerFactoryPluginInterface.
 */
class TransactionNotification extends RulesActionBase {

  /**
   * The logger channel the action will write log messages to.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The mail manager service.
   *
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
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Just allow access per default for now.
    if ($return_as_object) {
      return AccessResult::allowed();
    }
    return TRUE;
  }

  /**
   * Send the mail notification.
   *
   * @param AccountInterface $recipient
   *   The user who will receive the email.
   * @param string $subject
   *   The subject line of the email.
   * @param array $message
   *   Paragraphs of the email. Don't know whether translated.
   * @param string $reply
   *   The reply-to: address.
   * @param array $ccs
   *   Email addresses to cc the message to.
   * @param Transaction $transaction
   *   The transaction the email is concerned about.
   */
  protected function doExecute(AccountInterface $recipient, $subject, $message, $reply, $ccs, Transaction $transaction) {
    $recipients = implode(', ', $to);
    // @todo implement mcapi_rules to do token replacement and CCs on the mail
    $message = $this->mailManager->mail(
      'mcapi',
      'rules',
      $recipient->getEmail(),
      $recipient->getPreferredLangcode(TRUE),
      [
        'subject' => $subject,
        'message' => $message,
        'ccs' => $ccs,
      ],
      $reply
    );
    if ($message['result']) {
      $this->logger->notice('Successfully sent email to %recipient.', ['%recipient' => $recipients]);
    }
    else {
      $this->logger->error('Failed to notify %recipient of transaction.', ['%recipient' => $recipients]);
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
      'ccs' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    debug('At time of writing (d8 alpha 14) there is no way to trigger actions so this action is untested');
    $w = 0;
    $form['direction'] = array(
      '#title' => t('Direction'),
      '#type' => 'radios',
      '#options' => array(
        'entitypays' => t('User pays reservoir account'),
        'paysentity' => t('Reservoir account pays user'),
      ),
      '#default_value' => $this->configuration['direction'],
      '#weight' => $w++,
    );
    $form['otherwallet'] = array(
      '#title' => $this->t('Other wallet'),
      '#type' => 'wallet_reference_autocomplete',
      '#default_value' => $this->configuration['otherwallet'],
      '#weight' => $w++,
    );

    $form['worth_items'] = array(
      '#title' => t('Worth'),
      '#type' => 'fieldset',
    // This helps in the fieldset validation.
      '#name' => 'worth_items',
      '#description' => t('If either wallet cannot access a currency, an intertrading transaction will be attempted. Failre will produce a warning on screen and in the log.'),
      'worth' => array(
        // '#title' => t('Worths'),.
        '#type' => 'worth',
        '#default_value' => $this->configuration['worth'],
    // Ensures that all currencies are rendered.
        '#preset' => TRUE,
      ),
      '#weight' => $w++,
    );
    $form['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['description'],
      '#weight' => $w++,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();;
    foreach ($form_state->getValues() as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }

}
