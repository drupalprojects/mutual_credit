<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\mcapi_exchanges\Exchanges;
use Drupal\mcapi\ListBuilder\CurrencyListBuilder;
use Drupal\group\GroupMembershipLoader;
use Drupal\group\Entity\Group;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of currencies filtered by exchange
 */
class CurrencyListBuilderExchanges extends CurrencyListBuilder {

  private $currentUser;
  private $database;
  private $entityQuery;
  private $membershipLoader;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   */
  public function __construct($entity_type, $storage, AccountInterface $current_user, QueryFactory $entity_query, GroupMembershipLoader $membership_loader) {
    parent::__construct($entity_type, $storage);
    $this->currentUser = $current_user;
    $this->entityQuery = $entity_query;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage('mcapi_currency'),
      $container->get('current_user'),
      $container->get('entity.query'),
      $container->get('group.membership_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader() + ['exchanges' => $this->t('Used in')];
    if (!$this->currentUser->hasPermission('configure mcapi')) {
      unset($header['weight']);
    }
    return $header;
  }

  /**
   * {@inheritdoc}
   *
   * @todo we might want to somehow filter the currencies before they get here, if there are large number
   */
  public function buildRow(EntityInterface $entity) {

    $row = parent::buildRow($entity);
    //dsm(array_keys($row));
    // Find out if the currency is used in more than one group.
    $gids = $this->entityQuery->get('group')
      ->condition('currencies', $entity->id())
      ->execute();
    if (count($gids) > 1) {
      $row['exchanges']['#markup'] = $this->t('@count exchanges', array('@count' => count($gids)));
    }
    else {
      $link = '';
      if ($gid = reset($gids)) {
        $link = Group::load($gid)->toLink()->toString();
      }
      $row['exchanges']['#markup'] = $link;
    }

    if (!$this->currentUser->hasPermission('configure mcapi')) {
      unset($row['weight']);
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // Just show the currencies the current user has permission to edit
    // get the currencies in all exchanges of which current user is manager.
    if ($this->currentUser->hasPermission('manage mcapi')) {
      return parent::load();
    }
    $gids = $currencies = [];
    //get my exchange
    $membership = mcapi_exchanges_current_membership();
    if (!$membership) {
      return [];
    }

    $gid = $membership->getGroup()->id();

    // Get the currencies in those groups
    $curr_ids = Exchanges::getCurrenciesOfExchanges([$gid]);
    // No sort has been applied.
    $currencies = $this->storage->loadMultiple($curr_ids);

    return $currencies;
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if (!$this->currentUser->hasPermission('configure mcapi')) {
      unset($form[$this->entitiesKey]['#tabledrag']);
    }
    return $form;
  }

}
