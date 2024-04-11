<?php

namespace Drupal\stated_entity_reference;

use Drupal\stated_entity_reference\Entity\StatedEntityReference;

/**
 * Interface StatedEntityReferenceStorageInterface
 */
interface StatedEntityReferenceStorageInterface {

  /**
   * {@inheritDoc}
   */
  public function isSaveAvailable(StatedEntityReference $entity): bool;
}
