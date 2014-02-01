<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\ParamConverter\TransactionPreloader.
 */

namespace Drupal\mcapi_1stparty\ParamConverter;

use Drupal\Core\Entity\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\mcapi\McapiTransactionException;

/**
 * Preloads a transaction using the presets from the 1stparty form
 *
 * Example:
 *
 * pattern: '/transact/{1stparty_editform}'
 * options:
 *   parameters:
 *     1stparty_editform:
 *       type: 'entity:mcapi_transaction'
 *       serial: TRUE
 *
 * If transaction_serial is 0, the transaction will be pulled from the tempstore
 */
class TransactionPreloader extends EntityConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults, Request $request) {
    //get the settings from the transaction form
    $editform = \Drupal::entityManager()->getStorageController('1stparty_editform')->load($value);

    //check if there is an entity in the url, from which we should get the defaults.
    //@todo how do we even get the querystring from the request?
    //print_r($request->attributes->all());
    if (0) {
      $vars = array();
    }
    //set the defaults from the form configuration
    else{
      //prepare a transaction using the defaults here
      $vars = array('type' => $editform->type);
      foreach (mcapi_1stparty_transaction_tokens() as $prop) {
        if (property_exists($editform, $prop)) {
          if (is_null($editform->{$prop}['preset'])){
            $vars[$prop] = $editform->{$prop}['preset'];
          }
        }
      }

      //now handle the payer and payee, based on partner and direction
      if ($editform->direction['preset'] = 'incoming') {
        //TODO convert this to a wallet
        $vars['payee'] = \Drupal::currentUser()->id();
        $vars['payer'] = $partner;
      }
      elseif($editform->direction['preset'] = 'outgoing') {
        //TODO convert this to a wallet
        $vars['payer'] = \Drupal::currentUser()->uid;
        $vars['payee'] = $partner;
      }
    }

    $entity = \Drupal::entityManager()->getStorageController('mcapi_transaction')->create($vars);
    //which called preCreate which merged the vars with the default vars
    //if this entity doesn't end as this->entity in the entity form then why are we doing this?
    global $temp_mcapi_transaction;
    $temp_mcapi_transaction = $entity;
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $route->getPath() == '/transact/{1stparty_editform}';
  }
}