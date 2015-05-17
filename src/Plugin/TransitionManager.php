<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\TransitionManager.
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
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct(
      'Plugin/Transition',
      $namespaces,
      $module_handler,
      '\Drupal\mcapi\Plugin\TransitionInterface',
      '\Drupal\mcapi\Annotation\Transition'
    );
    $this->setCacheBackend($cache_backend, 'transaction_transitions');
    $this->config_factory = $config_factory;
    $this->plugins = [];
    //@todo can this be injected?
    $this->redirecter = \Drupal::service('redirect.destination');
  }

  /*
   * retrieve all the transition plugins
   *
   * @param bool $editable
   *   whether to get the configuration as immutable or editable.
   *
   * @todo use a collection?
   */
  public function all($editable = FALSE) {
    foreach ($this->getDefinitions() as $id => $def) {
      $this->getPlugin($id, $editable);
    }
    return $this->plugins;
  }

  /*
   * retrieve the plugins which are not disabled
   *
   * @param array $exclude
   *   the names of the active plugins not to return
   *
   * @param FieldInterface $worth
   *   a worth fieldlist instance from which to extract the currencies
   *
   * @todo use a collection?
   */
  public function active(array $exclude = [], $worth = NULL) {
    //no need to put this in a static coz should be used once only
    if ($worth) {
      //some deleteModes are not available for some currencies
      $exclude = array_merge($exclude, $this->deletemodes($worth->currencies(TRUE)));
    }
    $active = $this->config_factory->get('mcapi.misc')->get('active_transitions');
    foreach ($this->all(FALSE) as $id => $plugin) {
      if (!in_array($id, $exclude) and $active[$id]) {
        $output[$id] = $plugin;
      }
    }
    return $output;
  }

  /**
   * ensure a plugin is loaded and in the list
   *
   * @param type $id
   * @param type $editable
   *
   * @return type
   */
  public function getPlugin($id, $editable = FALSE) {
    if (!array_key_exists($id, $this->plugins)) {
      $config = $this->config_factory->get('mcapi.transition.'. $id)->getRawData();
      $this->plugins[$id] = $this->createInstance($id, $config);
    }
    return $this->plugins[$id];
  }

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

  //return the names of the config items
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
  function getLinks(TransactionInterface $transaction, $view_mode = 'certificate') {
    $renderable = [];
    //child transactions and unsaved transactions never show links
    if (!$transaction->parent->value && $transaction->serial->value) {
      $exclude = ['create'];
      //ideally we would remove view when the current url is NOT the canonical url
      //OR we need to NOT render links when the transaction is being previewed
      if ($view_mode == 'certificate') {
        $exclude[] = 'view';
      }

      foreach ($this->active($exclude, $transaction->worth) as $transition => $plugin) {
        if ($transaction->access($transition)->isAllowed()) {
          $route_name = $transition == 'view' ?
            'entity.mcapi_transaction.canonical' :
            'mcapi.transaction.op';
          $renderable['#links'][$transition] = [
            'title' => $plugin->getConfiguration('title'),
            'url' => Url::fromRoute($route_name, [
              'mcapi_transaction' => $transaction->serial->value,
              'transition' => $transition
            ])
          ];
          //@todo consider abstracting the funky link_building so it works in buttons too.
          $display = $plugin->getConfiguration('display');
          if ($display != MCAPI_CONFIRM_NORMAL) {
            if ($display == MCAPI_CONFIRM_MODAL) {
              $renderable['#attached']['library'][] = 'core/drupal.ajax';
              $renderable['#links'][$transition]['attributes'] = [
                'class' => ['use-ajax'],
                'data-accepts' => 'application/vnd.drupal-modal',
                'data-dialog-options' => Json::encode(['width' => 500])
              ];
            }
            elseif($display == MCAPI_CONFIRM_AJAX) {
              //curious how, to make a ajax link it seems necessary to put the url in 2 places
              $renderable['#links'][$transition]['ajax'] = [
                'wrapper' => 'transaction-'.$transaction->serial->value,
                'method' => 'replace',
                'url' => $renderable['#links'][$transition]['url']
              ];
            }
          }
          if(!$plugin->getConfiguration('redirect') && $transition != 'view'){
            $path = $this->redirecter->get();
            //@todo stop removing leading slash when the redirect service does it properly
            $renderable['#links'][$transition]['query'] = substr($path, 1);
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

