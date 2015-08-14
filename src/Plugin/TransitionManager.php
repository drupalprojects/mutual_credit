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
   * @param array $exclude
   *   the names of the active plugins not to return
   *
   * @param FieldInterface $worth
   *   a worth fieldlist instance from which to extract the currencies
   *
   * @todo use a collection?
   */
  public function active($transaction, array $exclude = []) {
    //no need to put this in a static coz should be used once only
    if ($transaction->worth) {
      //some deleteModes are not available for some currencies
      $exclude = array_merge($exclude, $this->deletemodes($transaction->worth->currencies(TRUE)));
    }
    $active = $this->config_factory->get('mcapi.misc')->get('active_transitions');
    foreach ($this->getDefinitions() as $id => $definition) {
      if (!in_array($id, $exclude) and $active[$id]) {
        $output[$id] = $this->getPlugin($id, $transaction);
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
  public function getPlugin($id, $transaction) {
    if (!array_key_exists($id, $this->plugins)) {
      $this->plugins[$id] = $this->createInstance($id, $this->getConfig($id)->getRawData());
      $this->plugins[$id]->setTransaction($transaction);
    }
    return $this->plugins[$id];
  }

  /**
   *
   * @param type $id
   * @param type $editable
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
    if (!$transaction->parent->value && $transaction->serial->value) {
      $exclude = ['create'];
      //ideally we would remove view when the current url is NOT the canonical url
      //OR we need to NOT render links when the transaction is being previewed
      if (!$show_view) {
        $exclude[] = 'view';
      }
      foreach ($this->active($transaction, $exclude) as $transition => $plugin) {
        if ($transaction->access($transition)->isAllowed()) {
          $route_name = $transition == 'view' ?
            'entity.mcapi_transaction.canonical' :
            'mcapi.transaction.transition';
          $renderable['#links'][$transition] = [
            'title' => $plugin->getConfiguration('title'),
            'url' => Url::fromRoute($route_name, [
              'mcapi_transaction' => $transaction->serial->value,
              'transition' => $transition
            ])
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
          if(!$plugin->getConfiguration('redirect') && $transition != 'view'){
            $path = $this->redirecter->get();

            //die($path);//@todo stop removing leading slash when the redirect service does it properly
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

/* not working
Array
        (
            [title] => Erase
            [url] => Drupal\Core\Url Object
                (
                    [urlGenerator:protected] =>
                    [urlAssembler:protected] =>
                    [accessManager:protected] =>
                    [routeName:protected] => mcapi.transaction.op
                    [routeParameters:protected] => Array
                        (
                            [mcapi_transaction] => 99
                            [transition] => erase
                        )

                    [options:protected] => Array
                        (
                        )

                    [external:protected] =>
                    [unrouted:protected] =>
                    [uri:protected] =>
                    [internalPath:protected] =>
                    [_serviceIds:protected] => Array
                        (
                        )

                )

            [attributes] => Array
                (
                    [class] => Array
                        (
                            [0] => use-ajax
                        )

                    [data-accepts] => application/vnd.drupal-modal
                    [data-dialog-options] => {"width":500}
                )

            [query] => transaction/99
 */

