<?php
/**
 * @file
 * Contains \Drupal\pathauto\PathAuto.php
 */

namespace Drupal\pathauto;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class PathAuto {

  /**
   * Case should be left as is in the generated path.
   */
  const PATHAUTO_CASE_LEAVE_ASIS = 0;

  /**
   * Case should be lowercased in the generated path.
   */
  const PATHAUTO_CASE_LOWER = 1;

  /**
   * "Do nothing. Leave the old alias intact."
   */
  const PATHAUTO_UPDATE_ACTION_NO_NEW = 0;

  /**
   * "Create a new alias. Leave the existing alias functioning."
   */
  const PATHAUTO_UPDATE_ACTION_LEAVE = 1;

  /**
   * "Create a new alias. Delete the old alias."
   */
  const PATHAUTO_UPDATE_ACTION_DELETE = 2;

  /**
   * Remove the punctuation from the alias.
   */
  const PATHAUTO_PUNCTUATION_REMOVE = 0;

  /**
   * Replace the punctuation with the separator in the alias.
   */
  const PATHAUTO_PUNCTUATION_REPLACE = 1;

  /**
   * Leave the punctuation as it is in the alias.
   */
  const PATHAUTO_PUNCTUATION_DO_NOTHING = 2;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var array
   */
  protected $cache;

  /**
   * @var array
   */
  protected $punctuation = [];

  /**
   * @var int
   */
  protected $maxlength;

  function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('pathauto.settings');
  }

  protected function getCache() {
    // Generate and cache variables used in this function so that on the second
    // call to pathauto_cleanstring() we focus on processing.
    if (empty($this->cache)) {
      $cache = $this->config->getRawData();
      if (!$this->moduleHandler->moduleExists('transliteration')) {
        unset($cache['transliteration']);
      }
      /*
      $cache = [
        'separator' => variable_get('pathauto_separator', '-'),
        'strings' => [],
        'transliterate' => variable_get('pathauto_transliterate', FALSE) && module_exists('transliteration'),
        'punctuation' => [],
        'reduce_ascii' => (bool) variable_get('pathauto_reduce_ascii', FALSE),
        'ignore_words_regex' => FALSE,
        'lowercase' => (bool) variable_get('pathauto_case', PATHAUTO_CASE_LOWER),
        'maxlength' => min(variable_get('pathauto_max_component_length', 100), _pathauto_get_schema_alias_maxlength()),
      ];
      */

      // Generate and cache the punctuation replacements for strtr().
      $punctuation = $this->punctuationCharacters();
      foreach ($punctuation as $name => $details) {
        $action = variable_get('pathauto_punctuation_' . $name, PATHAUTO_PUNCTUATION_REMOVE);
        switch ($action) {
          case PATHAUTO_PUNCTUATION_REMOVE:
            $cache['punctuation'][$details['value']] = '';
            break;
          case PATHAUTO_PUNCTUATION_REPLACE:
            $cache['punctuation'][$details['value']] = $cache['separator'];
            break;
          case PATHAUTO_PUNCTUATION_DO_NOTHING:
            // Literally do nothing.
            break;
        }
      }

      // Generate and cache the ignored words regular expression.
      $ignore_words = variable_get('pathauto_ignore_words', PATHAUTO_IGNORE_WORDS);
      $ignore_words_regex = preg_replace(['/^[,\s]+|[,\s]+$/', '/[,\s]+/'], ['', '\b|\b'], $ignore_words);
      if ($ignore_words_regex) {
        $cache['ignore_words_regex'] = '\b' . $ignore_words_regex . '\b';
        if (function_exists('mb_eregi_replace')) {
          mb_regex_encoding('UTF-8');
          $cache['ignore_words_callback'] = 'mb_eregi_replace';
        }
        else {
          $cache['ignore_words_callback'] = 'preg_replace';
          $cache['ignore_words_regex'] = '/' . $cache['ignore_words_regex'] . '/i';
        }
      }
      $this->cache = $cache;
    }
    return $this->cache;
  }

  public function clean($string, array $options = []) {
    $cache = $this->getCache();

    // Empty strings do not need any processing.
    if ($string === '' || $string === NULL) {
      return '';
    }

    $langcode = NULL;
    if (!empty($options['language']->language)) {
      $langcode = $options['language']->language;
    }
    elseif (!empty($options['langcode'])) {
      $langcode = $options['langcode'];
    }

    // Check if the string has already been processed, and if so return the
    // cached result.
    if (isset($cache['strings'][$langcode][$string])) {
      return $cache['strings'][$langcode][$string];
    }

    // Remove all HTML tags from the string.
    $output = strip_tags(decode_entities($string));

    // Optionally transliterate (by running through the Transliteration module)
    if ($cache['transliterate']) {
      // If the reduce strings to letters and numbers is enabled, don't bother
      // replacing unknown characters with a question mark. Use an empty string
      // instead.
      $output = transliteration_get($output, $cache['reduce_ascii'] ? '' : '?', $langcode);
    }

    // Replace or drop punctuation based on user settings
    $output = strtr($output, $cache['punctuation']);

    // Reduce strings to letters and numbers
    if ($cache['reduce_ascii']) {
      $output = preg_replace('/[^a-zA-Z0-9\/]+/', $cache['separator'], $output);
    }

    // Get rid of words that are on the ignore list
    if ($cache['ignore_words_regex']) {
      $words_removed = $cache['ignore_words_callback']($cache['ignore_words_regex'], '', $output);
      if (drupal_strlen(trim($words_removed)) > 0) {
        $output = $words_removed;
      }
    }

    // Always replace whitespace with the separator.
    $output = preg_replace('/\s+/', $cache['separator'], $output);

    // Trim duplicates and remove trailing and leading separators.
    $output = _pathauto_clean_separators($output, $cache['separator']);

    // Optionally convert to lower case.
    if ($cache['lowercase']) {
      $output = drupal_strtolower($output);
    }

    // Shorten to a logical place based on word boundaries.
    $output = truncate_utf8($output, $cache['maxlength'], TRUE);

    // Cache this result in the static array.
    $cache['strings'][$langcode][$string] = $output;

    return $output;

  }

  /**
   * @return array
   */
  public function punctuationCharacters() {
    if (empty($this->punctuation)) {
      $punctuation['double_quotes'] = [
        'value' => '"',
        'name' => t('Double quotation marks')
      ];
      $punctuation['quotes'] = [
        'value' => '\'',
        'name' => t("Single quotation marks (apostrophe)")
      ];
      $punctuation['backtick'] = [
        'value' => '`',
        'name' => t('Back tick')
      ];
      $punctuation['comma'] = [
        'value' => ',',
        'name' => t('Comma')
      ];
      $punctuation['period'] = [
        'value' => '.',
        'name' => t('Period')
      ];
      $punctuation['hyphen'] = [
        'value' => '-',
        'name' => t('Hyphen')
      ];
      $punctuation['underscore'] = [
        'value' => '_',
        'name' => t('Underscore')
      ];
      $punctuation['colon'] = [
        'value' => ':',
        'name' => t('Colon')
      ];
      $punctuation['semicolon'] = [
        'value' => ';',
        'name' => t('Semicolon')
      ];
      $punctuation['pipe'] = [
        'value' => '|',
        'name' => t('Vertical bar (pipe)')
      ];
      $punctuation['left_curly'] = [
        'value' => '{',
        'name' => t('Left curly bracket')
      ];
      $punctuation['left_square'] = [
        'value' => '[',
        'name' => t('Left square bracket')
      ];
      $punctuation['right_curly'] = [
        'value' => '}',
        'name' => t('Right curly bracket')
      ];
      $punctuation['right_square'] = [
        'value' => ']',
        'name' => t('Right square bracket')
      ];
      $punctuation['plus'] = [
        'value' => '+',
        'name' => t('Plus sign')
      ];
      $punctuation['equal'] = [
        'value' => '=',
        'name' => t('Equal sign')
      ];
      $punctuation['asterisk'] = [
        'value' => '*',
        'name' => t('Asterisk')
      ];
      $punctuation['ampersand'] = [
        'value' => '&',
        'name' => t('Ampersand')
      ];
      $punctuation['percent'] = [
        'value' => '%',
        'name' => t('Percent sign')
      ];
      $punctuation['caret'] = [
        'value' => '^',
        'name' => t('Caret')
      ];
      $punctuation['dollar'] = [
        'value' => '$',
        'name' => t('Dollar sign')
      ];
      $punctuation['hash'] = [
        'value' => '#',
        'name' => t('Number sign (pound sign, hash)')
      ];
      $punctuation['at'] = [
        'value' => '@',
        'name' => t('At sign')
      ];
      $punctuation['exclamation'] = [
        'value' => '!',
        'name' => t('Exclamation mark')
      ];
      $punctuation['tilde'] = [
        'value' => '~',
        'name' => t('Tilde')
      ];
      $punctuation['left_parenthesis'] = [
        'value' => '(',
        'name' => t('Left parenthesis')
      ];
      $punctuation['right_parenthesis'] = [
        'value' => ')',
        'name' => t('Right parenthesis')
      ];
      $punctuation['question_mark'] = [
        'value' => '?',
        'name' => t('Question mark')
      ];
      $punctuation['less_than'] = [
        'value' => '<',
        'name' => t('Less-than sign')
      ];
      $punctuation['greater_than'] = [
        'value' => '>',
        'name' => t('Greater-than sign')
      ];
      $punctuation['slash'] = [
        'value' => '/',
        'name' => t('Slash')
      ];
      $punctuation['back_slash'] = [
        'value' => '\\',
        'name' => t('Backslash')
      ];
      $this->moduleHandler->alter('pathauto_punctuation_chars', $punctuation);
      $this->punctuation = $punctuation;
    }

    return $this->punctuation;
  }

  public function maxLength() {
    if (empty($this->maxlength)) {
      $schema = drupal_get_module_schema('system', 'url_alias');
      $this->maxlength = $schema['fields']['alias']['length'];
    }
    return $this->maxlength;
  }

}
