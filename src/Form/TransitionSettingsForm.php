<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Exchange;
use Drupal\mcapi\Plugin\TransitionManager;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransitionSettingsForm extends ConfigFormBase {

  private $transition_id;
  private $transitionManager;
  private $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityManager $entityManager, TransitionManager $transitionManager, $routeMatch) {
    $this->transition_id = $routeMatch->getParameters()->get('transition');
    $this->transitionManager = $transitionManager;
    $this->entityManager = $entityManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('mcapi.transition_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_transition_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $config = $this->transitionManager->getConfig($this->transition_id);
    return $this->t(
      "'@name' transition",
      [
        '@name' => $config->get('title')
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $class = $this->transitionManager->getDefinition($this->transition_id)['class'];

    $config = $this->transitionManager->getConfig($this->transition_id);

    //@note currently there is NO WAY to put html in descriptions because twig autoescapes it
    //see cached classes extending TwigTemplate->doDisplay twig_drupal_escape_filter last argument
    $twig_help_link =
    $twig_help = t(
      'Use the following twig tokens: @tokens.',
      //transactionTokens are array keyed page_title, twig, format, button, cancel_button
      ['@tokens' => implode(', ', Exchange::transactionTokens(FALSE))]
    )
    .' '.
    \Drupal::l(
      t('What is twig?'),
      Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
    );
    //careful changing this form because the view transition alters it significantly
    $form['title'] = [
      '#title' => t('Link text'),
      '#description' => t('A one word title for this transition'),
      '#type' => 'textfield',
      '#default_value' => $config->get('title'),
      '#size' => 15,
      '#maxlength' => 15,
      '#weight' => 1,
    ];
    $form['tooltip'] = [
      '#title' => t('Short description'),
      '#description' => t('A few words suitable for a tooltip'),
      '#type' => 'textfield',
      '#default_value' => $config->get('tooltip'),
      '#size' => 64,
      '#maxlength' => 128,
      '#weight' => 2,
    ];

    $form['states'] = [
      '#title' => $this->t('Applies to states'),
      '#description' => $this->t('The transaction states that this transition could apply to'),
      '#type' => 'mcapi_states',
      '#multiple' => TRUE,
      '#default_value' => array_filter($config->get('states')),
      '#weight' => 3,
    ];

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
      '#default_value' => $config->get('page_title'),
      '#placeholder' => t('Are you sure?'),
      '#weight' => 4,
      '#required' => TRUE
    ];
    $form['sure']['format'] = [
      '#title' => t('View mode'),
      '#type' => 'radios',
      '#default_value' => $config->get('format'),
      '#required' => TRUE,
      '#weight' => 6
    ];
    foreach ($this->entityManager->getViewModes('mcapi_transaction') as $id => $def) {
      $form['sure']['format']['#options'][$id] = $def['label'];
    }
    $form['sure']['twig'] = [
      '#title' => t('Template'),
      '#description' => $twig_help,//@note this is escaped in twig so links don't work
      '#type' => 'textarea',
      '#default_value' => $config->get('twig'),
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
        TransitionManager::CONFIRM_NORMAL => $this->t('Basic - Go to a fresh page'),
        TransitionManager::CONFIRM_AJAX => $this->t('Ajax - Replace the form'),
        TransitionManager::CONFIRM_MODAL => $this->t('Modal - Confirm in a dialogue box')
      ],
      '#default_value' => $config->get('display'),
      '#weight' => 10
    ];

    $form['sure']['button']= [
      '#title' => $this->t('Button text'),
      '#description' => $this->t('The text that appears on the button'),
      '#type' => 'textfield',
      '#default_value' => $config->get('button'),
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
      '#default_value' => $config->get('cancel_button'),
      '#placeholder' => t('Cancel-o'),
      '#weight' => 12,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    ];

    $form['feedback']= [
      '#title' => $this->t('Feedback'),
      '#type' => 'fieldset',
      '#weight' => 6
    ];
    $form['feedback']['redirect'] = [
      '#title' => $this->t('Redirect path'),
      '#description' => implode(' ', [
        $this->t('Enter a path from the Drupal root, without leading slash. Use replacements.') . '<br />',
        $this->t('@token for the current user id', ['@token' => '[uid]']),
        $this->t('@token for the current transaction serial', ['@token' => '[serial]'])
      ]),
      '#type' => 'textfield',
      '#default_value' => $config->get('redirect'),
      '#placeholder' => 'transaction/[serial]',//@todo check this works
      '#weight' => 16
    ];
    $form['feedback']['message']= [
      '#title' => $this->t('Success message'),
      '#description' => $this->t('Appears in the message box along with the reloaded transaction certificate.') . 'TODO: put help for user and mcapi_transaction tokens, which should be working',
      '#type' => 'textfield',
      '#default_value' => $config->get('message'),
      '#placeholder' => $this->t('The transition was successful'),
      '#weight' => 18
    ];

    $class::settingsFormTweak($form, $form_state, $config);
    $form['access'] = [
      '#title' => $this->t('Permission'),
      '#description' => $this->t('When to show the @label link', ['@label' => $config->get('title')]),
      '#type' => 'details',
      '#open' => FALSE,
      '#weight' => 8,
    ];
    $class::accessSettingsElement($form['access'], $config->get('access'));

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $class = $this->transitionManager->getDefinition($this->transition_id)['class'];
    $class::validateSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $transition = NULL) {
  	$form_state->cleanValues();;

    $config = $this->transitionManager->getConfig($this->transition_id, TRUE);
    foreach ($form_state->getValues() as $key => $val) {
      $config->set($key, $val);
    }
    $config->save();
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('mcapi.admin.transactions');


    //@todo if the transition is View then clear the transaction render cache
    //tag might be called 'mcapi_transaction_view'
  }

  /**
   * {@inheritdoc}
   * @note I don't know when this is used.
   */
  protected function getEditableConfigNames() {
    return ['mcapi.transition.'.$this->transition_id];
  }
}

