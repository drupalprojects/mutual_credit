<?php

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for wallets.
 */
class WalletViewBuilder extends EntityViewBuilder {

  private $localTaskManager;
  private $accessManager;

  /**
   * Constructor
   */
  public function __construct($entity_type, $entity_manager, $language_manager, $theme_registry = NULL, $local_task_manager, $access_manager) {
    parent::__construct($entity_type, $entity_manager, $language_manager, $theme_registry);
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
      $container->get('theme.registry'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('access_manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @note For multiple nice wallets see theme callback 'mcapi_wallets'.
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // Add the css.
    $build['#attached'] = ['library' => ['mcapi/mcapi.wallets']];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
    $display = $displays['mcapi_wallet'];
    foreach ($entities as $key => $wallet) {
      $extra = mcapi_entity_extra_field_info()['mcapi_wallet']['mcapi_wallet']['display'];
      foreach (['balances', 'balance_bars', 'stats'] as $component) {
        if ($info = $display->getComponent($component)) {
          $build[$key][$component] = [
            '#title' => $extra[$component]['label'],
            '#theme' => 'wallet_' . $component,
            '#wallet' => $wallet,
            '#attributes' => ['class' => ['component', $component]],
            '#theme_wrappers' => ['mcapi_wallet_component'],
            '#weight' => $info['weight'],
          ];
        }
      }
      foreach (['histories'] as $component) {
        if ($info = $display->getComponent($component)) {
          $build[$key][$component] = [
          // Only used if we add theme wrappers.
            '#title' => t('Balance history'),
            '#type' => 'balance_histories',
            '#wallet' => $wallet,
            '#attributes' => ['class' => ['component', $component]],
            '#weight' => $info['weight'],
            '#width' => 300,
          ];
        }
      }
      if ($info = $display->getComponent('link_income_expenditure')) {
        $build[$key]['link_income_expenditure'] = [
          '#type' => 'link',
          '#title' => t('Income & Expenditure'),
          '#url' => Url::fromRoute('view.income_expenditure.wallet_page', ['mcapi_wallet' => $wallet->id()]),
          '#attributes' => ['class' => 'wallet-link'],
        ];
        // views_embed_view('income_expenditure', 'income_embed',$wallet->id());
      }
      if ($info = $display->getComponent('link_transactions')) {
        $build[$key]['link_transactions'] = [
          '#type' => 'link',
          '#title' => t('View transactions'),
          '#url' => Url::fromRoute('view.wallet_transactions.page', ['mcapi_wallet' => $wallet->id()]),
          '#attributes' => ['class' => 'wallet-link'],
        ];
      }
      if ($info = $display->getComponent('canonical_link')) {
        $build[$key]['canonical_link'] = [
          '#type' => 'link',
          '#title' => $this->t('more...'),
          '#url' => Url::fromRoute('entity.mcapi_wallet.canonical', ['mcapi_wallet' => $wallet->id()]),
          '#attributes' => ['class' => 'wallet-link'],
          '#weight' => $info['weight'],
        ];
      }
      if ($info = $display->getComponent('holder_link')) {
        $build[$key]['holder_link'] = [
          '#type' => 'link',
          '#title' => $this->t('Held by %name', ['%name' => $wallet->getHolder()->label()]),
          '#url' => $wallet->getHolder()->toUrl('canonical'),
          '#attributes' => ['class' => 'wallet-link'],
          '#weight' => $info['weight'],
        ];
      }
    }
    // @todo this should probably go elsewhere, and only on non-canonical pages
    // $build['#prefix'] = '<div class="wallets">';
    // $build['#suffix'] = '</div>';
  }

}
