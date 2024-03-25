<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Stated entity reference type entities.
 *
 * @see \Drupal\stated_entity_reference\Entity\StatedEntityReferenceType
 */
final class StatedEntityReferenceTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No Stated entity reference types available. <a href=":link">Add Stated entity reference type</a>.',
      [':link' => Url::fromRoute('entity.stated_entity_reference_type.add_form')->toString()],
    );

    return $build;
  }

}
