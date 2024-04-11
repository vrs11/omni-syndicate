<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;

/**
 * Handles all form modifications.
 */
class Manager {

  /**
   * Constructs a FormManager object.
   */
  public function __construct(
    private readonly ElementInfoManagerInterface $elementInfoManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function onEntityDelete(EntityInterface $entity) {
    $query = $this->entityTypeManager->getStorage('stated_entity_reference')->getQuery('OR');
    $source = $query->andConditionGroup()
      ->condition('source_entity_id__target_id', $entity->id())
      ->condition('source_entity_id__target_type', $entity->getEntityTypeId());
    $target = $query->andConditionGroup()
      ->condition('target_entity_id__target_id', $entity->id())
      ->condition('target_entity_id__target_type', $entity->getEntityTypeId());
    $ids = $query
      ->condition($source)
      ->condition($target)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return;
    }

    $entities = $this->entityTypeManager->getStorage('stated_entity_reference')->loadMultiple($ids);
    try {
      $this->entityTypeManager->getStorage('stated_entity_reference')->delete($entities);
    } catch (\Exception $e) {
      // @TODO: add error message
    }
  }
  
  public function processEntity(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $ref_types = $this->entityTypeManager->getStorage('stated_entity_reference_type')->loadByProperties([
      'source_bundle' => "{$type}:{$bundle}",
    ]);
    if (empty($ref_types)) {
      return;
    }

    foreach ($ref_types as $ref_type) {
      $refs = $this->entityTypeManager->getStorage('stated_entity_reference')->loadByProperties([
        'type' => $ref_type->id(),
        'source_entity_id__target_id' => $entity->id(),
        'source_entity_id__target_type' => $entity->getEntityTypeId(),
      ]);
      $refs = array_filter($refs, fn($ref) => $ref->access('view'), ARRAY_FILTER_USE_BOTH);
      if (empty($refs)) {
        continue;
      }

      $key = $ref_type->id() . '_stated_entity_reference';
      $build[$key] = [
        '#type' => 'details',
        '#title' => $ref_type->label(),
        '#open' => TRUE,
      ];

      $reference_view_builder = $this->entityTypeManager->getViewBuilder('stated_entity_reference');
      $build[$key]['refs'] = $reference_view_builder->viewMultiple($refs, 'entity_reference_source_page');
    }
  }
}
