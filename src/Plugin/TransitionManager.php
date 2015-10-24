<?php

/**
 * @file
 * Definition of \Drupal\mcapi\Plugin\TransitionManager.
 */

namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
//use Drupal\Core\Ajax\ReplaceCommand;

class TransitionManager extends DefaultPluginManager {

  private $config_factory;

  private $redirecter;

  private $plugins;

  private $config;
  
  
  const CONFIRM_NORMAL = 0;
  const CONFIRM_AJAX = 1;
  const CONFIRM_MODAL = 2;

  /**
   * Constructs the TransitionManager object
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   *
   * @param CacheBackendInterface $cache_backend
   *
   * @param ModuleHandlerInterface $module_handler
   *
   * @param ConfigFactory $config_factory
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory, $redirecter) {
    parent::__construct(
      'Plugin/Transition',
      $namespaces,
      $module_handler,
      '\Drupal\mcapi\Plugin\TransitionInterface',
      '\Drupal\mcapi\Annotation\Transition'
    );
    $this->setCacheBackend($cache_backend, 'transaction_transitions');
    $this->redirecter = $redirecter;
    $this->config_factory = $config_factory;
    $this->plugins = [];
    $this->config = [];
  }

  /*
   * retrieve the plugins which are not disabled
   *
   * @param TransactionInterface $transaction
   *   the names of the active plugins not to return
   *
   * @param array $exclude
   *   the names of the active plugins not to return
   *
   * @todo use a collection?
   */
  public function active($transaction, array $exclude = []) {
    //no need to put this in a static coz should be used once only
    if ($transaction->worth) {
      //some deleteModes are not available for some currencies
      $exclude = array_merge($exclude, $this->deletemodes($transaction->worth->currencies(TRUE)));
    }
    $active = $this->config_factory->get('mcapi.settings')->get('active_transitions');
    foreach ($this->getDefinitions() as $id => $definition) {
      if (!in_array($id, $exclude) && isset($active[$id])) {
        $output[$id] = $this->getPlugin($id, $transaction);
      }
    }
    return $output;
  }
  
  
  public function active_names(array $exclude = []) {
    $active = $this->config_factory->get('mcapi.settings')->get('active_transitions');
    foreach ($this->getDefinitions() as $id => $definition) {
      if (!in_array($id, $exclude) && isset($active[$id])) {
        $output[$id] = $this->getConfig($id)->get('title');
      }
    }
    return $output;
  }

  /**
   * ensure a plugin is loaded and in the list
   *
   * @param string $id
   * @param \Drupal\mcapi\Entity\Transaction $transaction
   *
   * @return type
   */
  public function getPlugin($id, $transaction) {
    if (!array_key_exists($id, $this->plugins)) {
      $this->plugins[$id] = $this->createInstance($id, $this->getConfig($id)->getRawData());
      $this->plugins[$id]->setTransaction($transaction);
    }
    return $this->plugins[$id];
  }

  /**
   *
   * @param string $id
   * @param boolean $editable
   *
   * @return Config
   *   editable or immutable
   */
  public function getConfig($id, $editable = FALSE) {
    return $editable ?
      $this->config_factory->getEditable('mcapi.transition.'. $id) :
      $this->config_factory->get('mcapi.transition.'. $id);
  }

  /**
   * get the delete modes which are common to all the given currencies
   *
   * @param array $currencies
   * @return type
   */
  private function deletemodes(array $currencies) {
    $modes = array(
    	'1' => 'erase',
      '2' => 'delete'
    );
    foreach ($currencies as $currency) {
      $deletemodes[] = intval($currency->deletion);
    }
    $deletemode = min($deletemodes);
    //return everything larger than the min to be excluded
    return array_slice($modes, $deletemode);
  }

  /**
   * return the names of the config items
   *
   * @return string[]
   *   the config names of all the transition settings
   */
  public function getNames() {
    foreach ($this->getDefinitions() as $name => $info) {
      $names[] = 'mcapi.transition.'.$name;
    }
    return $names;
  }

  /**
   *
   * @param TransactionInterface $transaction
   * @param string $view_mode
   * @param string $dest_type
   *   whether the links should go to a new page, a modal box, or an ajax refresh
   *
   * @return array
   *   A renderable array
   */
  function getLinks(TransactionInterface $transaction, $show_view = TRUE) {
    $renderable = [];
    //child transactions and unsaved transactions never show links
    if (!$transaction->isNew()) {
      $exclude = ['create'];
      //ideally we would remove view when the current url is NOT the canonical url
      //OR we need to NOT render links when the transaction is being previewed
      if (!$show_view) {
        $exclude[] = 'view';
      }
      foreach ($this->active($transaction, $exclude) as $transition => $plugin) {
        if ($transaction->access($transition)->isAllowed()) {
          
          $route_params = ['mcapi_transaction' => $transaction->serial->value];
          if ($transition == 'view') {
            $route_name = 'entity.mcapi_transaction.canonical';
          }
          else {
            $route_name = 'mcapi.transaction.transition';
            $route_params['transition'] = $transition;
          }
          $renderable['#links'][$transition] = [
            'title' => $plugin->getConfiguration('title'),
            'url' => Url::fromRoute($route_name, $route_params)
          ];
          $display = $plugin->getConfiguration('display');
          if ($display != Self::CONFIRM_NORMAL) {
            if ($display == Self::CONFIRM_MODAL) {
              $renderable['#attached']['library'][] = 'core/drupal.ajax';
              $renderable['#links'][$transition]['attributes'] = [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode(['width' => 500]),
              ];
            }
            elseif($display == Self::CONFIRM_AJAX) {
              //curious how, to make a ajax link it seems necessary to put the url in 2 places
              $renderable['#links'][$transition]['ajax'] = [
                //there must be either a callback or a path
                'wrapper' => 'transaction-'.$transaction->serial->value,
                'method' => 'replace',
                'path' => $renderable['#links'][$transition]['url']->getInternalPath()
              ];
            }
          }
          elseif ($display != Self::CONFIRM_NORMAL && $transition != 'view') {        
            //the link should redirect back to the current page, if not otherwise stated
            if($dest = $plugin->getConfiguration('redirect')) {
              $redirect = ['destination' => $dest];
            }
            else {
              $redirect = $this->redirecter->getAsArray();
            }
            //@todo stop removing leading slash when the redirect service does it properly
            $renderable['#links'][$transition]['query'] = $redirect;
          }
        }
      }
      if (array_key_exists('#links', $renderable)) {
        $renderable += [
          '#theme' => 'links',
          '#attributes' => new Attribute(
            ['class' => ['transaction-transitions']]
          ),
          '#cache' => []
        ];
      }
    }
    return $renderable;
  }

}

