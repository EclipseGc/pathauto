<?php
/**
 * @file
 * Contains \Drupal\pathauto\PatternTypePluginManager.php
 */

namespace Drupal\pathauto;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class PatternTypePluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PatternType', $namespaces, $module_handler, 'Drupal\pathauto\PatternTypeInterface', 'Drupal\pathauto\Annotation\PatternType');
    $this->alterInfo('pathauto_pattern_type_info');
    $this->setCacheBackend($cache_backend, 'pathauto_pattern_type');
  }

}
