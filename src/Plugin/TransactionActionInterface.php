<?php

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for transaction actions.
 */
interface TransactionActionInterface extends ConfigurablePluginInterface, PluginFormInterface, ContainerFactoryPluginInterface {


}
