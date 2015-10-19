<?php
/**
 * @file
 * Contains \Drupal\pathauto\Plugin\PatternType\EntityPatternType.php
 */

namespace Drupal\pathauto\Plugin\PatternType;


use Drupal\pathauto\PatternTypeBase;

class EntityPatternType extends PatternTypeBase {

  public function getPatternDefault() {
    return $this->getPluginDefinition()['pattern_default'];
  }

  public function getPattern() {
    if (!empty($this->configuration['pattern'])) {
      return $this->configuration['pattern'];
    }
    return $this->getPatternDefault();
  }

  public function getTokens() {
    $tokens = token_get_info($this->getDerivativeId());
    if ($tokens) {
      return $tokens;
    }
    return [];
  }
}
