<?php
/**
 * @file
 * Contains \Drupal\pathauto\Annotation\TextTransform.php
 */

namespace Drupal\pathauto\Annotation;


use Drupal\Component\Annotation\Plugin;
use Drupal\pathauto\PathAuto;

class TextTransform extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the block.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The administrative label of the block.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * @var string
   */
  public $separator = '-';

  /**
   * @var array
   */
  public $strings = [];

  /**
   * @var bool
   */
  public $transliterate = FALSE;

  /**
   * @var array
   */
  public $punctuation = [];

  /**
   * @var bool
   */
  public $reduce_ascii = FALSE;

  /**
   * @var bool
   */
  public $ignore_words_regex = FALSE;

  /**
   * @var int
   */
  public $lowercase = PathAuto::PATHAUTO_CASE_LOWER;

  /**
   * @var int
   */
  public $maxlength = 100;

}
