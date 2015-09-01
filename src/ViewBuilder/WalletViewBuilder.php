<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\WalletViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\mcapi\WalletInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for wallets.
 */
class WalletViewBuilder extends EntityViewBuilder {

  private $localTaskManager;
  private $accessManager;
  
  /**
   * Constructs a new EntityViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, $local_task_manager, $access_manager) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->localTaskManager = $local_task_manager;
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('access_manager')
    );
  }
  
  
  /**
   * {@inheritdoc}
   * For multiple nice wallets see theme callback 'mcapi_wallets'
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);
    //add the css
    $build['#attached'] = ['library' => ['mcapi/mcapi.wallets']];
    return $build;
  }
  
  //map the wallet extrafield names onto the methods in this class
  //this is particularlt handy since all these functions take same arguments i.e. one wallet
  function viewExtraField($component, $wallet) {
    $props = [];
    switch ($component) {
      case 'histories':
        $theme = 'wallet_history';
        $props = ['#currencies' => $wallet->currenciesUsed()];
        break;
      case 'balance_bars':
        $theme = 'balance_bars';
        break;
      case 'stats':
        $theme = 'wallet_stats';
        break;
      case 'balances':
        $theme = 'wallet_balances';
        break;
      case 'links':
        return $this->viewLinks($wallet);
        break;
    }
    return [
      '#theme' => $theme,
      '#wallet' => $wallet,
      '#theme_wrappers' => ['wallet_wrapper']
    ] + $props;
  }
  

  /**
   * present a wallet's local tasks as a set of links
   *
   * @param WalletInterface $wallet
   *
   * @return []
   *   a render array
   *
   * @todo Does this work? rename to viewLinks for consistency
   */
  function viewLinks(WalletInterface $wallet) {
    $links = [];
    $tree = $this->localTaskManager->getLocalTasksForRoute('entity.mcapi_wallet.canonical');
    //which is all very well but getTasksBuild assumes we are on the current route
    foreach ($tree[0] as $child) {
      $route_name = $child->getRouteName();
      $route_parameters = [
        //arg_0 can be removed when views args are working with the symfony router
        'arg_0' => $wallet->id(),//this is for view.wallet_statement.page_1
        'mcapi_wallet' => $wallet->id()
      ];
      if ($this->accessManager->checkNamedRoute($route_name, $route_parameters)) {
        $links[$route_name] = [
          'title' => $child->getTitle(),
          'url' => Url::fromRoute($route_name, $route_parameters)
        ];
      }
    }
    return [
      '#theme' => 'links',
      '#links' => $links,
    ];
  }

  /**
   * entity_label_callback().
   * default callback for wallet
   * put the Holder entity's name with the wallet name in brackets if there is one
   */
  public static function defaultLabel($wallet) {
    $output = $wallet->getHolder()->label();//what happens if the wallet is orphaned?

    $name = $wallet->name->value;

    if ($name == '_intertrade') {
      $output .= ' '.t('Import/Export');
    }
    elseif ($name) {
      $output .= ' ('.$name.')';
    }
    return $output;
  }
  
}
