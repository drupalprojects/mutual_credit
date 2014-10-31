<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Controller\ExchangeContactController.
 */

namespace Drupal\mcapi_exchanges\Controller;

use Drupal\contact\Controller\ContactController;
use Drupal\mcapi\ExchangeInterface;

/**
 * Controller routines for contact routes.
 */
class ExchangeContactController extends ContactController {


  /**
   * Form constructor for the personal contact form.
   *
   * @param ExchangeInterface $exchange
   *   The exchange to be contacted
   *
   * @return array
   *   The personal contact form as render array as expected by drupal_render().
   */
  public function page(ExchangeInterface $mcapi_exchange) {
    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser()->hasPermission('administer contact forms') && !$this->currentUser()->hasPermission('administer users')) {
      $this->contactFloodControl();
    }
    //TODO hope the contact module evolves a bit, or build our own contact form
    $message = $this->entityManager()->getStorage('contact_message')->create(array(
      'category' => 'exchange',
    ));
    drupal_set_message("Contact form isn't working yet");

    $form = $this->entityFormBuilder()->getForm($message);
    $form['#title'] = $this->t('Contact @exchangename', array('@exchangename' => $mcapi_exchange->label()));
    return $form;
  }

}
