<?php

namespace Drupal\dynamic_ownership\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\dynamic_ownership\Entity\UserOwnershipInterface;

/**
 * Event for a user_ownership.
 */
abstract class UserOwnershipEvent extends Event {

  /**
   * The user ownership entity.
   *
   * @var \Drupal\dynamic_ownership\Entity\UserOwnershipInterface
   */
  protected $ownership;

  /**
   * Constructs the object.
   *
   * @param \Drupal\dynamic_ownership\Entity\UserOwnershipInterface $ownership
   *   The user ownership entity.
   */
  public function __construct(
    UserOwnershipInterface $ownership
  ) {
    $this->ownership = $ownership;
  }

  /**
   * {@inheritDoc}
   */
  public function getOwnerShip(): UserOwnershipInterface {
    return $this->ownership;
  }
}
