<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

//I don't know if it is a good idea to extend the confirm form if we want ajax.
class TransitionForm extends EntityConfirmFormBase {

  private $transitionId;
  private $transition;
  private $request;

  function __construct($request, $route_match, $transitionManager) {
    $this->request = $request;
    $this->transitionId = $route_match->getparameter('transition') ? : 'view';
    $this->transition = $transitionManager->getPlugin($this->transitionId);
  }

  /**
   * calculate the route name and args
   * @return Url
   */
  private function getDestinationPath() {
    if ($this->request->getCurrentRequest()->query->has('destination')) {
      //@todo test the transition destination
      $path = $request->query->get('destination');
      $request->query->remove('destination');//can't remember why
    }
    elseif ($this->transition->getConfiguration('format2') == 'redirect') {
      if ($redirect = $this->transition->getConfiguration('redirect')) {
        $path = strtr($redirect, [
          '[uid]' => $this->currentUser()->id(),
          '[serial]' => $this->entity->serial->value
        ]);
      }
    }
    if ($path) {
      $url = Url::fromUri('base:'.$path);
    }
    else {
      //if it's not set, go to the transaction's own page
      $url = Url::fromRoute(
        'entity.mcapi_transaction.canonical',
        ['mcapi_transaction' => $this->entity->serial->value]
      );
    }
    return $url;
  }

  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('mcapi.transitions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'transaction_transition_'.$this->transitionId.'form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->transition->getConfiguration('page_title');
  }

  /**
   * we could add this to the plugin options.
   * although of course users don't know route names so there would be some complexity
   * How do we go back
   */
  public function getCancelUrl() {
    if ($this->transition->getConfiguration('id') == 'create') {//the transaction hasn't been created yet
      //we really want to go back the populated transaction form using the back button in the browser
      //failing that we want to go back to whatever form it was, fresh
      //failing that we go to the user page user.page
      return new Url('user.page');
    }
    return $this->entity->urlInfo('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->transition->getConfiguration('cancel_button') ? : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    //$this->entity->noLinks = FALSE;//this is a flag which ONLY speaks to template_preprocess_mcapi_transaction
    $this->entity->noLinks = TRUE;
    //this provides the transaction_view part of the form as defined in the transition settings
    switch($this->transition->getConfiguration('format')) {
      case 'twig':
        module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');
        return [
          '#type' => 'inline_template',
          '#template' => $this->transition->getConfiguration('twig'),
          '#context' => get_transaction_vars($this->entity)
        ];
      default://certificate or even sentence, but without the links
        //$renderable = Transaction::view(
        $renderable = \Drupal::entityManager()->getViewBuilder('mcapi_transaction')->view(
          $this->entity,
          $this->transition->getConfiguration('format')
        );
        return \Drupal::service('renderer')->render($renderable);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    //remane the confirmForm description field so it can't clash with the transaction field
    //see Edit transition
    $form['transaction'] = $form['description'];unset($form['description']);
    //no form elements yet have a #weight
    //this may be an entity form but we don't want the FieldAPI fields showing up.
    foreach (\Drupal\Core\Render\Element::children($form) as $fieldname) {
      if (array_key_exists('#type', $form[$fieldname]) && $form[$fieldname]['#type'] == 'container') {
       unset($form[$fieldname]); //should do it;
      }
    }
    //add any extra form elements as defined in the transition plugin.
    $form = $this->transition->form($this->entity) + $form;

    if ($this->transitionId == 'view') {
      unset($form['actions']);
    }

    //not sure why this is sometimes not included from TransactionViewBuilder
    $form['#attached']['library'][] = 'mcapi/mcapi.transaction';
    return $form;

    //this form may submit and expect ajax
    $form['#attached']['library'][] = 'core/jquery.form';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    //Left over from d7
    //when the button is clicked replace the whole transaction div with the results.
    $commands[] = ajax_command_replace(
      '#transaction-'.$transaction->serial,
      \Drupal::service('renderer')->render($form)
    );
    return [
      '#type' => 'ajax',
      '#commands' => $commands
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    //Don't worry the parent calls $transaction->validate();
    //do we need to validate form input? I guess the plugins don't support it yet.
    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    //the op might have injected values into the form, so it needs to be able to access them
    $values = $form_state->getValues();

    unset($values['confirm'], $values['langcode']);
    try {
      $renderable = $this->entity->transition($this->transition, $values);
    }
    catch (\Exception $e) {
      $mess = t(
        "Error performing @transition transition:",
        ['@transition' => $this->transition->label]
      );
    }
    $form_state->setRedirectUrl($this->getDestinationPath());

    return $renderable;
  }


  /**
   * wouldn't expect to need this here, copied from ContentEntityForm
   * but otherwise the version in EntityForm doesn't check hasfield
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $name => $values) {
      if ($entity->hasField($name)) {
        $entity->set($name, $values);
      }
    }
  }

}

