<?php

namespace Drupal\dynamic_ownership\Event;

/**
 * Event that is fired when a user_ownership is Updated.
 */
class UserOwnershipUpdatedEvent extends UserOwnershipEvent {

  const EVENT_NAME = 'user_ownership_updated_event';
}
