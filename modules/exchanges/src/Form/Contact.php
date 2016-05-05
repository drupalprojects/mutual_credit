<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Form\Contact.
 * Edit all the fields on an exchange
 */

namespace Drupal\mcapi_exchanges\Form;

use Drupal\mcapi_exchanges\ExchangeInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;


class Contact extends ContentEntityForm {

  protected $flood;

  protected $logger;

  protected $mailHandler;

  protected $dateFormatter;

  protected $languageManager;

  protected $mailManager;

  public function __construct($flood, $logger, $mail_handler, $date_formatter, $language_manager, $mail_manager) {
    $this->flood = $flood;
    $this->logger = $logger;
    $this->mailHandler = $mail_handler;
    $this->dateFormatter = $date_formatter;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('logger.factory')->get('contact'),
      $container->get('contact.mail_handler'),
      $container->get('date.formatter'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $user = $this->currentUser();

    if (count($this->languageManager->getLanguages()) > 1) {
      if (\Drupal::moduleHandler()->moduleExists('locale')) {
        //@todo prepare languages
        $form['intro'] = [
          '#type' => 'item',
          '#value' => t('In this exchange we speak @languages', ['@languages' => implode(',', 'english')]),
          '#weight' => -1
        ];
      }
    }
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => 0
    );
    $form['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#required' => TRUE,
      '#weight' => 2
    );
    if ($user->isAnonymous()) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }
    // Do not allow authenticated users to alter the name or email values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->getUsername();
      $form['name']['#required'] = FALSE;
      $form['name']['#plain_text'] = $user->getUsername();

      $form['mail']['#type'] = 'item';
      $form['mail']['#value'] = $user->getEmail();
      $form['mail']['#required'] = FALSE;
      $form['mail']['#plain_text'] = $user->getEmail();
    }

    $form['recipient'] = array(
      '#type' => 'item',
      '#title' => $this->t('To'),
      '#plain_text' => $this->t('The team @ @exchange', ['@exchange' => $this->entity->label()]),
      '#weight' => 4
    );
    $form['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#weight' => 6
    ];

    $form['copy'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send yourself a copy'),
      // Do not allow anonymous users to send themselves a copy, because it can
      // be abused to spam people.
      '#access' => $user->isAuthenticated(),
      '#weight' => 8
    );

    $form['#attributes']['class'][] = 'contact-form';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#weight' => 10
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser()->hasPermission('administer contact forms') && (!$this->currentUser()->hasPermission('administer users'))) {

      $limit = $this->config('contact.settings')->get('flood.limit');
      $interval = $this->config('contact.settings')->get('flood.interval');

      if (!$this->flood->isAllowed('contact', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('You cannot send more than %limit messages in @interval. Try again later.', array(
          '%limit' => $limit,
          '@interval' => $this->dateFormatter->formatInterval($interval),
        )));
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->sendMailMessages($form_state->getValues());

    $this->flood->register('contact', $this->config('contact.settings')->get('flood.interval'));
    drupal_set_message($this->t('Your message has been sent.'));

    // To avoid false error messages caused by flood control, redirect away from
    // the contact form; either to the contacted user account or the front page.
    $form_state->setRedirectUrl($this->entity->toUrl());
  }

  /**
   * {@inheritdoc}
   */
  private function sendMailMessages($values) {
    // Clone the sender, as we make changes to mail and name properties.
    $sender_cloned = clone User::load($this->currentUser()->id());

    if ($sender_cloned->isAnonymous()) {
      $sender_cloned->mail = $values['mail'];
      // For the email message, clarify that the sender name is not verified; it
      // could potentially clash with a username on this site.
      $sender_cloned->name = $this->t('(non member) @name ', array('@name' => $values['name']));
    }
    $headers = [];
    if ($values['copy']) {
      $headers['cc'] = $sender_cloned->getEmail();
    }

    // Send email to the recipient(s).
    $this->mailManager->mail(
      'mcapi_exchange',
      'contact',
      $this->entity->mail->value,
      $this->languageManager->getDefaultLanguage()->getId(),
      [
        'message' => $values['message'],
        'sender' => $sender_cloned,
        'recipient' => $this->entity->mail
      ],
      $sender_cloned->getEmail(),
      $headers
    );

    $this->logger->notice("%sender-name (@sender-from) sent %recipient-name an email.\n@body", [
      '%sender-name' => $sender_cloned->getUsername(),
      '@sender-from' => $sender_cloned->getEmail(),
      '%recipient-name' => $this->entity->label(),
      '@body' => $values['message']
    ]);
  }

}

