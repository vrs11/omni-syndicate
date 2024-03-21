<?php

namespace Drupal\dynamic_ownership\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining User ownership entities.
 *
 * @ingroup dynamic_ownership
 */
interface UserOwnershipInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the User ownership name.
   *
   * @return string
   *   Name of the User ownership.
   */
  public function getName();

  /**
   * Gets the User ownership creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User ownership.
   */
  public function getCreatedTime();

  /**
   * Sets the User ownership creation timestamp.
   *
   * @param int $timestamp
   *   The User ownership creation timestamp.
   *
   * @return \Drupal\dynamic_ownership\Entity\UserOwnershipInterface
   *   The called User ownership entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Implements the workflow_callback for the state field.
   *
   * @return string
   *   The workflow ID.
   *
   * @see \Drupal\state_machine\Plugin\Field\FieldType\StateItem
   */
  public static function getWorkflowId();

  /**
   * Gets the User from a relation.
   *
   * @return \Drupal\user\UserInterface
   *   User entity from the relation.
   */
  public function getUser();

  /**
   * Sets the User of a relation.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity.
   *
   * @return \Drupal\dynamic_ownership\Entity\UserOwnershipInterface
   *   The called User relation entity.
   */
  public function setUser(UserInterface $user);

  /**
   * Gets the Entity from a relation.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Entity from the relation.
   */
  public function getEntity();

  /**
   * Sets the Entity for a relation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity.
   *
   * @return \Drupal\dynamic_ownership\Entity\UserOwnershipInterface
   *   The called User relation entity.
   */
  public function setEntity(EntityInterface $entity);

  /**
   * Gets the Role from a relation.
   *
   * @return \Drupal\user\RoleInterface
   *   User entity from the relation.
   */
  public function getRole();

  /**
   * Sets the Role for a relation.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The Role entity.
   *
   * @return \Drupal\dynamic_ownership\Entity\UserOwnershipInterface
   *   The called User relation entity.
   */
  public function setRole(RoleInterface $role);

  /**
   * Gets the state of a relation.
   *
   * @return String
   *   The relation state.
   */
  public function getState();

  /**
   * Checks the permission for the relation.
   *
   * @param string $permission
   *   The permission to check.
   *
   * @return bool
   *   The permission state.
   */
  public function hasPermission(string $permission);

  /**
   * Checks if entity can be saved.
   *
   * @return bool
   *   Available or not.
   */
  public function isSaveAvailable();

}
