<?php

/**
 * @file
 * Contains Drupal\mcapi_signatories\Plugin\RulesAction\MailPendingSignatories.
 */

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides "Send email" rules action.
 *
 * @RulesAction(
 *   id = "mcapi_mail_pending_signatories",
 *   label = @Translation("Mail pending signatories "),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "transaction" = @ContextDefinition("string",
 *       label = @Translation("Transaction"),
 *       description = @Translation("The pending transaction"),
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Subject"),
 *       description = @Translation("The email's subject."),
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("The email's message body. MUST include token [signhere]"),
 *     )
 *   }
 * )
 *
 * @todo: Compare this with SystemSendEmail when rules is more developed
 */
class MailPendingSignatories extends Drupal\rules\Core\RulesActionBase implements Drupal\Core\Plugin\ContainerFactoryPluginInterface {

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

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $logger, $mail_manager) {
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
   * Send a system email.
   *
   * @param Transaction $transaction
   *   Email addresses of the recipients.
   * @param string $subject
   *   Subject of the email.
   * @param string $message
   *   Email message text.
   */
  protected function doExecute($transaction, $subject, $message) {
    $uids = \Drupal::service('mcapi.signatures')->setTransaction($transaction)->waitingOn();
    foreach ($uids as $uid) {
      $account = \Drupal\user\Entity\User::load($uid);
      $message = $this->mailManager->mail(
        'mcapi_signatures',
        'signhere',
        $account->getEmail(),
        $account->getPreferredLangcode(),
        [
          'recipient' => $account,
          'serial' => $transaction->serial->value,
          'subject' => $subject,
          'message' => $message,
        ],
        $reply
      );
      if ($message['result']) {
        $this->logger->notice(
          'Mailed %name to sign transaction @num',
          ['%name' => $account->getUsername(), '@num' => $transaction->serial->value]
        );
      }
    }

  }

}
