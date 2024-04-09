<?php

namespace Drupal\dynamic_ownership\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the User ownership type entity.
 *
 * @ConfigEntityType(
 *   id = "user_ownership_type",
 *   label = @Translation("User ownership type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dynamic_ownership\UserOwnershipTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dynamic_ownership\Form\UserOwnershipTypeForm",
 *       "edit" = "Drupal\dynamic_ownership\Form\UserOwnershipTypeForm",
 *       "delete" = "Drupal\dynamic_ownership\Form\UserOwnershipTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dynamic_ownership\UserOwnershipTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_export = {
 *      "id",
 *      "label",
 *      "uuid",
 *      "target_bundle",
 *      "conflicts_with",
 *      "limit",
 *      "target_roles",
 *      "entity_owner",
 *      "default_relation"
 *   },
 *   config_prefix = "user_ownership_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "user_ownership",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/user_ownership_type/{user_ownership_type}",
 *     "add-form" = "/admin/structure/user_ownership_type/add",
 *     "edit-form" = "/admin/structure/user_ownership_type/{user_ownership_type}/edit",
 *     "delete-form" = "/admin/structure/user_ownership_type/{user_ownership_type}/delete",
 *     "collection" = "/admin/structure/user_ownership_type"
 *   }
 * )
 */
class UserOwnershipType extends ConfigEntityBundleBase implements UserOwnershipTypeInterface {

  /**
   * The User ownership type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User ownership type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The User ownership target node bundle.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The User ownership conflict bundles.
   *
   * @var array
   */
  protected $conflicts_with;

  /**
   * The User ownership limit per user.
   *
   * @var string
   */
  protected $limit;

  /**
   * The User ownership allowed target user roles.
   *
   * @var array
   */
  protected $target_roles;

  /**
   * Became the entity owner or not.
   *
   * @var bool
   */
  protected $entity_owner;

  /**
   * Is default for claims or not.
   *
   * @var bool
   */
  protected $default_relation;

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

  /**
   * Returns the value of a conflict bundles value.
   *
   * @return array
   *   The conflict bundles.
   */
  public function getConflictBundles() {
    return $this->conflicts_with;
  }

  /**
   * Sets the value of conflict bundles.
   *
   * @param array $values
   *   The values the conflict bundles should be set to.
   *
   * @return $this
   */
  public function setConflictBundles(array $values) {
    $this->conflicts_with = $values;

    return $this;
  }

  /**
   * Returns the value of a limit config value.
   *
   * @return integer
   *   The limit.
   */
  public function getLimit() {
    return $this->limit;
  }

  /**
   * Sets the value of a limit.
   *
   * @param integer $value
   *   The value the target bundle should be set to.
   *
   * @return $this
   */
  public function setLimit($value) {
    if (is_integer($value)) {
      $this->limit = $value;
    }

    return $this;
  }

  /**
   * Returns the value of a target roles.
   *
   * @return array
   *   The target roles.
   */
  public function getTargetRoles() {
    return $this->target_roles;
  }

  /**
   * Sets the value of a target roles.
   *
   * @param array $values
   *   The value the target roles should be set to.
   *
   * @return $this
   */
  public function setTargetRoles($values) {
    if (!is_array($values)) {
      return $this;
    }

    $new_values = [];
    foreach ($values as $value) {
      if (!is_integer($value)) {
        continue;
      }

      $new_values[] = $value;
    }

    return $this;
  }

  /**
   * Adds the value to a target roles.
   *
   * @param string $value
   *   The value the target roles should be added to.
   *
   * @return $this
   */
  public function addTargetRoles($value) {
    if (is_integer($value) && !in_array($value, $this->target_roles)) {
      $this->target_roles[] = $value;
    }

    return $this;
  }

  /**
   * Became the entity owner or not.
   *
   * @return bool
   *   The state of the transition.
   */
  public function isMakingEntityOwner() {
    return $this->entity_owner;
  }

  /**
   * Returns the state of defaultness for the type.
   *
   * @return bool
   *   The default state.
   */
  public function isDefaultRelation() {
    return $this->default_relation;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadDefault(string $target_bundle) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('user_ownership_type');
    $defaults = $storage->loadByProperties([
      'target_bundle' => $target_bundle,
      'default_relation' => 1,
    ]);

    if (!empty($defaults)) {
      return reset($defaults);
    }

    $defaults = $storage->loadByProperties([
      'target_bundle' => $target_bundle,
    ]);

    if (!empty($defaults)) {
      return reset($defaults);
    }

    return NULL;
  }
}
