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

  function __construct($currency, $limit, $projected, $excess, $wallet) {
    $this->wallet = $wallet;
    $this->limit = $limit;
    $this->projected = $projected;
    $this->excess = $excess;
    $this->currency = $currency;
    //sets $field property
    parent::__construct($currency, $this->__toString());
  }

  function __toString() {
    $replacements = array(
      '!wallet' => $this->wallet->label(),
      '!excess' => $this->currency->format($this->excess),
      '!limit' => $this->currency->format($this->limit),
      '!projected' => $this->currency->format($this->projected),
    );
    //this may need replacing if it transgresses privacy concerns, perhaps in intertrading.
    if ($this->projected > 0) {
      return t("The transaction would take wallet '!wallet' !excess above the maximum limit of !limit.", $replacements);
    }
    else {
      return t("The transaction would take wallet '!wallet' !excess below the minimum limit of !limit.", $replacements);
    }
  }

}
