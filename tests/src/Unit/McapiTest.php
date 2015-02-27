<?php

namespace Drupal\mcapi\Unit;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


use Drupal\simpletest\WebTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class McapiTest extends WebTestBase {
  
  function test_1() {
//    $this->moduleInstaller->install(array('mcapi_tester'));
      
    \Drupal::service('module_installer')->uninstall(array('mcapi_tester'));
    
  }
  
  function test_2() {
//    $this->moduleInstaller->uninstall(['mcapi_tester']);
//    $this->moduleInstaller->install(['mcapi_exchanges']);
//    $this->moduleInstaller->install(['mcapi_tester']);
  }
  
  
  
}
