<?php

namespace Drupal\entity_reference_confirmation\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity_reference_confirmation\EntityReferenceConfirmAccessManager;
use Drupal\entity_reference_confirmation\Plugin\Field\FieldType\EntityReferenceConfirmItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_confirm_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference_confirm"
 *   }
 * )
 */
class EntityReferenceConfirmLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The 'Entity reference confirm' access manager.
   *
   * @var \Drupal\entity_reference_confirmation\EntityReferenceConfirmAccessManager
   */
  protected $entityReferenceConfirmAccessManager;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *    The entity repository.
   * @param \Drupal\entity_reference_confirmation\EntityReferenceConfirmAccessManager $entity_reference_confirm_access_manager
   *    The 'Entity reference confirm' access manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityRepositoryInterface $entity_repository,
    EntityReferenceConfirmAccessManager $entity_reference_confirm_access_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityRepository = $entity_repository;
    $this->entityReferenceConfirmAccessManager = $entity_reference_confirm_access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.repository'),
      $container->get('entity_reference_confirmation.access.manager'),
    );
  }

  /**
   * Returns the referenced entities for display.
   *
   * The method takes care of:
   * - checking entity access,
   * - placing the entities in the language expected for display.
   * It is thus strongly recommended that formatters use it in their
   * implementation of viewElements($items) rather than dealing with $items
   * directly.
   *
   * For each entity, the EntityReferenceItem by which the entity is referenced
   * is available in $entity->_referringItem. This is useful for field types
   * that store additional values next to the reference itself.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   *
   * @see ::prepareView()
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = [];

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $entity = $item->entity;

        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface) {
          $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);
        }

        $access = $this->checkAccessWithStatus($item, $entity, $delta);
        // Add the access result's cacheability, ::view() needs it.
        $item->_accessCacheability = CacheableMetadata::createFromObject($access);
        if ($access->isAllowed()) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
          $entities[$delta] = $entity;
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      if ($item->state != 'active') {
        $elements[$delta] = [
          '#type' => 'container',
          0 => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => '(' . $item->state . ')',
          ],
          1 => $elements[$delta],
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccessWithStatus(
    EntityReferenceConfirmItem $item,
    EntityInterface $entity,
    $delta,
  ) {
    return $this->entityReferenceConfirmAccessManager
      ->relationAccessCheck($item, $entity, $delta)
      ->orIf($this->checkAccess($entity));
  }
}
