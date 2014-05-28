<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transition\TransitionBase.
 */
namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;

/**
 * Base class for Transitions for default methods.
 */
abstract class TransitionBase implements TransitionInterface {

  public $definition;
  public $settings;

  /**
   *
   * @param array $config
   *   the settings for this transition
   * @param string $op
   *   the id of this transition
   * @param array $plugin_definition
   *   the definition of this transition
   */
  function __construct(array $settings, $op, array $plugin_definition) {
    $this->definition = $plugin_definition;
    $this->settings = $settings;
  }

  /**
   * @see \Drupal\mcapi\TransitionInterface::opAccess($transaction)
   */
  abstract public function opAccess(TransactionInterface $transaction);

  /**
   * @see \Drupal\mcapi\TransitionInterface::settingsForm($form)
   */
  public function settingsForm(array &$form) {
    //gives array keyed page_title, twig, format, button, cancel_button
    module_load_include ('inc', 'mcapi');
    $tokens = implode(', ', mcapi_transaction_list_tokens (FALSE));
    $help = t('Use the following twig tokens: @tokens.', array('@tokens' => $tokens)) .' '.
      l(
        t('What is twig?'),
        'http://twig.sensiolabs.org/doc/templates.html',
        array('external' => TRUE)
      );
    //careful changing this form because the view transition alters it significantly
    $form['title'] = array(
      '#title' => t('Link text'),
      '#description' => t('A one word title for this transition'),
      '#type' => 'textfield',
      '#default_value' => @$this->settings['title'],
      '#placeholder' => $this->definition['label'],
      '#size' => 15,
      '#maxlength' => 15,
      '#weight' => 1,
    );
    $form['tooltip'] = array(
      '#title' => t('Short description'),
      '#description' => t('A few words suitable for a tooltop'),
      '#type' => 'textfield',
      '#default_value' => @$this->settings['tooltip'],
      '#placeholder' => $this->definition['description'],
      '#size' => 64,
      '#maxlength' => 128,
      '#weight' => 2,
    );

    $form['sure']= array(
      '#title' => t('Are you sure page'),
      '#type' => 'fieldset',
      '#weight' => 3
    );

    $form['sure']['page_title'] = array(
      '#title' => t('Page title'),
      '#description' => t ("Page title for the transition's page") . ' TODO, make this use the serial number and description tokens or twig. Twig would make more sense, in this context.',
      '#type' => 'textfield',
      '#default_value' => $this->settings['page_title'],
      '#placeholder' => t('Are you sure?'),
      '#weight' => 4,
      '#required' => TRUE
    );
    $form['sure']['format'] = array(
      '#title' => t('Transaction display'),
      '#type' => 'radios',
      //TODO might want to get the full list of the transaction entity display modes
      '#options' => array(
        'certificate' => t('Certificate (can be themed per-currency)'),
        'twig' => t('Custom twig template')
      ),
      '#default_value' => $this->settings['format'],
      '#required' => TRUE,
      '#weight' => 6
    );
    $form['sure']['twig'] = array(
      '#title' => t('Template'),
      '#description' => $help,
      '#type' => 'textarea',
      '#default_value' => @$this->settings['twig'],
      '#states' => array(
        'visible' => array(
          ':input[name="format"]' => array(
            'value' => 'twig'
          )
        )
      ),
      '#weight' => 8
    );
    $form['sure']['button']= array(
      '#title' => t('Button text'),
      '#description' => t('The text that appears on the button'),
      '#type' => 'textfield',
      '#default_value' => @$this->settings['button'],
      '#placeholder' => t ("I'm sure!"),
      '#weight' => 10,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    );

    $form['sure']['cancel_button']= array(
      '#title' => t('Cancel button text'),
      '#description' => t('The text that appears on the cancel button'),
      '#type' => 'textfield',
      '#default_value' => @$this->settings['cancel_button'],
      '#placeholder' => t('Cancel'),
      '#weight' => 12,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    );

    $form['feedback']= array(
      '#title' => t('Feedback'),
      '#type' => 'fieldset',
      '#weight' => 6
    );
    $form['feedback']['format2']= array(
      '#title' => t('Confirm form transaction display'),
      '#type' => 'radios',
      // TODO get a list of the transaction display formats from the entity type
      '#options' => array(
        'certificate' => t('Certificate'),
        'twig' => t('Twig template'),
        'redirect' => t('Redirect to path') ." TODO this isn't working yet"
      ),
      '#default_value' => @$this->settings['format2'],
      '#required' => TRUE,
      '#weight' => 14
   );
    $form['feedback']['redirect'] = array(
      '#title' => t('Redirect path'),
      '#description' => t('Enter a path from the Drupal root, without leading slash.'),
      '#type' => 'textfield',
      '#default_value' => @$this->settings['redirect'],
      '#states' => array(
        'visible' => array(
          ':input[name="format2"]' => array(
            'value' => 'redirect'
          )
        )
      ),
      '#weight' => 16
    );
    $form['feedback']['twig2']= array(
      '#title' => t('Template'),
      '#description' => $help,
      '#type' => 'textarea',
      '#default_value' => @$this->settings['twig2'],
      '#states' => array(
        'visible' => array(
          ':input[name="format2"]' => array(
            'value' => 'twig'
          )
        )
      ),
      '#weight' => 16
    );
    $form['feedback']['message']= array(
      '#title' => t('Success message'),
      '#description' => t('Appears in the message box along with the reloaded transaction certificate'),
      '#type' => 'textfield',
      '#default_value' => @$this->settings['message'],
      '#weight' => 18,
      '#placeholder' => t('The transition was successful')
    );

    $tokens = mcapi_transaction_list_tokens(TRUE);
    unset($tokens[array_search('links', $tokens)]);
    $form['notify'] = array(
      '#type' => 'fieldset',
      '#title' => t('Notification'),
      '#description' => t(
        'Customise the subject and body of the mail with the following tokens: @tokens',
        array('@tokens' => '[mcapi:'. implode('], [mcapi:', $tokens) .']')
    ),
      '#weight' => 0
    );
    $form['notify']['send'] = array(
      '#title' => t('Mail the transactees, (but not the current user)'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings['send'],
      '#weight' =>  0
    );
    $form['notify']['subject'] = array(
      '#title' => t('Mail subject'),
      '#description' => '',
      '#type' => 'textfield',
      '#default_value' => $this->settings['subject'],
      '#weight' =>  1,
      '#states' => array(
        'visible' => array(
          ':input[name="send"]' => array('checked' => TRUE)
        )
      )
    );
    $form['notify']['body'] = array(
      '#title' => t('Mail body'),
      '#type' => 'textarea',
      '#default_value' => $this->settings['body'],
      '#weight' => 2,
      '#states' => array(
        'visible' => array(
          ':input[name="send"]' => array('checked' => TRUE)
        )
      )
    );
    $form['notify']['cc'] = array(
      '#title' => t('Carbon copy to'),
      '#description' => 'A valid email address',
      '#type' => 'email',
      '#default_value' => $this->settings['cc'],
      '#weight' => 3,
      '#states' => array(
        'visible' => array(
          ':input[name="send"]' => array('checked' => TRUE)
        )
      )
    );
  }

  /**
   * @see \Drupal\mcapi\TransitionInterface::form($transaction)
   */
  public function form(TransactionInterface $transaction) {
    return array();
  }

  /**
   * @see \Drupal\mcapi\TransitionInterface::ajax_submit($form_state_values)
   */
  function ajax_submit(array $form_state_values) {
    $transaction = entity_load('mcapi_transaction', $form_state['values']['serial']);
    $renderable = $this->execute ($form_state['transaction_transition'], $transaction, $form_state['values']);
    // if this is ajax we return the result, otherwise redirect the form
    $commands[]= ajax_command_replace ('#transaction-transition-form', drupal_render ($renderable));
    ajax_deliver (array(
      '#type' => 'ajax',
      '#commands' => $commands
   ));
    exit();
  }

  /**
   * @see \Drupal\mcapi\TransitionInterface::execute($transaction, array $context)
   */
  public function execute(TransactionInterface $transaction, array $context) {

    drupal_set_message('TODO: finish making the mail work in Transitionbase::execute - it might work already!');

    if ($this->settings['send']) {
      $subject = $this->settings['subject'];
      $body = $this->settings['body'];
      if (!$subject || !$body) continue;

      //here we are just sending one mail at a time, in the recipient's language
      global $language;
      $to = implode(user_load($transaction->payer)->mail, user_load($transaction->payee)->mail);
      $params['transaction'] = $transaction;
      $params['config'] = array(
      	'subject' => $subject,
        'body' => $body,
        'cc' => $this->settings['cc']
        //bcc is not supported! This is not some cloak and dagger thing!
      );
      drupal_mail('mcapi', 'transition', $to, $language->language, $params);
    }
  }
}

