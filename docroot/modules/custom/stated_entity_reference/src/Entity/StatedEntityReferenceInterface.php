<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the Stated entity reference entity interface.
 */
interface StatedEntityReferenceInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * {@inheritdoc}
   */
  public function getState();

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity();

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $entity);

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity();

  /**
   * {@inheritdoc}
   */
  public function setTargetEntity(EntityInterface $entity);

}
