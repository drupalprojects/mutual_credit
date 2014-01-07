<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityInterface;

interface TransactionInterface extends EntityInterface {

  public function buildChildren();

  public function links($mode = 'page', $view = FALSE);

  public function validate();

}