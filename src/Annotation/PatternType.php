<?php
/**
 * @file
 * Contains \Drupal\pathauto\Annotation\PatternType.php
 */

namespace Drupal\pathauto\Annotation;


use Drupal\Component\Annotation\Plugin;

class PatternType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the pattern type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The administrative description of the pattern type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The pattern default.
   *
   * @var string
   */
  public $pattern_default;

}
