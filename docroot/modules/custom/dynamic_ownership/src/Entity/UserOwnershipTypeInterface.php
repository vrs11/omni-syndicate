<?php

namespace Drupal\dynamic_ownership\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining User ownership type entities.
 */
interface UserOwnershipTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the value of a target bundle config value.
   *
   * @return string
   *   The target bundle.
   */
  public function getTargetBundle();

  /**
   * Sets the value of a target bundle.
   *
   * @param string $value
   *   The value the target bundle should be set to.
   *
   * @return $this
   */
  public function setTargetBundle($value);

  /**
   * Returns the value of a conflict bundles value.
   *
   * @return array
   *   The conflict bundles.
   */
  public function getConflictBundles();

  /**
   * Sets the value of conflict bundles.
   *
   * @param array $values
   *   The values the conflict bundles should be set to.
   *
   * @return $this
   */
  public function setConflictBundles(array $values);

  /**
   * Returns the value of a limit config value.
   *
   * @return integer
   *   The limit.
   */
  public function getLimit();

  /**
   * Sets the value of a limit.
   *
   * @param integer $value
   *   The value the target bundle should be set to.
   *
   * @return $this
   */
  public function setLimit($value);

  /**
   * Returns the value of a target roles.
   *
   * @return array
   *   The target roles.
   */
  public function getTargetRoles();

  /**
   * Sets the value of a target roles.
   *
   * @param array $values
   *   The value the target roles should be set to.
   *
   * @return $this
   */
  public function setTargetRoles($values);

  /**
   * Adds the value to a target roles.
   *
   * @param string $value
   *   The value the target roles should be add to.
   *
   * @return $this
   */
  public function addTargetRoles($value);

  /**
   * Returns the state of defaultness for the type.
   *
   * @return bool
   *   The default state.
   */
  public function isDefaultRelation();

  /**
   * {@inheritdoc}
   */
  public static function loadDefault(string $target_bundle);
}
