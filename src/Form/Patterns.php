<?php
/**
 * @file
 * Contains \Drupal\pathauto\Form\Patterns.php
 */

namespace Drupal\pathauto\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class Patterns extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pathauto_patterns_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Call the hook on all modules - an array of 'settings' objects is returned
    $all_settings = module_invoke_all('pathauto', 'settings');
    foreach ($all_settings as $settings) {
      $module = $settings->module;
      $patterndescr = $settings->patterndescr;
      $patterndefault = $settings->patterndefault;
      $groupheader = $settings->groupheader;

      $form[$module] = array(
        '#type' => 'fieldset',
        '#title' => $groupheader,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );

      // Prompt for the default pattern for this module
      $variable = 'pathauto_' . $module . '_pattern';
      $form[$module][$variable] = array(
        '#type' => 'textfield',
        '#title' => $patterndescr,
        '#default_value' => variable_get($variable, $patterndefault),
        '#size' => 65,
        '#maxlength' => 1280,
        '#element_validate' => array('token_element_validate'),
        '#after_build' => array('token_element_validate'),
        '#token_types' => array($settings->token_type),
        '#min_tokens' => 1,
        '#parents' => array($variable),
      );

      // If the module supports a set of specialized patterns, set
      // them up here
      if (isset($settings->patternitems)) {
        foreach ($settings->patternitems as $itemname => $itemlabel) {
          $variable = 'pathauto_' . $module . '_' . $itemname . '_pattern';
          $form[$module][$variable] = array(
            '#type' => 'textfield',
            '#title' => $itemlabel,
            '#default_value' => variable_get($variable, ''),
            '#size' => 65,
            '#maxlength' => 1280,
            '#element_validate' => array('token_element_validate'),
            '#after_build' => array('token_element_validate'),
            '#token_types' => array($settings->token_type),
            '#min_tokens' => 1,
            '#parents' => array($variable),
          );
        }
      }

      // Show the token help relevant to this pattern type.
      $form[$module]['token_help'] = array(
        '#theme' => 'token_tree',
        '#token_types' => array($settings->token_type),
        '#dialog' => TRUE,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
