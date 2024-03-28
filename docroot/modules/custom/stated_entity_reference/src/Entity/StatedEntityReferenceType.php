<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\node\Entity\NodeType;

/**
 * Defines the Stated entity reference type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "stated_entity_reference_type",
 *   label = @Translation("Stated entity reference type"),
 *   label_collection = @Translation("Stated entity reference types"),
 *   label_singular = @Translation("Stated entity reference type"),
 *   label_plural = @Translation("Stated entity references types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Stated entity references type",
 *     plural = "@count Stated entity references types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\stated_entity_reference\Form\StatedEntityReferenceTypeForm",
 *       "edit" = "Drupal\stated_entity_reference\Form\StatedEntityReferenceTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\stated_entity_reference\StatedEntityReferenceTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer stated_entity_reference types",
 *   bundle_of = "stated_entity_reference",
 *   config_prefix = "stated_entity_reference_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/stated_entity_reference_types/add",
 *     "edit-form" = "/admin/structure/stated_entity_reference_types/manage/{stated_entity_reference_type}",
 *     "delete-form" = "/admin/structure/stated_entity_reference_types/manage/{stated_entity_reference_type}/delete",
 *     "collection" = "/admin/structure/stated_entity_reference_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "source_bundle",
 *     "target_bundle",
 *   },
 * )
 */
class StatedEntityReferenceType extends ConfigEntityBundleBase implements StatedEntityReferenceTypeInterface {

  /**
   * The machine name of this Stated entity reference type.
   */
  protected string $id;

  /**
   * The human-readable name of the Stated entity reference type.
   */
  protected string $label;

  /**
   * The User ownership target node bundle.
   */
  protected $source_bundle;

  /**
   * The User ownership target node bundle.
   */
  protected $target_bundle;

  /**
   * Returns the value of a target bundle config value.
   *
   * @return string
   *   The target bundle.
   */
  public function getSourceBundle() {
    return $this->source_bundle;
  }

  /**
   * Sets the value of a target bundle.
   *
   * @param string $value
   *   The value the target bundle should be set to.
   *
   * @return $this
   */
  public function setSourceBundle($value) {
    if (is_string($value)) {
      $this->source_bundle = $value;
    }

    return $this;
  }

  /**
   * Returns the value of a target bundle config value.
   *
   * @return string
   *   The target bundle.
   */
  public function getTargetBundle() {
    return $this->target_bundle;
  }

  /**
   * Returns the value of a target bundle config value.
   *
   * @return string
   *   The target bundle.
   */
  public function getTargetBundleLable() {
    [$entity_type, $type] = explode(':', $this->target_bundle);
    $bundle_entity_type = $this->entityTypeManager()->getDefinition($entity_type)->get('bundle_entity_type');
    $class = $this->entityTypeManager()->getStorage($bundle_entity_type)->getEntityClass();
    $entity_type = $class::load($type);
    return $entity_type->label();
  }

  /**
   * Returns the value of a target bundle config value.
   *
   * @return string
   *   The target bundle.
   */
  public function getSourceBundleLable() {
    [$entity_type, $type] = explode(':', $this->source_bundle);
    $bundle_entity_type = $this->entityTypeManager()->getDefinition($entity_type)->get('bundle_entity_type');
    $class = $this->entityTypeManager()->getStorage($bundle_entity_type)->getEntityClass();
    $entity_type = $class::load($type);
    return $entity_type->label();
  }

  /**
   * Sets the value of a target bundle.
   *
   * @param string $value
   *   The value the target bundle should be set to.
   *
   * @return $this
   */
  public function setTargetBundle($value) {
    if (is_string($value)) {
      $this->target_bundle = $value;
    }

    return $this;
  }
}
