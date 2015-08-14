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
      $actions = $menu_links = [];
      //$row['menu_links'] = ['data' => []];
      if ($row['route']) {
        $url = Url::fromRoute($row['route'], (array) $row['route_parameters']);
        $links = \Drupal::service('plugin.manager.menu.link')->loadLinksByRoute(
          $row['route'], 
          (array) $row['route_parameters']
        );
        foreach ($links as $id => $link) {
          $menu_links[] = $this->l(
            t('Edit link'), 
            Url::fromRoute('menu_ui.link_edit', ['menu_link_plugin' => $id])
          );
        }
        if (empty($menu_links)) {
          $menu_links[] = $this->l(
            $this->t('Add link'), 
            Url::fromRoute('entity.menu.add_link_form', ['menu' => 'tools'])
          );
        }
      }
      $rows[$rowname] = [
        'title' => $row['title'],
        //@todo getInternalPath is deprecated but I can't see the proper way to do it.
        'path' => $this->l($url->getInternalPath(), $url),
        'operations' => ['data' => [
          '#type' => 'operations',
          '#links' => $row['operations']
        ]],
        'menu_links' => implode(' ', $menu_links)
      ];
    }
    
    $build['table'] = array(
      '#title' => 'hello',
      '#caption' => $this->t('Some of these forms may not have menu links'),
      '#type' => 'table',
      '#header' => [
        'title' => $this->t('Title'),
        'path' => $this->t('Path to form'),
        'operations' => $this->t('Form operations'),
        'menu_links' => [
          'title' => 'Excluding tasks and actions',
          'data' => $this->t('Menu links to forms')
        ]
      ],
      '#rows' => $rows
    );
    return $build;
  }  
}
