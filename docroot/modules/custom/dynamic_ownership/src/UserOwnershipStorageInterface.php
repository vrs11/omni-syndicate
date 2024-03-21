<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\dynamic_ownership\Entity\UserOwnershipInterface;

/**
 * Defines an interface for tool credits entity storage classes.
 */
interface UserOwnershipStorageInterface extends ContentEntityStorageInterface {

  /**
   * Checks if entity can be saved.
   *
   * @param \Drupal\dynamic_ownership\Entity\UserOwnershipInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   Available or not.
   */
  public function isSaveAvailable(UserOwnershipInterface $entity): bool;

  /**
   * Checks if new connection can be created.
   *
   * @param string $type_id
   *   Type of connection.
   * @param int $entity_id
   *   Entity ID.
   * @param string $entity_type
   *   Entity type id.
   * @param int $user_id
   *   User ID.
   * @param int|null $oid
   *   Set if this entity is not new.
   *
   * @return bool
   *   Available or not.
   */
  public function isClaimAvailable(string $type_id, int $entity_id, string $entity_type, int $user_id, ?int $oid = NULL): bool;

  /**
   * Checks if there is an ownership.
   *
   * @param int $entity_id
   *   Entity ID.
   * @param string $entity_type
   *   Entity type id.
   * @param int $user_id
   *   User ID.
   * @param null|string $type
   *   Type of connection.
   * @param bool $only_active
   *   Check only active or not.
   *
   * @return bool
   *   There is or not.
   */
  public function isOwnershipExists(int $entity_id, string $entity_type, int $user_id, ?string $type = NULL, bool $only_active = FALSE): bool;

  /**
   * Checks if new connection can be created.
   *
   * @param string $type_id
   *   Type of connection.
   * @param int $user_id
   *   User ID.
   *
   * @return bool
   *   Available or not.
   */
  public function isNewClaimAvailable(string $type_id, int $user_id): bool;

  /**
   * Checks if there is an ownership.
   *
   * @param int $entity_id
   *   Entity ID.
   * @param string $entity_type
   *   Entity type ID.
   * @param array $roles
   *   Target roles.
   * @param mixed|null $type
   *   Type of connection.
   * @param bool $only_active
   *   Only active or not.
   * @param bool|null $first
   *   Return only first.
   *
   * @return mixed
   *   Target ownerships.
   */
  public function getEntityOwnerships(int $entity_id, string $entity_type, array $roles = [], mixed $type = NULL, bool $only_active = TRUE, ?bool $first = FALSE): mixed;

  /**
   * Checks if there is an ownership.
   *
   * @param int $entity_id
   *   Entity ID.
   * @param string $entity_type
   *   Entity type ID.
   * @param array $roles
   *   Target roles.
   * @param mixed|null $type
   *   Type of connection.
   * @param bool $only_active
   *   Only active or not.
   *
   * @return mixed
   *   Target ownerships.
   */
  public function getFirstEntityOwnership(int $entity_id, string $entity_type, array $roles = [], mixed $type = NULL, bool $only_active = TRUE): mixed;

  /**
   * Checks if there is an ownership.
   *
   * @param int $user_id
   *   User ID.
   * @param array $roles
   *   Target roles.
   * @param array|string|null $type
   *   Type of connection.
   * @param bool $only_active
   *   Only active or not.
   * @param bool|null $first
   *   Return only first.
   *
   * @return mixed
   *   Target ownerships.
   */
  public function getUserOwnerships(int $user_id, array $roles = [], array|string $type = NULL, bool $only_active = TRUE, ?bool $first = FALSE): mixed;

  /**
   * Checks if there is an ownership.
   *
   * @param int $user_id
   *   User ID.
   * @param array $roles
   *   Target roles.
   * @param mixed|null $type
   *   Type of connection.
   * @param bool $only_active
   *   Only active or not.
   *
   * @return mixed
   *   Target ownerships.
   */
  public function getFirstUserOwnership(int $user_id, array $roles = [], mixed $type = NULL, bool $only_active = TRUE): mixed;

  /**
   * Checks if there is an ownership.
   *
   * @param int $entity_id
   *   Entity ID.
   * @param string $entity_type
   *   Entity type id.
   * @param int $user_id
   *   User ID.
   * @param array $roles
   *   Target roles.
   * @param mixed|null $type
   *   Type of connection.
   * @param bool $only_active
   *   Only active or not.
   * @param bool|null $first
   *   Return only first.
   *
   * @return mixed
   *   Target ownerships.
   */
  public function getOwnerships(int $entity_id, string $entity_type, int $user_id, array $roles = [], mixed $type = NULL, bool $only_active = TRUE, ?bool $first = FALSE): mixed;

  /**
   * Checks if there is an ownership.
   *
   * @param int $entity_id
   *   Entity ID.
   * @param string $entity_type
   *   Entity type id.
   * @param int $user_id
   *   User ID.
   * @param array $roles
   *   Target roles.
   * @param mixed|null $type
   *   Type of connection.
   * @param bool $only_active
   *   Only active or not.
   *
   * @return mixed
   *   Target ownerships.
   */
  public function getFirstOwnership(int $entity_id, string $entity_type, int $user_id, array $roles = [], mixed $type = NULL, bool $only_active = TRUE): mixed;
}
