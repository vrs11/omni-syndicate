<?php

namespace Drupal\dynamic_ownership\Event;

/**
 * Event that is fired when a user_ownership is Created.
 */
class UserOwnershipCreatedEvent extends UserOwnershipEvent {

  const EVENT_NAME = 'user_ownership_created_event';
}
