<?php
/**
 * @file
 * Contains \Drupal\pathauto\TextTransformPluginManager.php
 */

namespace Drupal\pathauto;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class TextTransformPluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TextTransform', $namespaces, $module_handler, 'Drupal\pathauto\TextTransformInterface', 'Drupal\pathauto\Annotation\TextTransform');
    $this->alterInfo('pathauto_text_transform_info');
    $this->setCacheBackend($cache_backend, 'pathauto_text_transform');
  }

  /**
   * Performs extra processing on plugin definitions.
   *
   * By default we add defaults for the type to the definition. If a type has
   * additional processing logic they can do that by replacing or extending the
   * method.
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    /*
     * The maxlength cannot exceed the database field length, so sanity check
     * the individual plugins.
     */
    // @todo inject the pathauto service.
    $definition['maxlength'] = min($definition['maxlength'], \Drupal::service('pathauto')->maxLength());
  }
}
