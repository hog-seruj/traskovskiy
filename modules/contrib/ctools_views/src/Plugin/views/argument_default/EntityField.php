<?php

namespace Drupal\ctools_views\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract an entity field value.
 *
 * @ViewsArgumentDefault(
 *   id = "entity_field",
 *   title = @Translation("Entity field from URL")
 * )
 */
class EntityField extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new Entity instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeRepositoryInterface $entity_type_repository, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.repository'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['entity_type'] = array('default' => '');
    $options['field'] = array('default' => '');
    $options['multiple'] = array('default' => 'and');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $labels = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
    $options = $labels[(string) t('Content', [], ['context' => 'Entity type group'])];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#default_value' => $this->options['entity_type'],
      '#options' => $options,
    ];

    if (!empty($this->options['entity_type'])) {
      $field_storage_definitions  = $this->entityFieldManager->getFieldStorageDefinitions($this->options['entity_type']);
      $fields = [];
      foreach ($field_storage_definitions as $field_name => $field_storage_definition) {
        $fields[$field_name] = $field_storage_definition->getLabel();
      }
      // @todo add AJAX to the select.
      $form['field'] = [
        '#type' => 'select',
        '#title' => $this->t('Field'),
        '#default_value' => $this->options['field'],
        '#options' => $fields,
      ];
      if (!empty($this->options['field'])) {
        $field_storage = $field_storage_definitions[$this->options['field']];
        if ($field_storage->isMultiple()) {
          $form['multiple'] = [
            '#type' => 'radios',
            '#title' => $this->t('Multiple values'),
            '#description' => $this->t('Conjunction to use when handling multiple values.'),
            '#default_value' => $this->options['multiple'],
            '#options' => [
              'and' => $this->t('AND'),
              'or' => $this->t('OR'),
            ],
          ];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $route_name = $this->routeMatch->getRouteName();
    if (strpos($route_name, 'entity.') === 0) {
      list (, $entity_type_id, ) = explode('.', $route_name);
      if (($entity = $this->routeMatch->getParameter($entity_type_id)) && $entity instanceof ContentEntityInterface && $entity_type_id === $this->options['entity_type'] && ($field = $this->options['field'])) {
        $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
        $field_storage = $field_storage_definitions[$field];
        /** @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
        if ($field_item_list = $entity->$field) {
          $values = [];
          /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
          foreach ($field_item_list as $field_item) {
            // Find the value using the main property of the field. If no main
            // property is provided fall back to 'value'.
            if ($main_property_name = $field_item->mainPropertyName()) {
              $values[] = $field_item->{$main_property_name};
            }
            else {
              $values[] = $field_item->value;
            }
          }
          if ($field_storage->isMultiple()) {
            $conjunction = ($this->options['multiple'] == 'and') ? ',' : '+';
            return implode($conjunction, $values);
          }
          else {
            return reset($values);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
