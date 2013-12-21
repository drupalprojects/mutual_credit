<?php

/*
 * @file
 * Definition of Drupal\mcapi_limits\McapiTransactionWorthException.
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;

/**
 *
 */
class McapiLimitsException extends McapiTransactionWorthException {

  private $name;
  private $limit;
  private $projected;
  private $excess;

  function __construct($currency, $uid, $limit, $projected, $excess) {
    $this->uid = $uid;
    $this->limit = $limit;
    $this->projected = $projected;
    $this->excess = $excess;
    $this->currency = $currency;
    //sets $field property
    parent::__construct($currency, $this->__toString());
  }

  function __toString() {

    $replacements = array(
      '!user' => user_load($this->uid)->getUsername(),
      '!amount' => $this->currency->format($this->excess),
      '!limit' => $this->currency->format($this->limit),
      '!projected' => $this->currency->format($this->projected),
    );
    //this may need replacing if it transgresses privacy concerns, perhaps in intertrading.
    if ($this->excess > 0) {
      return t('The transaction would take !user !amount above the maximum limit of !limit', $replacements);
    }
    else {
      return t('The transaction would take !user !amount below the minimum limit of !limit', $replacements);
    }
  }

}
