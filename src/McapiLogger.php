<?php

/**
 * @file
 * Definition of Drupal\mcapi\McapiLogger.
 * @todo I'm totally confused about whether we need a logger in this module and how, in code to log errors if the dblog
 */

namespace Drupal\mcapi;

use Psr\Log\LoggerTrait;
use \Drupal\dblog\Logger\DbLog;
use Psr\Log\LoggerInterface;

/**
 * Blog standard logging channel
 * NB the guidance in the https://drupal.org/list-changes talks about creating my own logger
 * but no example exists in core of DbLog being extended.
 */
class McapiLogger implements LoggerInterface {
  use LoggerTrait;//dunno what this is for

  /**
   * {@inheritdoc}
   * copied from DbLog
   * I don't see any need to change it.
   */
  public function log($level, $message, array $context = []) {
    // Remove any backtraces since they may contain an unserializable variable.
    unset($context['backtrace']);

    // Convert PSR3-style messages to String::format() style, so they can be
    // translated too in runtime.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    $this->database
    ->insert('watchdog')
    ->fields(array(
        'uid' => $context['uid'],
        'type' => substr($context['channel'], 0, 64),
        'message' => $message,
        'variables' => serialize($message_placeholders),
        'severity' => $level,
        'link' => substr($context['link'], 0, 255),
        'location' => $context['request_uri'],
        'referer' => $context['referer'],
        'hostname' => substr($context['ip'], 0, 128),
        'timestamp' => $context['timestamp'],
    ))
    ->execute();
  }

}

/*
 * So what is the
//Logs a notice
\Drupal::logger('mcapi')->notice($message);
// Logs an error
\Drupal::logger('mcapi')->error($message);
 */