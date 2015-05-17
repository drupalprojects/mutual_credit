<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransitionForm.
 */
namespace Drupal\mcapi\Form;

use Drupal\mcapi\TransactionSaveEvents;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;



//I don't know if it is a good idea to extend the confirm form if we want ajax.
class TransitionForm extends EntityConfirmFormBase {

  private $transeition;

  private $request;

  function __construct($request, $route_match, $transitionManager) {
    $this->request = $request;
    $transitionId = $route_match->getparameter('transition') ? : 'view';
    $this->transition = $transitionManager->getPlugin($transitionId);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('mcapi.transition_manager')
    );
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
    elseif (($this->transition->getConfiguration('format2') == 'redirect') &&
      ($redirect = $this->transition->getConfiguration('redirect'))) {
      $path = strtr($redirect, [
        '[uid]' => $this->currentUser()->id(),
        '[serial]' => $this->entity->serial->value
      ]);
    }
    else {
      $path = 'transaction/'.$this->entity->serial->value;
    }
    return Url::fromUri('base:'.$path);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'transaction_transition_'.$this->transition->getPluginId().'_form';
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
        $renderable = [
          '#type' => 'inline_template',
          '#template' => $this->transition->getConfiguration('twig'),
          '#context' => get_transaction_vars($this->entity)
        ];
        break;
      default://certificate or even sentence, but without the links
        //$renderable = Transaction::view(
        $renderable = $this->entityManager->getViewBuilder('mcapi_transaction')->view(
          $this->entity,
          $this->transition->getConfiguration('format')
        );
    }
    //@todo inject the renderer service
    return \Drupal::service('renderer')->render($renderable);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->transition->getPluginId() == 'view') {
      unset($form['actions']);
    }
    //rename the confirmForm description field so it can't clash with the transaction field
    //see Edit transition
    $form['preview'] = $form['description'];unset($form['description']);

    //add any extra form elements as defined in the transition plugin.
    $this->transition->form($form, $this->entity);

    //not sure why this is sometimes not included from TransactionViewBuilder
    $form['#attached']['library'][] = 'mcapi/mcapi.transaction';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    //@todo in d8-beta10 it looks like EntityForm::validate is deprecated
    //parent::validate($form, $form_state);
    $this->transition->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    //copy any fields from the form directly onto the transaction
    $display = EntityFormDisplay::collectRenderDisplay(
      $this->entity, 'admin'
    );
    $display->extractFormValues($this->entity, $form, $form_state);
    try {
      //the op might have injected values into the form, so it needs to be able to access them
      $values = $form_state->getValues();
//      unset($values['confirm']);
      $renderable = $this->transition->execute($this->entity, $values);
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


  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    //we are managing this function directly and only in $this->submitForm()
  }

}

