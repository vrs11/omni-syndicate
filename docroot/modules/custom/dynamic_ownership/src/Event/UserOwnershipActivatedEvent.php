<?php

namespace Drupal\dynamic_ownership\Event;

/**
 * Event that is fired when a user_ownership is activated.
 */
class UserOwnershipActivatedEvent extends UserOwnershipEvent {

  const EVENT_NAME = 'user_ownership_activated_event';
}
