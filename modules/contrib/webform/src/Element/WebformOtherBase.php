<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Base class for webform other element.
 */
abstract class WebformOtherBase extends FormElement {

  /**
   * Other option value.
   */
  const OTHER_OPTION = '_other_';

  /**
   * The type of element.
   *
   * @var string
   */
  protected static $type;

  /**
   * The properties of the element.
   *
   * @var array
   */
  protected static $properties = [
    '#required',
    '#options',
    '#options_display',
    '#default_value',
    '#attributes',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformOther'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#other__option_delimiter' => ', ',
      '#states' => [],
    ];
  }

  /**
   * Determine if the webform element contains multiple values.
   *
   * @param array $element
   *   A webform element.
   *
   * @return bool
   *   TRUE if the webform element contains multiple values.
   */
  public static function isMultiple(array $element) {
    return (!empty($element['#multiple']) || static::$type == 'checkboxes') ? TRUE : FALSE;
  }

  /**
   * Processes an 'other' element.
   *
   * See select list webform element for select list properties.
   *
   * @see \Drupal\Core\Render\Element\Select
   */
  public static function processWebformOther(&$element, FormStateInterface $form_state, &$complete_form) {
    // Remove 'webform_' prefix from type.
    $type = str_replace('webform_', '', static::$type);
    $properties = static::$properties;

    $element['#tree'] = TRUE;

    $element['#wrapper_attributes']['class'][] = "js-webform-$type-other";
    $element['#wrapper_attributes']['class'][] = "webform-$type-other";

    $element[$type]['#type'] = static::$type;
    $element[$type] += array_intersect_key($element, array_combine($properties, $properties));
    if (!isset($element[$type]['#options'][static::OTHER_OPTION])) {
      $element[$type]['#options'][static::OTHER_OPTION] = (!empty($element['#other__option_label'])) ? $element['#other__option_label'] : t('Other...');
    }
    $element[$type]['#error_no_message'] = TRUE;

    // Build other textfield.
    $element['other']['#error_no_message'] = TRUE;
    foreach ($element as $key => $value) {
      if (strpos($key, '#other__') === 0) {
        $other_key = str_replace('#other__', '#', $key);
        if (!isset($element['other'][$other_key])) {
          $element['other'][$other_key] = $value;
        }
      }
    }
    $element['other'] += [
      '#type' => 'textfield',
      '#placeholder' => t('Enter other...'),
    ];
    $element['other']['#wrapper_attributes']['class'][] = "js-webform-$type-other-input";
    $element['other']['#wrapper_attributes']['class'][] = "webform-$type-other-input";

    // Remove options.
    unset($element['#options']);

    // Set validation.
    if (isset($element['#element_validate'])) {
      $element['#element_validate'] = array_merge([[get_called_class(), 'validateWebformOther']], $element['#element_validate']);
    }
    else {
      $element['#element_validate'] = [[get_called_class(), 'validateWebformOther']];
    }

    // Attach library.
    $element['#attached']['library'][] = 'webform/webform.element.other';

    // Process states.
    webform_process_states($element, '#wrapper_attributes');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Remove 'webform_' prefix from type.
    $type = str_replace('webform_', '', static::$type);

    if ($input === FALSE) {
      $default_value = isset($element['#default_value']) ? $element['#default_value'] : NULL;
      if (!$default_value) {
        return $element;
      }

      if (static::isMultiple($element)) {
        if (is_array($default_value)) {
          $flattened_options = OptGroup::flattenOptions($element['#options']);
          if ($other_options = array_diff_key(array_combine($default_value, $default_value), $flattened_options)) {
            $element[$type]['#default_value'] = $default_value + [static::OTHER_OPTION => static::OTHER_OPTION];
            $element['other']['#default_value'] = implode($element['#other__option_delimiter'], $other_options);
          }
          return $element;
        }
      }
      elseif (!WebformOptionsHelper::hasOption($default_value, $element['#options'])) {
        $element[$type]['#default_value'] = static::OTHER_OPTION;
        $element['other']['#default_value'] = $default_value;
        return $element;
      }
      else {
        return $element;
      }
    }
    return NULL;
  }

  /**
   * Validates an other element.
   */
  public static function validateWebformOther(&$element, FormStateInterface $form_state, &$complete_form) {
    // Remove 'webform_' prefix from type.
    $type = str_replace('webform_', '', static::$type);

    $element_value = $element[$type]['#value'];
    $other_value = $element['other']['#value'];

    if (static::isMultiple($element)) {
      $value = $element_value;
      if (isset($element_value[static::OTHER_OPTION])) {
        unset($value[static::OTHER_OPTION]);
        if ($other_value === '') {
          static::setOtherError($element, $form_state);
          return;
        }
        else {
          $value[$other_value] = $other_value;
        }
      }
      $is_empty = (empty($value)) ? TRUE : FALSE;
    }
    else {
      $value = $element_value;
      if ($element_value == static::OTHER_OPTION) {
        if ($other_value === '') {
          static::setOtherError($element, $form_state);
          return;
        }
        else {
          $value = $other_value;
        }

      }
      $is_empty = ($value === '' || $value === NULL) ? TRUE : FALSE;
    }

    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($element['#required'] && $is_empty && $has_access) {
      static::setElementError($element, $form_state);
    }

    $form_state->setValueForElement($element[$type], NULL);
    $form_state->setValueForElement($element['other'], NULL);
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Set element required error.
   *
   * @param array $element
   *   The webform element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function setElementError(array &$element, FormStateInterface $form_state) {
    if (isset($element['#required_error'])) {
      $form_state->setError($element, $element['#required_error']);
    }
    elseif (isset($element['#title'])) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }
    else {
      $form_state->setError($element);
    }
  }

  /**
   * Set element required error.
   *
   * @param array $element
   *   The webform element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function setOtherError(array &$element, FormStateInterface $form_state) {
    if (isset($element['#required_error'])) {
      $form_state->setError($element['other'], $element['#required_error']);
    }
    elseif (isset($element['#title'])) {
      $form_state->setError($element['other'], t('@name field is required.', ['@name' => $element['#title']]));
    }
    else {
      $form_state->setError($element['other']);
    }
  }

}