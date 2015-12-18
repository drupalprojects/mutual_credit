<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\WalletViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, $local_task_manager, $access_manager, $entity_manager) {
    //@todo use the parent when the entityTypeManager is applied
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
    //parent::__construct($entity_type, $entity_type_manager, $language_manager);
    $this->localTaskManager = $local_task_manager;
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('access_manager'),
      $container->get('entity.manager')//deprecated
    );
  }
  
  
  /**
   * {@inheritdoc}
   * For multiple nice wallets see theme callback 'mcapi_wallets'
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    //add the css
    $build['#attached'] = ['library' => ['mcapi/mcapi.wallets']];
    return $build;
  }

  /**
   * default Wallet entity_label_callback
   * 
   * put the Holder entity's name with the wallet name in brackets if there is one
   */
  public static function defaultLabel($wallet) {
    $name = $wallet->name->value;
    if ($name == INTERTRADING_WALLET_NAME) {
      return t('Import/Export');
    }
    $holdername = $wallet->getHolder()->label();
    if ($name) {
      return  $holdername .' ('.$name.')';
    }
    else return $holdername;
  }
  
   /** 
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
    foreach ($entities as $id => $wallet) {
      $extra = mcapi_entity_extra_field_info()['mcapi_wallet']['mcapi_wallet']['display'];
      foreach (['balances', 'balance_bars', 'histories', 'stats'] as $component) {
        if ($info = $displays[$wallet->bundle()]->getComponent($component)) {
          $build[$id][$component] = [
            '#theme' => 'wallet_'.$component,
            '#wallet' => $wallet,
            '#attributes' => ['class' => ['component', $component]],
            '#theme_wrappers' => ['mcapi_wallet_component'],
            '#weight' => $info['weight'],
            '#title' => $extra[$component]['label']
          ];
        }
      }
      if ($info = $displays[$wallet->bundle()]->getComponent('wallet_log')) {
        $build[$id]['wallet_log'] = views_embed_view('wallet_log', 'embed', $wallet->id());
      }
      if ($info = $displays[$wallet->bundle()]->getComponent('income_expenditure')) {
        $build[$id]['income_expenditure'] = views_embed_view('income_expenditure', 'income_embed', $wallet->id());
      }
      if ($info = $displays[$wallet->bundle()]->getComponent('canonical_link')) {
        $build[$id]['canonical_link'] = [
          '#type' => 'link',
          '#title' => $this->t('Visit wallet'),
          '#url' => Url::fromRoute('entity.mcapi_wallet.canonical', ['mcapi_wallet' => $wallet->id()]),
          '#attributes' => ['class' => 'visit-wallet-link'],
          '#weight' => $info['weight']
        ];
      }
    }
    $build['#prefix'] = '<div class="wallets">';
    $build['#suffix'] = '</div>';
//    $build['#attributes']['class'][] = 'wallets';
  }
}
