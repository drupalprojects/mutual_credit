<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\AccountingAdmin.
 */

namespace Drupal\mcapi\Controller;

use Drupal\system\Controller\SystemController;
use Drupal\Core\Url;
use Drupal\mcapi\Access\TransactionAccessControlHandler;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Entity\State;
use Drupal\Core\Template\Attribute;

/**
 * Returns responses for Wallet routes.
 */
class AccountingAdmin extends SystemController {


  function systemAdminMenuBlockPage() {
    if (TransactionAccessControlHandler::enoughWallets($this->currentUser())->isForbidden()) {
      $message[] = $this->t("There aren't enough wallets for you to create a transaction.");
      if (mcapi_one_wallet_per_user_mode()) {
        $message[] = $this->l(
          $this->t("Allow more wallets or more entities to own wallets."),
          Url::fromRoute('mcapi.admin_wallets')
        );
      }
      else {
        $message[] = $this->l(
          $this->t("Give yourself a(nother) wallet."),
          Url::fromRoute('mcapi.wallet.add.user', ['user' => $this->currentUser()->id()])
        );
      }
      //don't both checking for create user access
      $message[] = $this->l(
        $this->t("Or create another user."),
        Url::fromRoute('user.admin_create')
      );
      $renderable['warning'] = ['#markup' => implode(' ', $message), '#weight' => -1];
    }

    $renderable[] = parent::systemAdminMenuBlockPage();
    $renderable[] = $this->visualise();
    return $renderable;
  }
  
  private function visualise() {
    foreach (Type::loadMultiple() as $type => $info) {
      $types[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $renderable['types'] = [
      '#type' => 'container',
      '#attributes' => new Attribute(['style' => 'display:inline-block; vertical-align:top;']),
      'title' => [
        '#markup' => "<h4>".t('Transaction types')."</h4>"
      ],
      'states' => [
        '#markup' => "<dl>".implode("\n\t", $types) . '</dl>'
      ]
    ];
    foreach (State::loadMultiple() as $id => $info) {
      $states[] = '<dt>'.$info->label.'</dt><dd>'.$info->description.'</dd>';
    }
    $renderable['states'] = [
      '#type' => 'container',
      '#attributes' => new Attribute(['style' => 'display:inline-block; margin-left:5em; vertical-align:top;']),
      'title' => [
        '#markup' => "<h4>".t('Workflow states')."</h4>"
      ],
      'states' => [
        '#markup' => "<dl>".implode("\n\t", $states) . '</dl>'
      ]
    ];
    return $renderable;
  }

}
