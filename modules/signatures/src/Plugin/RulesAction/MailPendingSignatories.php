<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\mcapi\Entity\Transaction;
use Drupal\user\Entity\User;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
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
class MailPendingSignatories extends RulesActionBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
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
      $container->get('logger.channel.mcapi'),
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
  protected function doExecute(Transaction $transaction, $subject, $message) {
    $uids = \Drupal::service('mcapi.signatures')->setTransaction($transaction)->waitingOn();
    foreach ($uids as $uid) {
      $account = User::load($uid);
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
