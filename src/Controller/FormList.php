<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\FormList.
 *
 */

namespace Drupal\mcapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Menu\MenuLinkManager;

/**
 * Returns responses for Wallet routes.
 */
class FormList extends ControllerBase {

  function buildPage() {
    //@todo put this in mcapi.api.php
    $items = $this->moduleHandler()->invokeAll('mcapi_form_list');
    //work out the menu links available for each path
    foreach ($items as $rowname => $row) {
      $row += ['route_parameters' => [], 'operations' => []];
      $actions = $menu_links = [];
      //$row['menu_links'] = ['data' => []];
      if ($row['route']) {
        $params = 
        $url = Url::fromRoute($row['route'], $row['route_parameters']);
        $links = \Drupal::service('plugin.manager.menu.link')->loadLinksByRoute(
          $row['route'], 
          (array) $row['route_parameters']
        );
      }
      $rows[$rowname] = [
        'title' => $row['title'],
        //@todo getInternalPath is deprecated but I can't see the proper way to do it.
        'path' => $this->l($url->getInternalPath(), $url),
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $row['operations']
          ]
        ],
      ];
    }
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        'title' => $this->t('Title'),
        'path' => $this->t('Path to form'),
        'operations' => $this->t('Form operations'),
      ],
      '#rows' => $rows
    ];
    return $build;
  }  
}
