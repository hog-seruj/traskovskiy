<?php

namespace Drupal\views_infinite_scroll\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\SqlBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Views pager plugin to handle infinite scrolling.
 *
 * @ViewsPager(
 *  id = "infinite_scroll",
 *  title = @Translation("Infinite Scroll"),
 *  short_title = @Translation("Infinite Scroll"),
 *  help = @Translation("A views plugin which provides infinte scroll."),
 *  theme = "views_infinite_scroll_pager"
 * )
 */
class InfiniteScroll extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    $this->view->setAjaxEnabled(TRUE);
    // Either call this again after setting the above flag or force people to
    // select 'use ajax' from the UI.
    views_views_pre_render($this->view);
    return [
      '#theme' => $this->themeFunctions(),
      '#options' => $this->options['views_infinite_scroll'],
      '#attached' => [
        'library' => ['views_infinite_scroll/views-infinite-scroll'],
      ],
      '#element' => $this->options['id'],
      '#parameters' => $input,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['views_infinite_scroll'] = [
      'contains' => [
        'button_text' => [
          'default' => $this->t('Load More'),
        ],
        'automatically_load_content' => [
          'default' => FALSE,
        ],
        'initial' => [
          'default' => 0,
        ],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $action = $this->options['views_infinite_scroll']['automatically_load_content'] ? $this->t('Automatic infinite scroll') : $this->t('Click to load');
    return $this->formatPlural($this->options['items_per_page'], '@action, @count item', '@action, @count items', ['@action' => $action, '@count' => $this->options['items_per_page']]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['tags']['#access'] = FALSE;
    $options = $this->options['views_infinite_scroll'];

    $form['views_infinite_scroll'] = [
      '#title' => $this->t('Infinite Scroll Options'),
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#input' => TRUE,
      '#weight' => -100,
      'button_text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Button Text'),
        '#default_value' => $options['button_text'],
      ],
      'automatically_load_content' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Automatically Load Content'),
        '#description' => $this->t('Automatically load subsequent pages as the user scrolls.'),
        '#default_value' => $options['automatically_load_content'],
      ],
      'initial' => [
        '#type' => 'textfield',
        '#title' => $this->t('Initial items'),
        '#description' => $this->t('The number of items to display initially. Enter 0 for use same as items per page (show more click).'),
        '#default_value' => $options['initial'],
        '#element_validate' => [[$this, 'validateIntegerPositive']],
      ],
    ];
  }

  /**
   * Validate Form value to be integer and positive.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateIntegerPositive(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value < 0)) {
      $form_state->setError($element, t('%name must be a positive integer.', array('%name' => $element['#title'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    // Set first page items limit.
    $options = $this->options['views_infinite_scroll'];
    $others_page = $this->options['items_per_page'];
    $limit = !empty($options['initial']) ? $options['initial'] : $others_page;
    $offset = !empty($options['offset']) ? $options['offset'] : 0;

    if ($this->current_page != 0) {
      $offset = $limit + (($this->current_page - 1) * $others_page) + $offset;
      $limit = $others_page;
    }

    $this->view->query->setLimit($limit);
    $this->view->query->setOffset($offset);
  }

}
