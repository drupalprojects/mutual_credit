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
    //prepare a transaction using the defaults here

    module_load_include ('tokens.inc', 'mcapi');
    $tokens = drupal_map_assoc(mcapi_transaction_list_tokens(FALSE));
    //remove all the fields which can't be preset
    unset(
      $tokens['payee'],
      $tokens['payer'],
      $tokens['serial'],
      $tokens['state'],
      $tokens['url'],
      $tokens['creator'],
      $tokens['created'],
      $tokens['type']
    );

    $vars['type'] = $editform->type;
    foreach ($tokens as $prop) {
      //if (is_null($editform->{$prop}['preset']));{echo "$prop ";continue;}
      $vars[$prop] = $editform->{$prop}['preset'];
    }
    //temp test data
    $vars = array('partner' => 2, 'direction' => 'outgoing', 'serial' => 111, 'description' => 'testdescription', 'blah' => 'blabla');

    $vars[] = 'direction';
    $vars[] = 'partner';

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