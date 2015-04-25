<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionIntegrityContraintValidator.
 * @see https://www.drupal.org/node/2105797
 * @see https://www.drupal.org/node/2438011
 */

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class TransactionIntegrityContraintValidator extends ConstraintValidator {

    
  /**
   * validate a transaction entity including $transaction->children[]
   * the entity field-constraints validation is done elsewhere
   * Exceptions are put into $transaction->warnings[] and $transaction->exceptions[]
   *
   * @param Entity $transaction
   * @param Constraint $constraint
   */ 
  public function validate($transaction, Constraint $constraint) {
    $flattened = $transaction->flatten();
    
    //validate the parent and children separately in case the errors are handled differently
    $transaction->errors = array_merge(
      $transaction->errors, 
      Self::validateOne(array_shift($flattened))//top level
    );
    foreach ($flattened as $child_transaction) {
      if ($entity->isNew()) {
        //I feel sure this shouldn't be necessary...
        //check that the uuid doesn't already exist. i.e. that we are not resubmitting the same transactions
        if (entity_load_multiple_by_properties('mcapi_transaction', ['uuid' => $entity->uuid->value])) {
          $this->context->addViolation(
            'Transaction has already been inserted: @uuid', 
            ['@uuid' => $entity->uuid->value]
          );
        }
      }
      //check the payer and payee aren't the same
      if ($entity->payer->target_id == $entity->payee->target_id) {
        $this->context->addViolation(
          'Wallet @num cannot pay itself.', 
          ['@num' => $entity->payer->target_id]
        );
      }
    }
  }
    
  static function validateOne($entity) {
    $errors = [];

     
    //check that the current user is permitted to pay out and in according to the wallet permissions
     //move to wallet
    $walletAccess = \Drupal::entityManager()->getAccessControlHandler('mcapi_wallet');
    if (!$walletAccess->checkAccess($entity->payer->entity, 'payout', NULL, \Drupal::currentUser())) {
      $errors[] = new McapiTransactionException(
        'payer', 
        t('You are not allowed to make payments from this wallet.')
      );
    }
    
    //move to wallet
    if (!$walletAccess->checkAccess($entity->payee->entity, 'payout', NULL, \Drupal::currentUser())) {
      $errors[] = new McapiTransactionException(
        'payee', 
        t('You are not allowed to pay into this wallet.')
      );
    }
    //check that the description isn't too long
    //Move to description field
    $length = strlen($entity->description->value);
    if ($length > MCAPI_MAX_DESCRIPTION) {
      $errors[] = new McapiTransactionException(
        'description', 
        t(
          'The description field is @num chars longer than the @max maximum',
          ['@num' => $length-MCAPI_MAX_DESCRIPTION, '@max' => MCAPI_MAX_DESCRIPTION]
        )
      );
    }
    return $errors;
  }
  
}