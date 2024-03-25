<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference\Entity;

/**
 * Defines the Stated entity reference type configuration entity interface.
 */
interface StatedEntityReferenceTypeInterface {

  /**
   * Returns the value of a target bundle config value.
   *
   * @return string
   *   The target bundle.
   */
  public function getSourceBundle();

  /**
   * Sets the value of a target bundle.
   *
   * @param string $value
   *   The value the target bundle should be set to.
   *
   * @return $this
   */
  public function setSourceBundle($value);

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
}
